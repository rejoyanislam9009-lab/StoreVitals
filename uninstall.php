<?php
/**
 * Uninstall StoreCheckup.
 *
 * @package StoreCheckup
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'storecheckup_scan_history' );
