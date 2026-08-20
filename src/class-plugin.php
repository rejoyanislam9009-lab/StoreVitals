<?php
/**
 * Plugin bootstrap.
 *
 * @package StoreVitals
 */

namespace StoreVitals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function boot() {
		load_plugin_textdomain( 'storevitals', false, dirname( plugin_basename( STOREVITALS_FILE ) ) . '/languages' );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_required_notice' ) );
			return;
		}

		if ( is_admin() ) {
			$history  = new History();
			$scanner  = new Scanner( $history );
			$admin    = new Admin( $scanner, $history );
			$exporter = new Exporter( $scanner );
			$admin->hooks();
			$exporter->hooks();
		}
	}

	public function woocommerce_required_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning"><p><?php echo esc_html__( 'StoreVitals requires WooCommerce to be installed and active.', 'storevitals' ); ?></p></div>
		<?php
	}
}
