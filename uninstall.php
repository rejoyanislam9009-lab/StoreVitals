<?php
/**
 * Uninstall StoreVitals.
 *
 * @package StoreVitals
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'storevitals_scan_history' );
