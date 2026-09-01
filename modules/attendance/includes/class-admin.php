<?php

defined( 'ABSPATH' ) || exit;

final class DLN_Attendance_Admin {
	public static function boot(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_dln_attendance_google_callback', array( 'DLN_Attendance_Google', 'handle_callback' ) );
		add_action( 'admin_post_dln_attendance_disconnect_google', array( __CLASS__, 'disconnect_google' ) );
		add_action( 'admin_post_dln_attendance_reconcile_now', array( __CLASS__, 'reconcile_now' ) );
		add_action( 'admin_post_dln_attendance_sync_payment_report', array( __CLASS__, 'sync_payment_report' ) );
		add_action( 'admin_post_dln_attendance_manual_status', array( __CLASS__, 'manual_status' ) );
		add_action( 'admin_post_dln_attendance_export_teachers', array( __CLASS__, 'export_teachers' ) );
		add_action( 'admin_post_dln_attendance_export_log', array( __CLASS__, 'export_attendance_log' ) );
		add_action( 'admin_post_dln_attendance_whatsapp_test', array( __CLASS__, 'whatsapp_test' ) );
		add_action( 'admin_post_dln_attendance_confirmation_send', array( __CLASS__, 'confirmation_send' ) );
		add_action( 'admin_post_dln_attendance_reminder_send', array( __CLASS__, 'reminder_send' ) );
		add_action( 'admin_post_dln_attendance_reminder_received', array( __CLASS__, 'reminder_received' ) );
		add_action( 'admin_post_dln_attendance_term_renewal_test', array( __CLASS__, 'term_renewal_test' ) );
		add_action( 'admin_post_dln_attendance_payment_record', array( __CLASS__, 'payment_record' ) );
		add_action( 'admin_post_dln_attendance_archive_record', array( __CLASS__, 'archive_record' ) );
		add_action( 'admin_post_dln_attendance_restore_record', array( __CLASS__, 'restore_record' ) );
		add_action( 'admin_post_dln_attendance_download_documentation', array( __CLASS__, 'download_documentation' ) );
		add_action( 'admin_post_dln_attendance_download_codex_context', array( __CLASS__, 'download_codex_context' ) );
	}

	public static function enqueue_assets(): void {
		$page = sanitize_key( wp_unslash( (string) ( $_GET['page'] ?? '' ) ) );
		if ( 'dln-delnavazan' !== $page && ! str_starts_with( $page, 'dln-attendance' ) ) {
			return;
		}
		wp_enqueue_style( 'dln-attendance-admin', plugins_url( 'assets/admin.css', DLN_ATTENDANCE_FILE ), array(), DLN_ATTENDANCE_VERSION . '.2' );
	}

	public static function menu(): void {
		add_menu_page( 'Delnavazan', 'Delnavazan', 'manage_options', 'dln-delnavazan', array( __CLASS__, 'overview_page' ), 'dashicons-admin-site-alt3', 58 );
		add_submenu_page( 'dln-delnavazan', 'Delnavazan overview', 'Overview', 'manage_options', 'dln-delnavazan', array( __CLASS__, 'overview_page' ) );
		add_submenu_page( 'dln-delnavazan', 'Attendance log', 'Attendance log', 'manage_options', 'dln-attendance', array( __CLASS__, 'attendance_page' ) );
		add_submenu_page( 'dln-delnavazan', 'Teacher payment report', 'Teacher payments', 'manage_options', 'dln-attendance-teachers', array( __CLASS__, 'teacher_report_page' ) );
		add_submenu_page( 'dln-delnavazan', 'Project documentation', 'Documentation', 'manage_options', 'dln-attendance-documentation', array( __CLASS__, 'documentation_page' ) );
		add_submenu_page( 'dln-delnavazan', 'Settings', 'Settings', 'manage_options', 'dln-attendance-settings', array( __CLASS__, 'settings_page' ) );
	}

	public static function overview_page(): void {
		self::guard();
		self::notice();
		$settings = DLN_Attendance_Plugin::settings();
		$account_count = DLN_Attendance_Google::account_count();
		$health = DLN_Attendance_WhatsApp::health();
		?>
		<div class="wrap dln-admin dln-overview-page">
			<h1>Delnavazan operations</h1>
			<p>Operational status and the controls used for bookings, attendance and communication.</p>
			<div class="dln-overview-grid">
				<section><span class="dashicons dashicons-groups" aria-hidden="true"></span><div><h2>Attendance verification</h2><p><?php echo absint( $account_count ); ?> teacher Google account(s) connected. Attendance outcomes are kept separate from Amelia booking status.</p><p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=dln-attendance' ) ); ?>">Open attendance log</a></p></div></section>
				<section><span class="dashicons dashicons-format-chat" aria-hidden="true"></span><div><h2>WhatsApp workflows</h2><p>Approval confirmation: <strong><?php echo empty( $settings['confirmation_whatsapp_enabled'] ) ? 'Off by design' : 'Enabled'; ?></strong><br>Next-day reminder: <strong><?php echo empty( $settings['whatsapp_sender_enabled'] ) ? 'Off by design' : 'Enabled'; ?></strong><br>Delivery webhook: <strong><?php echo empty( $health['last_webhook_at'] ) ? 'Waiting for first event' : 'Receiving events'; ?></strong></p><p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=dln-attendance-settings' ) ); ?>">Notification controls</a></p></div></section>
			</div>
			<p class="dln-overview-links"><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=dln-attendance-teachers' ) ); ?>">Teacher payments</a> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=dln-attendance-documentation' ) ); ?>">Documentation</a></p>
			<div class="notice notice-info inline"><p><strong>Plugin version:</strong> <?php echo esc_html( DLNV_ENHANCEMENTS_VERSION ); ?> · <strong>Attendance schema:</strong> <?php echo esc_html( DLN_Attendance_Database::DB_VERSION ); ?></p></div>
		</div>
		<?php
	}

	public static function documentation_page(): void {
		self::guard();
		self::notice();
		$download = wp_nonce_url( admin_url( 'admin-post.php?action=dln_attendance_download_documentation' ), 'dln_download_documentation' );
		$context_download = wp_nonce_url( admin_url( 'admin-post.php?action=dln_attendance_download_codex_context' ), 'dln_download_codex_context' );
		$context = self::operations_context();
		?>
		<div class="wrap dln-admin dln-documentation"><h1>Delnavazan project documentation</h1>
		<p>This administrator-only page is generated from documentation bundled with the current plugin release. It contains no passwords, access tokens or OAuth secrets.</p>
		<div class="notice notice-info inline"><p><strong>Current release:</strong> <?php echo esc_html( DLN_ATTENDANCE_VERSION ); ?> · <strong>Database schema:</strong> <?php echo esc_html( DLN_Attendance_Database::DB_VERSION ); ?> · <strong>Completion rule:</strong> verified teacher-student overlap of at least <?php echo absint( DLN_Attendance_Plugin::settings()['minimum_attendance_minutes'] ); ?> minutes.</p></div>
		<p class="dln-doc-actions"><a class="button button-primary" href="<?php echo esc_url( $download ); ?>">Download master DOCX</a> <a class="button" href="<?php echo esc_url( $context_download ); ?>">Download Codex context</a> <button class="button" type="button" id="dln-copy-context">Copy Codex context</button></p>
		<label class="dln-doc-search"><span class="screen-reader-text">Search documentation</span><input id="dln-doc-search" type="search" placeholder="Search documentation…"></label>
		<textarea id="dln-context-source" hidden><?php echo esc_textarea( $context ); ?></textarea>
		<div id="dln-operations-content" class="dln-operations-content"><?php echo self::render_operations_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<script>(function(){var search=document.getElementById('dln-doc-search');var content=document.getElementById('dln-operations-content');var copy=document.getElementById('dln-copy-context');var source=document.getElementById('dln-context-source');if(search&&content){search.addEventListener('input',function(){var needle=this.value.trim().toLowerCase();content.querySelectorAll('section').forEach(function(section){section.hidden=needle&&!section.textContent.toLowerCase().includes(needle);});});}if(copy&&source){copy.addEventListener('click',function(){navigator.clipboard.writeText(source.value).then(function(){copy.textContent='Copied';window.setTimeout(function(){copy.textContent='Copy Codex context';},1500);});});}})();</script>
		<style>.dln-documentation{max-width:1050px}.dln-doc-actions{margin:18px 0}.dln-doc-search input{width:min(100%,520px);padding:8px 12px}.dln-operations-content{margin-top:18px}.dln-operations-content section{margin:0 0 16px;padding:20px 24px;background:#fff;border:1px solid #dcdcde;border-radius:10px}.dln-operations-content h2{margin:0 0 14px;color:#38236d}.dln-operations-content p,.dln-operations-content li{line-height:1.65}.dln-operations-content code{background:#f1eff7;padding:2px 5px;border-radius:4px}.dln-operations-content .dln-doc-intro{border-left:4px solid #6f42c1}</style></div>
		<?php
	}

	public static function download_documentation(): void {
		self::guard();
		check_admin_referer( 'dln_download_documentation' );
		$path = DLN_ATTENDANCE_DIR . 'docs/Master_Project_Documentation_V5.docx';
		if ( ! is_readable( $path ) ) {
			wp_die( esc_html__( 'The bundled master project document is unavailable.', 'delnavazan-attendance' ), '', array( 'response' => 404 ) );
		}
		$data = file_get_contents( $path );
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document' );
		header( 'Content-Disposition: attachment; filename="Delnavazan-Master-Project-Documentation-V5.docx"' );
		header( 'Content-Length: ' . (string) strlen( $data ) );
		echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public static function download_codex_context(): void {
		self::guard();
		check_admin_referer( 'dln_download_codex_context' );
		$data = self::operations_context();
		nocache_headers();
		header( 'Content-Type: text/markdown; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="Delnavazan-Attendance-Codex-Context-' . sanitize_file_name( DLN_ATTENDANCE_VERSION ) . '.md"' );
		header( 'Content-Length: ' . (string) strlen( $data ) );
		echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public static function register_settings(): void {
		register_setting( 'dln_attendance', 'dln_attendance_settings', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ) ) );
	}

	public static function sanitize_settings( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$old   = DLN_Attendance_Plugin::settings();
		return array(
			'notification_email'         => sanitize_email( (string) ( $input['notification_email'] ?? '' ) ),
			'webhook_url'                => esc_url_raw( (string) ( $input['webhook_url'] ?? '' ) ),
			'webhook_secret'             => DLN_Attendance_Secrets::seal( '' !== (string) ( $input['webhook_secret'] ?? '' ) ? sanitize_text_field( (string) $input['webhook_secret'] ) : (string) ( $old['webhook_secret'] ?? '' ) ),
			'minimum_attendance_minutes' => max( 1, min( 240, absint( $input['minimum_attendance_minutes'] ?? 20 ) ) ),
			'intro_service_category_id'  => max( 1, absint( $input['intro_service_category_id'] ?? 3 ) ),
			'retention_days'              => max( 30, min( 3650, absint( $input['retention_days'] ?? 180 ) ) ),
			'google_client_id'            => sanitize_text_field( (string) ( $input['google_client_id'] ?? '' ) ),
			'google_client_secret'        => DLN_Attendance_Secrets::seal( '' !== (string) ( $input['google_client_secret'] ?? '' ) ? sanitize_text_field( (string) $input['google_client_secret'] ) : (string) ( $old['google_client_secret'] ?? '' ) ),
			'amelia_google_client_id'     => sanitize_text_field( (string) ( $old['amelia_google_client_id'] ?? '' ) ),
			'amelia_google_client_secret' => DLN_Attendance_Secrets::seal( (string) ( $old['amelia_google_client_secret'] ?? '' ) ),
			'amelia_combined_oauth'       => (int) ( $old['amelia_combined_oauth'] ?? 0 ),
			'confirmation_whatsapp_enabled' => empty( $input['confirmation_whatsapp_enabled'] ) ? 0 : 1,
			'confirmation_template_name' => sanitize_key( (string) ( $input['confirmation_template_name'] ?? 'student_intro_class_confirmed' ) ),
			'confirmation_template_language' => preg_match( '/^[a-z]{2,3}(?:_[A-Z]{2})?$/', (string) ( $input['confirmation_template_language'] ?? '' ) ) ? (string) $input['confirmation_template_language'] : 'fa',
			'whatsapp_sender_enabled'     => empty( $input['whatsapp_sender_enabled'] ) ? 0 : 1,
			'whatsapp_phone_number_id'    => preg_replace( '/\D+/', '', (string) ( $input['whatsapp_phone_number_id'] ?? '' ) ),
			'whatsapp_access_token'       => DLN_Attendance_Secrets::seal( '' !== trim( (string) ( $input['whatsapp_access_token'] ?? '' ) ) ? sanitize_text_field( (string) $input['whatsapp_access_token'] ) : (string) ( $old['whatsapp_access_token'] ?? '' ) ),
			'whatsapp_template_name'      => sanitize_key( (string) ( $input['whatsapp_template_name'] ?? 'student_class_reminder_v2' ) ),
			'whatsapp_template_language'  => preg_match( '/^[a-z]{2,3}(?:_[A-Z]{2})?$/', (string) ( $input['whatsapp_template_language'] ?? '' ) )
				? (string) $input['whatsapp_template_language']
				: 'fa',
			'whatsapp_graph_version'      => preg_match( '/^v\d{1,2}\.\d$/', (string) ( $input['whatsapp_graph_version'] ?? '' ) )
				? (string) $input['whatsapp_graph_version']
				: 'v25.0',
			'whatsapp_send_time'          => preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) ( $input['whatsapp_send_time'] ?? '' ) )
				? (string) $input['whatsapp_send_time']
				: '07:00',
			'whatsapp_default_country_code' => ltrim( (string) preg_replace( '/\D+/', '', (string) ( $input['whatsapp_default_country_code'] ?? '61' ) ), '0' ),
			'whatsapp_webhook_verify_token' => DLN_Attendance_Secrets::seal( '' !== trim( (string) ( $input['whatsapp_webhook_verify_token'] ?? '' ) ) ? sanitize_text_field( (string) $input['whatsapp_webhook_verify_token'] ) : (string) ( $old['whatsapp_webhook_verify_token'] ?? '' ) ),
			'teacher_absence_whatsapp_enabled' => empty( $input['teacher_absence_whatsapp_enabled'] ) ? 0 : 1,
			'teacher_absence_template_name' => sanitize_key( (string) ( $input['teacher_absence_template_name'] ?? 'teacher_student_absence_alert' ) ),
			'teacher_absence_template_language' => preg_match( '/^[a-z]{2,3}(?:_[A-Z]{2})?$/', (string) ( $input['teacher_absence_template_language'] ?? '' ) ) ? (string) $input['teacher_absence_template_language'] : 'fa',
			'term_renewal_whatsapp_enabled' => empty( $input['term_renewal_whatsapp_enabled'] ) ? 0 : 1,
			'term_renewal_template_name' => sanitize_key( (string) ( $input['term_renewal_template_name'] ?? 'student_term_renewal_reminder' ) ),
			'term_renewal_template_language' => preg_match( '/^[a-z]{2,3}(?:_[A-Z]{2})?$/', (string) ( $input['term_renewal_template_language'] ?? '' ) ) ? (string) $input['term_renewal_template_language'] : 'fa',
			'term_renewal_sessions_remaining' => max( 1, min( 6, absint( $input['term_renewal_sessions_remaining'] ?? 2 ) ) ),
		);
	}

	public static function attendance_page(): void {
		self::guard();
		self::notice();
		$archived_view      = 'archived' === sanitize_key( wp_unslash( (string) ( $_GET['view'] ?? '' ) ) );
		$filters            = self::attendance_filters();
		$filters['archive'] = $archived_view ? 'archived' : 'active';
		$rows    = DLN_Attendance_Database::recent_filtered( $filters, 500 );
		$options = DLN_Attendance_Database::filter_options();
		$archive_count = DLN_Attendance_Database::archived_count();
		$export  = wp_nonce_url(
			add_query_arg( array_merge( array( 'action' => 'dln_attendance_export_log' ), $filters ), admin_url( 'admin-post.php' ) ),
			'dln_export_attendance'
		);
		?>
		<div class="wrap dln-admin dln-attendance-page"><h1><?php echo $archived_view ? 'Archived attendance records' : 'Attendance log'; ?></h1>
		<?php if ( $archived_view ) : ?><p>Archived records are excluded from the normal attendance log and all payment totals. They can be restored here. Records that remain archived for <?php echo absint( DLN_Attendance_Plugin::settings()['retention_days'] ); ?> days are permanently removed.</p><?php else : ?>
		<p>A class is completed only when the assigned teacher and booked student overlap in Google Meet for at least <strong><?php echo absint( DLN_Attendance_Plugin::settings()['minimum_attendance_minutes'] ); ?> minutes</strong>. Link clicks alone are not attendance proof.</p>
		<?php endif; ?>
		<form method="get" class="dln-filter-form"><input type="hidden" name="page" value="dln-attendance"><?php if ( $archived_view ) : ?><input type="hidden" name="view" value="archived"><?php endif; ?>
		<label>From <input type="date" name="from" value="<?php echo esc_attr( $filters['from'] ); ?>"></label>
		<label>To <input type="date" name="to" value="<?php echo esc_attr( $filters['to'] ); ?>"></label>
		<label>Teacher <select name="employee_email"><option value="">All</option><?php foreach ( $options['teachers'] as $teacher ) : ?><option value="<?php echo esc_attr( $teacher['employee_email'] ); ?>" <?php selected( $filters['employee_email'], $teacher['employee_email'] ); ?>><?php echo esc_html( $teacher['employee_name'] ); ?></option><?php endforeach; ?></select></label>
		<label>Class <select name="service_name"><option value="">All</option><?php foreach ( $options['services'] as $service ) : ?><option value="<?php echo esc_attr( $service ); ?>" <?php selected( $filters['service_name'], $service ); ?>><?php echo esc_html( $service ); ?></option><?php endforeach; ?></select></label>
		<label>Student <input type="search" name="student" value="<?php echo esc_attr( $filters['student'] ); ?>" placeholder="Name or email"></label>
		<label>Status <select name="status"><option value="">All</option><?php foreach ( array( 'pending', 'join_clicked', 'absence_reported', 'attended', 'review_needed', 'no_show', 'cancelled' ) as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( $filters['status'], $status ); ?>><?php echo esc_html( self::attendance_status_label( $status ) ); ?></option><?php endforeach; ?></select></label>
		<label>WhatsApp <select name="whatsapp"><option value="">All</option><?php foreach ( array( 'sent' => 'Production accepted', 'tested' => 'Test accepted', 'failed' => 'Failed', 'not_attempted' => 'Not attempted' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['whatsapp'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
		<?php submit_button( 'Filter', 'secondary', '', false ); ?> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=dln-attendance' . ( $archived_view ? '&view=archived' : '' ) ) ); ?>">Clear</a>
		</form>
		<p><?php if ( ! $archived_view ) : ?><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=dln_attendance_reconcile_now' ), 'dln_reconcile' ) ); ?>">Sync site bookings and reconcile Google Meet now</a> <?php endif; ?><a class="button" href="<?php echo esc_url( $export ); ?>">Export filtered CSV</a> <?php if ( $archived_view ) : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=dln-attendance' ) ); ?>">Back to attendance log</a><?php else : ?><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=dln-attendance&view=archived' ) ); ?>">View archived records (<?php echo absint( $archive_count ); ?>)</a><?php endif; ?></p>
		<div class="dln-table-scroll"><table class="widefat striped dln-attendance-table"><thead><tr><th>Class time</th><th>Student</th><th>Teacher</th><th>Service</th><th>Status</th><th>WhatsApp</th><th>Meet evidence</th><th>Manual correction</th></tr></thead><tbody>
		<?php if ( ! $rows ) : ?><tr><td colspan="8">No attendance records yet.</td></tr><?php endif; ?>
		<?php foreach ( $rows as $row ) : ?>
		<tr>
			<td><?php echo esc_html( self::local_date( $row['booking_start'] ) ); ?><br><small>Booking #<?php echo absint( $row['booking_id'] ); ?></small></td>
			<td><?php echo esc_html( $row['customer_name'] ); ?></td><td><?php echo esc_html( $row['employee_name'] ); ?></td><td><?php if ( DLN_Attendance_Database::is_introductory_category( absint( $row['service_category_id'] ?? 0 ) ) ) : ?><strong>Introductory class</strong><br><small><?php echo esc_html( $row['service_name'] ); ?> · Non-payable</small><?php else : ?><?php echo esc_html( $row['service_name'] ); ?><?php endif; ?></td>
			<td><span class="dln-status dln-status-<?php echo esc_attr( sanitize_html_class( (string) $row['status'] ) ); ?>"><?php echo esc_html( self::attendance_status_label( (string) $row['status'] ) ); ?></span><br><small>Join clicks: <?php echo absint( $row['join_click_count'] ); ?></small></td>
			<td class="dln-whatsapp-cell"><div class="dln-mini-workflow"><div class="dln-mini-workflow-title">Booking approval confirmation</div><?php echo self::confirmation_status_html( $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php self::confirmation_send_form( $row ); ?></div><div class="dln-mini-workflow"><div class="dln-mini-workflow-title">Next-day class reminder</div><?php echo self::reminder_status_html( $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php self::reminder_received_form( $row ); ?><?php self::reminder_send_form( $row ); ?></div></td>
			<td><small>Student: <?php echo esc_html( $row['student_participant_name'] ?: $row['meet_participant_name'] ); ?><?php if ( $row['student_duration_seconds'] || $row['meet_duration_seconds'] ) : ?> (<?php echo esc_html( round( ( $row['student_duration_seconds'] ?: $row['meet_duration_seconds'] ) / 60, 1 ) ); ?> min)<?php endif; ?><br>Teacher: <?php echo esc_html( $row['teacher_participant_name'] ); ?><?php if ( $row['teacher_duration_seconds'] ) : ?> (<?php echo esc_html( round( $row['teacher_duration_seconds'] / 60, 1 ) ); ?> min)<?php endif; ?><br>Overlap: <?php echo esc_html( round( absint( $row['overlap_seconds'] ) / 60, 1 ) ); ?> min<br><?php echo esc_html( $row['verification_note'] ); ?></small></td>
			<td><?php if ( $archived_view ) : ?><?php self::restore_form( (int) $row['id'] ); ?><?php else : ?><?php self::status_form( (int) $row['id'], (string) $row['status'] ); ?><hr><?php self::payment_form( $row ); ?><?php endif; ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody></table></div><style>.dln-filter-form{display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin:15px 0}.dln-filter-form label{display:flex;flex-direction:column;gap:4px}</style></div>
		<?php
	}

	public static function teacher_report_page(): void {
		self::guard();
		self::notice();
		$today = current_datetime();
		$from  = self::valid_date( $_GET['from'] ?? '' ) ?: $today->format( 'Y-m-01' );
		$to    = self::valid_date( $_GET['to'] ?? '' ) ?: $today->format( 'Y-m-d' );
		$teacher_email = sanitize_email( wp_unslash( $_GET['teacher_email'] ?? '' ) );
		$rate = max( 0, min( 100000, (float) ( $_GET['rate'] ?? 0 ) ) );
		$rows = DLN_Attendance_Database::teacher_report( $from, $to, $teacher_email );
		$totals = array();
		foreach ( $rows as $row ) {
			$key = $row['employee_email'] ?: $row['employee_name'];
			if ( ! isset( $totals[ $key ] ) ) {
				$totals[ $key ] = array( 'name' => $row['employee_name'], 'email' => $row['employee_email'], 'count' => 0 );
			}
			$totals[ $key ]['count'] += absint( $row['completed_count'] );
		}
		$options = DLN_Attendance_Database::filter_options();
		$export = wp_nonce_url( add_query_arg( array( 'action' => 'dln_attendance_export_teachers', 'from' => $from, 'to' => $to, 'teacher_email' => $teacher_email, 'rate' => $rate ), admin_url( 'admin-post.php' ) ), 'dln_export_teachers' );
		$sync = wp_nonce_url( add_query_arg( array( 'action' => 'dln_attendance_sync_payment_report', 'from' => $from, 'to' => $to, 'teacher_email' => $teacher_email, 'rate' => $rate ), admin_url( 'admin-post.php' ) ), 'dln_sync_payment_report' );
		$sessions = $teacher_email ? self::group_teacher_sessions( DLN_Attendance_Database::teacher_sessions( $from, $to, $teacher_email ) ) : array();
		$intro_sessions = array_values( array_filter( $sessions, static fn( array $session ): bool => ! empty( $session['introductory'] ) ) );
		$selected_name = '';
		foreach ( $options['teachers'] as $teacher ) {
			if ( $teacher_email === $teacher['employee_email'] ) { $selected_name = $teacher['employee_name']; break; }
		}
		$completed_total = array_sum( array_map( static fn( $row ) => absint( $row['completed_count'] ), $rows ) );
		$payment_total = $completed_total * $rate;
		$logo_url = wp_get_attachment_image_url( absint( get_theme_mod( 'custom_logo' ) ), 'medium' );
		?>
		<div class="wrap dln-admin dln-payment-page"><h1>Teacher payment report</h1>
		<p>Completed classes require at least <?php echo absint( DLN_Attendance_Plugin::settings()['minimum_attendance_minutes'] ); ?> minutes of verified teacher-student overlap in Google Meet, or a documented manual confirmation. Each Amelia appointment is counted once.</p>
		<form method="get" class="dln-payment-filter"><input type="hidden" name="page" value="dln-attendance-teachers"><label>From <input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"></label> <label>To <input type="date" name="to" value="<?php echo esc_attr( $to ); ?>"></label> <label>Teacher <select name="teacher_email"><option value="">All teachers</option><?php foreach ( $options['teachers'] as $teacher ) : ?><option value="<?php echo esc_attr( $teacher['employee_email'] ); ?>" <?php selected( $teacher_email, $teacher['employee_email'] ); ?>><?php echo esc_html( $teacher['employee_name'] ); ?></option><?php endforeach; ?></select></label> <label>Rate per class (Toman) <input type="number" min="0" step="1" name="rate" value="<?php echo esc_attr( $rate ?: '' ); ?>"></label> <?php submit_button( 'Create statement', 'primary', '', false ); ?> <a class="button" href="<?php echo esc_url( $sync ); ?>">Sync Amelia changes</a> <a class="button" href="<?php echo esc_url( $export ); ?>">Export CSV</a></form>
		<h2>Teacher totals</h2><table class="widefat striped"><thead><tr><th>Teacher</th><th>Email</th><th>Completed classes</th></tr></thead><tbody>
		<?php if ( ! $totals ) : ?><tr><td colspan="3">No completed classes in this period.</td></tr><?php endif; ?>
		<?php foreach ( $totals as $teacher ) : $statement_url = add_query_arg( array( 'page' => 'dln-attendance-teachers', 'from' => $from, 'to' => $to, 'teacher_email' => $teacher['email'], 'rate' => $rate ), admin_url( 'admin.php' ) ); ?><tr><td><a href="<?php echo esc_url( $statement_url ); ?>"><?php echo esc_html( $teacher['name'] ); ?></a></td><td><?php echo esc_html( $teacher['email'] ); ?></td><td><strong><?php echo absint( $teacher['count'] ); ?></strong></td></tr><?php endforeach; ?>
		</tbody></table>
		<h2>Service breakdown</h2><table class="widefat striped"><thead><tr><th>Teacher</th><th>Service</th><th>Completed classes</th></tr></thead><tbody>
		<?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row['employee_name'] ); ?></td><td><?php echo esc_html( $row['service_name'] ); ?></td><td><?php echo absint( $row['completed_count'] ); ?></td></tr><?php endforeach; ?>
		</tbody></table>
		<?php if ( $teacher_email ) : ?>
		<section class="dln-payment-statement" id="dln-payment-statement"><header><?php if ( $logo_url ) : ?><img class="dln-payment-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="Delnavazan"><?php endif; ?><div><h1>Delnavazan | Teacher Payment Statement</h1><p>گزارش پرداخت مدرس</p></div><div><strong><?php echo esc_html( $from ); ?> — <?php echo esc_html( $to ); ?></strong><br><small>Generated <?php echo esc_html( wp_date( 'Y-m-d' ) ); ?></small></div></header>
		<table class="dln-statement-meta"><tr><th>Teacher</th><td><?php echo esc_html( $selected_name ); ?></td><th>Email</th><td><?php echo esc_html( $teacher_email ); ?></td></tr><tr><th>Completed payable classes</th><td><?php echo absint( $completed_total ); ?></td><th>Introductory non-payable classes</th><td><?php echo absint( count( $intro_sessions ) ); ?></td></tr><tr><th>Rate</th><td><?php echo esc_html( number_format_i18n( $rate, 0 ) ); ?> تومان</td><th>Total payment</th><td><?php echo esc_html( number_format_i18n( $payment_total, 0 ) ); ?> تومان</td></tr></table>
		<h2>Service summary</h2><table><thead><tr><th>Service</th><th>Classes</th><th>Subtotal</th></tr></thead><tbody><?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row['service_name'] ); ?></td><td><?php echo absint( $row['completed_count'] ); ?></td><td><?php echo esc_html( number_format_i18n( absint( $row['completed_count'] ) * $rate, 0 ) ); ?> تومان</td></tr><?php endforeach; ?></tbody><tfoot><tr><th colspan="2">Total payment</th><th><?php echo esc_html( number_format_i18n( $payment_total, 0 ) ); ?> تومان</th></tr></tfoot></table>
		<?php if ( $intro_sessions ) : ?><h2>Introductory classes — non-payable</h2><table><thead><tr><th>Date</th><th>Student(s)</th><th>Service</th><th>Payment</th></tr></thead><tbody><?php foreach ( $intro_sessions as $session ) : ?><tr><td><?php echo esc_html( self::local_date( $session['booking_start'] ) ); ?></td><td><?php echo esc_html( implode( ', ', $session['students'] ) ); ?></td><td>Introductory class — <?php echo esc_html( $session['service_name'] ); ?></td><td><strong>Non-payable</strong></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
		<p class="dln-statement-note">This statement summarises classes verified by Google Meet attendance data or manually reconciled by Delnavazan.</p>
		<footer class="dln-payment-footer">Delnavazan Academy · Teacher payment statement</footer></section>
		<p class="no-print"><button class="button button-primary" type="button" onclick="window.print()">Print / Save branded PDF</button> <a class="button" href="<?php echo esc_url( $export ); ?>">Export detailed CSV</a></p>
		<section class="dln-session-details"><h2>Verified session log</h2><table class="widefat striped"><thead><tr><th>Date</th><th>Student(s)</th><th>Service</th><th>Payment</th><th>Scheduled</th><th>Student Meet</th><th>Teacher Meet</th><th>Overlap</th><th>Verification</th><th>Reference</th></tr></thead><tbody><?php foreach ( $sessions as $session ) : ?><tr><td><?php echo esc_html( self::local_date( $session['booking_start'] ) ); ?></td><td><?php echo esc_html( implode( ', ', $session['students'] ) ); ?></td><td><?php if ( ! empty( $session['introductory'] ) ) : ?><strong>Introductory class</strong><br><?php endif; ?><?php echo esc_html( $session['service_name'] ); ?></td><td><?php echo empty( $session['payable'] ) ? '<strong>Non-payable</strong>' : 'Payable'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td><td><?php echo esc_html( $session['scheduled_minutes'] ); ?> min</td><td><?php echo esc_html( round( $session['student_seconds'] / 60, 1 ) ); ?> min</td><td><?php echo esc_html( round( $session['teacher_seconds'] / 60, 1 ) ); ?> min</td><td><?php echo esc_html( round( $session['overlap_seconds'] / 60, 1 ) ); ?> min</td><td><?php echo esc_html( $session['verification_note'] ); ?></td><td>#<?php echo absint( $session['appointment_id'] ?: $session['booking_id'] ); ?></td></tr><?php endforeach; ?></tbody></table></section>
		<?php endif; ?>
		<style>.dln-payment-filter{display:flex;flex-wrap:wrap;align-items:end;gap:10px;margin:15px 0}.dln-payment-filter label{display:flex;flex-direction:column;gap:4px}.dln-payment-statement{max-width:900px;margin:30px 0;padding:34px;background:#fff;border:1px solid #d9d2ea;border-radius:14px}.dln-payment-statement header{display:flex;align-items:center;gap:18px;justify-content:space-between;border-bottom:3px solid #6f42c1;margin-bottom:20px}.dln-payment-logo{width:70px;height:auto;object-fit:contain}.dln-payment-statement table{width:100%;border-collapse:collapse}.dln-payment-statement th,.dln-payment-statement td{padding:10px;border-bottom:1px solid #ddd;text-align:left}.dln-payment-statement tfoot th{font-size:1.1em}.dln-statement-note,.dln-payment-footer{margin-top:25px;color:#555}.dln-payment-footer{padding-top:12px;border-top:1px solid #ddd;font-size:11px}@media print{#wpadminbar,#adminmenumain,.notice,.no-print,.dln-payment-filter,.dln-payment-page>h1,.dln-payment-page>p,.dln-payment-page>h2,.dln-payment-page>.widefat,.dln-session-details{display:none!important}#wpcontent,#wpbody-content{margin:0!important;padding:0!important}.dln-payment-statement{border:0;max-width:none;margin:0;padding:20px;box-shadow:none}}</style></div>
		<?php
	}

	public static function settings_page(): void {
		self::guard();
		self::notice();
		$s = DLN_Attendance_Plugin::settings();
		$accounts = DLN_Attendance_Google::accounts();
		$employee_google = DLN_Attendance_Google::employee_diagnostic();
		$whatsapp = DLN_Attendance_WhatsApp::diagnostic();
		$teacher_whatsapp = DLN_Attendance_WhatsApp::teacher_diagnostic();
		$confirmation_whatsapp = DLN_Attendance_WhatsApp::confirmation_diagnostic();
		$term_whatsapp = DLN_Attendance_WhatsApp::term_renewal_diagnostic();
		$health   = DLN_Attendance_WhatsApp::health();
		$recent_booking = DLN_Attendance_WhatsApp::most_recent_test_booking();
		$recent_renewal_booking = DLN_Attendance_WhatsApp::most_recent_test_booking( true );
		$bookings = $recent_booking ? array( $recent_booking ) : array();
		$renewal_test_bookings = $recent_renewal_booking ? array( $recent_renewal_booking ) : array();
		?>
		<div class="wrap dln-admin dln-settings-page"><h1>Delnavazan Attendance settings</h1>
		<p><strong>Plugin version:</strong> <?php echo esc_html( DLN_ATTENDANCE_VERSION ); ?> · WordPress timezone: <?php echo esc_html( wp_timezone_string() ); ?></p>
		<div class="dln-health-grid"><div><strong>WhatsApp automation</strong><br><?php echo empty( $s['whatsapp_sender_enabled'] ) ? 'Disabled' : 'Enabled'; ?></div><div><strong>Last cron trigger</strong><br><?php echo empty( $health['last_trigger_at'] ) ? 'Waiting for first trigger' : esc_html( wp_date( 'Y-m-d H:i:s', absint( $health['last_trigger_at'] ) ) ); ?></div><div><strong>Last reminder scan</strong><br><?php echo empty( $health['last_scan_at'] ) ? 'Not run yet' : esc_html( wp_date( 'Y-m-d H:i:s', absint( $health['last_scan_at'] ) ) ); ?></div><div><strong>Last term-renewal scan</strong><br><?php echo empty( $health['last_term_renewal_scan_at'] ) ? 'Not run yet' : esc_html( wp_date( 'Y-m-d H:i:s', absint( $health['last_term_renewal_scan_at'] ) ) ); ?></div><div><strong>Google accounts</strong><br><?php echo absint( count( $accounts ) ); ?> connected</div></div>
		<?php if ( $employee_google ) : ?><div class="notice notice-info inline"><p><strong>Employee Panel Google check:</strong> <?php echo esc_html( $employee_google['state'] ?? 'unknown' ); ?> — <?php echo esc_html( $employee_google['message'] ?? '' ); ?><?php if ( ! empty( $employee_google['employee_id'] ) ) : ?> Employee ID <?php echo absint( $employee_google['employee_id'] ); ?>.<?php endif; ?> <small>Last checked <?php echo esc_html( wp_date( 'Y-m-d H:i:s', absint( $employee_google['updated_at'] ?? 0 ) ) ); ?>.</small></p></div><?php endif; ?>
		<form method="post" action="options.php"><?php settings_fields( 'dln_attendance' ); ?><h2>Everyday controls</h2><table class="form-table" role="presentation">
		<tr><th>Notification email</th><td><input class="regular-text" type="email" name="dln_attendance_settings[notification_email]" value="<?php echo esc_attr( $s['notification_email'] ); ?>"><p class="description">Receives absence notices as well as the assigned teacher.</p></td></tr>
		<tr><th>Completion rule</th><td><input type="number" min="1" max="240" name="dln_attendance_settings[minimum_attendance_minutes]" value="<?php echo absint( $s['minimum_attendance_minutes'] ); ?>"> minutes of teacher-student overlap<p class="description">Production rule: 20 minutes. Other outcomes require an attendance review.</p></td></tr>
		<tr><th>Introductory category</th><td>Amelia category ID <input type="number" min="1" name="dln_attendance_settings[intro_service_category_id]" value="<?php echo absint( $s['intro_service_category_id'] ); ?>"><p class="description">Category 3 (جلسه معارفه) is introductory and always excluded from teacher payment.</p></td></tr></table>
		<details class="dln-settings-panel"><summary><strong>Shared WhatsApp connection and delivery webhook</strong></summary><table class="form-table"><tr><th>Phone number ID</th><td><input class="regular-text" inputmode="numeric" type="text" name="dln_attendance_settings[whatsapp_phone_number_id]" value="<?php echo esc_attr( $s['whatsapp_phone_number_id'] ); ?>"></td></tr><tr><th>Permanent access token</th><td><input class="large-text" type="password" autocomplete="new-password" name="dln_attendance_settings[whatsapp_access_token]" value="" placeholder="Saved — leave blank to keep"></td></tr><tr><th>Default phone country</th><td>+<input class="small-text" inputmode="numeric" type="text" name="dln_attendance_settings[whatsapp_default_country_code]" value="<?php echo esc_attr( $s['whatsapp_default_country_code'] ); ?>"></td></tr><tr><th>Graph API version</th><td><input class="small-text" type="text" name="dln_attendance_settings[whatsapp_graph_version]" value="<?php echo esc_attr( $s['whatsapp_graph_version'] ); ?>"></td></tr><tr><th>Webhook source</th><td><code class="dln-break-code"><?php echo esc_html( admin_url( 'admin-ajax.php?action=wpamelia_api&call=__notifications__whatsapp__webhook' ) ); ?></code><p class="description">The existing Amelia callback remains registered. Delnavazan observes its Meta status events first, then Amelia continues processing normally.</p></td></tr><tr><th>Meta subscription</th><td><strong>messages</strong> field required and currently subscribed. Do not replace the Amelia callback.</td></tr><tr><th>Last delivery event</th><td><?php echo empty( $health['last_webhook_at'] ) ? 'Waiting for the first Meta status event' : esc_html( wp_date( 'Y-m-d H:i:s', absint( $health['last_webhook_at'] ) ) . ' · ' . absint( $health['last_webhook_updates'] ?? 0 ) . ' known record(s) updated' ); ?></td></tr></table></details>
		<details class="dln-settings-panel"><summary><strong>Google Meet attendance connection</strong></summary><table class="form-table"><tr><th>OAuth client ID</th><td><input class="large-text" type="text" name="dln_attendance_settings[google_client_id]" value="<?php echo esc_attr( $s['google_client_id'] ); ?>"></td></tr><tr><th>OAuth client secret</th><td><input class="regular-text" type="password" autocomplete="new-password" name="dln_attendance_settings[google_client_secret]" value="" placeholder="Saved — leave blank to keep"></td></tr><tr><th>Authorized redirect URI</th><td><code><?php echo esc_html( DLN_Attendance_Google::redirect_uri() ); ?></code></td></tr><tr><th>Employee Panel card</th><td><code>[dln_attendance_google_connect]</code></td></tr></table></details>
		<details class="dln-settings-panel"><summary><strong>Advanced integrations</strong></summary><table class="form-table"><tr><th>Archive retention</th><td><input type="number" min="30" max="3650" name="dln_attendance_settings[retention_days]" value="<?php echo absint( $s['retention_days'] ); ?>"> days</td></tr><tr><th>Outgoing attendance webhook</th><td><input class="regular-text" type="url" name="dln_attendance_settings[webhook_url]" value="<?php echo esc_attr( $s['webhook_url'] ); ?>"><p class="description">Leave blank unless an external attendance automation is ready.</p></td></tr><tr><th>Webhook secret</th><td><input class="regular-text" type="password" autocomplete="new-password" name="dln_attendance_settings[webhook_secret]" value="" placeholder="Saved — leave blank to keep"></td></tr></table></details>
		<h2 class="dln-section-heading">WhatsApp notification workflows</h2>
		<p class="dln-section-intro">All workflows use the same structure. Purple buttons open a production action for the saved recipient; outlined buttons are controlled tests. Production sends always ask for confirmation and prevent duplicates. Student date and time values use the Amelia timezone first, then a safe phone-country fallback; the production form shows the exact result before sending.</p>
		<div class="dln-notification-grid">
		<article class="dln-notification-card">
			<header class="dln-card-header"><div><h3>Booking approval confirmation</h3><p>Student · Amelia approval</p></div><span class="dln-state-pill <?php echo empty( $s['confirmation_whatsapp_enabled'] ) ? 'is-off' : 'is-on'; ?>"><?php echo empty( $s['confirmation_whatsapp_enabled'] ) ? 'Off by design' : 'Enabled'; ?></span></header>
			<div class="dln-card-body">
				<div class="dln-control-row"><span class="dln-control-label">Status</span><label><input type="checkbox" name="dln_attendance_settings[confirmation_whatsapp_enabled]" value="1" <?php checked( ! empty( $s['confirmation_whatsapp_enabled'] ) ); ?>> Send automatically</label></div>
				<div class="dln-control-row"><span class="dln-control-label">Recipient</span><strong>Student</strong></div>
				<div class="dln-control-row"><span class="dln-control-label">Trigger</span><span>Introductory booking and appointment become Approved. Duplicate sends are blocked.</span></div>
				<div class="dln-control-row"><span class="dln-control-label">Template</span><div class="dln-inline-fields"><input class="regular-text" name="dln_attendance_settings[confirmation_template_name]" value="<?php echo esc_attr( $s['confirmation_template_name'] ); ?>"><label>Language <input class="small-text" name="dln_attendance_settings[confirmation_template_language]" value="<?php echo esc_attr( $s['confirmation_template_language'] ); ?>"></label></div></div>
				<div class="dln-control-row"><span class="dln-control-label">Latest result</span><span><?php echo self::diagnostic_html( $confirmation_whatsapp, 'No approval confirmation attempted yet.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></div>
			</div>
			<footer class="dln-card-actions"><a class="button button-primary dln-control-button" href="<?php echo esc_url( admin_url( 'admin.php?page=dln-attendance' ) ); ?>">Open production sends</a></footer>
		</article>
		<article class="dln-notification-card">
			<header class="dln-card-header"><div><h3>Next-day class reminder</h3><p>Student · Day before class</p></div><span class="dln-state-pill <?php echo empty( $s['whatsapp_sender_enabled'] ) ? 'is-off' : 'is-on'; ?>"><?php echo empty( $s['whatsapp_sender_enabled'] ) ? 'Off by design' : 'Enabled'; ?></span></header>
			<div class="dln-card-body">
				<div class="dln-control-row"><span class="dln-control-label">Status</span><label><input type="checkbox" name="dln_attendance_settings[whatsapp_sender_enabled]" value="1" <?php checked( ! empty( $s['whatsapp_sender_enabled'] ) ); ?>> Send tracked reminder</label></div>
				<div class="dln-control-row"><span class="dln-control-label">Recipient</span><strong>Student</strong></div>
				<div class="dln-control-row"><span class="dln-control-label">Trigger</span><span>Approved next-day booking, scanned every 15 minutes after <input type="time" name="dln_attendance_settings[whatsapp_send_time]" value="<?php echo esc_attr( $s['whatsapp_send_time'] ); ?>"> Brisbane time.</span></div>
				<div class="dln-control-row"><span class="dln-control-label">Template</span><div class="dln-inline-fields"><input class="regular-text" name="dln_attendance_settings[whatsapp_template_name]" value="<?php echo esc_attr( $s['whatsapp_template_name'] ); ?>"><label>Language <input class="small-text" name="dln_attendance_settings[whatsapp_template_language]" value="<?php echo esc_attr( $s['whatsapp_template_language'] ); ?>"></label></div></div>
				<div class="dln-control-row"><span class="dln-control-label">Latest result</span><span><?php echo self::diagnostic_html( $whatsapp, 'No reminder scan has run yet.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></div>
			</div>
			<footer class="dln-card-actions"><a class="button button-primary dln-control-button" href="<?php echo esc_url( admin_url( 'admin.php?page=dln-attendance' ) ); ?>">Open production sends</a><a class="button dln-control-button" href="#dln-controlled-tests">Controlled test</a></footer>
		</article>
		<article class="dln-notification-card">
			<header class="dln-card-header"><div><h3>Teacher absence alert</h3><p>Teacher · Student reports absence</p></div><span class="dln-state-pill <?php echo empty( $s['teacher_absence_whatsapp_enabled'] ) ? 'is-off' : 'is-on'; ?>"><?php echo empty( $s['teacher_absence_whatsapp_enabled'] ) ? 'Off by design' : 'Enabled'; ?></span></header>
			<div class="dln-card-body">
				<div class="dln-control-row"><span class="dln-control-label">Status</span><label><input type="checkbox" name="dln_attendance_settings[teacher_absence_whatsapp_enabled]" value="1" <?php checked( ! empty( $s['teacher_absence_whatsapp_enabled'] ) ); ?>> Alert assigned teacher</label></div>
				<div class="dln-control-row"><span class="dln-control-label">Recipient</span><span>Assigned teacher; a valid teacher phone number is required.</span></div>
				<div class="dln-control-row"><span class="dln-control-label">Trigger</span><span>Student confirms absence from the signed absence page.</span></div>
				<div class="dln-control-row"><span class="dln-control-label">Template</span><div class="dln-inline-fields"><input class="regular-text" name="dln_attendance_settings[teacher_absence_template_name]" value="<?php echo esc_attr( $s['teacher_absence_template_name'] ); ?>"><label>Language <input class="small-text" name="dln_attendance_settings[teacher_absence_template_language]" value="<?php echo esc_attr( $s['teacher_absence_template_language'] ); ?>"></label></div></div>
				<div class="dln-control-row"><span class="dln-control-label">Latest result</span><span><?php echo self::diagnostic_html( $teacher_whatsapp, 'No teacher absence alert attempted yet.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></div>
			</div>
			<footer class="dln-card-actions"><span class="dln-safety-note">Automatic after a signed absence · duplicate protected</span></footer>
		</article>
		<article class="dln-notification-card">
			<header class="dln-card-header"><div><h3>Term-renewal reminder</h3><p>Student · Sessions nearly finished</p></div><span class="dln-state-pill <?php echo empty( $s['term_renewal_whatsapp_enabled'] ) ? 'is-off' : 'is-on'; ?>"><?php echo empty( $s['term_renewal_whatsapp_enabled'] ) ? 'Off by design' : 'Enabled'; ?></span></header>
			<div class="dln-card-body">
				<div class="dln-control-row"><span class="dln-control-label">Status</span><label><input type="checkbox" name="dln_attendance_settings[term_renewal_whatsapp_enabled]" value="1" <?php checked( ! empty( $s['term_renewal_whatsapp_enabled'] ) ); ?>> Send once per term</label></div>
				<div class="dln-control-row"><span class="dln-control-label">Recipient</span><strong>Student</strong></div>
				<div class="dln-control-row"><span class="dln-control-label">Trigger</span><span><input type="number" min="1" max="6" name="dln_attendance_settings[term_renewal_sessions_remaining]" value="<?php echo absint( $s['term_renewal_sessions_remaining'] ); ?>"> payable sessions remain; introductory sessions are excluded.</span></div>
				<div class="dln-control-row"><span class="dln-control-label">Template</span><div class="dln-inline-fields"><input class="regular-text" name="dln_attendance_settings[term_renewal_template_name]" value="<?php echo esc_attr( $s['term_renewal_template_name'] ); ?>"><label>Language <input class="small-text" name="dln_attendance_settings[term_renewal_template_language]" value="<?php echo esc_attr( $s['term_renewal_template_language'] ); ?>"></label></div></div>
				<div class="dln-control-row"><span class="dln-control-label">Latest result</span><span><?php echo self::diagnostic_html( $term_whatsapp, 'No term-renewal scan has run yet.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></div>
			</div>
			<footer class="dln-card-actions"><a class="button dln-control-button" href="#dln-controlled-tests">Controlled test</a></footer>
		</article>
		</div>
		<?php submit_button( 'Save notification settings', 'primary dln-save-button' ); ?></form>
		<div id="dln-controlled-tests"><h2>Controlled notification tests</h2>
		<details class="dln-settings-panel"><summary><strong>Next-day reminder test</strong></summary><p>Sends the approved reminder template to an administrator-controlled number and does not mark the production reminder as sent.</p><table class="widefat striped"><thead><tr><th>Booking</th><th>Class</th><th>Student</th><th>Meet</th><th>Test recipient</th><th></th></tr></thead><tbody><?php if ( ! $bookings ) : ?><tr><td colspan="6">No recent approved booking found.</td></tr><?php endif; ?><?php foreach ( $bookings as $booking ) : ?><tr><td>#<?php echo absint( $booking['booking_id'] ); ?></td><td><?php echo esc_html( $booking['service_name'] ); ?></td><td><?php echo esc_html( $booking['customer_name'] ); ?></td><td><?php echo empty( $booking['meet_url'] ) ? 'Missing' : 'Ready'; ?></td><td colspan="2"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'dln_whatsapp_test' ); ?><input type="hidden" name="action" value="dln_attendance_whatsapp_test"><input type="hidden" name="booking_id" value="<?php echo absint( $booking['booking_id'] ); ?>"><input type="tel" name="recipient" required placeholder="+61…"> <?php submit_button( 'Send controlled test', 'secondary', '', false ); ?></form></td></tr><?php endforeach; ?></tbody></table></details>
		<details class="dln-settings-panel"><summary><strong>Term-renewal test</strong></summary><p>Sends the approved renewal template to an administrator-controlled number without creating production history.</p><table class="widefat striped"><tbody><?php if ( ! $renewal_test_bookings ) : ?><tr><td>No recent eligible non-introductory booking found.</td></tr><?php endif; ?><?php foreach ( $renewal_test_bookings as $booking ) : ?><tr><td>#<?php echo absint( $booking['booking_id'] ); ?></td><td><?php echo esc_html( $booking['service_name'] ); ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'dln_term_renewal_test' ); ?><input type="hidden" name="action" value="dln_attendance_term_renewal_test"><input type="hidden" name="booking_id" value="<?php echo absint( $booking['booking_id'] ); ?>"><input type="tel" name="recipient" required placeholder="+61…"> <?php submit_button( 'Send renewal test', 'secondary', '', false ); ?></form></td></tr><?php endforeach; ?></tbody></table></details></div>
		<h2>Connected Google organizer accounts</h2>
		<?php if ( ! $s['google_client_id'] || ! $s['google_client_secret'] ) : ?><p>Save the OAuth client ID and secret before connecting accounts.</p><?php else : ?><p><a class="button button-primary" href="<?php echo esc_url( DLN_Attendance_Google::authorization_url() ); ?>">Connect Google account</a></p><?php endif; ?>
		<table class="widefat striped"><thead><tr><th>Name</th><th>Email</th><th>Source</th><th>Connected</th><th></th></tr></thead><tbody>
		<?php if ( ! $accounts ) : ?><tr><td colspan="5">No Google organizer accounts connected.</td></tr><?php endif; ?>
		<?php foreach ( $accounts as $account ) : $url = wp_nonce_url( add_query_arg( array( 'action' => 'dln_attendance_disconnect_google', 'email' => $account['email'] ), admin_url( 'admin-post.php' ) ), 'dln_disconnect_google' ); ?><tr><td><?php echo esc_html( $account['name'] ); ?></td><td><?php echo esc_html( $account['email'] ); ?></td><td><?php echo esc_html( 'amelia' === ( $account['source'] ?? '' ) ? 'Amelia combined' : ( ! empty( $account['amelia_employee_id'] ) ? 'Employee Panel attendance' : 'Administrator' ) ); ?></td><td><?php echo esc_html( wp_date( 'Y-m-d H:i', absint( $account['connected_at'] ) ) ); ?></td><td><a href="<?php echo esc_url( $url ); ?>">Disconnect</a></td></tr><?php endforeach; ?>
		</tbody></table>
		<p class="description">Optional email placeholders: <code>%attendanceJoinUrl%</code> and <code>%attendanceAbsenceUrl%</code>.</p>
		<style>.dln-health-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;max-width:1000px;margin:18px 0}.dln-health-grid>div{padding:15px;background:#fff;border:1px solid #ddd;border-left:4px solid #6f42c1;border-radius:8px}.dln-settings-panel{max-width:1100px;margin:14px 0;background:#fff;border:1px solid #ddd;border-radius:8px}.dln-settings-panel summary{padding:15px;cursor:pointer}.dln-settings-panel>.form-table,.dln-settings-panel>table,.dln-settings-panel>p{margin:0 15px 15px;width:calc(100% - 30px)}.dln-message-preview{max-width:620px;padding:16px 18px;background:#f8f5ff;border:1px solid #e2d8f3;border-radius:10px;font-family:Tahoma,Arial,sans-serif;line-height:1.9}.dln-message-preview p{margin:0 0 10px}.dln-message-preview p:last-child{margin-bottom:0}</style>
		</div>
		<?php
	}

	public static function disconnect_google(): void {
		self::guard(); check_admin_referer( 'dln_disconnect_google' );
		DLN_Attendance_Google::disconnect( sanitize_email( wp_unslash( $_GET['email'] ?? '' ) ) );
		self::redirect( 'dln-attendance-settings', 'Google account disconnected.' );
	}

	public static function reconcile_now(): void {
		self::guard(); check_admin_referer( 'dln_reconcile' );
		$result  = DLN_Attendance_Google::reconcile_pending( 100 );
		$message = sprintf( 'Synced %d Amelia bookings; checked %d; completed %d; review needed %d; errors %d.', $result['synced'] ?? 0, $result['checked'], $result['attended'], $result['review'], count( $result['errors'] ) );
		self::redirect( 'dln-attendance', $message );
	}

	public static function sync_payment_report(): void {
		self::guard();
		check_admin_referer( 'dln_sync_payment_report' );
		$today = current_datetime();
		$from = self::valid_date( $_GET['from'] ?? '' ) ?: $today->format( 'Y-m-01' );
		$to = self::valid_date( $_GET['to'] ?? '' ) ?: $today->format( 'Y-m-d' );
		$teacher_email = sanitize_email( wp_unslash( $_GET['teacher_email'] ?? '' ) );
		$rate = max( 0, min( 100000, (float) ( $_GET['rate'] ?? 0 ) ) );
		$result = DLN_Attendance_WhatsApp::sync_attendance_records_range( $from, $to );
		$error = sanitize_text_field( (string) ( $result['error'] ?? '' ) );
		$message = $error ?: sprintf(
			'Synced %d Amelia bookings for this statement; %d missing active bookings reconciled.',
			absint( $result['synced'] ?? 0 ),
			absint( $result['cancelled'] ?? 0 )
		);
		set_transient( 'dln_attendance_notice_' . get_current_user_id(), array( $error ? 'error' : 'success', $message ), MINUTE_IN_SECONDS );
		$url = add_query_arg(
			array(
				'page' => 'dln-attendance-teachers',
				'from' => $from,
				'to' => $to,
				'teacher_email' => $teacher_email,
				'rate' => $rate,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public static function manual_status(): void {
		self::guard(); check_admin_referer( 'dln_manual_status' );
		$id     = absint( $_POST['record_id'] ?? 0 );
		$status = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
		$row    = DLN_Attendance_Database::get_by_id( $id );
		if ( $row && in_array( $status, array( 'pending', 'attended', 'no_show', 'review_needed' ), true ) ) {
			$extra = array( 'verification_note' => 'Status set manually by WordPress user #' . get_current_user_id() . '.' );
			if ( 'attended' === $status ) { $extra['attended_at'] = current_time( 'mysql', true ); }
			if ( 'no_show' === $status ) { $extra['no_show_at'] = current_time( 'mysql', true ); }
			DLN_Attendance_Database::update_status( $id, $status, $extra );
			do_action( 'dln_attendance_status_changed', $row, $status );
		}
		self::redirect( 'dln-attendance', 'Attendance status updated.' );
	}

	public static function confirmation_send(): void {
		self::guard();
		check_admin_referer( 'dln_confirmation_send' );
		if ( empty( $_POST['confirm_send'] ) ) { self::redirect( 'dln-attendance', 'Confirm the production recipient before sending.', 'error' ); }
		$booking_id = absint( $_POST['booking_id'] ?? 0 );
		$result = DLN_Attendance_WhatsApp::send_booking_confirmation( $booking_id, true );
		if ( is_wp_error( $result ) ) { self::redirect( 'dln-attendance', $result->get_error_message(), 'error' ); }
		if ( ! empty( $result['skipped'] ) ) { self::redirect( 'dln-attendance', 'Confirmation already accepted by Meta; duplicate send blocked.', 'warning' ); }
		self::redirect( 'dln-attendance', 'Meta accepted the student booking confirmation.', 'success' );
	}


	public static function reminder_send(): void {
		self::guard();
		check_admin_referer( 'dln_reminder_send' );
		if ( empty( $_POST['confirm_send'] ) ) { self::redirect( 'dln-attendance', 'Confirm the production recipient before sending.', 'error' ); }
		$booking_id = absint( $_POST['booking_id'] ?? 0 );
		$result = DLN_Attendance_WhatsApp::send_booking_reminder_now( $booking_id );
		if ( is_wp_error( $result ) ) { self::redirect( 'dln-attendance', $result->get_error_message(), 'error' ); }
		if ( ! empty( $result['skipped'] ) ) { self::redirect( 'dln-attendance', 'Next-day reminder already accepted by Meta; duplicate send blocked.', 'warning' ); }
		self::redirect( 'dln-attendance', 'Meta accepted the next-day student reminder.', 'success' );
	}

	public static function reminder_received(): void {
		self::guard();
		check_admin_referer( 'dln_reminder_received' );
		if ( empty( $_POST['confirm_received'] ) ) { self::redirect( 'dln-attendance', 'Confirm that the customer independently reported receiving the reminder.', 'error' ); }
		$record_id = absint( $_POST['record_id'] ?? 0 );
		$record = DLN_Attendance_Database::get_by_id( $record_id );
		if ( ! $record || ! empty( $record['archived_at'] ) || empty( $record['whatsapp_sent_at'] ) ) { self::redirect( 'dln-attendance', 'The reminder record is not eligible for manual receipt confirmation.', 'error' ); }
		if ( ! DLN_Attendance_Database::confirm_whatsapp_received( $record_id ) ) { self::redirect( 'dln-attendance', 'The customer receipt confirmation could not be recorded.', 'error' ); }
		self::redirect( 'dln-attendance', 'Customer receipt confirmation recorded. No WhatsApp message was sent.', 'success' );
	}

	public static function whatsapp_test(): void {
		self::guard();
		check_admin_referer( 'dln_whatsapp_test' );
		$booking_id = absint( $_POST['booking_id'] ?? 0 );
		$recipient  = sanitize_text_field( wp_unslash( $_POST['recipient'] ?? '' ) );
		$result     = DLN_Attendance_WhatsApp::send_booking_test( $booking_id, $recipient );
		if ( is_wp_error( $result ) ) {
			self::redirect( 'dln-attendance-settings', $result->get_error_message(), 'error' );
		}
		self::redirect( 'dln-attendance-settings', 'Meta accepted the controlled WhatsApp test message.', 'success' );
	}

	public static function term_renewal_test(): void {
		self::guard();
		check_admin_referer( 'dln_term_renewal_test' );
		$booking_id = absint( $_POST['booking_id'] ?? 0 );
		$recipient  = sanitize_text_field( wp_unslash( $_POST['recipient'] ?? '' ) );
		$result     = DLN_Attendance_WhatsApp::send_term_renewal_test( $booking_id, $recipient );
		if ( is_wp_error( $result ) ) {
			self::redirect( 'dln-attendance-settings', $result->get_error_message(), 'error' );
		}
		self::redirect( 'dln-attendance-settings', 'Meta accepted the controlled term-renewal WhatsApp test. Production history was not changed.', 'success' );
	}

	public static function payment_record(): void {
		self::guard(); check_admin_referer( 'dln_payment_record' );
		$id = absint( $_POST['record_id'] ?? 0 );
		$payable = ! empty( $_POST['payable'] );
		$note = sanitize_textarea_field( wp_unslash( $_POST['payment_note'] ?? '' ) );
		DLN_Attendance_Database::update_payment_record( $id, $payable, $note );
		self::redirect( 'dln-attendance', 'Payment treatment updated.' );
	}

	public static function archive_record(): void {
		self::guard(); check_admin_referer( 'dln_archive_record' );
		DLN_Attendance_Database::archive_record( absint( $_POST['record_id'] ?? 0 ) );
		self::redirect( 'dln-attendance', 'Attendance record archived. It remains in the database audit trail.' );
	}

	public static function restore_record(): void {
		self::guard(); check_admin_referer( 'dln_restore_record' );
		DLN_Attendance_Database::restore_record( absint( $_POST['record_id'] ?? 0 ) );
		self::redirect( 'dln-attendance&view=archived', 'Attendance record restored. It is visible in the normal attendance log again.' );
	}

	public static function export_teachers(): void {
		self::guard(); check_admin_referer( 'dln_export_teachers' );
		$today = current_datetime();
		$from = self::valid_date( $_GET['from'] ?? '' ) ?: $today->format( 'Y-m-01' );
		$to   = self::valid_date( $_GET['to'] ?? '' ) ?: $today->format( 'Y-m-d' );
		$teacher_email = sanitize_email( wp_unslash( $_GET['teacher_email'] ?? '' ) );
		$rate = max( 0, min( 100000, (float) ( $_GET['rate'] ?? 0 ) ) );
		$rows = $teacher_email
			? self::group_teacher_sessions( DLN_Attendance_Database::teacher_sessions( $from, $to, $teacher_email ) )
			: DLN_Attendance_Database::teacher_report( $from, $to );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="teacher-payment-' . $from . '-to-' . $to . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" );
		if ( $teacher_email ) {
			fputcsv( $out, array( 'Date', 'Teacher', 'Email', 'Student(s)', 'Service', 'Payment treatment', 'Scheduled minutes', 'Student Meet minutes', 'Teacher Meet minutes', 'Overlap minutes', 'Verification', 'Rate Toman', 'Amount Toman', 'Reference' ), ',', '"', '' );
			foreach ( $rows as $row ) {
				$payable = ! empty( $row['payable'] );
				$service = ! empty( $row['introductory'] ) ? 'Introductory class — ' . $row['service_name'] : $row['service_name'];
				fputcsv( $out, array_map( array( __CLASS__, 'csv_cell' ), array( self::local_date( $row['booking_start'] ), $row['employee_name'], $teacher_email, implode( ', ', $row['students'] ), $service, $payable ? 'Payable' : 'Non-payable', $row['scheduled_minutes'], round( $row['student_seconds'] / 60, 1 ), round( $row['teacher_seconds'] / 60, 1 ), round( $row['overlap_seconds'] / 60, 1 ), $row['verification_note'], $payable ? $rate : 0, $payable ? $rate : 0, $row['appointment_id'] ?: $row['booking_id'] ) ), ',', '"', '' );
			}
		} else {
			fputcsv( $out, array( 'From', 'To', 'Teacher', 'Email', 'Service', 'Completed classes' ), ',', '"', '' );
			foreach ( $rows as $row ) { fputcsv( $out, array_map( array( __CLASS__, 'csv_cell' ), array( $from, $to, $row['employee_name'], $row['employee_email'], $row['service_name'], $row['completed_count'] ) ), ',', '"', '' ); }
		}
		fclose( $out );
		exit;
	}

	public static function export_attendance_log(): void {
		self::guard(); check_admin_referer( 'dln_export_attendance' );
		$filters = self::attendance_filters();
		$rows = DLN_Attendance_Database::recent_filtered( $filters, 5000 );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="attendance-log-' . wp_date( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array( 'Class time', 'Booking ID', 'Student', 'Student email', 'Teacher', 'Teacher email', 'Service', 'Category ID', 'Payment treatment', 'Payment note', 'Status', 'WhatsApp', 'Student Meet minutes', 'Teacher Meet minutes', 'Overlap minutes', 'Verification note' ), ',', '"', '' );
		foreach ( $rows as $row ) {
			$whatsapp = $row['whatsapp_sent_at'] ? 'Production accepted by Meta' : ( $row['whatsapp_tested_at'] ? 'Test accepted by Meta' : ( $row['whatsapp_last_error'] ? 'Failed' : 'Not attempted' ) );
			fputcsv( $out, array_map( array( __CLASS__, 'csv_cell' ), array( self::local_date( $row['booking_start'] ), $row['booking_id'], $row['customer_name'], $row['customer_email'], $row['employee_name'], $row['employee_email'], DLN_Attendance_Database::is_introductory_category( absint( $row['service_category_id'] ?? 0 ) ) ? 'Introductory class — ' . $row['service_name'] : $row['service_name'], absint( $row['service_category_id'] ?? 0 ), empty( $row['payable'] ) ? 'Non-payable' : 'Payable', $row['payment_note'], self::attendance_status_label( (string) $row['status'] ), $whatsapp, round( ( $row['student_duration_seconds'] ?: $row['meet_duration_seconds'] ) / 60, 1 ), round( $row['teacher_duration_seconds'] / 60, 1 ), round( $row['overlap_seconds'] / 60, 1 ), $row['verification_note'] ) ), ',', '"', '' );
		}
		fclose( $out );
		exit;
	}

	private static function attendance_filters(): array {
		return array(
			'from'           => self::valid_date( $_GET['from'] ?? '' ),
			'to'             => self::valid_date( $_GET['to'] ?? '' ),
			'employee_email' => sanitize_email( wp_unslash( $_GET['employee_email'] ?? '' ) ),
			'service_name'   => sanitize_text_field( wp_unslash( $_GET['service_name'] ?? '' ) ),
			'student'        => sanitize_text_field( wp_unslash( $_GET['student'] ?? '' ) ),
			'status'         => sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ),
			'whatsapp'       => sanitize_key( wp_unslash( $_GET['whatsapp'] ?? '' ) ),
			'archive'        => 'archived' === sanitize_key( wp_unslash( (string) ( $_GET['view'] ?? $_GET['archive'] ?? '' ) ) ) ? 'archived' : 'active',
		);
	}

	private static function group_teacher_sessions( array $rows ): array {
		$grouped = array();
		foreach ( $rows as $row ) {
			$key = ! empty( $row['appointment_id'] ) ? 'a:' . absint( $row['appointment_id'] ) : 'b:' . absint( $row['booking_id'] );
			if ( ! isset( $grouped[ $key ] ) ) {
				$start = strtotime( (string) $row['booking_start'] . ' UTC' ) ?: 0;
				$end   = strtotime( (string) $row['booking_end'] . ' UTC' ) ?: 0;
				$grouped[ $key ] = array(
					'appointment_id' => absint( $row['appointment_id'] ),
					'booking_id' => absint( $row['booking_id'] ),
					'booking_start' => $row['booking_start'],
					'employee_name' => $row['employee_name'],
					'service_name' => $row['service_name'],
					'service_category_id' => absint( $row['service_category_id'] ?? 0 ),
					'introductory' => DLN_Attendance_Database::is_introductory_category( absint( $row['service_category_id'] ?? 0 ) ),
					'payable' => ! empty( $row['payable'] ),
					'scheduled_minutes' => $start && $end && $end > $start ? round( ( $end - $start ) / 60 ) : 0,
					'student_seconds' => 0,
					'teacher_seconds' => 0,
					'overlap_seconds' => 0,
					'verification_note' => (string) $row['verification_note'],
					'students' => array(),
				);
			}
			$grouped[ $key ]['payable'] = ! empty( $grouped[ $key ]['payable'] ) && ! empty( $row['payable'] );
			$grouped[ $key ]['student_seconds'] = max( $grouped[ $key ]['student_seconds'], absint( $row['student_duration_seconds'] ?: $row['meet_duration_seconds'] ) );
			$grouped[ $key ]['teacher_seconds'] = max( $grouped[ $key ]['teacher_seconds'], absint( $row['teacher_duration_seconds'] ) );
			$grouped[ $key ]['overlap_seconds'] = max( $grouped[ $key ]['overlap_seconds'], absint( $row['overlap_seconds'] ) );
			if ( ! empty( $row['customer_name'] ) ) {
				$grouped[ $key ]['students'][] = $row['customer_name'];
				$grouped[ $key ]['students'] = array_values( array_unique( $grouped[ $key ]['students'] ) );
			}
		}
		return array_values( $grouped );
	}

	private static function csv_cell( $value ): string {
		$value = (string) $value;
		return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value;
	}

	private static function attendance_status_label( string $status ): string {
		$labels = array( 'pending' => 'Awaiting attendance check', 'join_clicked' => 'Join link opened', 'absence_reported' => 'Absence reported', 'attended' => 'Attendance confirmed', 'review_needed' => 'Attendance review required', 'no_show' => 'No attendance detected', 'cancelled' => 'Amelia booking cancelled' );
		return $labels[ sanitize_key( $status ) ] ?? ucwords( str_replace( '_', ' ', $status ) );
	}

	private static function delivery_status_label( string $status, string $fallback = 'Meta accepted — delivery receipt pending' ): string {
		$labels = array( 'sent' => 'Sent by Meta — delivery receipt pending', 'delivered' => 'Delivered', 'read' => 'Read', 'failed' => 'Failed' );
		return $labels[ sanitize_key( $status ) ] ?? $fallback;
	}

	private static function diagnostic_html( array $diagnostic, string $empty ): string {
		if ( ! $diagnostic ) { return esc_html( $empty ); }
		$html = '<strong>' . esc_html( (string) ( $diagnostic['state'] ?? 'unknown' ) ) . '</strong> — ' . esc_html( (string) ( $diagnostic['message'] ?? '' ) );
		if ( ! empty( $diagnostic['updated_at'] ) ) { $html .= '<br><small>' . esc_html( wp_date( 'Y-m-d H:i:s', absint( $diagnostic['updated_at'] ) ) ) . '</small>'; }
		return $html;
	}

	private static function confirmation_status_html( array $row ): string {
		if ( ! empty( $row['confirmation_whatsapp_sent_at'] ) ) {
			$delivery = sanitize_key( (string) ( $row['confirmation_whatsapp_delivery_status'] ?? '' ) );
			$status = self::delivery_status_label( $delivery );
			$time = $row['confirmation_whatsapp_status_at'] ?: $row['confirmation_whatsapp_sent_at'];
			$class = 'failed' === $delivery ? 'no_show' : ( in_array( $delivery, array( 'delivered', 'read' ), true ) ? 'attended' : 'pending' );
			$source = in_array( $delivery, array( 'delivered', 'read', 'failed' ), true ) ? 'Meta delivery receipt · ' : '';
			return '<span class="dln-status dln-status-' . esc_attr( $class ) . '">' . esc_html( $status ) . '</span><br><small>' . esc_html( $source . self::local_date( $time ) ) . '</small>';
		}
		if ( ! empty( $row['confirmation_whatsapp_last_error'] ) ) { return '<span class="dln-status dln-status-no_show">Failed</span><br><small>' . esc_html( (string) $row['confirmation_whatsapp_last_error'] ) . '</small>'; }
		return '<span class="dln-status dln-status-pending">Not sent</span>';
	}

	private static function reminder_status_html( array $row ): string {
		if ( ! empty( $row['whatsapp_sent_at'] ) ) {
			$delivery = sanitize_key( (string) ( $row['whatsapp_delivery_status'] ?? '' ) );
			if ( in_array( $delivery, array( 'delivered', 'read', 'failed' ), true ) ) {
				$status = self::delivery_status_label( $delivery );
				$time = $row['whatsapp_status_at'] ?: $row['whatsapp_sent_at'];
				$class = 'failed' === $delivery ? 'no_show' : 'attended';
				return '<span class="dln-status dln-status-' . esc_attr( $class ) . '">' . esc_html( $status ) . '</span><br><small>Meta delivery receipt · ' . esc_html( self::local_date( $time ) ) . '</small>';
			}
			if ( ! empty( $row['join_click_count'] ) ) {
				$time = $row['join_clicked_at'] ?: $row['whatsapp_sent_at'];
				return '<span class="dln-status dln-status-join_clicked">Class link used</span><br><small>Receipt confirmed by tracked link activity · ' . esc_html( self::local_date( $time ) ) . '</small>';
			}
			if ( ! empty( $row['whatsapp_received_confirmed_at'] ) ) {
				return '<span class="dln-status dln-status-join_clicked">Customer confirmed receipt</span><br><small>Recorded by administrator · ' . esc_html( self::local_date( $row['whatsapp_received_confirmed_at'] ) ) . '</small>';
			}
			$status = self::delivery_status_label( $delivery );
			$time = $row['whatsapp_status_at'] ?: $row['whatsapp_sent_at'];
			return '<span class="dln-status dln-status-pending">' . esc_html( $status ) . '</span><br><small>' . esc_html( self::local_date( $time ) ) . '</small>';
		}
		if ( ! empty( $row['whatsapp_tested_at'] ) ) { return '<span class="dln-status dln-status-join_clicked">Controlled test</span><br><small>' . esc_html( self::local_date( $row['whatsapp_tested_at'] ) ) . '</small>'; }
		if ( ! empty( $row['whatsapp_last_error'] ) ) { return '<span class="dln-status dln-status-no_show">Failed</span><br><small>' . esc_html( (string) $row['whatsapp_last_error'] ) . '</small>'; }
		return '<span class="dln-status dln-status-pending">Not sent</span>';
	}

	private static function notification_preview( array $preview ): void {
		$student = trim( (string) ( $preview['customer_phone'] ?? '' ) );
		$student_time = trim( (string) ( $preview['message_date'] ?? '' ) . ' ' . (string) ( $preview['message_time'] ?? '' ) );
		$teacher_time = trim( (string) ( $preview['teacher_date'] ?? '' ) . ' ' . (string) ( $preview['teacher_time'] ?? '' ) );
		$site_time = trim( (string) ( $preview['site_date'] ?? '' ) . ' ' . (string) ( $preview['site_time'] ?? '' ) );
		?><dl class="dln-send-preview">
		<div><dt>Student phone</dt><dd><strong><?php echo esc_html( $student ?: 'unavailable' ); ?></strong></dd></div>
		<div><dt>Student time</dt><dd><strong><?php echo esc_html( $student_time ?: 'unavailable' ); ?></strong><br><?php echo esc_html( (string) ( $preview['message_timezone'] ?? '' ) ); ?> <em>(<?php echo esc_html( (string) ( $preview['message_timezone_source'] ?? '' ) ); ?>)</em></dd></div>
		<div><dt>Teacher time</dt><dd><strong><?php echo esc_html( $teacher_time ?: 'unavailable' ); ?></strong><br><?php echo esc_html( (string) ( $preview['teacher_timezone'] ?? '' ) ); ?> <em>(<?php echo esc_html( (string) ( $preview['teacher_timezone_source'] ?? '' ) ); ?>)</em></dd></div>
		<div><dt>Site time</dt><dd><strong><?php echo esc_html( $site_time ?: 'unavailable' ); ?></strong><br><?php echo esc_html( (string) ( $preview['site_timezone'] ?? '' ) ); ?></dd></div>
		<div><dt>Class button</dt><dd><strong><?php echo esc_html( (string) ( $preview['class_button_status'] ?? 'unavailable' ) ); ?></strong></dd></div>
		</dl><?php
	}

	private static function confirmation_send_form( array $row ): void {
		$eligible = 'approved' === (string) ( $row['source_status'] ?? '' ) && DLN_Attendance_Database::is_introductory_category( absint( $row['service_category_id'] ?? 0 ) );
		if ( ! $eligible || ! empty( $row['archived_at'] ) || ! empty( $row['confirmation_whatsapp_sent_at'] ) ) { return; }
		$preview = DLN_Attendance_WhatsApp::booking_preview( absint( $row['booking_id'] ?? 0 ) );
		?><form class="dln-production-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'dln_confirmation_send' ); ?><input type="hidden" name="action" value="dln_attendance_confirmation_send"><input type="hidden" name="booking_id" value="<?php echo absint( $row['booking_id'] ); ?>"><?php self::notification_preview( $preview ); ?><label class="dln-send-confirm"><input type="checkbox" name="confirm_send" value="1" required> Confirm production send</label><?php submit_button( 'Send confirmation now', 'primary dln-control-button', '', false ); ?></form><?php
	}

	private static function reminder_received_form( array $row ): void {
		$delivery = sanitize_key( (string) ( $row['whatsapp_delivery_status'] ?? '' ) );
		if ( empty( $row['whatsapp_sent_at'] ) || ! empty( $row['archived_at'] ) || ! empty( $row['whatsapp_received_confirmed_at'] ) || ! empty( $row['join_click_count'] ) || in_array( $delivery, array( 'delivered', 'read', 'failed' ), true ) ) { return; }
		?><form class="dln-production-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'dln_reminder_received' ); ?><input type="hidden" name="action" value="dln_attendance_reminder_received"><input type="hidden" name="record_id" value="<?php echo absint( $row['id'] ); ?>"><small>Use only after the customer independently confirms receipt.</small><label class="dln-send-confirm"><input type="checkbox" name="confirm_received" value="1" required> Customer confirmed receipt</label><?php submit_button( 'Confirm customer receipt', 'secondary dln-control-button', '', false ); ?></form><?php
	}

	private static function reminder_send_form( array $row ): void {
		if ( 'approved' !== (string) ( $row['source_status'] ?? '' ) || ! empty( $row['archived_at'] ) || ! empty( $row['whatsapp_sent_at'] ) ) { return; }
		$timestamp = strtotime( (string) ( $row['booking_start'] ?? '' ) . ' UTC' ) ?: 0;
		$booking_day = $timestamp ? wp_date( 'Y-m-d', $timestamp, wp_timezone() ) : '';
		if ( $booking_day !== current_datetime()->modify( 'tomorrow' )->format( 'Y-m-d' ) ) { return; }
		$preview = DLN_Attendance_WhatsApp::booking_preview( absint( $row['booking_id'] ?? 0 ) );
		?><form class="dln-production-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'dln_reminder_send' ); ?><input type="hidden" name="action" value="dln_attendance_reminder_send"><input type="hidden" name="booking_id" value="<?php echo absint( $row['booking_id'] ); ?>"><?php self::notification_preview( $preview ); ?><label class="dln-send-confirm"><input type="checkbox" name="confirm_send" value="1" required> Confirm production send</label><?php submit_button( 'Send reminder now', 'primary dln-control-button', '', false ); ?></form><?php
	}

	private static function status_form( int $id, string $current ): void {
		?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'dln_manual_status' ); ?><input type="hidden" name="action" value="dln_attendance_manual_status"><input type="hidden" name="record_id" value="<?php echo absint( $id ); ?>"><select name="status"><?php foreach ( array( 'pending', 'attended', 'no_show', 'review_needed' ) as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( $current, $status ); ?>><?php echo esc_html( self::attendance_status_label( $status ) ); ?></option><?php endforeach; ?></select> <?php submit_button( 'Save', 'small', '', false ); ?></form><?php
	}

	private static function payment_form( array $row ): void {
		$introductory = DLN_Attendance_Database::is_introductory_category( absint( $row['service_category_id'] ?? 0 ) );
		?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'dln_payment_record' ); ?><input type="hidden" name="action" value="dln_attendance_payment_record"><input type="hidden" name="record_id" value="<?php echo absint( $row['id'] ); ?>"><label><input type="checkbox" name="payable" value="1" <?php checked( ! empty( $row['payable'] ) ); ?> <?php disabled( $introductory ); ?>> Payable</label><?php if ( $introductory ) : ?><br><small>Introductory category is always non-payable.</small><?php endif; ?><br><input type="text" name="payment_note" value="<?php echo esc_attr( $row['payment_note'] ?? '' ); ?>" placeholder="e.g. Introductory lesson — free" aria-label="Payment note" <?php echo $introductory ? 'readonly' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>> <?php submit_button( 'Update pay', 'small', '', false ); ?></form><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Archive this record? It remains available for audit but is excluded from reports.');"><?php wp_nonce_field( 'dln_archive_record' ); ?><input type="hidden" name="action" value="dln_attendance_archive_record"><input type="hidden" name="record_id" value="<?php echo absint( $row['id'] ); ?>"><?php submit_button( 'Archive record', 'link-delete', '', false ); ?></form><?php
	}

	private static function restore_form( int $id ): void {
		?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Restore this record to the normal attendance log?');"><?php wp_nonce_field( 'dln_restore_record' ); ?><input type="hidden" name="action" value="dln_attendance_restore_record"><input type="hidden" name="record_id" value="<?php echo absint( $id ); ?>"><?php submit_button( 'Restore record', 'secondary', '', false ); ?></form><?php
	}

	private static function operations_context(): string {
		$path = DLN_ATTENDANCE_DIR . 'docs/CODEX_CONTEXT.md';
		if ( ! is_readable( $path ) ) {
			return '# Documentation unavailable' . "\n\n" . 'The bundled Codex context file could not be read.';
		}
		$data = file_get_contents( $path );
		return is_string( $data ) ? $data : '';
	}

	/** Small, intentionally restricted Markdown renderer for the bundled trusted file. */
	private static function render_operations_context( string $markdown ): string {
		$lines = preg_split( '/\R/u', $markdown ) ?: array();
		$html = '';
		$section_open = false;
		$list = '';
		$close_list = static function () use ( &$html, &$list ): void {
			if ( $list ) {
				$html .= '</' . $list . '>';
				$list = '';
			}
		};
		$inline = static function ( string $text ): string {
			$text = esc_html( trim( $text ) );
			return preg_replace( '/`([^`]+)`/', '<code>$1</code>', $text ) ?: $text;
		};
		foreach ( $lines as $line ) {
			$line = rtrim( $line );
			if ( str_starts_with( $line, '# ' ) ) {
				$close_list();
				if ( $section_open ) { $html .= '</section>'; }
				$html .= '<section class="dln-doc-intro"><h2 id="overview">' . $inline( substr( $line, 2 ) ) . '</h2>';
				$section_open = true;
			} elseif ( str_starts_with( $line, '## ' ) ) {
				$close_list();
				if ( $section_open ) { $html .= '</section>'; }
				$title = trim( substr( $line, 3 ) );
				$html .= '<section><h2 id="' . esc_attr( sanitize_title( $title ) ) . '">' . $inline( $title ) . '</h2>';
				$section_open = true;
			} elseif ( preg_match( '/^-\s+(.+)$/u', $line, $match ) ) {
				if ( 'ul' !== $list ) { $close_list(); $html .= '<ul>'; $list = 'ul'; }
				$html .= '<li>' . $inline( $match[1] ) . '</li>';
			} elseif ( preg_match( '/^\d+\.\s+(.+)$/u', $line, $match ) ) {
				if ( 'ol' !== $list ) { $close_list(); $html .= '<ol>'; $list = 'ol'; }
				$html .= '<li>' . $inline( $match[1] ) . '</li>';
			} elseif ( '' === trim( $line ) ) {
				$close_list();
			} else {
				$close_list();
				$html .= '<p>' . $inline( preg_replace( '/\s{2}$/', '', $line ) ) . '</p>';
			}
		}
		$close_list();
		if ( $section_open ) { $html .= '</section>'; }
		return $html;
	}

	private static function notice(): void {
		$notice = get_transient( 'dln_attendance_notice_' . get_current_user_id() );
		if ( $notice ) { delete_transient( 'dln_attendance_notice_' . get_current_user_id() ); echo '<div class="notice notice-' . esc_attr( $notice[0] ) . ' is-dismissible"><p>' . esc_html( $notice[1] ) . '</p></div>'; }
	}

	private static function redirect( string $page, string $message, string $type = 'success' ): void {
		$type = in_array( $type, array( 'success', 'warning', 'error', 'info' ), true ) ? $type : 'info';
		set_transient( 'dln_attendance_notice_' . get_current_user_id(), array( $type, $message ), MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) ); exit;
	}

	private static function guard(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'You are not allowed to view this page.', 'delnavazan-attendance' ), '', array( 'response' => 403 ) ); }
	}

	private static function valid_date( $value ): string {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	private static function local_date( $mysql ): string {
		return $mysql ? get_date_from_gmt( (string) $mysql, 'Y-m-d H:i' ) : '';
	}
}

