<?php

defined( 'ABSPATH' ) || exit;

/**
 * Attempts to reuse Amelia's employee Google authorization for Meet attendance.
 *
 * Amelia documents filters/actions around its Google Calendar OAuth flow. This
 * adapter adds the read-only Meet scope and accepts credentials only when a
 * renewable token is exposed. It never changes Amelia's saved payload.
 */
final class DLN_Attendance_Amelia_Google {
	private const STATUS_OPTION  = 'dln_attendance_amelia_oauth_status';
	private const CLIENTS_OPTION = 'dln_attendance_amelia_oauth_clients';

	public static function boot(): void {
		add_filter( 'amelia_get_google_calendar_auth_url_filter', array( __CLASS__, 'filter_auth_url' ), 10, 2 );
		add_filter( 'amelia_before_google_calendar_added_filter', array( __CLASS__, 'capture_filter' ), 10, 2 );
		add_action( 'amelia_before_google_calendar_added', array( __CLASS__, 'capture_action' ), 10, 2 );
		add_action( 'amelia_after_google_calendar_added', array( __CLASS__, 'capture_action' ), 10, 2 );
	}

	public static function filter_auth_url( $auth_url, $employee_id ): string {
		$auth_url = is_string( $auth_url ) ? $auth_url : '';
		if ( ! self::enabled() || ! $auth_url ) {
			return $auth_url;
		}

		$query = array();
		wp_parse_str( (string) wp_parse_url( $auth_url, PHP_URL_QUERY ), $query );
		$scopes   = preg_split( '/\s+/', trim( (string) ( $query['scope'] ?? '' ) ) ) ?: array();
		$scopes   = array_merge( $scopes, array( 'openid', 'email', 'profile', DLN_Attendance_Google::meet_scope() ) );
		$scopes   = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $scopes ) ) ) );

		$auth_url = add_query_arg(
			array(
				'scope'                  => implode( ' ', $scopes ),
				'include_granted_scopes' => 'true',
				'access_type'             => 'offline',
			),
			$auth_url
		);

		$employee_id = absint( $employee_id );
		$client_id   = sanitize_text_field( (string) ( $query['client_id'] ?? '' ) );
		$clients     = get_option( self::CLIENTS_OPTION, array() );
		$clients     = is_array( $clients ) ? $clients : array();
		if ( $employee_id && $client_id ) {
			$clients[ $employee_id ] = $client_id;
			update_option( self::CLIENTS_OPTION, $clients, false );
		}

		self::set_status(
			$employee_id,
			'authorization_extended',
			'Amelia authorization URL includes the read-only Google Meet attendance scope.'
		);
		return $auth_url;
	}

	public static function capture_filter( $payload, $employee_id ) {
		self::capture( $payload, absint( $employee_id ) );
		return $payload;
	}

	public static function capture_action( $payload, $employee_id ): void {
		self::capture( $payload, absint( $employee_id ) );
	}

	public static function status(): array {
		$status = get_option( self::STATUS_OPTION, array() );
		return is_array( $status ) ? $status : array();
	}

	private static function enabled(): bool {
		$settings = DLN_Attendance_Plugin::settings();
		return ! empty( $settings['amelia_combined_oauth'] );
	}

	private static function capture( $payload, int $employee_id ): void {
		if ( ! self::enabled() || ! $employee_id ) {
			return;
		}

		$current = self::status();
		if ( 'ready' === (string) ( $current['state'] ?? '' ) && $employee_id === absint( $current['employee_id'] ?? 0 ) ) {
			return;
		}

		$credentials = self::extract_credentials( $payload );
		if ( empty( $credentials['access_token'] ) ) {
			if ( in_array( (string) ( $current['state'] ?? '' ), array( 'client_mismatch', 'meet_scope_missing', 'refresh_token_not_exposed' ), true ) ) {
				return;
			}
			self::set_status( $employee_id, 'token_not_exposed', 'Amelia completed its hook without exposing a reusable access token.' );
			return;
		}

		$email = self::employee_email( $employee_id );
		if ( ! $email ) {
			self::set_status( $employee_id, 'employee_not_found', 'The Amelia employee email could not be resolved.' );
			return;
		}

		$clients          = get_option( self::CLIENTS_OPTION, array() );
		$clients          = is_array( $clients ) ? $clients : array();
		$amelia_client_id = sanitize_text_field( (string) ( $clients[ $employee_id ] ?? '' ) );
		$settings         = DLN_Attendance_Plugin::settings();
		$plugin_client_id = sanitize_text_field( (string) ( $settings['amelia_google_client_id'] ?? '' ) );
		if ( ! $amelia_client_id || ! $plugin_client_id || ! hash_equals( $plugin_client_id, $amelia_client_id ) ) {
			self::set_status( $employee_id, 'client_mismatch', 'The Amelia OAuth client ID does not match the dedicated Amelia client saved in Attendance.' );
			return;
		}

		$result = DLN_Attendance_Google::store_amelia_credentials( $email, $credentials, $employee_id );
		if ( is_wp_error( $result ) ) {
			self::set_status( $employee_id, $result->get_error_code(), $result->get_error_message() );
			return;
		}

		self::set_status( $employee_id, 'ready', 'Single Google authorization captured a renewable Meet attendance connection.' );
	}

	private static function extract_credentials( $payload ): array {
		$found = array(
			'access_token'  => '',
			'refresh_token' => '',
			'expires_in'    => 0,
		);
		self::walk_payload( $payload, $found, 0 );
		return $found;
	}

	private static function walk_payload( $value, array &$found, int $depth ): void {
		if ( $depth > 8 ) {
			return;
		}
		if ( is_string( $value ) ) {
			$trimmed = trim( $value );
			if ( ( str_starts_with( $trimmed, '{' ) || str_starts_with( $trimmed, '[' ) ) ) {
				$decoded = json_decode( $trimmed, true );
				if ( is_array( $decoded ) ) {
					self::walk_payload( $decoded, $found, $depth + 1 );
				}
			} elseif ( str_starts_with( $trimmed, 'ya29.' ) ) {
				$found['access_token'] = sanitize_text_field( $trimmed );
			}
			return;
		}
		if ( is_object( $value ) ) {
			if ( is_callable( array( $value, 'toArray' ) ) ) {
				$value = $value->toArray();
			} elseif ( $value instanceof JsonSerializable ) {
				$value = $value->jsonSerialize();
			} else {
				$value = get_object_vars( $value );
			}
		}
		if ( ! is_array( $value ) ) {
			return;
		}

		foreach ( $value as $key => $item ) {
			$normalized = strtolower( preg_replace( '/[^a-z0-9]/i', '', (string) $key ) );
			if ( 'accesstoken' === $normalized && is_scalar( $item ) ) {
				$found['access_token'] = sanitize_text_field( (string) $item );
			} elseif ( 'refreshtoken' === $normalized && is_scalar( $item ) ) {
				$found['refresh_token'] = sanitize_text_field( (string) $item );
			} elseif ( 'expiresin' === $normalized && is_scalar( $item ) ) {
				$found['expires_in'] = absint( $item );
			} elseif ( is_array( $item ) || is_object( $item ) || is_string( $item ) ) {
				self::walk_payload( $item, $found, $depth + 1 );
			}
		}
	}

	private static function employee_email( int $employee_id ): string {
		global $wpdb;
		$table = $wpdb->prefix . 'amelia_users';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$email = $wpdb->get_var( $wpdb->prepare( "SELECT email FROM {$table} WHERE id = %d AND type = %s LIMIT 1", $employee_id, 'provider' ) );
		return sanitize_email( (string) $email );
	}

	private static function set_status( int $employee_id, string $state, string $message ): void {
		update_option(
			self::STATUS_OPTION,
			array(
				'employee_id' => $employee_id,
				'state'       => sanitize_key( $state ),
				'message'     => sanitize_text_field( $message ),
				'updated_at'  => time(),
			),
			false
		);
	}
}

