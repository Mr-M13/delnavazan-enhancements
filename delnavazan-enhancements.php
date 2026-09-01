<?php
/**
 * Plugin Name: Delnavazan Enhancements
 * Description: Unified site enhancements, Amelia enrolment helpers, attendance tracking, Google Meet reconciliation, and WhatsApp workflows.
 * Version: 2.0.17
 * Author: Delnavazan
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: delnavazan-enhancements
 */

defined( 'ABSPATH' ) || exit;

define( 'DLNV_ENHANCEMENTS_VERSION', '2.0.17' );
define( 'DLNV_ENHANCEMENTS_FILE', __FILE__ );
define( 'DLNV_ENHANCEMENTS_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Detect a legacy plugin from WordPress's active-plugin registry instead of
 * relying on load order. This is essential during migration: the unified
 * plugin can be loaded before the old Site Enhancements plugin.
 */
function dlnv_enhancements_legacy_plugin_active( string $main_file ): bool {
	$active = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}
	foreach ( $active as $plugin ) {
		if ( $main_file === basename( (string) $plugin ) && plugin_basename( __FILE__ ) !== (string) $plugin ) {
			return true;
		}
	}
	return false;
}

$dlnv_legacy_site_active       = dlnv_enhancements_legacy_plugin_active( 'delnavazan-site-enhancements.php' );
$dlnv_legacy_attendance_active = dlnv_enhancements_legacy_plugin_active( 'delnavazan-attendance.php' );

// Keep the entire unified replacement inert while legacy plugins are active.
// This also avoids duplicate snippet behavior before the controlled cutover.
if ( ! $dlnv_legacy_site_active && ! $dlnv_legacy_attendance_active ) {
	require_once DLNV_ENHANCEMENTS_DIR . 'modules/password-reset-email.php';
	require_once DLNV_ENHANCEMENTS_DIR . 'modules/password-popup.php';
	require_once DLNV_ENHANCEMENTS_DIR . 'modules/meta-pixel.php';
}

/*
 * During a staged migration the legacy Site Enhancements plugin may still be
 * active. Defer these modules until the next request after it is deactivated.
 */
if ( ! $dlnv_legacy_site_active && ! defined( 'DN_ENHANCEMENTS_VERSION' ) ) {
	define( 'DN_ENHANCEMENTS_VERSION', DLNV_ENHANCEMENTS_VERSION );
	define( 'DN_ENHANCEMENTS_PATH', DLNV_ENHANCEMENTS_DIR );
	require_once DLNV_ENHANCEMENTS_DIR . 'modules/site/articles.php';
	require_once DLNV_ENHANCEMENTS_DIR . 'modules/site/homepage.php';
	require_once DLNV_ENHANCEMENTS_DIR . 'modules/site/enrolment.php';
	require_once DLNV_ENHANCEMENTS_DIR . 'modules/site/payment.php';
	require_once DLNV_ENHANCEMENTS_DIR . 'modules/site-core.php';
}

/* The same guard prevents Attendance class redeclarations during migration. */
if ( ! $dlnv_legacy_attendance_active && ! class_exists( 'DLN_Attendance_Plugin', false ) ) {
	require_once DLNV_ENHANCEMENTS_DIR . 'modules/attendance/bootstrap.php';
	define( 'DLNV_ENHANCEMENTS_ATTENDANCE_LOADED', true );
}

if ( $dlnv_legacy_site_active || $dlnv_legacy_attendance_active ) {
	add_action(
		'admin_notices',
		static function () use ( $dlnv_legacy_site_active, $dlnv_legacy_attendance_active ): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$waiting = array();
			if ( $dlnv_legacy_site_active ) {
				$waiting[] = 'Delnavazan Site Enhancements';
			}
			if ( $dlnv_legacy_attendance_active ) {
				$waiting[] = 'Delnavazan Attendance Tracker';
			}
			echo '<div class="notice notice-warning"><p><strong>Delnavazan Enhancements is in safe migration mode.</strong> Its overlapping modules will remain off while these legacy plugins are active: ' . esc_html( implode( ', ', $waiting ) ) . '.</p></div>';
		}
	);
}

function dlnv_enhancements_activate(): void {
	if ( dlnv_enhancements_legacy_plugin_active( 'delnavazan-attendance.php' ) ) {
		return;
	}
	if ( ! class_exists( 'DLN_Attendance_Database', false ) ) {
		require_once DLNV_ENHANCEMENTS_DIR . 'modules/attendance/bootstrap.php';
	}
	DLN_Attendance_Database::activate();
}
register_activation_hook( __FILE__, 'dlnv_enhancements_activate' );

function dlnv_enhancements_deactivate(): void {
	if ( defined( 'DLNV_ENHANCEMENTS_ATTENDANCE_LOADED' ) && class_exists( 'DLN_Attendance_Plugin', false ) ) {
		DLN_Attendance_Plugin::deactivate();
	}
}
register_deactivation_hook( __FILE__, 'dlnv_enhancements_deactivate' );

