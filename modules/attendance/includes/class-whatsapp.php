<?php

defined( 'ABSPATH' ) || exit;

/**
 * Sends the tracked reminder template through Meta's WhatsApp Cloud API.
 *
 * Amelia remains the source of booking data and the external Amelia cron
 * remains the scheduler. This class only reads Amelia tables and never writes
 * booking data back to them.
 */
final class DLN_Attendance_WhatsApp {
	private const DIAGNOSTIC_OPTION = 'dln_attendance_whatsapp_diagnostic';
	private const CONFIRMATION_DIAGNOSTIC_OPTION = 'dln_attendance_confirmation_whatsapp_diagnostic';
	private const TEACHER_DIAGNOSTIC_OPTION = 'dln_attendance_teacher_whatsapp_diagnostic';
	private const TERM_DIAGNOSTIC_OPTION = 'dln_attendance_term_renewal_whatsapp_diagnostic';
	private const TERM_HISTORY_OPTION = 'dln_attendance_term_renewal_whatsapp_history';
	private const HEALTH_OPTION     = 'dln_attendance_whatsapp_health';
	private const RUN_LOCK_OPTION   = 'dln_attendance_whatsapp_run_lock';
	private const TEMPLATE_DEFAULT  = 'student_class_reminder_v2';
	private const TERM_LENGTH       = 12;

	public static function boot(): void {
		add_action( 'amelia_before_send_scheduled_notifications', array( __CLASS__, 'run_scheduled' ), 20 );
		add_action( 'AmeliaAppointmentBookingStatusUpdated', array( __CLASS__, 'handle_amelia_booking_event' ), 20, 3 );
		add_action( 'AmeliaAppointmentBookingAdded', array( __CLASS__, 'handle_amelia_booking_event' ), 20, 3 );
		add_action( 'amelia_after_appointment_status_updated', array( __CLASS__, 'handle_amelia_appointment_status' ), 20, 2 );
		add_action( 'admin_post_dln_attendance_whatsapp_webhook', array( __CLASS__, 'handle_webhook' ) );
		add_action( 'admin_post_nopriv_dln_attendance_whatsapp_webhook', array( __CLASS__, 'handle_webhook' ) );
		add_action( 'wp_ajax_wpamelia_api', array( __CLASS__, 'observe_amelia_webhook' ), 1 );
		add_action( 'wp_ajax_nopriv_wpamelia_api', array( __CLASS__, 'observe_amelia_webhook' ), 1 );
	}

	public static function diagnostic(): array {
		$value = get_option( self::DIAGNOSTIC_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function teacher_diagnostic(): array {
		$value = get_option( self::TEACHER_DIAGNOSTIC_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function term_renewal_diagnostic(): array {
		$value = get_option( self::TERM_DIAGNOSTIC_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function confirmation_diagnostic(): array {
		$value = get_option( self::CONFIRMATION_DIAGNOSTIC_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function handle_amelia_booking_event( $reservation = null, $bookings = null, $container = null ): void {
		$ids = array_merge( self::booking_ids_from_payload( $bookings, true ), self::booking_ids_from_payload( $reservation, false ) );
		foreach ( array_unique( array_filter( array_map( 'absint', $ids ) ) ) as $booking_id ) {
			self::send_booking_confirmation( $booking_id, false );
		}
	}

	public static function handle_amelia_appointment_status( $appointment, $requested_status ): void {
		if ( 'approved' !== sanitize_key( (string) $requested_status ) ) {
			return;
		}
		foreach ( array_unique( self::booking_ids_from_payload( $appointment, false ) ) as $booking_id ) {
			self::send_booking_confirmation( absint( $booking_id ), false );
		}
	}

	public static function send_booking_confirmation( int $booking_id, bool $manual = false ) {
		$settings = DLN_Attendance_Plugin::settings();
		if ( ! $manual && empty( $settings['confirmation_whatsapp_enabled'] ) ) {
			return array( 'skipped' => 'disabled' );
		}
		$row = self::booking_by_id( $booking_id );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		if ( ! $row ) {
			return new WP_Error( 'confirmation_booking_missing', 'The Amelia customer booking was not found.' );
		}
		if ( 'approved' !== (string) ( $row['booking_status'] ?? '' ) || 'approved' !== (string) ( $row['appointment_status'] ?? '' ) ) {
			return new WP_Error( 'confirmation_not_approved', 'The Amelia booking and appointment must both be approved.' );
		}
		if ( ! DLN_Attendance_Database::is_introductory_category( absint( $row['service_category_id'] ?? 0 ) ) ) {
			return new WP_Error( 'confirmation_not_introductory', 'The approved student confirmation is restricted to introductory-category bookings.' );
		}
		$record = DLN_Attendance_Database::upsert_context( self::context_from_booking( $row ) );
		if ( ! $record ) {
			return new WP_Error( 'confirmation_record_failed', 'The attendance record could not be prepared.' );
		}
		if ( ! empty( $record['confirmation_whatsapp_sent_at'] ) ) {
			return array( 'skipped' => 'already_sent', 'message_id' => (string) ( $record['confirmation_whatsapp_message_id'] ?? '' ) );
		}
		$phone_id = trim( (string) ( $settings['whatsapp_phone_number_id'] ?? '' ) );
		$token = trim( (string) ( $settings['whatsapp_access_token'] ?? '' ) );
		$to = self::normalize_phone( (string) ( $row['customer_phone'] ?? '' ), (string) ( $settings['whatsapp_default_country_code'] ?? '61' ) );
		$meet_url = esc_url_raw( (string) ( $row['meet_url'] ?? '' ) );
		$meet_parts = wp_parse_url( $meet_url );
		$error = '';
		if ( ! $phone_id || ! $token ) {
			$error = 'WhatsApp connection settings are incomplete.';
		} elseif ( ! $to ) {
			$error = 'The student has no valid WhatsApp phone number.';
		} elseif ( 'https' !== ( $meet_parts['scheme'] ?? '' ) || 'meet.google.com' !== strtolower( (string) ( $meet_parts['host'] ?? '' ) ) ) {
			$error = 'The approved booking does not contain a valid Google Meet URL.';
		}
		$timestamp = self::booking_timestamp( (string) ( $row['booking_start'] ?? '' ) );
		$timezone = self::resolve_booking_timezone( $row );
		$zone = $timezone['zone'];
		$date = $timestamp ? wp_date( (string) get_option( 'date_format', 'Y/m/d' ), $timestamp, new DateTimeZone( $zone ) ) : '';
		$time = $timestamp ? wp_date( (string) get_option( 'time_format', 'H:i' ), $timestamp, new DateTimeZone( $zone ) ) . ' (' . $zone . ')' : '';
		$values = array( (string) ( $row['customer_name'] ?? '' ), (string) ( $row['service_name'] ?? '' ), $date, $time, (string) ( $row['employee_name'] ?? '' ), $meet_url );
		if ( ! $error && in_array( '', array_map( 'trim', $values ), true ) ) {
			$error = 'One or more approved-booking confirmation values are empty.';
		}
		DLN_Attendance_Database::mark_confirmation_whatsapp_attempt( (int) $record['id'] );
		if ( $error ) {
			DLN_Attendance_Database::mark_confirmation_whatsapp_error( (int) $record['id'], $error );
			self::set_confirmation_diagnostic( 'error', $error, $booking_id );
			return new WP_Error( 'confirmation_configuration', $error );
		}
		$payload = array(
			'messaging_product' => 'whatsapp',
			'recipient_type' => 'individual',
			'to' => $to,
			'type' => 'template',
			'template' => array(
				'name' => sanitize_key( (string) ( $settings['confirmation_template_name'] ?? 'student_intro_class_confirmed' ) ),
				'language' => array( 'code' => sanitize_text_field( (string) ( $settings['confirmation_template_language'] ?? 'fa' ) ) ),
				'components' => array( array( 'type' => 'body', 'parameters' => array_map( static fn( string $value ): array => array( 'type' => 'text', 'text' => $value ), $values ) ) ),
			),
		);
		$response = self::post_template( $phone_id, $token, $settings, $payload );
		if ( is_wp_error( $response ) ) {
			DLN_Attendance_Database::mark_confirmation_whatsapp_error( (int) $record['id'], $response->get_error_message() );
			self::set_confirmation_diagnostic( 'error', $response->get_error_message(), $booking_id );
			return $response;
		}
		DLN_Attendance_Database::mark_confirmation_whatsapp_sent( (int) $record['id'], $response['message_id'] );
		self::set_confirmation_diagnostic( 'ready', 'Meta accepted the approved-booking confirmation.', $booking_id, $response['message_id'] );
		return array( 'message_id' => $response['message_id'], 'recipient' => $to );
	}

	public static function webhook_verify_token(): string {
		$settings = DLN_Attendance_Plugin::settings();
		$token = trim( (string) ( $settings['whatsapp_webhook_verify_token'] ?? '' ) );
		return $token ?: hash_hmac( 'sha256', 'dln-whatsapp-status', wp_salt( 'auth' ) );
	}

	public static function webhook_url(): string {
		return add_query_arg( array( 'action' => 'dln_attendance_whatsapp_webhook', 'key' => self::webhook_verify_token() ), admin_url( 'admin-post.php' ) );
	}

	public static function handle_webhook(): void {
		$key = sanitize_text_field( wp_unslash( (string) ( $_GET['key'] ?? '' ) ) );
		if ( ! hash_equals( self::webhook_verify_token(), $key ) ) {
			status_header( 403 ); echo 'Forbidden'; exit;
		}
		if ( 'GET' === strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
			$mode = sanitize_text_field( wp_unslash( (string) ( $_GET['hub_mode'] ?? '' ) ) );
			$verify = sanitize_text_field( wp_unslash( (string) ( $_GET['hub_verify_token'] ?? '' ) ) );
			$challenge = sanitize_text_field( wp_unslash( (string) ( $_GET['hub_challenge'] ?? '' ) ) );
			if ( 'subscribe' === $mode && hash_equals( self::webhook_verify_token(), $verify ) && '' !== $challenge ) {
				header( 'Content-Type: text/plain; charset=utf-8' ); echo $challenge; exit;
			}
			status_header( 403 ); echo 'Verification failed'; exit;
		}
		$raw = file_get_contents( 'php://input' );
		$data = json_decode( is_string( $raw ) ? $raw : '', true );
		if ( ! is_array( $data ) ) {
			status_header( 400 ); echo 'Invalid payload'; exit;
		}
		self::process_webhook_payload( $data );
		status_header( 200 ); echo 'EVENT_RECEIVED'; exit;
	}


	public static function observe_amelia_webhook(): void {
		$call = sanitize_text_field( wp_unslash( (string) ( $_REQUEST['call'] ?? '' ) ) );
		if ( '__notifications__whatsapp__webhook' !== $call || 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
			return;
		}
		$raw = file_get_contents( 'php://input' );
		$data = json_decode( is_string( $raw ) ? $raw : '', true );
		if ( is_array( $data ) ) {
			self::process_webhook_payload( $data );
		}
	}

	private static function process_webhook_payload( array $data ): int {
		$processed = 0;
		foreach ( (array) ( $data['entry'] ?? array() ) as $entry ) {
			foreach ( (array) ( $entry['changes'] ?? array() ) as $change ) {
				foreach ( (array) ( $change['value']['statuses'] ?? array() ) as $event ) {
					$message_id = sanitize_text_field( (string) ( $event['id'] ?? '' ) );
					$status = sanitize_key( (string) ( $event['status'] ?? '' ) );
					$occurred_at = absint( $event['timestamp'] ?? time() );
					$error_data = is_array( $event['errors'][0] ?? null ) ? $event['errors'][0] : array();
					$error = trim( sanitize_text_field( (string) ( $error_data['title'] ?? $error_data['message'] ?? '' ) ) );
					$processed += DLN_Attendance_Database::update_whatsapp_delivery_status( $message_id, $status, $occurred_at, $error );
					$processed += self::update_term_history_delivery( $message_id, $status, $occurred_at, $error );
				}
			}
		}
		self::update_health( array( 'last_webhook_at' => time(), 'last_webhook_updates' => $processed ) );
		return $processed;
	}

	private static function update_term_history_delivery( string $message_id, string $status, int $occurred_at, string $error ): int {
		if ( '' === $message_id || ! in_array( $status, array( 'sent', 'delivered', 'read', 'failed' ), true ) ) {
			return 0;
		}
		$history = self::term_history();
		foreach ( $history as $marker => $entry ) {
			if ( ! is_array( $entry ) || ! hash_equals( (string) ( $entry['message_id'] ?? '' ), $message_id ) ) {
				continue;
			}
			$history[ $marker ]['delivery_status'] = $status;
			$history[ $marker ]['status_at'] = $occurred_at;
			if ( 'failed' === $status && $error ) { $history[ $marker ]['error'] = $error; }
			self::save_term_history( $history );
			return 1;
		}
		return 0;
	}

	private static function booking_ids_from_payload( $value, bool $inside_bookings ): array {
		if ( is_object( $value ) ) {
			$value = json_decode( wp_json_encode( $value ), true );
		}
		if ( ! is_array( $value ) ) {
			return $inside_bookings && is_numeric( $value ) ? array( absint( $value ) ) : array();
		}
		$ids = array();
		foreach ( array( 'bookingId', 'booking_id', 'customerBookingId', 'customer_booking_id' ) as $key ) {
			if ( isset( $value[ $key ] ) && is_numeric( $value[ $key ] ) ) { $ids[] = absint( $value[ $key ] ); }
		}
		if ( $inside_bookings && isset( $value['id'] ) && is_numeric( $value['id'] ) ) { $ids[] = absint( $value['id'] ); }
		foreach ( $value as $key => $child ) {
			$next_inside = $inside_bookings || in_array( strtolower( (string) $key ), array( 'booking', 'bookings', 'customerbookings', 'customer_bookings' ), true );
			if ( is_array( $child ) || is_object( $child ) ) {
				$ids = array_merge( $ids, self::booking_ids_from_payload( $child, $next_inside ) );
			} elseif ( $next_inside && is_int( $key ) && is_numeric( $child ) ) {
				$ids[] = absint( $child );
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Sends only after the signed absence page was confirmed. It deliberately does
	 * not write to Amelia: staff keep control of any reschedule or cancellation.
	 */
	public static function send_teacher_absence( array $record ) {
		$settings = DLN_Attendance_Plugin::settings();
		if ( empty( $settings['teacher_absence_whatsapp_enabled'] ) ) {
			return array( 'skipped' => 'disabled' );
		}
		if ( ! empty( $record['teacher_whatsapp_sent_at'] ) ) {
			return array( 'skipped' => 'already_sent' );
		}
		if ( empty( $record['employee_phone'] ) ) {
			$row = self::booking_by_id( absint( $record['booking_id'] ?? 0 ) );
			if ( is_array( $row ) ) {
				$record = DLN_Attendance_Database::upsert_context( self::context_from_booking( $row ) ) ?: $record;
			}
		}
		$phone_id = trim( (string) ( $settings['whatsapp_phone_number_id'] ?? '' ) );
		$token    = trim( (string) ( $settings['whatsapp_access_token'] ?? '' ) );
		$to       = self::normalize_phone( (string) ( $record['employee_phone'] ?? '' ), (string) ( $settings['whatsapp_default_country_code'] ?? '61' ) );
		if ( ! $phone_id || ! $token || ! $to ) {
			$error = new WP_Error( 'teacher_whatsapp_configuration', ! $to ? 'The assigned teacher has no valid WhatsApp phone number.' : 'WhatsApp connection settings are incomplete.' );
			DLN_Attendance_Database::mark_teacher_whatsapp_error( (int) $record['id'], $error->get_error_message() );
			self::set_teacher_diagnostic( 'error', $error->get_error_message(), (int) $record['booking_id'] );
			return $error;
		}
		$timestamp = self::booking_timestamp( (string) ( $record['booking_start'] ?? '' ) );
		$teacher_timezone = self::resolve_teacher_timezone( $record );
		$zone      = $teacher_timezone['zone'];
		$date      = $timestamp ? wp_date( (string) get_option( 'date_format', 'Y/m/d' ), $timestamp, new DateTimeZone( $zone ) ) : '';
		$time      = $timestamp ? wp_date( (string) get_option( 'time_format', 'H:i' ), $timestamp, new DateTimeZone( $zone ) ) . ' (' . $zone . ')' : '';
		$values    = array( (string) $record['employee_name'], (string) $record['customer_name'], (string) $record['service_name'], $date, $time, (string) $record['booking_id'] );
		if ( in_array( '', array_map( 'trim', $values ), true ) ) {
			$error = new WP_Error( 'teacher_whatsapp_values', 'One or more teacher absence template values are empty.' );
			DLN_Attendance_Database::mark_teacher_whatsapp_error( (int) $record['id'], $error->get_error_message() );
			self::set_teacher_diagnostic( 'error', $error->get_error_message(), (int) $record['booking_id'] );
			return $error;
		}
		$payload = array( 'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $to, 'type' => 'template', 'template' => array(
			'name' => sanitize_key( (string) ( $settings['teacher_absence_template_name'] ?? 'teacher_student_absence_alert' ) ),
			'language' => array( 'code' => sanitize_text_field( (string) ( $settings['teacher_absence_template_language'] ?? 'fa' ) ) ),
			'components' => array( array( 'type' => 'body', 'parameters' => array_map( static fn( string $value ): array => array( 'type' => 'text', 'text' => $value ), $values ) ) ),
		) );
		DLN_Attendance_Database::mark_teacher_whatsapp_attempt( (int) $record['id'] );
		$response = self::post_template( $phone_id, $token, $settings, $payload );
		if ( is_wp_error( $response ) ) {
			DLN_Attendance_Database::mark_teacher_whatsapp_error( (int) $record['id'], $response->get_error_message() );
			self::set_teacher_diagnostic( 'error', $response->get_error_message(), (int) $record['booking_id'] );
			return $response;
		}
		DLN_Attendance_Database::mark_teacher_whatsapp_sent( (int) $record['id'], $response['message_id'] );
		self::set_teacher_diagnostic( 'ready', 'Meta accepted the teacher absence alert.', (int) $record['booking_id'], $response['message_id'] );
		return $response;
	}

	public static function health(): array {
		$value = get_option( self::HEALTH_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function run_scheduled(): void {
		$settings = DLN_Attendance_Plugin::settings();
		self::update_health( array( 'last_trigger_at' => time() ) );
		if ( empty( $settings['whatsapp_sender_enabled'] ) && empty( $settings['term_renewal_whatsapp_enabled'] ) ) {
			return;
		}

		$now       = current_datetime();
		$send_time = self::valid_time( (string) ( $settings['whatsapp_send_time'] ?? '07:00' ) );
		$scheduled = $now->setTime( (int) substr( $send_time, 0, 2 ), (int) substr( $send_time, 3, 2 ) );
		if ( $now < $scheduled ) {
			return;
		}
		if ( ! self::acquire_run_lock() ) {
			return;
		}
		try {
			if ( ! empty( $settings['whatsapp_sender_enabled'] ) ) {
				self::send_due_reminders( $now );
			}
			if ( ! empty( $settings['term_renewal_whatsapp_enabled'] ) ) {
				self::send_due_term_renewals( $now );
			}
		} finally {
			delete_option( self::RUN_LOCK_OPTION );
		}
	}

	public static function send_due_reminders( ?DateTimeImmutable $now = null ): array {
		$now      = $now ?: current_datetime();
		$tomorrow = $now->modify( 'tomorrow' )->setTime( 0, 0, 0 );
		$after    = $tomorrow->modify( '+1 day' );
		$utc      = new DateTimeZone( 'UTC' );
		$rows     = self::booking_rows(
			$tomorrow->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
			$after->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
			500
		);
		self::update_health( array( 'last_scan_at' => time() ) );

		if ( is_wp_error( $rows ) ) {
			self::set_diagnostic( 'error', $rows->get_error_message() );
			return array( 'found' => 0, 'sent' => 0, 'skipped' => 0, 'failed' => 1 );
		}
		$result = array( 'found' => count( $rows ), 'sent' => 0, 'skipped' => 0, 'failed' => 0 );
		$errors = array();

		foreach ( $rows as $row ) {
			$record = DLN_Attendance_Database::upsert_context( self::context_from_booking( $row ) );
			if ( ! $record ) {
				++$result['failed'];
				continue;
			}
			if ( ! empty( $record['whatsapp_sent_at'] ) ) {
				++$result['skipped'];
				continue;
			}
			$last_attempt = strtotime( (string) ( $record['whatsapp_attempted_at'] ?? '' ) . ' UTC' ) ?: 0;
			if ( $last_attempt > time() - 10 * MINUTE_IN_SECONDS ) {
				++$result['skipped'];
				continue;
			}

			$timezone = self::resolve_booking_timezone( $row );
			$record['customer_timezone'] = $timezone['zone'];
			$record['customer_timezone_source'] = $timezone['source'];
			$sent = self::send_record( $record, (string) ( $row['customer_phone'] ?? '' ), true );
			if ( is_wp_error( $sent ) ) {
				++$result['failed'];
				$errors[] = $sent->get_error_message();
			} else {
				++$result['sent'];
			}
		}

		self::set_diagnostic(
			$result['failed'] ? 'warning' : 'ready',
			trim( sprintf(
				'Scheduled scan found %d eligible bookings: %d sent, %d skipped, %d failed.',
				$result['found'],
				$result['sent'],
				$result['skipped'],
				$result['failed']
			) . ( $errors ? ' Last error: ' . end( $errors ) : '' ) )
		);
		return $result;
	}

	/**
	 * Sends one renewal reminder when exactly the configured number of payable
	 * Amelia appointments remain. Introductory-category appointments never count.
	 * A term marker derived from the completed 12-session blocks prevents repeats.
	 */
	public static function send_due_term_renewals( ?DateTimeImmutable $now = null ): array {
		$settings  = DLN_Attendance_Plugin::settings();
		$remaining = max( 1, min( 6, absint( $settings['term_renewal_sessions_remaining'] ?? 2 ) ) );
		$now       = $now ?: current_datetime();
		$candidates = self::term_renewal_candidates( $now, $remaining );
		self::update_health( array( 'last_term_renewal_scan_at' => time() ) );

		if ( is_wp_error( $candidates ) ) {
			self::set_term_diagnostic( 'error', $candidates->get_error_message() );
			return array( 'found' => 0, 'sent' => 0, 'skipped' => 0, 'failed' => 1 );
		}

		$result  = array( 'found' => count( $candidates ), 'sent' => 0, 'skipped' => 0, 'failed' => 0 );
		$history = self::term_history();
		$errors  = array();
		$phone_id = trim( (string) ( $settings['whatsapp_phone_number_id'] ?? '' ) );
		$token    = trim( (string) ( $settings['whatsapp_access_token'] ?? '' ) );

		foreach ( $candidates as $candidate ) {
			$marker = self::term_marker( $candidate );
			$entry  = is_array( $history[ $marker ] ?? null ) ? $history[ $marker ] : array();
			if ( 'sent' === (string) ( $entry['status'] ?? '' ) ) {
				++$result['skipped'];
				continue;
			}
			if ( absint( $entry['attempted_at'] ?? 0 ) > time() - HOUR_IN_SECONDS ) {
				++$result['skipped'];
				continue;
			}

			$to     = self::normalize_phone( (string) ( $candidate['customer_phone'] ?? '' ), (string) ( $settings['whatsapp_default_country_code'] ?? '61' ) );
			$values = array(
				sanitize_text_field( (string) ( $candidate['customer_name'] ?? '' ) ),
				sanitize_text_field( (string) ( $candidate['service_name'] ?? '' ) ),
			);
			$error = '';
			if ( ! $phone_id || ! $token ) {
				$error = 'WhatsApp connection settings are incomplete.';
			} elseif ( ! $to ) {
				$error = 'The student has no valid WhatsApp phone number.';
			} elseif ( in_array( '', array_map( 'trim', $values ), true ) ) {
				$error = 'One or more term-renewal template values are empty.';
			}

			$history[ $marker ] = array(
				'status'       => $error ? 'error' : 'attempting',
				'attempted_at' => time(),
				'customer_id'  => absint( $candidate['customer_id'] ?? 0 ),
				'service_id'   => absint( $candidate['service_id'] ?? 0 ),
				'provider_id'  => absint( $candidate['provider_id'] ?? 0 ),
				'term_index'   => absint( $candidate['term_index'] ?? 0 ),
				'error'        => sanitize_text_field( $error ),
			);
			self::save_term_history( $history );

			if ( $error ) {
				++$result['failed'];
				$errors[] = $error;
				continue;
			}

			$payload = array(
				'messaging_product' => 'whatsapp',
				'recipient_type'    => 'individual',
				'to'                => $to,
				'type'              => 'template',
				'template'          => array(
					'name'       => sanitize_key( (string) ( $settings['term_renewal_template_name'] ?? 'student_term_renewal_reminder' ) ),
					'language'   => array( 'code' => sanitize_text_field( (string) ( $settings['term_renewal_template_language'] ?? 'fa' ) ) ),
					'components' => array(
						array(
							'type'       => 'body',
							'parameters' => array_map( static fn( string $value ): array => array( 'type' => 'text', 'text' => $value ), $values ),
						),
					),
				),
			);
			$response = self::post_template( $phone_id, $token, $settings, $payload );
			if ( is_wp_error( $response ) ) {
				$history[ $marker ]['status'] = 'error';
				$history[ $marker ]['error']  = sanitize_text_field( $response->get_error_message() );
				++$result['failed'];
				$errors[] = $response->get_error_message();
				self::save_term_history( $history );
				continue;
			}

			$history[ $marker ]['status']     = 'sent';
			$history[ $marker ]['sent_at']    = time();
			$history[ $marker ]['message_id'] = sanitize_text_field( (string) $response['message_id'] );
			$history[ $marker ]['error']      = '';
			++$result['sent'];
			self::save_term_history( $history );
		}

		self::set_term_diagnostic(
			$result['failed'] ? 'warning' : 'ready',
			trim( sprintf(
				'Term-renewal scan found %d eligible students: %d sent, %d skipped, %d failed.',
				$result['found'],
				$result['sent'],
				$result['skipped'],
				$result['failed']
			) . ( $errors ? ' Last error: ' . end( $errors ) : '' ) )
		);
		return $result;
	}

	public static function booking_preview( int $booking_id ): array {
		static $cache = array();
		if ( isset( $cache[ $booking_id ] ) ) { return $cache[ $booking_id ]; }
		$row = self::booking_by_id( $booking_id );
		if ( ! is_array( $row ) ) { return array(); }

		$timestamp = self::booking_timestamp( (string) ( $row['booking_start'] ?? '' ) );
		$date_format = (string) get_option( 'date_format', 'Y/m/d' );
		$time_format = (string) get_option( 'time_format', 'H:i' );

		$student_timezone = self::resolve_booking_timezone( $row );
		$row['message_timezone'] = $student_timezone['zone'];
		$row['message_timezone_source'] = $student_timezone['source'];
		$row['message_date'] = $timestamp ? wp_date( $date_format, $timestamp, new DateTimeZone( $student_timezone['zone'] ) ) : '';
		$row['message_time'] = $timestamp ? wp_date( $time_format, $timestamp, new DateTimeZone( $student_timezone['zone'] ) ) : '';

		$teacher_timezone = self::resolve_teacher_timezone( $row );
		$row['teacher_timezone'] = $teacher_timezone['zone'];
		$row['teacher_timezone_source'] = $teacher_timezone['source'];
		$row['teacher_date'] = $timestamp ? wp_date( $date_format, $timestamp, new DateTimeZone( $teacher_timezone['zone'] ) ) : '';
		$row['teacher_time'] = $timestamp ? wp_date( $time_format, $timestamp, new DateTimeZone( $teacher_timezone['zone'] ) ) : '';

		$site_zone = self::valid_timezone( wp_timezone_string() ) ? wp_timezone_string() : 'Australia/Brisbane';
		$row['site_timezone'] = $site_zone;
		$row['site_date'] = $timestamp ? wp_date( $date_format, $timestamp, new DateTimeZone( $site_zone ) ) : '';
		$row['site_time'] = $timestamp ? wp_date( $time_format, $timestamp, new DateTimeZone( $site_zone ) ) : '';

		$record = DLN_Attendance_Database::get_by_booking_id( $booking_id );
		if ( $record ) {
			$join_token = DLN_Attendance_Plugin::token_for( $record, 'join' );
			$validated = DLN_Attendance_Plugin::record_from_token( $join_token, 'join' );
			$row['class_button_status'] = $validated && absint( $validated['id'] ?? 0 ) === absint( $record['id'] ?? 0 ) ? 'Validated' : 'Invalid or expired';
		} else {
			$row['class_button_status'] = 'Will be created and validated before sending';
		}
		$cache[ $booking_id ] = $row;
		return $row;
	}

	public static function send_booking_reminder_now( int $booking_id ) {
		$row = self::booking_by_id( $booking_id );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		if ( ! $row ) {
			return new WP_Error( 'reminder_booking_missing', 'The Amelia customer booking was not found.' );
		}
		if ( 'approved' !== (string) ( $row['booking_status'] ?? '' ) || 'approved' !== (string) ( $row['appointment_status'] ?? '' ) ) {
			return new WP_Error( 'reminder_not_approved', 'The Amelia booking and appointment must both be approved.' );
		}
		$timestamp = self::booking_timestamp( (string) ( $row['booking_start'] ?? '' ) );
		$booking_day = $timestamp ? wp_date( 'Y-m-d', $timestamp, wp_timezone() ) : '';
		$tomorrow = current_datetime()->modify( 'tomorrow' )->format( 'Y-m-d' );
		if ( '' === $booking_day || $booking_day !== $tomorrow ) {
			return new WP_Error( 'reminder_not_next_day', 'Production next-day reminders can only be sent on the day before the approved class.' );
		}
		$record = DLN_Attendance_Database::upsert_context( self::context_from_booking( $row ) );
		if ( ! $record ) {
			return new WP_Error( 'reminder_record_failed', 'The attendance record could not be prepared.' );
		}
		if ( ! empty( $record['whatsapp_sent_at'] ) ) {
			return array( 'skipped' => 'already_sent', 'message_id' => (string) ( $record['whatsapp_message_id'] ?? '' ) );
		}
		$lock_key = 'dln_attendance_manual_reminder_lock_' . $booking_id;
		$lock_time = absint( get_option( $lock_key, 0 ) );
		if ( $lock_time > time() - MINUTE_IN_SECONDS ) {
			return new WP_Error( 'reminder_in_flight', 'This reminder is currently being submitted to Meta. Reload the log to see its result.' );
		}
		if ( $lock_time ) {
			delete_option( $lock_key );
		}
		if ( ! add_option( $lock_key, time(), '', false ) ) {
			return new WP_Error( 'reminder_in_flight', 'This reminder is currently being submitted to Meta. Reload the log to see its result.' );
		}
		$timezone = self::resolve_booking_timezone( $row );
		$record['customer_timezone'] = $timezone['zone'];
		$record['customer_timezone_source'] = $timezone['source'];
		$result = self::send_record( $record, (string) ( $row['customer_phone'] ?? '' ), true );
		delete_option( $lock_key );
		return $result;
	}

	public static function send_booking_test( int $booking_id, string $recipient_override = '' ) {
		if ( '' === trim( $recipient_override ) ) {
			return new WP_Error( 'booking_test_recipient_required', 'Enter an administrator-controlled test recipient. The booking test never defaults to the student number.' );
		}
		$row = self::booking_by_id( $booking_id );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		if ( ! $row ) {
			return new WP_Error( 'booking_not_found', 'The Amelia customer booking was not found.' );
		}
		$record = DLN_Attendance_Database::upsert_context( self::context_from_booking( $row ) );
		if ( ! $record ) {
			return new WP_Error( 'record_failed', 'The attendance record could not be prepared.' );
		}
		$timezone = self::resolve_booking_timezone( $row );
		$record['customer_timezone'] = $timezone['zone'];
		$record['customer_timezone_source'] = $timezone['source'];
		return self::send_record( $record, $recipient_override, false );
	}

	/** Sends the renewal template to an administrator-controlled number only. */
	public static function send_term_renewal_test( int $booking_id, string $recipient_override ) {
		if ( '' === trim( $recipient_override ) ) {
			return new WP_Error( 'term_test_recipient_required', 'Enter an administrator-controlled test recipient. The renewal test never defaults to the student number.' );
		}
		$row = self::booking_by_id( $booking_id );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		if ( ! $row ) {
			return new WP_Error( 'booking_not_found', 'The Amelia customer booking was not found.' );
		}
		if ( DLN_Attendance_Database::is_introductory_category( absint( $row['service_category_id'] ?? 0 ) ) ) {
			return new WP_Error( 'intro_term_test_blocked', 'Choose a non-introductory class for the term-renewal test.' );
		}
		$settings = DLN_Attendance_Plugin::settings();
		$phone_id = trim( (string) ( $settings['whatsapp_phone_number_id'] ?? '' ) );
		$token    = trim( (string) ( $settings['whatsapp_access_token'] ?? '' ) );
		$to       = self::normalize_phone( $recipient_override, (string) ( $settings['whatsapp_default_country_code'] ?? '61' ) );
		$values   = array(
			sanitize_text_field( (string) ( $row['customer_name'] ?? '' ) ),
			sanitize_text_field( (string) ( $row['service_name'] ?? '' ) ),
		);
		if ( ! $phone_id || ! $token ) {
			return new WP_Error( 'term_test_configuration', 'WhatsApp connection settings are incomplete.' );
		}
		if ( ! $to ) {
			return new WP_Error( 'term_test_phone', 'The controlled test recipient is not a valid international number.' );
		}
		if ( in_array( '', array_map( 'trim', $values ), true ) ) {
			return new WP_Error( 'term_test_values', 'The selected booking is missing the student or class name.' );
		}
		$payload = array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $to,
			'type'              => 'template',
			'template'          => array(
				'name'       => sanitize_key( (string) ( $settings['term_renewal_template_name'] ?? 'student_term_renewal_reminder' ) ),
				'language'   => array( 'code' => sanitize_text_field( (string) ( $settings['term_renewal_template_language'] ?? 'fa' ) ) ),
				'components' => array(
					array(
						'type'       => 'body',
						'parameters' => array_map( static fn( string $value ): array => array( 'type' => 'text', 'text' => $value ), $values ),
					),
				),
			),
		);
		$response = self::post_template( $phone_id, $token, $settings, $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return array( 'message_id' => $response['message_id'], 'recipient' => $to );
	}

	public static function upcoming_bookings( int $limit = 20 ): array {
		$now  = current_datetime()->modify( '-1 day' )->setTime( 0, 0 );
		$end  = $now->modify( '+32 days' );
		$utc  = new DateTimeZone( 'UTC' );
		$rows = self::booking_rows( $now->setTimezone( $utc )->format( 'Y-m-d H:i:s' ), $end->setTimezone( $utc )->format( 'Y-m-d H:i:s' ), $limit );
		return is_wp_error( $rows ) ? array() : $rows;
	}

	/** Returns the highest-ID approved booking in the controlled-test window. */
	public static function most_recent_test_booking( bool $exclude_introductory = false ): array {
		if ( ! self::amelia_tables_exist() ) {
			return array();
		}
		global $wpdb;
		$now = current_datetime()->modify( '-1 day' )->setTime( 0, 0 );
		$end = $now->modify( '+32 days' );
		$utc = new DateTimeZone( 'UTC' );
		$from_utc = $now->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
		$to_utc   = $end->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
		$sql = self::base_booking_query()
			. " WHERE a.bookingStart >= %s AND a.bookingStart < %s
				AND a.status = 'approved' AND cb.status = 'approved'";
		if ( $exclude_introductory ) {
			$intro_id = absint( DLN_Attendance_Plugin::settings()['intro_service_category_id'] ?? 3 );
			$sql .= ' AND COALESCE(s.categoryId, 0) <> %d ORDER BY cb.id DESC LIMIT 1';
			$row = $wpdb->get_row( $wpdb->prepare( $sql, $from_utc, $to_utc, $intro_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$sql .= ' ORDER BY cb.id DESC LIMIT 1';
			$row = $wpdb->get_row( $wpdb->prepare( $sql, $from_utc, $to_utc ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return is_array( $row ) ? $row : array();
	}

	public static function sync_attendance_records( int $past_days = 31, int $future_days = 7 ): array {
		$timezone = wp_timezone();
		$utc      = new DateTimeZone( 'UTC' );
		$from     = current_datetime()->setTimezone( $timezone )->modify( '-' . max( 1, $past_days ) . ' days' )->setTime( 0, 0 );
		$to       = current_datetime()->setTimezone( $timezone )->modify( '+' . max( 1, $future_days ) . ' days' )->setTime( 23, 59, 59 );
		return self::sync_attendance_records_between( $from->setTimezone( $utc )->format( 'Y-m-d H:i:s' ), $to->setTimezone( $utc )->format( 'Y-m-d H:i:s' ) );
	}

	/** Syncs the exact local date range selected on a financial statement. */
	public static function sync_attendance_records_range( string $from_date, string $to_date ): array {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to_date ) ) {
			return array( 'found' => 0, 'synced' => 0, 'cancelled' => 0, 'error' => 'Choose a valid payment-report date range.' );
		}
		try {
			$timezone = wp_timezone();
			$utc = new DateTimeZone( 'UTC' );
			$from = new DateTimeImmutable( $from_date . ' 00:00:00', $timezone );
			$to = new DateTimeImmutable( $to_date . ' 23:59:59', $timezone );
		} catch ( Exception $exception ) {
			return array( 'found' => 0, 'synced' => 0, 'cancelled' => 0, 'error' => 'Choose a valid payment-report date range.' );
		}
		if ( $from > $to ) {
			return array( 'found' => 0, 'synced' => 0, 'cancelled' => 0, 'error' => 'The report start date must not be after its end date.' );
		}
		return self::sync_attendance_records_between( $from->setTimezone( $utc )->format( 'Y-m-d H:i:s' ), $to->setTimezone( $utc )->format( 'Y-m-d H:i:s' ) );
	}

	private static function sync_attendance_records_between( string $from_utc, string $to_utc ): array {
		// Fetch one sentinel row beyond the safe reconciliation batch so a
		// truncated Amelia result can never be mistaken for the full interval.
		$rows = self::booking_rows_all( $from_utc, $to_utc, 2001 );
		if ( is_wp_error( $rows ) ) {
			return array( 'found' => 0, 'synced' => 0, 'error' => $rows->get_error_message() );
		}
		$truncated = count( $rows ) > 2000;
		if ( $truncated ) {
			$rows = array_slice( $rows, 0, 2000 );
		}
		$synced = 0;
		$booking_ids = array();
		foreach ( $rows as $row ) {
			$booking_ids[] = absint( $row['booking_id'] ?? 0 );
			if ( DLN_Attendance_Database::upsert_context( self::context_from_booking( $row ) ) ) {
				++$synced;
			}
		}
		$cancelled = $truncated ? 0 : DLN_Attendance_Database::reconcile_missing_source_bookings( $booking_ids, $from_utc, $to_utc );
		$error = $truncated ? 'The selected Amelia range exceeds 2,000 bookings. Records were updated, but missing-booking cancellation was safely skipped; sync a shorter date range.' : '';
		return array( 'found' => count( $rows ), 'synced' => $synced, 'cancelled' => $cancelled, 'error' => $error );
	}

	private static function send_record( array $record, string $recipient, bool $mark_sent ) {
		$settings = DLN_Attendance_Plugin::settings();
		$required = array(
			'phone number ID' => trim( (string) ( $settings['whatsapp_phone_number_id'] ?? '' ) ),
			'access token'    => trim( (string) ( $settings['whatsapp_access_token'] ?? '' ) ),
		);
		foreach ( $required as $label => $value ) {
			if ( '' === $value ) {
				return new WP_Error( 'whatsapp_settings_missing', 'WhatsApp ' . $label . ' is missing in Attendance settings.' );
			}
		}

		$to = self::normalize_phone( $recipient, (string) ( $settings['whatsapp_default_country_code'] ?? '61' ) );
		if ( ! $to ) {
			return new WP_Error( 'invalid_phone', 'The recipient phone number is not a valid international number.' );
		}

		$meet_url = esc_url_raw( (string) ( $record['meet_url'] ?? '' ) );
		$parts    = wp_parse_url( $meet_url );
		if ( 'https' !== ( $parts['scheme'] ?? '' ) || 'meet.google.com' !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) {
			$error = new WP_Error( 'missing_meet_url', 'This booking does not contain a valid Google Meet URL.' );
			DLN_Attendance_Database::mark_whatsapp_error( (int) $record['id'], $error->get_error_message() );
			return $error;
		}

		$join_token    = DLN_Attendance_Plugin::token_for( $record, 'join' );
		$absence_token = DLN_Attendance_Plugin::token_for( $record, 'absence' );
		$join_record   = DLN_Attendance_Plugin::record_from_token( $join_token, 'join' );
		$absence_record = DLN_Attendance_Plugin::record_from_token( $absence_token, 'absence' );
		if ( ! $join_record || ! $absence_record || absint( $join_record['id'] ?? 0 ) !== absint( $record['id'] ?? 0 ) || absint( $absence_record['id'] ?? 0 ) !== absint( $record['id'] ?? 0 ) ) {
			$error = new WP_Error( 'invalid_class_action_link', 'The class action buttons failed validation. No WhatsApp message was sent.' );
			DLN_Attendance_Database::mark_whatsapp_error( (int) $record['id'], $error->get_error_message() );
			return $error;
		}
		$join_url      = DLN_Attendance_Plugin::public_url( 'join', $join_token );
		$timestamp     = self::booking_timestamp( (string) ( $record['booking_start'] ?? '' ) );
		$timezone      = self::record_timezone( $record, wp_timezone_string() );
		$date          = $timestamp ? wp_date( (string) get_option( 'date_format', 'Y/m/d' ), $timestamp, new DateTimeZone( $timezone ) ) : '';
		$time          = $timestamp ? wp_date( (string) get_option( 'time_format', 'H:i' ), $timestamp, new DateTimeZone( $timezone ) ) . ' (' . $timezone . ')' : '';
		$body_values   = array(
			(string) ( $record['customer_name'] ?? '' ),
			(string) ( $record['service_name'] ?? '' ),
			(string) ( $record['employee_name'] ?? '' ),
			$date,
			$time,
			$join_url,
		);
		foreach ( $body_values as $value ) {
			if ( '' === trim( $value ) ) {
				$error = new WP_Error( 'missing_template_value', 'One or more required WhatsApp template values are empty.' );
				DLN_Attendance_Database::mark_whatsapp_error( (int) $record['id'], $error->get_error_message() );
				return $error;
			}
		}

		$payload = array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $to,
			'type'              => 'template',
			'template'          => array(
				'name'       => sanitize_key( (string) ( $settings['whatsapp_template_name'] ?? self::TEMPLATE_DEFAULT ) ),
				'language'   => array( 'code' => sanitize_text_field( (string) ( $settings['whatsapp_template_language'] ?? 'fa' ) ) ),
				'components' => array(
					array(
						'type'       => 'body',
						'parameters' => array_map(
							static fn( string $value ): array => array( 'type' => 'text', 'text' => $value ),
							$body_values
						),
					),
					array(
						'type'       => 'button',
						'sub_type'   => 'url',
						'index'      => '0',
						'parameters' => array( array( 'type' => 'text', 'text' => $join_token ) ),
					),
					array(
						'type'       => 'button',
						'sub_type'   => 'url',
						'index'      => '1',
						'parameters' => array( array( 'type' => 'text', 'text' => $absence_token ) ),
					),
				),
			),
		);

		$version = self::valid_graph_version( (string) ( $settings['whatsapp_graph_version'] ?? 'v25.0' ) );
		$url     = 'https://graph.facebook.com/' . rawurlencode( $version ) . '/' . rawurlencode( $required['phone number ID'] ) . '/messages';
		if ( $mark_sent ) {
			DLN_Attendance_Database::mark_whatsapp_attempt( (int) $record['id'] );
		}
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $required['access token'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			)
		);

		if ( is_wp_error( $response ) ) {
			DLN_Attendance_Database::mark_whatsapp_error( (int) $record['id'], $response->get_error_message() );
			self::set_diagnostic( 'error', $response->get_error_message(), (int) $record['booking_id'] );
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $data ) || empty( $data['messages'][0]['id'] ) ) {
			$message = self::response_error( $data, $status );
			DLN_Attendance_Database::mark_whatsapp_error( (int) $record['id'], $message );
			self::set_diagnostic( 'error', $message, (int) $record['booking_id'] );
			return new WP_Error( 'meta_send_failed', $message, array( 'http_status' => $status ) );
		}

		$message_id = sanitize_text_field( (string) $data['messages'][0]['id'] );
		if ( $mark_sent ) {
			DLN_Attendance_Database::mark_whatsapp_sent( (int) $record['id'], $message_id );
		} else {
			DLN_Attendance_Database::mark_whatsapp_tested( (int) $record['id'], $message_id );
		}
		self::set_diagnostic( 'ready', 'Meta accepted the WhatsApp template message.', (int) $record['booking_id'], $message_id );
		return array( 'message_id' => $message_id, 'recipient' => $to );
	}

	private static function booking_by_id( int $booking_id ) {
		if ( ! self::amelia_tables_exist() ) {
			return new WP_Error( 'amelia_tables_missing', 'Required Amelia booking tables were not found.' );
		}
		global $wpdb;
		$sql = self::base_booking_query() . ' WHERE cb.id = %d LIMIT 1';
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $booking_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $row ) ? $row : null;
	}

	private static function booking_rows( string $from, string $to, int $limit ) {
		return self::booking_rows_query( $from, $to, $limit, true );
	}

	private static function booking_rows_all( string $from, string $to, int $limit ) {
		return self::booking_rows_query( $from, $to, $limit, false );
	}

	private static function booking_rows_query( string $from, string $to, int $limit, bool $approved_only ) {
		if ( ! self::amelia_tables_exist() ) {
			return new WP_Error( 'amelia_tables_missing', 'Required Amelia booking tables were not found.' );
		}
		global $wpdb;
		$limit = max( 1, min( 2001, $limit ) );
		$status_sql = $approved_only ? " AND a.status = 'approved' AND cb.status = 'approved'" : '';
		$sql   = self::base_booking_query()
			. " WHERE a.bookingStart >= %s AND a.bookingStart < %s
				{$status_sql}
			 ORDER BY a.bookingStart ASC, cb.id ASC LIMIT %d";
		return $wpdb->get_results( $wpdb->prepare( $sql, $from, $to, $limit ), ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function base_booking_query(): string {
		global $wpdb;
		$appointments = $wpdb->prefix . 'amelia_appointments';
		$bookings     = $wpdb->prefix . 'amelia_customer_bookings';
		$users        = $wpdb->prefix . 'amelia_users';
		$services     = $wpdb->prefix . 'amelia_services';
		$timezone_col = self::amelia_user_timezone_column( $users );
		$customer_timezone = $timezone_col ? 'c.`' . $timezone_col . '`' : "''";
		return "SELECT
			cb.id AS booking_id,
			a.id AS appointment_id,
			cb.customerId AS customer_id,
			TRIM(CONCAT(COALESCE(c.firstName, ''), ' ', COALESCE(c.lastName, ''))) AS customer_name,
			c.email AS customer_email,
			c.phone AS customer_phone,
			{$customer_timezone} AS customer_timezone,
			TRIM(CONCAT(COALESCE(p.firstName, ''), ' ', COALESCE(p.lastName, ''))) AS employee_name,
			p.email AS employee_email,
			p.phone AS employee_phone,
			s.name AS service_name,
			s.categoryId AS service_category_id,
			a.status AS appointment_status,
			cb.status AS booking_status,
			a.bookingStart AS booking_start,
			a.bookingEnd AS booking_end,
			a.googleMeetUrl AS meet_url
		FROM {$bookings} cb
		INNER JOIN {$appointments} a ON a.id = cb.appointmentId
		INNER JOIN {$users} c ON c.id = cb.customerId
		INNER JOIN {$users} p ON p.id = a.providerId
		INNER JOIN {$services} s ON s.id = a.serviceId";
	}

	private static function amelia_tables_exist(): bool {
		global $wpdb;
		foreach ( array( 'amelia_appointments', 'amelia_customer_bookings', 'amelia_users', 'amelia_services' ) as $suffix ) {
			$table = $wpdb->prefix . $suffix;
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
			if ( $table !== $found ) {
				return false;
			}
		}
		return true;
	}

	/** Amelia schema names differ by release; only select the field if it exists. */
	private static function amelia_user_timezone_column( string $table ): string {
		global $wpdb;
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( array( 'timeZone', 'timezone', 'time_zone' ) as $candidate ) {
			if ( in_array( $candidate, $columns, true ) ) { return $candidate; }
		}
		return '';
	}

	private static function context_from_booking( array $row ): array {
		$meet_url = esc_url_raw( (string) ( $row['meet_url'] ?? '' ) );
		$timezone = self::resolve_booking_timezone( $row );
		return array(
			'booking_id' => absint( $row['booking_id'] ?? 0 ),
			'appointment_id' => absint( $row['appointment_id'] ?? 0 ),
			'customer_id' => absint( $row['customer_id'] ?? 0 ),
			'customer_name' => sanitize_text_field( (string) ( $row['customer_name'] ?? '' ) ),
			'customer_email' => sanitize_email( (string) ( $row['customer_email'] ?? '' ) ),
			'customer_timezone' => sanitize_text_field( (string) $timezone['zone'] ),
			'employee_name' => sanitize_text_field( (string) ( $row['employee_name'] ?? '' ) ),
			'employee_email' => sanitize_email( (string) ( $row['employee_email'] ?? '' ) ),
			'employee_phone' => sanitize_text_field( (string) ( $row['employee_phone'] ?? '' ) ),
			'service_name' => sanitize_text_field( (string) ( $row['service_name'] ?? '' ) ),
			'service_category_id' => absint( $row['service_category_id'] ?? 0 ),
			'source_status' => ( 'approved' === (string) ( $row['appointment_status'] ?? '' ) && 'approved' === (string) ( $row['booking_status'] ?? '' ) ) ? 'approved' : 'cancelled',
			'booking_start' => sanitize_text_field( (string) ( $row['booking_start'] ?? '' ) ),
			'booking_end' => sanitize_text_field( (string) ( $row['booking_end'] ?? '' ) ),
			'meet_url' => $meet_url,
			'meet_code' => DLN_Attendance_Context::meet_code( $meet_url ),
		);
	}

	/**
	 * Finds established recurring students who have exactly $remaining approved,
	 * non-introductory appointments in the future. At least ten earlier payable
	 * appointments are required so a new or ad-hoc two-class booking cannot qualify.
	 */
	private static function term_renewal_candidates( DateTimeImmutable $now, int $remaining ) {
		if ( ! self::amelia_tables_exist() ) {
			return new WP_Error( 'amelia_tables_missing', 'Required Amelia booking tables were not found.' );
		}
		global $wpdb;
		$appointments = $wpdb->prefix . 'amelia_appointments';
		$bookings     = $wpdb->prefix . 'amelia_customer_bookings';
		$users        = $wpdb->prefix . 'amelia_users';
		$services     = $wpdb->prefix . 'amelia_services';
		$intro_id     = absint( DLN_Attendance_Plugin::settings()['intro_service_category_id'] ?? 3 );
		$utc          = new DateTimeZone( 'UTC' );
		$now_utc      = $now->setTimezone( $utc );
		$now_mysql    = $now_utc->format( 'Y-m-d H:i:s' );
		$to           = $now_utc->modify( '+2 years' )->format( 'Y-m-d H:i:s' );
		$minimum_past = self::TERM_LENGTH - $remaining;
		$sql = "SELECT
			cb.customerId AS customer_id,
			a.serviceId AS service_id,
			a.providerId AS provider_id,
			MAX(TRIM(CONCAT(COALESCE(c.firstName, ''), ' ', COALESCE(c.lastName, '')))) AS customer_name,
			MAX(c.phone) AS customer_phone,
			MAX(s.name) AS service_name,
			SUM(CASE WHEN a.bookingStart < %s THEN 1 ELSE 0 END) AS past_count,
			SUM(CASE WHEN a.bookingStart >= %s THEN 1 ELSE 0 END) AS future_count,
			SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN a.bookingStart >= %s THEN cb.id END ORDER BY a.bookingStart ASC, cb.id ASC SEPARATOR ','), ',', 1) AS booking_id,
			MIN(CASE WHEN a.bookingStart >= %s THEN a.bookingStart END) AS booking_start
		FROM {$bookings} cb
		INNER JOIN {$appointments} a ON a.id = cb.appointmentId
		INNER JOIN {$users} c ON c.id = cb.customerId
		INNER JOIN {$services} s ON s.id = a.serviceId
		WHERE a.status = 'approved'
			AND cb.status = 'approved'
			AND COALESCE(s.categoryId, 0) <> %d
			AND a.bookingStart < %s
		GROUP BY cb.customerId, a.serviceId, a.providerId
		HAVING past_count >= %d AND future_count = %d
		ORDER BY booking_start ASC";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $now_mysql, $now_mysql, $now_mysql, $now_mysql, $intro_id, $to, $minimum_past, $remaining ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! is_array( $rows ) ) {
			return new WP_Error( 'term_renewal_query_failed', 'The term-renewal Amelia query failed.' );
		}

		$candidates = array();
		foreach ( $rows as $row ) {
			if ( ! absint( $row['customer_id'] ?? 0 ) || ! absint( $row['service_id'] ?? 0 ) || ! absint( $row['provider_id'] ?? 0 ) || ! absint( $row['booking_id'] ?? 0 ) ) {
				continue;
			}
			$row['past_count'] = absint( $row['past_count'] ?? 0 );
			$row['term_index'] = intdiv( $row['past_count'], self::TERM_LENGTH );
			$candidates[] = $row;
		}
		return $candidates;
	}

	private static function term_marker( array $candidate ): string {
		return hash(
			'sha256',
			implode(
				'|',
				array(
					absint( $candidate['customer_id'] ?? 0 ),
					absint( $candidate['service_id'] ?? 0 ),
					absint( $candidate['provider_id'] ?? 0 ),
					absint( $candidate['term_index'] ?? 0 ),
				)
			)
		);
	}

	private static function term_history(): array {
		$value = get_option( self::TERM_HISTORY_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	private static function save_term_history( array $history ): void {
		$cutoff = time() - 3 * YEAR_IN_SECONDS;
		foreach ( $history as $marker => $entry ) {
			if ( ! is_array( $entry ) || absint( $entry['attempted_at'] ?? 0 ) < $cutoff ) {
				unset( $history[ $marker ] );
			}
		}
		if ( count( $history ) > 2000 ) {
			uasort( $history, static fn( array $a, array $b ): int => absint( $a['attempted_at'] ?? 0 ) <=> absint( $b['attempted_at'] ?? 0 ) );
			$history = array_slice( $history, -2000, null, true );
		}
		update_option( self::TERM_HISTORY_OPTION, $history, false );
	}

	private static function normalize_phone( string $phone, string $default_country_code ): string {
		$digits = preg_replace( '/\D+/', '', $phone );
		$digits = is_string( $digits ) ? $digits : '';
		if ( str_starts_with( $digits, '00' ) ) {
			$digits = substr( $digits, 2 );
		}
		$country = preg_replace( '/\D+/', '', $default_country_code );
		$country = is_string( $country ) ? ltrim( $country, '0' ) : '';
		if ( str_starts_with( $digits, '0' ) && $country ) {
			$digits = $country . substr( $digits, 1 );
		} elseif ( 9 === strlen( $digits ) && str_starts_with( $digits, '4' ) && '61' === $country ) {
			$digits = '61' . $digits;
		}
		return preg_match( '/^[1-9]\d{7,14}$/', $digits ) ? $digits : '';
	}

	private static function booking_timestamp( string $mysql ): int {
		$date = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $mysql, new DateTimeZone( 'UTC' ) );
		return $date instanceof DateTimeImmutable ? $date->getTimestamp() : 0;
	}

	private static function resolve_booking_timezone( array $row ): array {
		$profile_zone = sanitize_text_field( (string) ( $row['customer_timezone'] ?? '' ) );
		if ( self::valid_timezone( $profile_zone ) ) {
			return array( 'zone' => $profile_zone, 'source' => 'Amelia customer timezone' );
		}
		$phone_zone = self::phone_timezone( (string) ( $row['customer_phone'] ?? '' ) );
		if ( '' !== $phone_zone ) {
			return array( 'zone' => $phone_zone, 'source' => 'phone-country fallback' );
		}
		$fallback = wp_timezone_string();
		return array( 'zone' => self::valid_timezone( $fallback ) ? $fallback : 'Australia/Brisbane', 'source' => 'site fallback' );
	}

	private static function resolve_teacher_timezone( array $row ): array {
		$profile_zone = sanitize_text_field( (string) ( $row['employee_timezone'] ?? '' ) );
		if ( self::valid_timezone( $profile_zone ) ) { return array( 'zone' => $profile_zone, 'source' => 'Amelia teacher timezone' ); }
		$phone_zone = self::phone_timezone( (string) ( $row['employee_phone'] ?? '' ) );
		if ( '' !== $phone_zone ) { return array( 'zone' => $phone_zone, 'source' => 'teacher phone-country fallback' ); }
		$fallback = wp_timezone_string();
		return array( 'zone' => self::valid_timezone( $fallback ) ? $fallback : 'Australia/Brisbane', 'source' => 'site fallback' );
	}

	private static function phone_timezone( string $phone ): string {
		$settings = DLN_Attendance_Plugin::settings();
		$digits = self::normalize_phone( $phone, (string) ( $settings['whatsapp_default_country_code'] ?? '61' ) );
		if ( '' === $digits ) { return ''; }
		$zones = array(
			'358' => 'Europe/Helsinki', '353' => 'Europe/Dublin',
			'971' => 'Asia/Dubai', '972' => 'Asia/Jerusalem', '973' => 'Asia/Bahrain', '974' => 'Asia/Qatar', '965' => 'Asia/Kuwait', '966' => 'Asia/Riyadh', '968' => 'Asia/Muscat',
			'880' => 'Asia/Dhaka',
			'31' => 'Europe/Amsterdam', '32' => 'Europe/Brussels', '33' => 'Europe/Paris', '36' => 'Europe/Budapest', '39' => 'Europe/Rome',
			'40' => 'Europe/Bucharest', '41' => 'Europe/Zurich', '43' => 'Europe/Vienna', '44' => 'Europe/London', '45' => 'Europe/Copenhagen', '46' => 'Europe/Stockholm', '47' => 'Europe/Oslo', '48' => 'Europe/Warsaw', '49' => 'Europe/Berlin',
			'27' => 'Africa/Johannesburg', '30' => 'Europe/Athens', '81' => 'Asia/Tokyo', '82' => 'Asia/Seoul', '86' => 'Asia/Shanghai', '90' => 'Europe/Istanbul', '91' => 'Asia/Kolkata', '92' => 'Asia/Karachi', '93' => 'Asia/Kabul', '98' => 'Asia/Tehran',
		);
		foreach ( $zones as $prefix => $zone ) { if ( str_starts_with( $digits, $prefix ) ) { return $zone; } }
		return '';
	}

	private static function valid_timezone( string $zone ): bool {
		if ( '' === $zone ) { return false; }
		try { new DateTimeZone( $zone ); return true; } catch ( Exception $exception ) { return false; }
	}

	private static function record_timezone( array $record, string $fallback ): string {
		$zone = sanitize_text_field( (string) ( $record['customer_timezone'] ?? '' ) );
		try { new DateTimeZone( $zone ); return $zone; } catch ( Exception $exception ) { return $fallback ?: 'Australia/Brisbane'; }
	}

	private static function post_template( string $phone_id, string $token, array $settings, array $payload ) {
		$version = self::valid_graph_version( (string) ( $settings['whatsapp_graph_version'] ?? 'v25.0' ) );
		$response = wp_remote_post( 'https://graph.facebook.com/' . rawurlencode( $version ) . '/' . rawurlencode( $phone_id ) . '/messages', array( 'timeout' => 20, 'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
		if ( is_wp_error( $response ) ) { return $response; }
		$status = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $data ) || empty( $data['messages'][0]['id'] ) ) { return new WP_Error( 'meta_send_failed', self::response_error( $data, $status ), array( 'http_status' => $status ) ); }
		return array( 'message_id' => sanitize_text_field( (string) $data['messages'][0]['id'] ) );
	}

	private static function valid_time( string $value ): string {
		return preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '07:00';
	}

	private static function valid_graph_version( string $value ): string {
		return preg_match( '/^v\d{1,2}\.\d$/', $value ) ? $value : 'v25.0';
	}

	private static function response_error( $data, int $status ): string {
		$message = is_array( $data ) ? sanitize_text_field( (string) ( $data['error']['message'] ?? '' ) ) : '';
		$code    = is_array( $data ) ? sanitize_text_field( (string) ( $data['error']['code'] ?? '' ) ) : '';
		return sprintf(
			'Meta rejected the WhatsApp message (HTTP %d%s)%s',
			$status,
			$code ? ', code ' . $code : '',
			$message ? ': ' . $message : '.'
		);
	}

	private static function set_diagnostic( string $state, string $message, int $booking_id = 0, string $message_id = '' ): void {
		update_option(
			self::DIAGNOSTIC_OPTION,
			array(
				'state'       => sanitize_key( $state ),
				'message'     => sanitize_text_field( $message ),
				'booking_id'  => $booking_id,
				'message_id'  => sanitize_text_field( $message_id ),
				'updated_at'  => time(),
			),
			false
		);
	}

	private static function set_confirmation_diagnostic( string $state, string $message, int $booking_id = 0, string $message_id = '' ): void {
		update_option( self::CONFIRMATION_DIAGNOSTIC_OPTION, array( 'state' => sanitize_key( $state ), 'message' => sanitize_text_field( $message ), 'booking_id' => $booking_id, 'message_id' => sanitize_text_field( $message_id ), 'updated_at' => time() ), false );
	}

	private static function set_teacher_diagnostic( string $state, string $message, int $booking_id = 0, string $message_id = '' ): void {
		update_option( self::TEACHER_DIAGNOSTIC_OPTION, array( 'state' => sanitize_key( $state ), 'message' => sanitize_text_field( $message ), 'booking_id' => $booking_id, 'message_id' => sanitize_text_field( $message_id ), 'updated_at' => time() ), false );
	}

	private static function set_term_diagnostic( string $state, string $message ): void {
		update_option( self::TERM_DIAGNOSTIC_OPTION, array( 'state' => sanitize_key( $state ), 'message' => sanitize_text_field( $message ), 'updated_at' => time() ), false );
	}

	private static function update_health( array $changes ): void {
		$health = self::health();
		update_option( self::HEALTH_OPTION, array_merge( $health, $changes ), false );
	}

	private static function acquire_run_lock(): bool {
		$now = time();
		$lock = absint( get_option( self::RUN_LOCK_OPTION, 0 ) );
		if ( $lock && $lock > $now - 10 * MINUTE_IN_SECONDS ) {
			return false;
		}
		if ( $lock ) {
			delete_option( self::RUN_LOCK_OPTION );
		}
		return add_option( self::RUN_LOCK_OPTION, $now, '', false );
	}
}

