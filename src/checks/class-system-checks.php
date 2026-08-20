<?php
/** System checks. @package StoreVitals */
namespace StoreVitals\Checks;
use StoreVitals\Result;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class System_Checks {
	public function run() { return array( $this->https_check(), $this->permalink_check(), $this->memory_check(), $this->woocommerce_version_check(), $this->hpos_check() ); }
	private function https_check() {
		if ( is_ssl() || 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) ) { return new Result( 'https', 'system', Result::PASSED, __( 'HTTPS is enabled', 'storevitals' ), __( 'The store home URL uses HTTPS.', 'storevitals' ) ); }
		return new Result( 'https', 'system', Result::CRITICAL, __( 'HTTPS is not enabled', 'storevitals' ), __( 'Checkout and account traffic should be protected with HTTPS.', 'storevitals' ), 1, admin_url( 'options-general.php' ), __( 'Review site URLs', 'storevitals' ) );
	}
	private function permalink_check() {
		if ( ! empty( get_option( 'permalink_structure' ) ) ) { return new Result( 'permalinks', 'system', Result::PASSED, __( 'Pretty permalinks are enabled', 'storevitals' ), __( 'WordPress is using a custom permalink structure.', 'storevitals' ) ); }
		return new Result( 'permalinks', 'system', Result::WARNING, __( 'Plain permalinks are enabled', 'storevitals' ), __( 'Pretty permalinks are generally easier to manage for storefront URLs.', 'storevitals' ), 1, admin_url( 'options-permalink.php' ), __( 'Open Permalinks', 'storevitals' ) );
	}
	private function memory_check() {
		if ( wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) >= 134217728 ) { return new Result( 'memory', 'system', Result::PASSED, __( 'WordPress memory limit looks healthy', 'storevitals' ), sprintf( __( 'Configured memory limit: %s.', 'storevitals' ), WP_MEMORY_LIMIT ) ); }
		return new Result( 'memory', 'system', Result::WARNING, __( 'WordPress memory limit is low', 'storevitals' ), sprintf( __( 'Configured memory limit is %s. Larger WooCommerce stores often benefit from at least 128M.', 'storevitals' ), WP_MEMORY_LIMIT ) );
	}
	private function woocommerce_version_check() {
		$version = defined( 'WC_VERSION' ) ? WC_VERSION : '0';
		if ( version_compare( $version, '10.8', '>=' ) ) { return new Result( 'wc-version', 'system', Result::PASSED, __( 'WooCommerce version is supported', 'storevitals' ), sprintf( __( 'Detected WooCommerce %s.', 'storevitals' ), $version ) ); }
		return new Result( 'wc-version', 'system', Result::CRITICAL, __( 'WooCommerce is below the supported baseline', 'storevitals' ), sprintf( __( 'Detected WooCommerce %s. StoreVitals 1.0 supports WooCommerce 10.8 or newer.', 'storevitals' ), $version ), 1, admin_url( 'plugins.php' ), __( 'Open Plugins', 'storevitals' ) );
	}
	private function hpos_check() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			$enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			return new Result( 'hpos', 'orders', Result::PASSED, $enabled ? __( 'HPOS is enabled', 'storevitals' ) : __( 'Order storage mode detected', 'storevitals' ), $enabled ? __( 'WooCommerce High-Performance Order Storage is active.', 'storevitals' ) : __( 'The store currently uses the legacy WordPress posts order datastore. StoreVitals supports both modes.', 'storevitals' ) );
		}
		return new Result( 'hpos', 'orders', Result::INFO, __( 'HPOS status unavailable', 'storevitals' ), __( 'WooCommerce did not expose the HPOS status utility in this environment.', 'storevitals' ) );
	}
}
