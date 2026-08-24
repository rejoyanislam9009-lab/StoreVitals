<?php
/**
 * Plugin Name:       Rejoyan Store Health for WooCommerce
 * Description:       Read-only store health diagnostics for WooCommerce products, inventory, checkout, orders, payments, shipping, and system configuration.
 * Version:           1.0.0
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Rejoyan Islam
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rejoyan-store-health
 * Domain Path:       /languages
 * WC requires at least: 10.8
 * WC tested up to:   11.0.1
 *
 * @package StoreCheckup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STORECHECKUP_VERSION', '1.0.0' );
define( 'STORECHECKUP_FILE', __FILE__ );
define( 'STORECHECKUP_PATH', plugin_dir_path( __FILE__ ) );
define( 'STORECHECKUP_URL', plugin_dir_url( __FILE__ ) );

require_once STORECHECKUP_PATH . 'src/class-result.php';
require_once STORECHECKUP_PATH . 'src/class-history.php';
require_once STORECHECKUP_PATH . 'src/checks/class-system-checks.php';
require_once STORECHECKUP_PATH . 'src/checks/class-store-checks.php';
require_once STORECHECKUP_PATH . 'src/checks/class-product-checks.php';
require_once STORECHECKUP_PATH . 'src/checks/class-order-checks.php';
require_once STORECHECKUP_PATH . 'src/class-scanner.php';
require_once STORECHECKUP_PATH . 'src/class-exporter.php';
require_once STORECHECKUP_PATH . 'src/trait-admin-presentation.php';
require_once STORECHECKUP_PATH . 'src/trait-admin-diagnostics.php';
require_once STORECHECKUP_PATH . 'src/trait-admin-reports.php';
require_once STORECHECKUP_PATH . 'src/trait-admin-helpers.php';
require_once STORECHECKUP_PATH . 'src/class-admin.php';
require_once STORECHECKUP_PATH . 'src/class-plugin.php';

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', STORECHECKUP_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		\StoreCheckup\Plugin::instance()->boot();
	}
);
