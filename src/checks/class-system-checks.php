<?php
/**
 * WordPress and WooCommerce environment checks.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup\Checks;

use StoreCheckup\Result;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class System_Checks {
	public function run() {
		return array(
			$this->https_check(),
			$this->permalink_check(),
			$this->memory_check(),
			$this->php_version_check(),
			$this->wordpress_version_check(),
			$this->woocommerce_version_check(),
			$this->hpos_check(),
			$this->cron_check(),
			$this->uploads_check(),
			$this->debug_display_check(),
			$this->environment_check(),
			$this->object_cache_check(),
		);
	}

	private function https_check() {
		if ( is_ssl() || 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) ) {
			return new Result( 'https', 'system', Result::PASSED, __( 'HTTPS is enabled', 'flow-store-check' ), __( 'The store home URL uses HTTPS.', 'flow-store-check' ) );
		}
		return new Result( 'https', 'system', Result::CRITICAL, __( 'HTTPS is not enabled', 'flow-store-check' ), __( 'Checkout and account traffic should be protected with HTTPS.', 'flow-store-check' ), 1, admin_url( 'options-general.php' ), __( 'Review site URLs', 'flow-store-check' ) );
	}

	private function permalink_check() {
		if ( ! empty( get_option( 'permalink_structure' ) ) ) {
			return new Result( 'permalinks', 'system', Result::PASSED, __( 'Pretty permalinks are enabled', 'flow-store-check' ), __( 'WordPress is using a custom permalink structure.', 'flow-store-check' ) );
		}
		return new Result( 'permalinks', 'system', Result::WARNING, __( 'Plain permalinks are enabled', 'flow-store-check' ), __( 'Pretty permalinks are generally easier to manage for storefront URLs and REST endpoints.', 'flow-store-check' ), 1, admin_url( 'options-permalink.php' ), __( 'Open Permalinks', 'flow-store-check' ) );
	}

	private function memory_check() {
		$bytes = defined( 'WP_MEMORY_LIMIT' ) ? wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) : 0;
		if ( $bytes >= 134217728 ) {
			/* translators: %s: configured WordPress memory limit, for example 256M. */
			return new Result( 'memory', 'system', Result::PASSED, __( 'WordPress memory limit looks healthy', 'flow-store-check' ), sprintf( __( 'Configured memory limit: %s.', 'flow-store-check' ), WP_MEMORY_LIMIT ) );
		}
		/* translators: %s: configured WordPress memory limit, for example 64M. */
		return new Result( 'memory', 'system', Result::WARNING, __( 'WordPress memory limit is low', 'flow-store-check' ), sprintf( __( 'Configured memory limit is %s. Larger WooCommerce stores often benefit from at least 128M.', 'flow-store-check' ), defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'unknown', 'flow-store-check' ) ) );
	}

	private function php_version_check() {
		if ( version_compare( PHP_VERSION, '8.1', '>=' ) ) {
			/* translators: %s: detected PHP version number. */
			return new Result( 'php-version', 'system', Result::PASSED, __( 'PHP version looks modern', 'flow-store-check' ), sprintf( __( 'Detected PHP %s.', 'flow-store-check' ), PHP_VERSION ) );
		}
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			/* translators: %s: detected PHP version number. */
			return new Result( 'php-version', 'system', Result::INFO, __( 'PHP meets the plugin minimum', 'flow-store-check' ), sprintf( __( 'Detected PHP %s. Consider a supported modern PHP branch when your hosting stack allows it.', 'flow-store-check' ), PHP_VERSION ) );
		}
		/* translators: %s: detected PHP version number. */
		return new Result( 'php-version', 'system', Result::CRITICAL, __( 'PHP is below the supported minimum', 'flow-store-check' ), sprintf( __( 'Detected PHP %s. Flow Store Check requires PHP 7.4 or newer.', 'flow-store-check' ), PHP_VERSION ) );
	}

	private function wordpress_version_check() {
		$version = get_bloginfo( 'version' );
		if ( version_compare( $version, '6.9', '>=' ) ) {
			/* translators: %s: detected WordPress version number. */
			return new Result( 'wp-version', 'system', Result::PASSED, __( 'WordPress version is supported', 'flow-store-check' ), sprintf( __( 'Detected WordPress %s.', 'flow-store-check' ), $version ) );
		}
		/* translators: %s: detected WordPress version number. */
		return new Result( 'wp-version', 'system', Result::CRITICAL, __( 'WordPress is below the supported baseline', 'flow-store-check' ), sprintf( __( 'Detected WordPress %s. Flow Store Check 1.0 requires WordPress 6.9 or newer.', 'flow-store-check' ), $version ), 1, admin_url( 'update-core.php' ), __( 'Review updates', 'flow-store-check' ) );
	}

	private function woocommerce_version_check() {
		$version = defined( 'WC_VERSION' ) ? WC_VERSION : '0';
		if ( version_compare( $version, '10.8', '>=' ) ) {
			/* translators: %s: detected WooCommerce version number. */
			return new Result( 'wc-version', 'system', Result::PASSED, __( 'WooCommerce version is supported', 'flow-store-check' ), sprintf( __( 'Detected WooCommerce %s.', 'flow-store-check' ), $version ) );
		}
		/* translators: %s: detected WooCommerce version number. */
		return new Result( 'wc-version', 'system', Result::CRITICAL, __( 'WooCommerce is below the supported baseline', 'flow-store-check' ), sprintf( __( 'Detected WooCommerce %s. Flow Store Check 1.0 supports WooCommerce 10.8 or newer.', 'flow-store-check' ), $version ), 1, admin_url( 'plugins.php' ), __( 'Open Plugins', 'flow-store-check' ) );
	}

	private function hpos_check() {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
			$enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			return new Result( 'hpos', 'orders', Result::PASSED, $enabled ? __( 'HPOS is enabled', 'flow-store-check' ) : __( 'Order storage mode detected', 'flow-store-check' ), $enabled ? __( 'WooCommerce High-Performance Order Storage is active.', 'flow-store-check' ) : __( 'The store currently uses the legacy WordPress posts order datastore. Flow Store Check supports both modes.', 'flow-store-check' ) );
		}
		return new Result( 'hpos', 'orders', Result::INFO, __( 'HPOS status unavailable', 'flow-store-check' ), __( 'WooCommerce did not expose the HPOS status utility in this environment.', 'flow-store-check' ) );
	}

	private function cron_check() {
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return new Result( 'wp-cron', 'system', Result::WARNING, __( 'WordPress cron is disabled', 'flow-store-check' ), __( 'This is safe only when the site has a reliable server-side scheduler replacing WP-Cron.', 'flow-store-check' ) );
		}
		return new Result( 'wp-cron', 'system', Result::PASSED, __( 'WordPress cron is available', 'flow-store-check' ), __( 'WP-Cron is not disabled by configuration.', 'flow-store-check' ) );
	}

	private function uploads_check() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new Result( 'uploads', 'system', Result::CRITICAL, __( 'Uploads directory reports an error', 'flow-store-check' ), sanitize_text_field( $uploads['error'] ) );
		}
		$basedir = isset( $uploads['basedir'] ) ? $uploads['basedir'] : '';
		if ( $basedir && wp_is_writable( $basedir ) ) {
			return new Result( 'uploads', 'system', Result::PASSED, __( 'Uploads directory is writable', 'flow-store-check' ), __( 'WordPress can write to the configured uploads directory.', 'flow-store-check' ) );
		}
		return new Result( 'uploads', 'system', Result::WARNING, __( 'Uploads directory may not be writable', 'flow-store-check' ), __( 'Media uploads and generated files can fail when the uploads directory is not writable.', 'flow-store-check' ) );
	}

	private function debug_display_check() {
		$debug         = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$debug_display = defined( 'WP_DEBUG_DISPLAY' ) ? WP_DEBUG_DISPLAY : true;
		if ( $debug && $debug_display ) {
			return new Result( 'debug-display', 'system', Result::WARNING, __( 'Debug display is enabled', 'flow-store-check' ), __( 'Visible PHP notices can expose implementation details on a public storefront. Log debugging is usually safer for production.', 'flow-store-check' ) );
		}
		return new Result( 'debug-display', 'system', Result::PASSED, __( 'Debug output is not configured for public display', 'flow-store-check' ), __( 'No active WordPress debug-display condition was detected.', 'flow-store-check' ) );
	}

	private function environment_check() {
		$type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		/* translators: %s: current WordPress environment type, such as production or staging. */
		return new Result( 'environment', 'system', Result::INFO, __( 'WordPress environment type', 'flow-store-check' ), sprintf( __( 'Environment type: %s.', 'flow-store-check' ), $type ) );
	}

	private function object_cache_check() {
		$enabled = wp_using_ext_object_cache();
		return new Result( 'object-cache', 'system', Result::INFO, $enabled ? __( 'Persistent object cache is active', 'flow-store-check' ) : __( 'Persistent object cache was not detected', 'flow-store-check' ), $enabled ? __( 'WordPress reports that an external object cache is active.', 'flow-store-check' ) : __( 'This is informational; many stores run correctly without a persistent object cache.', 'flow-store-check' ) );
	}
}
