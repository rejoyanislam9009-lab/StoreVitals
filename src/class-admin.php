<?php
/**
 * StoreCheckup admin application.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {
	use Admin_Presentation;
	use Admin_Diagnostics;
	use Admin_Reports;
	use Admin_Helpers;
	private $scanner;
	private $history;
	private $hook_suffix = '';

	public function __construct( Scanner $scanner, History $history ) {
		$this->scanner = $scanner;
		$this->history = $history;
	}

	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ), 60 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_storecheckup_rescan', array( $this, 'rescan' ) );
		add_action( 'admin_post_storecheckup_clear_history', array( $this, 'clear_history' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( STORECHECKUP_FILE ), array( $this, 'plugin_action_links' ) );
	}

	public function menu() {
		$this->hook_suffix = add_submenu_page(
			'woocommerce',
			__( 'Cartiloq Store Health', 'cartiloq-store-health' ),
			__( 'Cartiloq Store Health', 'cartiloq-store-health' ),
			'manage_woocommerce',
			'storecheckup',
			array( $this, 'render' )
		);
	}

	public function assets( $hook ) {
		if ( $hook !== $this->hook_suffix ) {
			return;
		}
		wp_enqueue_style( 'storecheckup-admin', STORECHECKUP_URL . 'assets/admin-layout.css', array(), STORECHECKUP_VERSION );
		wp_enqueue_style( 'storecheckup-admin-components', STORECHECKUP_URL . 'assets/admin-components.css', array( 'storecheckup-admin' ), STORECHECKUP_VERSION );
		wp_enqueue_style( 'storecheckup-admin-responsive', STORECHECKUP_URL . 'assets/admin-responsive.css', array( 'storecheckup-admin-components' ), STORECHECKUP_VERSION );
		wp_enqueue_script( 'storecheckup-admin', STORECHECKUP_URL . 'assets/admin.js', array(), STORECHECKUP_VERSION, true );
	}

	public function plugin_action_links( $links ) {
		$dashboard = '<a href="' . esc_url( admin_url( 'admin.php?page=storecheckup' ) ) . '">' . esc_html__( 'Dashboard', 'cartiloq-store-health' ) . '</a>';
		array_unshift( $links, $dashboard );
		return $links;
	}

	public function rescan() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to run this scan.', 'cartiloq-store-health' ) );
		}
		check_admin_referer( 'storecheckup_rescan' );
		$this->scanner->clear_cache();
		set_transient( 'storecheckup_notice_' . get_current_user_id(), 'rescanned', MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=storecheckup' ) );
		exit;
	}

	public function clear_history() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to clear scan history.', 'cartiloq-store-health' ) );
		}
		check_admin_referer( 'storecheckup_clear_history' );
		$this->history->clear();
		set_transient( 'storecheckup_notice_' . get_current_user_id(), 'history_cleared', MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=storecheckup&view=history' ) );
		exit;
	}

	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$scan = $this->scanner->scan();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter; no state is changed.
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'overview';
		if ( ! in_array( $view, array( 'overview', 'issues', 'catalog', 'operations', 'system', 'history', 'reports' ), true ) ) {
			$view = 'overview';
		}
		?>
		<div class="wrap storecheckup-wrap">
			<?php $this->render_header( $scan, $view ); ?>
			<?php $this->render_notices(); ?>
			<main class="storecheckup-main">
				<?php
				switch ( $view ) {
					case 'issues':
						$this->render_issue_view( $scan, null, __( 'All diagnostics', 'cartiloq-store-health' ), __( 'Search and filter every diagnostic result from the current bounded scan.', 'cartiloq-store-health' ) );
						break;
					case 'catalog':
						$this->render_issue_view( $scan, array( 'products', 'inventory' ), __( 'Catalog & inventory', 'cartiloq-store-health' ), __( 'Product completeness, variation integrity, stock signals, and bounded catalog quality checks.', 'cartiloq-store-health' ) );
						break;
					case 'operations':
						$this->render_issue_view( $scan, array( 'orders', 'checkout', 'payments', 'shipping' ), __( 'Store operations', 'cartiloq-store-health' ), __( 'Order states, checkout configuration, gateways, shipping, HPOS, and operational background tasks.', 'cartiloq-store-health' ) );
						break;
					case 'system':
						$this->render_issue_view( $scan, array( 'system', 'store' ), __( 'System & configuration', 'cartiloq-store-health' ), __( 'WordPress, WooCommerce, runtime, email, store configuration, cron, and environment diagnostics.', 'cartiloq-store-health' ) );
						break;
					case 'history':
						$this->render_history();
						break;
					case 'reports':
						$this->render_reports( $scan );
						break;
					case 'overview':
					default:
						$this->render_overview( $scan );
						break;
				}
				?>
			</main>
		</div>
		<?php
	}

}
