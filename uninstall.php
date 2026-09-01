<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Preserve operational and payment evidence unless deletion is explicitly enabled.
if ( ! defined( 'DLN_ATTENDANCE_REMOVE_DATA_ON_UNINSTALL' ) || true !== DLN_ATTENDANCE_REMOVE_DATA_ON_UNINSTALL ) {
	return;
}

global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'dln_attendance' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
foreach ( array(
	'dln_attendance_settings',
	'dln_attendance_google_accounts',
	'dln_attendance_db_version',
	'dln_attendance_whatsapp_diagnostic',
	'dln_attendance_teacher_whatsapp_diagnostic',
	'dln_attendance_term_renewal_whatsapp_diagnostic',
	'dln_attendance_term_renewal_whatsapp_history',
	'dln_attendance_whatsapp_health',
	'dln_attendance_whatsapp_run_lock',
	'dln_attendance_amelia_oauth_status',
	'dln_attendance_amelia_oauth_clients',
	'dln_attendance_employee_google_diagnostic',
	'dln_attendance_master_document',
) as $option ) {
	delete_option( $option );
}

