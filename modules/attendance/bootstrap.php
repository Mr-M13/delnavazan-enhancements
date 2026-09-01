<?php

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'DLN_Attendance_Plugin', false ) ) {
	return;
}

defined( 'DLN_ATTENDANCE_VERSION' ) || define( 'DLN_ATTENDANCE_VERSION', DLNV_ENHANCEMENTS_VERSION );
defined( 'DLN_ATTENDANCE_FILE' ) || define( 'DLN_ATTENDANCE_FILE', __FILE__ );
defined( 'DLN_ATTENDANCE_DIR' ) || define( 'DLN_ATTENDANCE_DIR', plugin_dir_path( __FILE__ ) );

require_once DLN_ATTENDANCE_DIR . 'includes/class-secrets.php';
require_once DLN_ATTENDANCE_DIR . 'includes/class-database.php';
require_once DLN_ATTENDANCE_DIR . 'includes/class-context.php';
require_once DLN_ATTENDANCE_DIR . 'includes/class-whatsapp.php';
require_once DLN_ATTENDANCE_DIR . 'includes/class-google.php';
require_once DLN_ATTENDANCE_DIR . 'includes/class-amelia-google.php';
require_once DLN_ATTENDANCE_DIR . 'includes/class-plugin.php';
require_once DLN_ATTENDANCE_DIR . 'includes/class-admin.php';

add_action(
	'plugins_loaded',
	static function (): void {
		DLN_Attendance_Plugin::instance()->boot();
	}
);

