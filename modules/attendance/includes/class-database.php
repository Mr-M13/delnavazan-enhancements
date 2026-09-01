<?php

defined( 'ABSPATH' ) || exit;

final class DLN_Attendance_Database {
	const DB_VERSION = '9';

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'dln_attendance';
	}

	public static function activate(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(32) NOT NULL,
			booking_id bigint(20) unsigned NOT NULL,
			appointment_id bigint(20) unsigned DEFAULT NULL,
			customer_id bigint(20) unsigned DEFAULT NULL,
			customer_name varchar(191) NOT NULL DEFAULT '',
			customer_email varchar(191) NOT NULL DEFAULT '',
			employee_name varchar(191) NOT NULL DEFAULT '',
			employee_email varchar(191) NOT NULL DEFAULT '',
			employee_phone varchar(64) NOT NULL DEFAULT '',
			service_name varchar(191) NOT NULL DEFAULT '',
			service_category_id bigint(20) unsigned DEFAULT NULL,
			source_status varchar(32) NOT NULL DEFAULT 'approved',
			booking_start datetime DEFAULT NULL,
			booking_end datetime DEFAULT NULL,
			meet_code varchar(32) NOT NULL DEFAULT '',
			meet_url text NULL,
			status varchar(32) NOT NULL DEFAULT 'pending',
			join_click_count int(10) unsigned NOT NULL DEFAULT 0,
			join_clicked_at datetime DEFAULT NULL,
			absence_reported_at datetime DEFAULT NULL,
			attended_at datetime DEFAULT NULL,
			no_show_at datetime DEFAULT NULL,
			meet_participant_name varchar(191) NOT NULL DEFAULT '',
			meet_duration_seconds int(10) unsigned DEFAULT NULL,
			student_participant_name varchar(191) NOT NULL DEFAULT '',
			student_duration_seconds int(10) unsigned DEFAULT NULL,
			teacher_participant_name varchar(191) NOT NULL DEFAULT '',
			teacher_duration_seconds int(10) unsigned DEFAULT NULL,
			overlap_seconds int(10) unsigned DEFAULT NULL,
			verification_note text NULL,
			whatsapp_attempted_at datetime DEFAULT NULL,
			whatsapp_sent_at datetime DEFAULT NULL,
			whatsapp_message_id varchar(191) NOT NULL DEFAULT '',
			whatsapp_delivery_status varchar(20) NOT NULL DEFAULT '',
			whatsapp_status_at datetime DEFAULT NULL,
			whatsapp_received_confirmed_at datetime DEFAULT NULL,
			whatsapp_received_confirmed_by bigint(20) unsigned DEFAULT NULL,
			whatsapp_tested_at datetime DEFAULT NULL,
			whatsapp_test_message_id varchar(191) NOT NULL DEFAULT '',
			whatsapp_test_delivery_status varchar(20) NOT NULL DEFAULT '',
			whatsapp_test_status_at datetime DEFAULT NULL,
			whatsapp_last_error text NULL,
			confirmation_whatsapp_attempted_at datetime DEFAULT NULL,
			confirmation_whatsapp_sent_at datetime DEFAULT NULL,
			confirmation_whatsapp_message_id varchar(191) NOT NULL DEFAULT '',
			confirmation_whatsapp_delivery_status varchar(20) NOT NULL DEFAULT '',
			confirmation_whatsapp_status_at datetime DEFAULT NULL,
			confirmation_whatsapp_last_error text NULL,
			teacher_whatsapp_attempted_at datetime DEFAULT NULL,
			teacher_whatsapp_sent_at datetime DEFAULT NULL,
			teacher_whatsapp_message_id varchar(191) NOT NULL DEFAULT '',
			teacher_whatsapp_delivery_status varchar(20) NOT NULL DEFAULT '',
			teacher_whatsapp_status_at datetime DEFAULT NULL,
			teacher_whatsapp_last_error text NULL,
			customer_timezone varchar(64) NOT NULL DEFAULT '',
			payable tinyint(1) NOT NULL DEFAULT 1,
			payment_note text NULL,
			archived_at datetime DEFAULT NULL,
			archived_by bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY booking_id (booking_id),
			KEY status (status),
			KEY booking_start (booking_start),
			KEY expires_at (expires_at)
		) {$charset};";

		dbDelta( $sql );
		update_option( 'dln_attendance_db_version', self::DB_VERSION, false );
		self::backfill_latest_whatsapp_test();
		self::backfill_introductory_payment();
		self::review_legacy_automatic_completions();
		if ( did_action( 'init' ) ) {
			DLN_Attendance_Plugin::register_rewrite_rules();
			flush_rewrite_rules( false );
		} else {
			add_action( 'init', static function (): void {
				DLN_Attendance_Plugin::register_rewrite_rules();
				flush_rewrite_rules( false );
			}, 99 );
		}

		if ( ! wp_next_scheduled( 'dln_attendance_reconcile' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'dln_attendance_reconcile' );
		}
		if ( ! wp_next_scheduled( 'dln_attendance_cleanup' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'dln_attendance_cleanup' );
		}
	}

	public static function maybe_upgrade(): void {
		if ( self::DB_VERSION !== (string) get_option( 'dln_attendance_db_version', '' ) ) {
			self::activate();
		}
		self::ensure_schedules();
	}

	/** Restore recurring jobs after a staged migration deactivates the legacy plugin. */
	public static function ensure_schedules(): void {
		if ( ! wp_next_scheduled( 'dln_attendance_reconcile' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'dln_attendance_reconcile' );
		}
		if ( ! wp_next_scheduled( 'dln_attendance_cleanup' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'dln_attendance_cleanup' );
		}
	}

	public static function upsert_context( array $context ): ?array {
		global $wpdb;
		$booking_id = absint( $context['booking_id'] ?? 0 );
		if ( ! $booking_id ) {
			return null;
		}

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE booking_id = %d ORDER BY id DESC LIMIT 1", $booking_id ),
			ARRAY_A
		);
		$now   = current_time( 'mysql', true );
		$data  = array(
			'appointment_id' => self::nullable_id( $context['appointment_id'] ?? null ),
			'customer_id'    => self::nullable_id( $context['customer_id'] ?? null ),
			'customer_name'  => sanitize_text_field( (string) ( $context['customer_name'] ?? '' ) ),
			'customer_email' => sanitize_email( (string) ( $context['customer_email'] ?? '' ) ),
			'employee_name'  => sanitize_text_field( (string) ( $context['employee_name'] ?? '' ) ),
			'employee_email' => sanitize_email( (string) ( $context['employee_email'] ?? '' ) ),
			'employee_phone' => sanitize_text_field( (string) ( $context['employee_phone'] ?? '' ) ),
			'customer_timezone' => self::timezone( (string) ( $context['customer_timezone'] ?? '' ) ),
			'service_name'   => sanitize_text_field( (string) ( $context['service_name'] ?? '' ) ),
			'service_category_id' => self::nullable_id( $context['service_category_id'] ?? null ),
			'source_status'  => sanitize_key( (string) ( $context['source_status'] ?? 'approved' ) ),
			'booking_start'  => self::mysql_datetime( $context['booking_start'] ?? null ),
			'booking_end'    => self::mysql_datetime( $context['booking_end'] ?? null ),
			'meet_code'      => sanitize_text_field( (string) ( $context['meet_code'] ?? '' ) ),
			'meet_url'       => esc_url_raw( (string) ( $context['meet_url'] ?? '' ) ),
			'updated_at'     => $now,
		);
		$data  = array_filter(
			$data,
			static fn( $value ) => null !== $value && '' !== $value
		);
		if ( self::is_introductory_category( absint( $context['service_category_id'] ?? 0 ) ) ) {
			$data['payable'] = 0;
			$data['payment_note'] = 'Introductory class — non-payable';
		} elseif ( $row && 'Introductory class — non-payable' === (string) ( $row['payment_note'] ?? '' ) ) {
			// Reverse only the automatic intro treatment when Amelia moves the
			// booking to another category. Administrator payment choices remain intact.
			$data['payable'] = 1;
			$data['payment_note'] = '';
		}

		if ( $row ) {
			if ( ! empty( $data['booking_start'] ) && (string) ( $row['booking_start'] ?? '' ) !== (string) $data['booking_start'] ) {
				// Keep the signed public capability stable so WhatsApp buttons already
				// delivered to the customer continue working after a reschedule.
				// Cancellation, archive state and expiry still revoke access.
				$start_ts = strtotime( $data['booking_start'] . ' UTC' ) ?: time();
				$data['expires_at'] = gmdate( 'Y-m-d H:i:s', max( time(), $start_ts ) + 30 * DAY_IN_SECONDS );
			}
			$wpdb->update( $table, $data, array( 'id' => (int) $row['id'] ) );
			$updated = self::get_by_id( (int) $row['id'] );
			if ( $updated ) {
				self::apply_source_status( $updated );
			}
			return self::get_by_id( (int) $row['id'] );
		}

		$start_ts = ! empty( $data['booking_start'] ) ? strtotime( $data['booking_start'] . ' UTC' ) : time();
		$expires  = gmdate( 'Y-m-d H:i:s', max( time(), $start_ts ) + 30 * DAY_IN_SECONDS );
		$insert   = array_merge(
			$data,
			array(
				'public_id'  => bin2hex( random_bytes( 16 ) ),
				'booking_id' => $booking_id,
				'status'     => 'pending',
				'created_at' => $now,
				'updated_at' => $now,
				'expires_at' => $expires,
			)
		);
		$wpdb->insert( $table, $insert );
		if ( ! $wpdb->insert_id ) {
			return null;
		}
		$created = self::get_by_id( (int) $wpdb->insert_id );
		if ( $created ) {
			self::apply_source_status( $created );
		}
		return self::get_by_id( (int) $wpdb->insert_id );
	}

	/** Keeps financial treatment separate from the attendance evidence. */
	public static function update_payment_record( int $id, bool $payable, string $note ): bool {
		global $wpdb;
		$row = self::get_by_id( $id );
		if ( $row && self::is_introductory_category( absint( $row['service_category_id'] ?? 0 ) ) ) {
			$payable = false;
			$note = 'Introductory class — non-payable';
		}
		return false !== $wpdb->update(
			self::table(),
			array( 'payable' => $payable ? 1 : 0, 'payment_note' => sanitize_textarea_field( $note ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $id )
		);
	}

	/** Archive is reversible and retains the audit trail; it is never a hard financial delete. */
	public static function archive_record( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array( 'archived_at' => current_time( 'mysql', true ), 'archived_by' => get_current_user_id(), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $id )
		);
	}

	/** Restores an archived record without changing its attendance or payment evidence. */
	public static function restore_record( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array( 'archived_at' => null, 'archived_by' => null, 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $id )
		);
	}

	public static function archived_count(): int {
		global $wpdb;
		return absint( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE archived_at IS NOT NULL' ) );
	}

	public static function get_by_id( int $id ): ?array {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	public static function get_by_public_id( string $public_id ): ?array {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE public_id = %s LIMIT 1", $public_id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public static function get_by_booking_id( int $booking_id ): ?array {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE booking_id = %d ORDER BY id DESC LIMIT 1", $booking_id ),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public static function mark_confirmation_whatsapp_attempt( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'confirmation_whatsapp_attempted_at' => current_time( 'mysql', true ),
				'confirmation_whatsapp_last_error'   => '',
				'updated_at'                         => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function mark_confirmation_whatsapp_sent( int $id, string $message_id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'confirmation_whatsapp_sent_at'    => current_time( 'mysql', true ),
				'confirmation_whatsapp_message_id' => sanitize_text_field( $message_id ),
				'confirmation_whatsapp_last_error' => '',
				'updated_at'                       => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function mark_confirmation_whatsapp_error( int $id, string $message ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'confirmation_whatsapp_last_error' => sanitize_textarea_field( $message ),
				'updated_at'                       => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function mark_whatsapp_attempt( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'whatsapp_attempted_at' => current_time( 'mysql', true ),
				'whatsapp_last_error'   => '',
				'updated_at'            => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function mark_whatsapp_sent( int $id, string $message_id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'whatsapp_sent_at'     => current_time( 'mysql', true ),
				'whatsapp_message_id'  => sanitize_text_field( $message_id ),
				'whatsapp_last_error'  => '',
				'updated_at'           => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function mark_whatsapp_error( int $id, string $message ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'whatsapp_last_error' => sanitize_textarea_field( $message ),
				'updated_at'          => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function mark_whatsapp_tested( int $id, string $message_id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'whatsapp_tested_at'        => current_time( 'mysql', true ),
				'whatsapp_test_message_id' => sanitize_text_field( $message_id ),
				'updated_at'                => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function mark_teacher_whatsapp_attempt( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'teacher_whatsapp_attempted_at' => current_time( 'mysql', true ),
				'teacher_whatsapp_last_error'   => '',
				'updated_at'                    => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function mark_teacher_whatsapp_sent( int $id, string $message_id ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'teacher_whatsapp_sent_at'    => current_time( 'mysql', true ),
				'teacher_whatsapp_message_id' => sanitize_text_field( $message_id ),
				'teacher_whatsapp_last_error' => '',
				'updated_at'                   => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function mark_teacher_whatsapp_error( int $id, string $message ): bool {
		global $wpdb;
		return false !== $wpdb->update(
			self::table(),
			array(
				'teacher_whatsapp_last_error' => sanitize_textarea_field( $message ),
				'updated_at'                   => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
	}

	public static function update_whatsapp_delivery_status( string $message_id, string $status, int $occurred_at, string $error = '' ): int {
		global $wpdb;
		$status = sanitize_key( $status );
		$message_id = sanitize_text_field( trim( $message_id ) );
		$ranks = array( 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4 );
		if ( ! isset( $ranks[ $status ] ) || '' === $message_id ) {
			return 0;
		}
		$event_time = $occurred_at > 0 ? $occurred_at : time();
		$time = gmdate( 'Y-m-d H:i:s', $event_time );
		$mappings = array(
			array( 'confirmation_whatsapp_message_id', 'confirmation_whatsapp_delivery_status', 'confirmation_whatsapp_status_at', 'confirmation_whatsapp_last_error' ),
			array( 'whatsapp_message_id', 'whatsapp_delivery_status', 'whatsapp_status_at', 'whatsapp_last_error' ),
			array( 'whatsapp_test_message_id', 'whatsapp_test_delivery_status', 'whatsapp_test_status_at', 'whatsapp_last_error' ),
			array( 'teacher_whatsapp_message_id', 'teacher_whatsapp_delivery_status', 'teacher_whatsapp_status_at', 'teacher_whatsapp_last_error' ),
		);
		$updated = 0;
		$table = self::table();
		foreach ( $mappings as $mapping ) {
			$current = $wpdb->get_row(
				$wpdb->prepare( "SELECT {$mapping[1]} AS delivery_status, {$mapping[2]} AS status_at FROM {$table} WHERE {$mapping[0]} = %s LIMIT 1", $message_id ),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! is_array( $current ) ) {
				continue;
			}
			$current_status = sanitize_key( (string) ( $current['delivery_status'] ?? '' ) );
			$current_time = strtotime( (string) ( $current['status_at'] ?? '' ) . ' UTC' ) ?: 0;
			if ( isset( $ranks[ $current_status ] ) && $ranks[ $current_status ] > $ranks[ $status ] ) {
				continue;
			}
			if ( $current_time > $event_time || ( $current_time === $event_time && $current_status === $status ) ) {
				continue;
			}
			$data = array( $mapping[1] => $status, $mapping[2] => $time, 'updated_at' => current_time( 'mysql', true ) );
			if ( 'failed' === $status && '' !== trim( $error ) ) {
				$data[ $mapping[3] ] = sanitize_textarea_field( $error );
			}
			$result = $wpdb->update( $table, $data, array( $mapping[0] => $message_id ) );
			if ( false !== $result ) {
				$updated += (int) $result;
			}
		}
		return $updated;
	}

	public static function confirm_whatsapp_received( int $id ): bool {
		global $wpdb;
		if ( $id <= 0 ) {
			return false;
		}
		$row = self::get_by_id( $id );
		if ( ! $row || empty( $row['whatsapp_sent_at'] ) ) {
			return false;
		}
		$now = current_time( 'mysql', true );
		return false !== $wpdb->update(
			self::table(),
			array(
				'whatsapp_received_confirmed_at' => $now,
				'whatsapp_received_confirmed_by' => get_current_user_id(),
				'updated_at'                     => $now,
			),
			array( 'id' => $id )
		);
	}

	public static function update_status( int $id, string $status, array $extra = array() ): bool {
		global $wpdb;
		$allowed = array( 'pending', 'join_clicked', 'absence_reported', 'attended', 'no_show', 'review_needed', 'cancelled' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}
		$data = array_merge(
			$extra,
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql', true ),
			)
		);
		return false !== $wpdb->update( self::table(), $data, array( 'id' => $id ) );
	}

	/** Atomically accepts absence only from an active approved booking state. */
	public static function record_absence( int $id ): bool {
		global $wpdb;
		$table = self::table();
		$now   = current_time( 'mysql', true );
		$sql   = $wpdb->prepare(
			"UPDATE {$table}
			 SET status = 'absence_reported', absence_reported_at = %s, updated_at = %s
			 WHERE id = %d
			   AND archived_at IS NULL
			   AND source_status = 'approved'
			   AND status IN ('pending','join_clicked')",
			$now,
			$now,
			$id
		);
		return 1 === (int) $wpdb->query( $sql );
	}

	public static function record_join( int $id ): bool {
		global $wpdb;
		$table = self::table();
		$now   = current_time( 'mysql', true );
		$sql   = $wpdb->prepare(
			"UPDATE {$table}
			 SET join_click_count = join_click_count + 1,
			     join_clicked_at = COALESCE(join_clicked_at, %s),
			     status = CASE WHEN status = 'pending' THEN 'join_clicked' ELSE status END,
			     updated_at = %s
			 WHERE id = %d",
			$now,
			$now,
			$id
		);
		return false !== $wpdb->query( $sql );
	}

	public static function records_for_reconciliation( int $limit = 20 ): array {
		global $wpdb;
		$table  = self::table();
		$before = gmdate( 'Y-m-d H:i:s', time() - 15 * MINUTE_IN_SECONDS );
		$limit  = max( 1, min( 100, $limit ) );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				 WHERE status IN ('pending','join_clicked','absence_reported','review_needed')
				   AND archived_at IS NULL
				   AND source_status = 'approved'
				   AND meet_code <> ''
				   AND booking_start IS NOT NULL
				   AND booking_start < %s
				 ORDER BY booking_start ASC LIMIT %d",
				$before,
				$limit
			),
			ARRAY_A
		) ?: array();
	}

	public static function recent( int $limit = 100 ): array {
		return self::recent_filtered( array(), $limit );
	}

	public static function recent_filtered( array $filters, int $limit = 500 ): array {
		global $wpdb;
		$limit = max( 1, min( 5000, $limit ) );
		$archive = sanitize_key( (string) ( $filters['archive'] ?? 'active' ) );
		$where   = array( 'archived' === $archive ? 'archived_at IS NOT NULL' : 'archived_at IS NULL' );
		$args  = array();
		if ( ! empty( $filters['from'] ) ) {
			$bounds  = self::local_date_bounds( (string) $filters['from'], (string) ( $filters['to'] ?? $filters['from'] ) );
			$where[] = 'booking_start BETWEEN %s AND %s';
			$args[]  = $bounds[0];
			$args[]  = $bounds[1];
		}
		foreach ( array( 'employee_email', 'service_name', 'status' ) as $field ) {
			if ( '' !== (string) ( $filters[ $field ] ?? '' ) ) {
				$where[] = $field . ' = %s';
				$args[]  = (string) $filters[ $field ];
			}
		}
		if ( '' !== (string) ( $filters['student'] ?? '' ) ) {
			$where[] = '(customer_name LIKE %s OR customer_email LIKE %s)';
			$like    = '%' . $wpdb->esc_like( (string) $filters['student'] ) . '%';
			$args[]  = $like;
			$args[]  = $like;
		}
		$whatsapp = (string) ( $filters['whatsapp'] ?? '' );
		if ( 'sent' === $whatsapp ) {
			$where[] = 'whatsapp_sent_at IS NOT NULL';
		} elseif ( 'tested' === $whatsapp ) {
			$where[] = 'whatsapp_sent_at IS NULL AND whatsapp_tested_at IS NOT NULL';
		} elseif ( 'failed' === $whatsapp ) {
			$where[] = "whatsapp_sent_at IS NULL AND whatsapp_last_error <> ''";
		} elseif ( 'not_attempted' === $whatsapp ) {
			$where[] = 'whatsapp_attempted_at IS NULL AND whatsapp_tested_at IS NULL';
		}
		$args[] = $limit;
		$sql = 'SELECT * FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY booking_start DESC, id DESC LIMIT %d';
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ) ?: array();
	}

	public static function filter_options(): array {
		global $wpdb;
		$table = self::table();
		return array(
			'teachers' => $wpdb->get_results( "SELECT DISTINCT employee_email, employee_name FROM {$table} WHERE employee_name <> '' ORDER BY employee_name", ARRAY_A ) ?: array(),
			'services' => $wpdb->get_col( "SELECT DISTINCT service_name FROM {$table} WHERE service_name <> '' ORDER BY service_name" ) ?: array(),
		);
	}

	public static function teacher_report( string $from, string $to, string $teacher_email = '' ): array {
		global $wpdb;
		list( $from_mysql, $to_mysql ) = self::local_date_bounds( $from, $to );
		$teacher_sql = '';
		$args = array( 'attended', $from_mysql, $to_mysql );
		if ( $teacher_email ) {
			$teacher_sql = ' AND employee_email = %s';
			$args[] = $teacher_email;
		}
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT employee_email, employee_name, service_name,
				 COUNT(DISTINCT IF(appointment_id IS NULL, -booking_id, appointment_id)) AS completed_count
				 FROM ' . self::table() . '
			 WHERE status = %s AND payable = 1 AND archived_at IS NULL AND booking_start BETWEEN %s AND %s' . $teacher_sql . '
				 GROUP BY employee_email, employee_name, service_name
				 ORDER BY employee_name ASC, service_name ASC',
				$args
			),
			ARRAY_A
		) ?: array();
	}

	public static function teacher_sessions( string $from, string $to, string $teacher_email ): array {
		global $wpdb;
		list( $from_mysql, $to_mysql ) = self::local_date_bounds( $from, $to );
		$sql = 'SELECT * FROM ' . self::table() . '
			WHERE status = %s AND archived_at IS NULL AND booking_start BETWEEN %s AND %s AND employee_email = %s
			ORDER BY booking_start ASC, id ASC';
		return $wpdb->get_results( $wpdb->prepare( $sql, 'attended', $from_mysql, $to_mysql, $teacher_email ), ARRAY_A ) ?: array();
	}

	/** Marks missing upcoming source bookings cancelled, while preserving completed history. */
	public static function reconcile_missing_source_bookings( array $booking_ids, string $from_utc, string $to_utc ): int {
		global $wpdb;
		$ids = array_values( array_filter( array_map( 'absint', $booking_ids ) ) );
		$where = "booking_start BETWEEN %s AND %s AND status NOT IN ('attended','no_show','cancelled')";
		$args = array( $from_utc, $to_utc );
		if ( $ids ) {
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$where .= " AND booking_id NOT IN ({$placeholders})";
			$args = array_merge( $args, $ids );
		}
		$sql = 'UPDATE ' . self::table() . " SET source_status = 'cancelled', status = 'cancelled', verification_note = 'The Amelia booking was deleted or is no longer returned by reconciliation.', updated_at = UTC_TIMESTAMP() WHERE " . $where;
		return (int) $wpdb->query( $wpdb->prepare( $sql, $args ) );
	}

	public static function cleanup(): void {
		global $wpdb;
		$settings = DLN_Attendance_Plugin::settings();
		$days     = max( 30, absint( $settings['retention_days'] ?? 180 ) );
		$before   = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		// Only records intentionally archived for the full retention period are
		// purged. Active historical attendance and payment evidence is preserved.
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE archived_at IS NOT NULL AND archived_at < %s', $before ) );
	}

	private static function nullable_id( $value ): ?int {
		$value = absint( $value );
		return $value ?: null;
	}

	public static function is_introductory_category( int $category_id ): bool {
		$settings = DLN_Attendance_Plugin::settings();
		return $category_id > 0 && $category_id === absint( $settings['intro_service_category_id'] ?? 3 );
	}

	/** Backfills category identity from Amelia without writing to Amelia tables. */
	private static function backfill_introductory_payment(): void {
		global $wpdb;
		$attendance = self::table();
		$bookings = $wpdb->prefix . 'amelia_customer_bookings';
		$appointments = $wpdb->prefix . 'amelia_appointments';
		$services = $wpdb->prefix . 'amelia_services';
		foreach ( array( $bookings, $appointments, $services ) as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
			if ( $table !== $found ) {
				return;
			}
		}
		$intro_id = absint( DLN_Attendance_Plugin::settings()['intro_service_category_id'] ?? 3 );
		if ( ! $intro_id ) {
			return;
		}
		$intro_note = 'Introductory class — non-payable';
		$sql = "UPDATE {$attendance} d
			INNER JOIN {$bookings} cb ON cb.id = d.booking_id
			INNER JOIN {$appointments} a ON a.id = cb.appointmentId
			INNER JOIN {$services} s ON s.id = a.serviceId
			SET d.service_category_id = s.categoryId,
				d.payable = CASE
					WHEN s.categoryId = %d THEN 0
					WHEN s.categoryId <> %d AND d.payment_note = %s THEN 1
					ELSE d.payable
				END,
				d.payment_note = CASE
					WHEN s.categoryId = %d THEN %s
					WHEN s.categoryId <> %d AND d.payment_note = %s THEN ''
					ELSE d.payment_note
				END";
		$wpdb->query( $wpdb->prepare( $sql, $intro_id, $intro_id, $intro_note, $intro_id, $intro_note, $intro_id, $intro_note ) );
	}

	private static function timezone( string $value ): string {
		$value = trim( $value );
		try {
			new DateTimeZone( $value );
			return $value;
		} catch ( Exception $exception ) {
			return '';
		}
	}

	private static function mysql_datetime( $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}
		if ( is_numeric( $value ) ) {
			$timestamp = (int) $value;
		} else {
			$date = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', (string) $value, new DateTimeZone( 'UTC' ) );
			$timestamp = $date instanceof DateTimeImmutable ? $date->getTimestamp() : strtotime( (string) $value . ' UTC' );
		}
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	private static function local_date_bounds( string $from, string $to ): array {
		$timezone = wp_timezone();
		$start = DateTimeImmutable::createFromFormat( '!Y-m-d', $from, $timezone ) ?: current_datetime()->setTime( 0, 0 );
		$end   = DateTimeImmutable::createFromFormat( '!Y-m-d', $to, $timezone ) ?: $start;
		$utc   = new DateTimeZone( 'UTC' );
		return array(
			$start->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
			$end->setTime( 23, 59, 59 )->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
		);
	}

	private static function apply_source_status( array $row ): void {
		$source = sanitize_key( (string) ( $row['source_status'] ?? 'approved' ) );
		$status = sanitize_key( (string) ( $row['status'] ?? 'pending' ) );
		if ( 'approved' !== $source && ! in_array( $status, array( 'attended', 'no_show', 'cancelled' ), true ) ) {
			self::update_status( (int) $row['id'], 'cancelled', array( 'verification_note' => 'The Amelia booking is no longer approved.' ) );
		} elseif ( 'approved' === $source && 'cancelled' === $status ) {
			self::update_status( (int) $row['id'], 'pending', array( 'verification_note' => 'The Amelia booking is approved again.' ) );
		}
	}

	private static function backfill_latest_whatsapp_test(): void {
		$diagnostic = get_option( 'dln_attendance_whatsapp_diagnostic', array() );
		if ( ! is_array( $diagnostic ) || empty( $diagnostic['booking_id'] ) || empty( $diagnostic['message_id'] ) ) {
			return;
		}
		$row = self::get_by_booking_id( absint( $diagnostic['booking_id'] ) );
		if ( ! $row || ! empty( $row['whatsapp_sent_at'] ) || ! empty( $row['whatsapp_tested_at'] ) ) {
			return;
		}
		global $wpdb;
		$wpdb->update(
			self::table(),
			array(
				'whatsapp_tested_at'        => gmdate( 'Y-m-d H:i:s', absint( $diagnostic['updated_at'] ?? time() ) ),
				'whatsapp_test_message_id' => sanitize_text_field( (string) $diagnostic['message_id'] ),
			),
			array( 'id' => (int) $row['id'] )
		);
	}

	private static function review_legacy_automatic_completions(): void {
		global $wpdb;
		$table = self::table();
		$wpdb->query(
			"UPDATE {$table}
			 SET status = 'review_needed',
			     verification_note = 'Legacy completion requires teacher-student overlap review under the 20-minute production rule.',
			     updated_at = UTC_TIMESTAMP()
			 WHERE status = 'attended'
			   AND (teacher_duration_seconds IS NULL OR overlap_seconds IS NULL)
			   AND verification_note NOT LIKE 'Status set manually%'"
		);
	}
}

