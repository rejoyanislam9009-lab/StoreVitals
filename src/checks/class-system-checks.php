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
			return new Result( 'https', 'system', Result::PASSED, __( 'HTTPS is enabled', 'rejoyan-store-health' ), __( 'The store home URL uses HTTPS.', 'rejoyan-store-health' ) );
		}
		return new Result( 'https', 'system', Result::CRITICAL, __( 'HTTPS is not enabled', 'rejoyan-store-health' ), __( 'Checkout and account traffic should be protected with HTTPS.', 'rejoyan-store-health' ), 1, admin_url( 'options-general.php' ), __( 'Review site URLs', 'rejoyan-store-health' ) );
	}

	private function permalink_check() {
		if ( ! empty( get_option( 'permalink_structure' ) ) ) {
			return new Result( 'permalinks', 'system', Result::PASSED, __( 'Pretty permalinks are enabled', 'rejoyan-store-health' ), __( 'WordPress is using a custom permalink structure.', 'rejoyan-store-health' ) );
		}
		return new Result( 'permalinks', 'system', Result::WARNING, __( 'Plain permalinks are enabled', 'rejoyan-store-health' ), __( 'Pretty permalinks are generally easier to manage for storefront URLs and REST endpoints.', 'rejoyan-store-health' ), 1, admin_url( 'options-permalink.php' ), __( 'Open Permalinks', 'rejoyan-store-health' ) );
	}

	private function memory_check() {
		$bytes = defined( 'WP_MEMORY_LIMIT' ) ? wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) : 0;
		if ( $bytes >= 134217728 ) {
			/* translators: %s: configured WordPress memory limit, for example 256M. */
			return new Result( 'memory', 'system', Result::PASSED, __( 'WordPress memory limit looks healthy', 'rejoyan-store-health' ), sprintf( __( 'Configured memory limit: %s.', 'rejoyan-store-health' ), WP_MEMORY_LIMIT ) );
		}
		/* translators: %s: configured WordPress memory limit, for example 64M. */
		return new Result( 'memory', 'system', Result::WARNING, __( 'WordPress memory limit is low', 'rejoyan-store-health' ), sprintf( __( 'Configured memory limit is %s. Larger WooCommerce stores often benefit from at least 128M.', 'rejoyan-store-health' ), defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'unknown', 'rejoyan-store-health' ) ) );
	}

	private function php_version_check() {
		if ( version_compare( PHP_VERSION, '8.1', '>=' ) ) {
			/* translators: %s: detected PHP version number. */
			return new Result( 'php-version', 'system', Result::PASSED, __( 'PHP version looks modern', 'rejoyan-store-health' ), sprintf( __( 'Detected PHP %s.', 'rejoyan-store-health' ), PHP_VERSION ) );
		}
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			/* translators: %s: detected PHP version number. */
			return new Result( 'php-version', 'system', Result::INFO, __( 'PHP meets the plugin minimum', 'rejoyan-store-health' ), sprintf( __( 'Detected PHP %s. Consider a supported modern PHP branch when your hosting stack allows it.', 'rejoyan-store-health' ), PHP_VERSION ) );
		}
		/* translators: %s: detected PHP version number. */
		return new Result( 'php-version', 'system', Result::CRITICAL, __( 'PHP is below the supported minimum', 'rejoyan-store-health' ), sprintf( __( 'Detected PHP %s. Rejoyan Store Health requires PHP 7.4 or newer.', 'rejoyan-store-health' ), PHP_VERSION ) );
	}

	private function wordpress_version_check() {
		$version = get_bloginfo( 'version' );
		if ( version_compare( $version, '6.9', '>=' ) ) {
			/* translators: %s: detected WordPress version number. */
			return new Result( 'wp-version', 'system', Result::PASSED, __( 'WordPress version is supported', 'rejoyan-store-health' ), sprintf( __( 'Detected WordPress %s.', 'rejoyan-store-health' ), $version ) );
		}
		/* translators: %s: detected WordPress version number. */
		return new Result( 'wp-version', 'system', Result::CRITICAL, __( 'WordPress is below the supported baseline', 'rejoyan-store-health' ), sprintf( __( 'Detected WordPress %s. Rejoyan Store Health 1.0 requires WordPress 6.9 or newer.', 'rejoyan-store-health' ), $version ), 1, admin_url( 'update-core.php' ), __( 'Review updates', 'rejoyan-store-health' ) );
	}

	private function woocommerce_version_check() {
		$version = defined( 'WC_VERSION' ) ? WC_VERSION : '0';
		if ( version_compare( $version, '10.8', '>=' ) ) {
			/* translators: %s: detected WooCommerce version number. */
			return new Result( 'wc-version', 'system', Result::PASSED, __( 'WooCommerce version is supported', 'rejoyan-store-health' ), sprintf( __( 'Detected WooCommerce %s.', 'rejoyan-store-health' ), $version ) );
		}
		/* translators: %s: detected WooCommerce version number. */
		return new Result( 'wc-version', 'system', Result::CRITICAL, __( 'WooCommerce is below the supported baseline', 'rejoyan-store-health' ), sprintf( __( 'Detected WooCommerce %s. Rejoyan Store Health 1.0 supports WooCommerce 10.8 or newer.', 'rejoyan-store-health' ), $version ), 1, admin_url( 'plugins.php' ), __( 'Open Plugins', 'rejoyan-store-health' ) );
	}

	private function hpos_check() {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
			$enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			return new Result( 'hpos', 'orders', Result::PASSED, $enabled ? __( 'HPOS is enabled', 'rejoyan-store-health' ) : __( 'Order storage mode detected', 'rejoyan-store-health' ), $enabled ? __( 'WooCommerce High-Performance Order Storage is active.', 'rejoyan-store-health' ) : __( 'The store currently uses the legacy WordPress posts order datastore. Rejoyan Store Health supports both modes.', 'rejoyan-store-health' ) );
		}
		return new Result( 'hpos', 'orders', Result::INFO, __( 'HPOS status unavailable', 'rejoyan-store-health' ), __( 'WooCommerce did not expose the HPOS status utility in this environment.', 'rejoyan-store-health' ) );
	}

	private function cron_check() {
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return new Result( 'wp-cron', 'system', Result::WARNING, __( 'WordPress cron is disabled', 'rejoyan-store-health' ), __( 'This is safe only when the site has a reliable server-side scheduler replacing WP-Cron.', 'rejoyan-store-health' ) );
		}
		return new Result( 'wp-cron', 'system', Result::PASSED, __( 'WordPress cron is available', 'rejoyan-store-health' ), __( 'WP-Cron is not disabled by configuration.', 'rejoyan-store-health' ) );
	}

	private function uploads_check() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new Result( 'uploads', 'system', Result::CRITICAL, __( 'Uploads directory reports an error', 'rejoyan-store-health' ), sanitize_text_field( $uploads['error'] ) );
		}
		$basedir = isset( $uploads['basedir'] ) ? $uploads['basedir'] : '';
		if ( $basedir && wp_is_writable( $basedir ) ) {
			return new Result( 'uploads', 'system', Result::PASSED, __( 'Uploads directory is writable', 'rejoyan-store-health' ), __( 'WordPress can write to the configured uploads directory.', 'rejoyan-store-health' ) );
		}
		return new Result( 'uploads', 'system', Result::WARNING, __( 'Uploads directory may not be writable', 'rejoyan-store-health' ), __( 'Media uploads and generated files can fail when the uploads directory is not writable.', 'rejoyan-store-health' ) );
	}

	private function debug_display_check() {
		$debug         = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$debug_display = defined( 'WP_DEBUG_DISPLAY' ) ? WP_DEBUG_DISPLAY : true;
		if ( $debug && $debug_display ) {
			return new Result( 'debug-display', 'system', Result::WARNING, __( 'Debug display is enabled', 'rejoyan-store-health' ), __( 'Visible PHP notices can expose implementation details on a public storefront. Log debugging is usually safer for production.', 'rejoyan-store-health' ) );
		}
		return new Result( 'debug-display', 'system', Result::PASSED, __( 'Debug output is not configured for public display', 'rejoyan-store-health' ), __( 'No active WordPress debug-display condition was detected.', 'rejoyan-store-health' ) );
	}

	private function environment_check() {
		$type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		/* translators: %s: current WordPress environment type, such as production or staging. */
		return new Result( 'environment', 'system', Result::INFO, __( 'WordPress environment type', 'rejoyan-store-health' ), sprintf( __( 'Environment type: %s.', 'rejoyan-store-health' ), $type ) );
	}

	private function object_cache_check() {
		$enabled = wp_using_ext_object_cache();
		return new Result( 'object-cache', 'system', Result::INFO, $enabled ? __( 'Persistent object cache is active', 'rejoyan-store-health' ) : __( 'Persistent object cache was not detected', 'rejoyan-store-health' ), $enabled ? __( 'WordPress reports that an external object cache is active.', 'rejoyan-store-health' ) : __( 'This is informational; many stores run correctly without a persistent object cache.', 'rejoyan-store-health' ) );
	}
}
