<?php

defined( 'ABSPATH' ) || exit;

/** Authenticated encryption for reusable external credentials stored in options. */
final class DLN_Attendance_Secrets {
	private const PREFIX = 'dlnenc:v1:';

	public static function seal( string $value ): string {
		if ( '' === $value || str_starts_with( $value, self::PREFIX ) ) {
			return $value;
		}
		$key = self::key();
		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$payload = array(
				'a' => 'sodium',
				'n' => base64_encode( $nonce ),
				'c' => base64_encode( sodium_crypto_secretbox( $value, $nonce, $key ) ),
			);
		} elseif ( function_exists( 'openssl_encrypt' ) ) {
			$iv = random_bytes( 12 );
			$tag = '';
			$ciphertext = openssl_encrypt( $value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
			if ( false === $ciphertext ) {
				return '';
			}
			$payload = array(
				'a' => 'aes-256-gcm',
				'n' => base64_encode( $iv ),
				't' => base64_encode( $tag ),
				'c' => base64_encode( $ciphertext ),
			);
		} else {
			return '';
		}
		return self::PREFIX . base64_encode( wp_json_encode( $payload ) );
	}

	public static function unseal( string $value ): string {
		if ( '' === $value || ! str_starts_with( $value, self::PREFIX ) ) {
			return $value;
		}
		$encoded = substr( $value, strlen( self::PREFIX ) );
		$json = base64_decode( $encoded, true );
		$payload = is_string( $json ) ? json_decode( $json, true ) : null;
		if ( ! is_array( $payload ) ) {
			return '';
		}
		$nonce = base64_decode( (string) ( $payload['n'] ?? '' ), true );
		$ciphertext = base64_decode( (string) ( $payload['c'] ?? '' ), true );
		if ( ! is_string( $nonce ) || ! is_string( $ciphertext ) ) {
			return '';
		}
		if ( 'sodium' === (string) ( $payload['a'] ?? '' ) && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$plain = sodium_crypto_secretbox_open( $ciphertext, $nonce, self::key() );
			return false === $plain ? '' : $plain;
		}
		if ( 'aes-256-gcm' === (string) ( $payload['a'] ?? '' ) && function_exists( 'openssl_decrypt' ) ) {
			$tag = base64_decode( (string) ( $payload['t'] ?? '' ), true );
			if ( ! is_string( $tag ) ) {
				return '';
			}
			$plain = openssl_decrypt( $ciphertext, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $nonce, $tag );
			return false === $plain ? '' : $plain;
		}
		return '';
	}

	public static function migrate_options(): void {
		$settings = get_option( 'dln_attendance_settings', array() );
		if ( is_array( $settings ) ) {
			$changed = false;
			foreach ( array( 'webhook_secret', 'google_client_secret', 'amelia_google_client_secret', 'whatsapp_access_token' ) as $field ) {
				$value = (string) ( $settings[ $field ] ?? '' );
				if ( '' !== $value && ! str_starts_with( $value, self::PREFIX ) ) {
					$sealed = self::seal( $value );
					if ( '' !== $sealed ) {
						$settings[ $field ] = $sealed;
						$changed = true;
					}
				}
			}
			if ( $changed ) {
				update_option( 'dln_attendance_settings', $settings, false );
			}
		}

		$accounts = get_option( 'dln_attendance_google_accounts', array() );
		if ( is_array( $accounts ) ) {
			$changed = false;
			foreach ( $accounts as &$account ) {
				if ( ! is_array( $account ) ) {
					continue;
				}
				foreach ( array( 'access_token', 'refresh_token' ) as $field ) {
					$value = (string) ( $account[ $field ] ?? '' );
					if ( '' !== $value && ! str_starts_with( $value, self::PREFIX ) ) {
						$sealed = self::seal( $value );
						if ( '' !== $sealed ) {
							$account[ $field ] = $sealed;
							$changed = true;
						}
					}
				}
			}
			unset( $account );
			if ( $changed ) {
				update_option( 'dln_attendance_google_accounts', $accounts, false );
			}
		}
	}

	private static function key(): string {
		return hash( 'sha256', wp_salt( 'secure_auth' ) . '|delnavazan-attendance-secrets-v1', true );
	}
}

