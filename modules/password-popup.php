<?php

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	static function (): void {
		if ( ! is_page( 'enrol' ) ) {
			return;
		}
		require DLNV_ENHANCEMENTS_DIR . 'assets/password-popup.html';
	},
	1
);

