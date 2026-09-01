<?php

defined( 'ABSPATH' ) || exit;

final class DLN_Attendance_Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		DLN_Attendance_Secrets::migrate_options();
		DLN_Attendance_Database::maybe_upgrade();
		add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_public_route' ) );
		add_action( 'dln_attendance_reconcile', array( 'DLN_Attendance_Google', 'reconcile_pending' ) );
		add_action( 'dln_attendance_cleanup', array( 'DLN_Attendance_Database', 'cleanup' ) );
		add_action( 'dln_attendance_status_changed', array( $this, 'send_webhook' ), 10, 2 );
		add_shortcode( 'dln_attendance_google_connect', array( 'DLN_Attendance_Google', 'employee_card' ) );
		add_action( 'wp_head', array( 'DLN_Attendance_Google', 'employee_login_capture' ), 1 );
		add_action( 'wp_ajax_dln_attendance_employee_connection_status', array( 'DLN_Attendance_Google', 'employee_connection_status' ) );
		add_action( 'wp_ajax_nopriv_dln_attendance_employee_connection_status', array( 'DLN_Attendance_Google', 'employee_connection_status' ) );
		add_action( 'admin_post_dln_attendance_employee_google_start', array( 'DLN_Attendance_Google', 'employee_start' ) );
		add_action( 'admin_post_nopriv_dln_attendance_employee_google_start', array( 'DLN_Attendance_Google', 'employee_start' ) );
		add_action( 'admin_post_nopriv_dln_attendance_google_callback', array( 'DLN_Attendance_Google', 'handle_callback' ) );
		DLN_Attendance_Amelia_Google::boot();
		DLN_Attendance_WhatsApp::boot();

		foreach ( array( 'attendanceJoinToken', 'attendanceAbsenceToken', 'attendanceJoinUrl', 'attendanceAbsenceUrl' ) as $placeholder ) {
			add_filter( 'amelia_dynamic_placeholder_' . $placeholder, array( $this, 'amelia_placeholder' ), 10, 3 );
		}

		if ( is_admin() ) {
			DLN_Attendance_Admin::boot();
		}
	}

	public static function settings(): array {
		$defaults = array(
			'notification_email'          => get_option( 'admin_email' ),
			'webhook_url'                 => '',
			'webhook_secret'              => '',
			'minimum_attendance_minutes'  => 20,
			'intro_service_category_id'   => 3,
			'retention_days'               => 180,
			'google_client_id'             => '',
			'google_client_secret'         => '',
			'amelia_google_client_id'      => '',
			'amelia_google_client_secret'  => '',
			'amelia_combined_oauth'        => 0,
			'confirmation_whatsapp_enabled' => 1,
			'confirmation_template_name' => 'student_intro_class_confirmed',
			'confirmation_template_language' => 'fa',
			'whatsapp_sender_enabled'      => 0,
			'whatsapp_phone_number_id'     => '',
			'whatsapp_access_token'        => '',
			'whatsapp_template_name'       => 'student_class_reminder_v2',
			'whatsapp_template_language'   => 'fa',
			'whatsapp_graph_version'       => 'v25.0',
			'whatsapp_send_time'           => '07:00',
			'whatsapp_default_country_code'=> '61',
			'whatsapp_webhook_verify_token' => hash_hmac( 'sha256', 'dln-whatsapp-status', wp_salt( 'auth' ) ),
			'teacher_absence_whatsapp_enabled' => 0,
			'teacher_absence_template_name' => 'teacher_student_absence_alert',
			'teacher_absence_template_language' => 'fa',
			'term_renewal_whatsapp_enabled' => 0,
			'term_renewal_template_name' => 'student_term_renewal_reminder',
			'term_renewal_template_language' => 'fa',
			'term_renewal_sessions_remaining' => 2,
		);
		$saved = get_option( 'dln_attendance_settings', array() );
		if ( is_array( $saved ) ) {
			foreach ( array( 'webhook_secret', 'google_client_secret', 'amelia_google_client_secret', 'whatsapp_access_token', 'whatsapp_webhook_verify_token' ) as $field ) {
				$saved[ $field ] = DLN_Attendance_Secrets::unseal( (string) ( $saved[ $field ] ?? '' ) );
			}
		}
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	}

	public static function register_rewrite_rules(): void {
		add_rewrite_rule( '^class/(join|absence)/([^/]+)/?$', 'index.php?dln_attendance_action=$matches[1]&dln_attendance_token=$matches[2]', 'top' );
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'dln_attendance_reconcile' );
		wp_clear_scheduled_hook( 'dln_attendance_cleanup' );
		flush_rewrite_rules( false );
	}

	public function query_vars( array $vars ): array {
		$vars[] = 'dln_attendance_action';
		$vars[] = 'dln_attendance_token';
		return $vars;
	}

	public function amelia_placeholder( $value = '', $data = null, $placeholder = '' ): string {
		$filter = (string) current_filter();
		$name   = str_replace( 'amelia_dynamic_placeholder_', '', $filter );
		$input  = null !== $data ? $data : $value;
		$record = DLN_Attendance_Database::upsert_context( DLN_Attendance_Context::extract( $input ) );
		if ( ! $record ) {
			return is_scalar( $value ) ? (string) $value : '';
		}
		$action = str_contains( strtolower( $name ), 'absence' ) ? 'absence' : 'join';
		$token  = self::token_for( $record, $action );
		return str_ends_with( $name, 'Url' ) ? self::public_url( $action, $token ) : $token;
	}

	public static function token_for( array $record, string $action ): string {
		$public_id = sanitize_text_field( (string) ( $record['public_id'] ?? '' ) );
		$signature = hash_hmac( 'sha256', $action . '|' . $public_id, wp_salt( 'auth' ), true );
		return $public_id . '.' . rtrim( strtr( base64_encode( $signature ), '+/', '-_' ), '=' );
	}

	public static function public_url( string $action, string $token ): string {
		return home_url( '/class/' . rawurlencode( $action ) . '/' . rawurlencode( $token ) . '/' );
	}

	public static function record_from_token( string $token, string $action ): ?array {
		// Meta may preserve a literal or encoded URL-button placeholder and append
		// the supplied parameter after it. Extract only a token-shaped suffix, then
		// perform the same strict public-ID, expiry, and HMAC checks as normal.
		$token = rawurldecode( trim( $token ) );
		if ( preg_match( '/([a-f0-9]{32}\.[A-Za-z0-9_-]{43})$/', $token, $matches ) ) {
			$token = $matches[1];
		}
		$parts = explode( '.', $token, 2 );
		if ( 2 !== count( $parts ) || ! preg_match( '/^[a-f0-9]{32}$/', $parts[0] ) ) {
			return null;
		}
		$record = DLN_Attendance_Database::get_by_public_id( $parts[0] );
		if ( ! $record || ! empty( $record['archived_at'] ) || 'approved' !== (string) ( $record['source_status'] ?? '' ) || strtotime( (string) $record['expires_at'] . ' UTC' ) < time() ) {
			return null;
		}
		return hash_equals( self::token_for( $record, $action ), $token ) ? $record : null;
	}

	public function handle_public_route(): void {
		$action = sanitize_key( (string) get_query_var( 'dln_attendance_action' ) );
		if ( ! in_array( $action, array( 'join', 'absence' ), true ) ) {
			return;
		}
		$token  = sanitize_text_field( (string) get_query_var( 'dln_attendance_token' ) );
		$record = self::record_from_token( $token, $action );
		if ( ! $record ) {
			$this->render_page( 'لینک نامعتبر است', 'این لینک معتبر نیست یا منقضی شده است.', '', 410 );
		}

		if ( 'POST' === strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) {
			$post_action = sanitize_key( wp_unslash( $_POST['attendance_action'] ?? '' ) );
			if ( ! hash_equals( $action, $post_action ) ) {
				$this->render_page( 'درخواست نامعتبر', 'لطفاً دوباره از لینک داخل پیام استفاده کنید.', '', 400 );
			}
			if ( 'join' === $action ) {
				$this->process_join( $record );
			} else {
				$this->process_absence( $record );
			}
		}

		if ( 'join' === $action ) {
			$this->render_join_confirmation( $record );
		}
		$this->render_absence_confirmation( $record );
	}

	private function process_join( array $record ): void {
		$url   = esc_url_raw( (string) ( $record['meet_url'] ?? '' ) );
		$parts = wp_parse_url( $url );
		if ( 'https' !== ( $parts['scheme'] ?? '' ) || 'meet.google.com' !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) {
			$this->render_page( 'لینک کلاس آماده نیست', 'لینک معتبر Google Meet برای این رزرو پیدا نشد. لطفاً با آموزشگاه تماس بگیرید.', '', 409 );
		}
		DLN_Attendance_Database::record_join( (int) $record['id'] );
		do_action( 'dln_attendance_join_clicked', $record );
		wp_redirect( $url, 302, 'Delnavazan Attendance' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	private function process_absence( array $record ): void {
		if ( in_array( (string) ( $record['status'] ?? '' ), array( 'absence_reported', 'attended', 'no_show' ), true ) ) {
			$this->render_page( 'ثبت قبلی حفظ شد', 'وضعیت این کلاس قبلاً نهایی شده است و دوباره تغییر نکرد.', '', 200 );
		}
		if ( ! DLN_Attendance_Database::record_absence( (int) $record['id'] ) ) {
			$this->render_page( 'امکان ثبت غیبت وجود ندارد', 'این کلاس لغو، بایگانی یا تغییر وضعیت داده شده است. لطفاً با آموزشگاه تماس بگیرید.', '', 409 );
		}
		$updated = DLN_Attendance_Database::get_by_id( (int) $record['id'] ) ?: $record;
		do_action( 'dln_attendance_status_changed', $updated, 'absence_reported' );
		$this->notify_absence( $updated );
			// Separate delivery channel; a Meta failure must not undo the student's report.
		DLN_Attendance_WhatsApp::send_teacher_absence( $updated );
		$this->render_page(
			'غیبت شما ثبت شد',
			'سپاس که اطلاع دادید. این ثبت به معنی تأیید کلاس جایگزین نیست؛ لطفاً برای هماهنگی و تأیید زمان جدید، مستقیماً با مدرس خود تماس بگیرید.',
			'',
			200
		);
	}

	private function notify_absence( array $record ): void {
		$settings = self::settings();
		$recipients = array_values( array_unique( array_filter( array_map( 'sanitize_email', array(
			(string) ( $record['employee_email'] ?? '' ),
			(string) ( $settings['notification_email'] ?? '' ),
		) ) ) ) );
		if ( ! $recipients ) {
			return;
		}
		$subject = sprintf( 'اعلام عدم حضور هنرجو — کد کلاس %d', absint( $record['booking_id'] ) );
		$message = implode(
			"\n",
			array(
				'هنرجو: ' . $record['customer_name'],
				'کلاس: ' . $record['service_name'],
				'مدرس: ' . $record['employee_name'],
				'تاریخ و ساعت کلاس: ' . get_date_from_gmt( (string) $record['booking_start'], 'Y-m-d H:i' ),
				'کد کلاس: ' . $record['booking_id'],
				'',
				'هنرجو برای هماهنگی زمان جایگزین، براساس زمان‌های در دسترس شما، با شما تماس خواهد گرفت. پس از توافق درباره زمان جدید، لطفاً وارد حساب کاربری خود در سایت دلنوازان شوید و تاریخ و ساعت کلاس را تغییر دهید. پس از تأیید شما، پیام تأیید زمان جدید برای هنرجو ارسال خواهد شد.',
			)
		);
		foreach ( $recipients as $recipient ) {
			wp_mail( $recipient, $subject, $message );
		}
	}

	public function send_webhook( array $record, string $status ): void {
		$settings = self::settings();
		$url      = esc_url_raw( (string) ( $settings['webhook_url'] ?? '' ) );
		if ( ! $url ) {
			return;
		}
		$payload = wp_json_encode(
			array(
				'event'          => 'attendance.status_changed',
				'status'         => sanitize_key( $status ),
				'booking_id'     => absint( $record['booking_id'] ?? 0 ),
				'appointment_id' => absint( $record['appointment_id'] ?? 0 ),
				'customer_name'  => sanitize_text_field( (string) ( $record['customer_name'] ?? '' ) ),
				'service_name'   => sanitize_text_field( (string) ( $record['service_name'] ?? '' ) ),
				'booking_start'  => sanitize_text_field( (string) ( $record['booking_start'] ?? '' ) ),
				'occurred_at'    => gmdate( 'c' ),
			)
		);
		$headers = array( 'Content-Type' => 'application/json' );
		$secret  = (string) ( $settings['webhook_secret'] ?? '' );
		if ( $secret ) {
			$headers['X-Delnavazan-Signature'] = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );
		}
		wp_remote_post( $url, array( 'timeout' => 8, 'blocking' => false, 'headers' => $headers, 'body' => $payload ) );
	}

	private function render_join_confirmation( array $record ): void {
		$details = $this->record_details( $record );
		$form    = '<form method="post"><input type="hidden" name="attendance_action" value="join"><button type="submit">ورود به کلاس در Google Meet</button></form>';
		$this->render_page( 'ورود به کلاس', 'با انتخاب دکمه زیر به Google Meet منتقل می‌شوید. ورود شما بعداً با گزارش Google Meet بررسی خواهد شد.', $details . $form );
	}

	private function render_absence_confirmation( array $record ): void {
		$details = $this->record_details( $record );
		$form    = '<form method="post"><input type="hidden" name="attendance_action" value="absence"><button class="danger" type="submit">تأیید می‌کنم که نمی‌توانم شرکت کنم</button></form>';
		$this->render_page( 'عدم حضور در کلاس', 'پس از ثبت عدم حضور، برای هماهنگی کلاس جایگزین با مدرس خود تماس بگیرید. ثبت عدم حضور به معنی تأیید کلاس جایگزین نیست و زمان جدید باید با مدرس هماهنگ و تأیید شود.', $details . $form );
	}

	private function record_details( array $record ): string {
		$timestamp = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', (string) ( $record['booking_start'] ?? '' ), new DateTimeZone( 'UTC' ) );
		$zone = sanitize_text_field( (string) ( $record['customer_timezone'] ?? '' ) );
		try { $timezone = new DateTimeZone( $zone ); } catch ( Exception $exception ) { $zone = wp_timezone_string() ?: 'Australia/Brisbane'; $timezone = new DateTimeZone( $zone ); }
		$when = $timestamp instanceof DateTimeImmutable ? wp_date( 'Y/m/d H:i', $timestamp->getTimestamp(), $timezone ) . ' (' . $zone . ')' : '';
		return '<dl>'
			. '<dt>هنرجو</dt><dd>' . esc_html( $record['customer_name'] ) . '</dd>'
			. '<dt>کلاس</dt><dd>' . esc_html( $record['service_name'] ) . '</dd>'
			. '<dt>مدرس</dt><dd>' . esc_html( $record['employee_name'] ) . '</dd>'
			. '<dt>زمان</dt><dd>' . esc_html( $when ) . '</dd>'
			. '</dl>';
	}

	private function render_page( string $title, string $message, string $content = '', int $status = 200 ): void {
		status_header( $status );
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset', 'UTF-8' ) );
		?><!doctype html>
		<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
		<title><?php echo esc_html( $title ); ?></title>
		<style>body{margin:0;background:#f5f6f4;color:#20352c;font-family:Tahoma,Arial,sans-serif}.card{max-width:560px;margin:8vh auto;padding:32px;background:#fff;border-radius:18px;box-shadow:0 8px 30px #0001}h1{font-size:1.6rem}p{line-height:1.9}dl{display:grid;grid-template-columns:90px 1fr;gap:10px;margin:25px 0}dt{font-weight:bold}dd{margin:0}button{width:100%;border:0;border-radius:10px;padding:14px;background:#176b4d;color:#fff;font:inherit;cursor:pointer}.danger{background:#9e3d3d}@media(max-width:620px){.card{margin:0;min-height:100vh;border-radius:0;box-sizing:border-box}}</style>
		</head><body><main class="card"><h1><?php echo esc_html( $title ); ?></h1><p><?php echo esc_html( $message ); ?></p><?php echo wp_kses( $content, array( 'dl' => array(), 'dt' => array(), 'dd' => array(), 'form' => array( 'method' => array() ), 'input' => array( 'type' => array(), 'name' => array(), 'value' => array() ), 'button' => array( 'type' => array(), 'class' => array() ) ) ); ?></main></body></html><?php
		exit;
	}
}

