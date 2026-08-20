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
			return new Result( 'https', 'system', Result::PASSED, __( 'HTTPS is enabled', 'storecheckup' ), __( 'The store home URL uses HTTPS.', 'storecheckup' ) );
		}
		return new Result( 'https', 'system', Result::CRITICAL, __( 'HTTPS is not enabled', 'storecheckup' ), __( 'Checkout and account traffic should be protected with HTTPS.', 'storecheckup' ), 1, admin_url( 'options-general.php' ), __( 'Review site URLs', 'storecheckup' ) );
	}

	private function permalink_check() {
		if ( ! empty( get_option( 'permalink_structure' ) ) ) {
			return new Result( 'permalinks', 'system', Result::PASSED, __( 'Pretty permalinks are enabled', 'storecheckup' ), __( 'WordPress is using a custom permalink structure.', 'storecheckup' ) );
		}
		return new Result( 'permalinks', 'system', Result::WARNING, __( 'Plain permalinks are enabled', 'storecheckup' ), __( 'Pretty permalinks are generally easier to manage for storefront URLs and REST endpoints.', 'storecheckup' ), 1, admin_url( 'options-permalink.php' ), __( 'Open Permalinks', 'storecheckup' ) );
	}

	private function memory_check() {
		$bytes = defined( 'WP_MEMORY_LIMIT' ) ? wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) : 0;
		if ( $bytes >= 134217728 ) {
			/* translators: %s: configured WordPress memory limit, for example 256M. */
			return new Result( 'memory', 'system', Result::PASSED, __( 'WordPress memory limit looks healthy', 'storecheckup' ), sprintf( __( 'Configured memory limit: %s.', 'storecheckup' ), WP_MEMORY_LIMIT ) );
		}
		/* translators: %s: configured WordPress memory limit, for example 64M. */
		return new Result( 'memory', 'system', Result::WARNING, __( 'WordPress memory limit is low', 'storecheckup' ), sprintf( __( 'Configured memory limit is %s. Larger WooCommerce stores often benefit from at least 128M.', 'storecheckup' ), defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'unknown', 'storecheckup' ) ) );
	}

	private function php_version_check() {
		if ( version_compare( PHP_VERSION, '8.1', '>=' ) ) {
			/* translators: %s: detected PHP version number. */
			return new Result( 'php-version', 'system', Result::PASSED, __( 'PHP version looks modern', 'storecheckup' ), sprintf( __( 'Detected PHP %s.', 'storecheckup' ), PHP_VERSION ) );
		}
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			/* translators: %s: detected PHP version number. */
			return new Result( 'php-version', 'system', Result::INFO, __( 'PHP meets the plugin minimum', 'storecheckup' ), sprintf( __( 'Detected PHP %s. Consider a supported modern PHP branch when your hosting stack allows it.', 'storecheckup' ), PHP_VERSION ) );
		}
		/* translators: %s: detected PHP version number. */
		return new Result( 'php-version', 'system', Result::CRITICAL, __( 'PHP is below the supported minimum', 'storecheckup' ), sprintf( __( 'Detected PHP %s. StoreCheckup requires PHP 7.4 or newer.', 'storecheckup' ), PHP_VERSION ) );
	}

	private function wordpress_version_check() {
		$version = get_bloginfo( 'version' );
		if ( version_compare( $version, '6.9', '>=' ) ) {
			/* translators: %s: detected WordPress version number. */
			return new Result( 'wp-version', 'system', Result::PASSED, __( 'WordPress version is supported', 'storecheckup' ), sprintf( __( 'Detected WordPress %s.', 'storecheckup' ), $version ) );
		}
		/* translators: %s: detected WordPress version number. */
		return new Result( 'wp-version', 'system', Result::CRITICAL, __( 'WordPress is below the supported baseline', 'storecheckup' ), sprintf( __( 'Detected WordPress %s. StoreCheckup 1.0 requires WordPress 6.9 or newer.', 'storecheckup' ), $version ), 1, admin_url( 'update-core.php' ), __( 'Review updates', 'storecheckup' ) );
	}

	private function woocommerce_version_check() {
		$version = defined( 'WC_VERSION' ) ? WC_VERSION : '0';
		if ( version_compare( $version, '10.8', '>=' ) ) {
			/* translators: %s: detected WooCommerce version number. */
			return new Result( 'wc-version', 'system', Result::PASSED, __( 'WooCommerce version is supported', 'storecheckup' ), sprintf( __( 'Detected WooCommerce %s.', 'storecheckup' ), $version ) );
		}
		/* translators: %s: detected WooCommerce version number. */
		return new Result( 'wc-version', 'system', Result::CRITICAL, __( 'WooCommerce is below the supported baseline', 'storecheckup' ), sprintf( __( 'Detected WooCommerce %s. StoreCheckup 1.0 supports WooCommerce 10.8 or newer.', 'storecheckup' ), $version ), 1, admin_url( 'plugins.php' ), __( 'Open Plugins', 'storecheckup' ) );
	}

	private function hpos_check() {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
			$enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			return new Result( 'hpos', 'orders', Result::PASSED, $enabled ? __( 'HPOS is enabled', 'storecheckup' ) : __( 'Order storage mode detected', 'storecheckup' ), $enabled ? __( 'WooCommerce High-Performance Order Storage is active.', 'storecheckup' ) : __( 'The store currently uses the legacy WordPress posts order datastore. StoreCheckup supports both modes.', 'storecheckup' ) );
		}
		return new Result( 'hpos', 'orders', Result::INFO, __( 'HPOS status unavailable', 'storecheckup' ), __( 'WooCommerce did not expose the HPOS status utility in this environment.', 'storecheckup' ) );
	}

	private function cron_check() {
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return new Result( 'wp-cron', 'system', Result::WARNING, __( 'WordPress cron is disabled', 'storecheckup' ), __( 'This is safe only when the site has a reliable server-side scheduler replacing WP-Cron.', 'storecheckup' ) );
		}
		return new Result( 'wp-cron', 'system', Result::PASSED, __( 'WordPress cron is available', 'storecheckup' ), __( 'WP-Cron is not disabled by configuration.', 'storecheckup' ) );
	}

	private function uploads_check() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new Result( 'uploads', 'system', Result::CRITICAL, __( 'Uploads directory reports an error', 'storecheckup' ), sanitize_text_field( $uploads['error'] ) );
		}
		$basedir = isset( $uploads['basedir'] ) ? $uploads['basedir'] : '';
		if ( $basedir && wp_is_writable( $basedir ) ) {
			return new Result( 'uploads', 'system', Result::PASSED, __( 'Uploads directory is writable', 'storecheckup' ), __( 'WordPress can write to the configured uploads directory.', 'storecheckup' ) );
		}
		return new Result( 'uploads', 'system', Result::WARNING, __( 'Uploads directory may not be writable', 'storecheckup' ), __( 'Media uploads and generated files can fail when the uploads directory is not writable.', 'storecheckup' ) );
	}

	private function debug_display_check() {
		$debug         = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$debug_display = defined( 'WP_DEBUG_DISPLAY' ) ? WP_DEBUG_DISPLAY : true;
		if ( $debug && $debug_display ) {
			return new Result( 'debug-display', 'system', Result::WARNING, __( 'Debug display is enabled', 'storecheckup' ), __( 'Visible PHP notices can expose implementation details on a public storefront. Log debugging is usually safer for production.', 'storecheckup' ) );
		}
		return new Result( 'debug-display', 'system', Result::PASSED, __( 'Debug output is not configured for public display', 'storecheckup' ), __( 'No active WordPress debug-display condition was detected.', 'storecheckup' ) );
	}

	private function environment_check() {
		$type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		/* translators: %s: current WordPress environment type, such as production or staging. */
		return new Result( 'environment', 'system', Result::INFO, __( 'WordPress environment type', 'storecheckup' ), sprintf( __( 'Environment type: %s.', 'storecheckup' ), $type ) );
	}

	private function object_cache_check() {
		$enabled = wp_using_ext_object_cache();
		return new Result( 'object-cache', 'system', Result::INFO, $enabled ? __( 'Persistent object cache is active', 'storecheckup' ) : __( 'Persistent object cache was not detected', 'storecheckup' ), $enabled ? __( 'WordPress reports that an external object cache is active.', 'storecheckup' ) : __( 'This is informational; many stores run correctly without a persistent object cache.', 'storecheckup' ) );
	}
}
