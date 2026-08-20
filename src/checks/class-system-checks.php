<?php
/**
 * WordPress and WooCommerce environment checks.
 *
 * @package StoreVitals
 */

namespace StoreVitals\Checks;

use StoreVitals\Result;

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
			return new Result( 'https', 'system', Result::PASSED, __( 'HTTPS is enabled', 'storevitals' ), __( 'The store home URL uses HTTPS.', 'storevitals' ) );
		}
		return new Result( 'https', 'system', Result::CRITICAL, __( 'HTTPS is not enabled', 'storevitals' ), __( 'Checkout and account traffic should be protected with HTTPS.', 'storevitals' ), 1, admin_url( 'options-general.php' ), __( 'Review site URLs', 'storevitals' ) );
	}

	private function permalink_check() {
		if ( ! empty( get_option( 'permalink_structure' ) ) ) {
			return new Result( 'permalinks', 'system', Result::PASSED, __( 'Pretty permalinks are enabled', 'storevitals' ), __( 'WordPress is using a custom permalink structure.', 'storevitals' ) );
		}
		return new Result( 'permalinks', 'system', Result::WARNING, __( 'Plain permalinks are enabled', 'storevitals' ), __( 'Pretty permalinks are generally easier to manage for storefront URLs and REST endpoints.', 'storevitals' ), 1, admin_url( 'options-permalink.php' ), __( 'Open Permalinks', 'storevitals' ) );
	}

	private function memory_check() {
		$bytes = defined( 'WP_MEMORY_LIMIT' ) ? wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) : 0;
		if ( $bytes >= 134217728 ) {
			return new Result( 'memory', 'system', Result::PASSED, __( 'WordPress memory limit looks healthy', 'storevitals' ), sprintf( __( 'Configured memory limit: %s.', 'storevitals' ), WP_MEMORY_LIMIT ) );
		}
		return new Result( 'memory', 'system', Result::WARNING, __( 'WordPress memory limit is low', 'storevitals' ), sprintf( __( 'Configured memory limit is %s. Larger WooCommerce stores often benefit from at least 128M.', 'storevitals' ), defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'unknown', 'storevitals' ) ) );
	}

	private function php_version_check() {
		if ( version_compare( PHP_VERSION, '8.1', '>=' ) ) {
			return new Result( 'php-version', 'system', Result::PASSED, __( 'PHP version looks modern', 'storevitals' ), sprintf( __( 'Detected PHP %s.', 'storevitals' ), PHP_VERSION ) );
		}
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			return new Result( 'php-version', 'system', Result::INFO, __( 'PHP meets the plugin minimum', 'storevitals' ), sprintf( __( 'Detected PHP %s. Consider a supported modern PHP branch when your hosting stack allows it.', 'storevitals' ), PHP_VERSION ) );
		}
		return new Result( 'php-version', 'system', Result::CRITICAL, __( 'PHP is below the supported minimum', 'storevitals' ), sprintf( __( 'Detected PHP %s. StoreVitals requires PHP 7.4 or newer.', 'storevitals' ), PHP_VERSION ) );
	}

	private function wordpress_version_check() {
		$version = get_bloginfo( 'version' );
		if ( version_compare( $version, '6.9', '>=' ) ) {
			return new Result( 'wp-version', 'system', Result::PASSED, __( 'WordPress version is supported', 'storevitals' ), sprintf( __( 'Detected WordPress %s.', 'storevitals' ), $version ) );
		}
		return new Result( 'wp-version', 'system', Result::CRITICAL, __( 'WordPress is below the supported baseline', 'storevitals' ), sprintf( __( 'Detected WordPress %s. StoreVitals 1.1 requires WordPress 6.9 or newer.', 'storevitals' ), $version ), 1, admin_url( 'update-core.php' ), __( 'Review updates', 'storevitals' ) );
	}

	private function woocommerce_version_check() {
		$version = defined( 'WC_VERSION' ) ? WC_VERSION : '0';
		if ( version_compare( $version, '10.8', '>=' ) ) {
			return new Result( 'wc-version', 'system', Result::PASSED, __( 'WooCommerce version is supported', 'storevitals' ), sprintf( __( 'Detected WooCommerce %s.', 'storevitals' ), $version ) );
		}
		return new Result( 'wc-version', 'system', Result::CRITICAL, __( 'WooCommerce is below the supported baseline', 'storevitals' ), sprintf( __( 'Detected WooCommerce %s. StoreVitals 1.1 supports WooCommerce 10.8 or newer.', 'storevitals' ), $version ), 1, admin_url( 'plugins.php' ), __( 'Open Plugins', 'storevitals' ) );
	}

	private function hpos_check() {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
			$enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			return new Result( 'hpos', 'orders', Result::PASSED, $enabled ? __( 'HPOS is enabled', 'storevitals' ) : __( 'Order storage mode detected', 'storevitals' ), $enabled ? __( 'WooCommerce High-Performance Order Storage is active.', 'storevitals' ) : __( 'The store currently uses the legacy WordPress posts order datastore. StoreVitals supports both modes.', 'storevitals' ) );
		}
		return new Result( 'hpos', 'orders', Result::INFO, __( 'HPOS status unavailable', 'storevitals' ), __( 'WooCommerce did not expose the HPOS status utility in this environment.', 'storevitals' ) );
	}

	private function cron_check() {
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return new Result( 'wp-cron', 'system', Result::WARNING, __( 'WordPress cron is disabled', 'storevitals' ), __( 'This is safe only when the site has a reliable server-side scheduler replacing WP-Cron.', 'storevitals' ) );
		}
		return new Result( 'wp-cron', 'system', Result::PASSED, __( 'WordPress cron is available', 'storevitals' ), __( 'WP-Cron is not disabled by configuration.', 'storevitals' ) );
	}

	private function uploads_check() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new Result( 'uploads', 'system', Result::CRITICAL, __( 'Uploads directory reports an error', 'storevitals' ), sanitize_text_field( $uploads['error'] ) );
		}
		$basedir = isset( $uploads['basedir'] ) ? $uploads['basedir'] : '';
		if ( $basedir && wp_is_writable( $basedir ) ) {
			return new Result( 'uploads', 'system', Result::PASSED, __( 'Uploads directory is writable', 'storevitals' ), __( 'WordPress can write to the configured uploads directory.', 'storevitals' ) );
		}
		return new Result( 'uploads', 'system', Result::WARNING, __( 'Uploads directory may not be writable', 'storevitals' ), __( 'Media uploads and generated files can fail when the uploads directory is not writable.', 'storevitals' ) );
	}

	private function debug_display_check() {
		$debug         = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$debug_display = defined( 'WP_DEBUG_DISPLAY' ) ? WP_DEBUG_DISPLAY : true;
		if ( $debug && $debug_display ) {
			return new Result( 'debug-display', 'system', Result::WARNING, __( 'Debug display is enabled', 'storevitals' ), __( 'Visible PHP notices can expose implementation details on a public storefront. Log debugging is usually safer for production.', 'storevitals' ) );
		}
		return new Result( 'debug-display', 'system', Result::PASSED, __( 'Debug output is not configured for public display', 'storevitals' ), __( 'No active WordPress debug-display condition was detected.', 'storevitals' ) );
	}

	private function environment_check() {
		$type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		return new Result( 'environment', 'system', Result::INFO, __( 'WordPress environment type', 'storevitals' ), sprintf( __( 'Environment type: %s.', 'storevitals' ), $type ) );
	}

	private function object_cache_check() {
		$enabled = wp_using_ext_object_cache();
		return new Result( 'object-cache', 'system', Result::INFO, $enabled ? __( 'Persistent object cache is active', 'storevitals' ) : __( 'Persistent object cache was not detected', 'storevitals' ), $enabled ? __( 'WordPress reports that an external object cache is active.', 'storevitals' ) : __( 'This is informational; many stores run correctly without a persistent object cache.', 'storevitals' ) );
	}
}
