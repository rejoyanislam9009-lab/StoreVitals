<?php
/**
 * StoreVitals admin application.
 *
 * @package StoreVitals
 */

namespace StoreVitals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {
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
		add_action( 'admin_post_storevitals_rescan', array( $this, 'rescan' ) );
		add_action( 'admin_post_storevitals_clear_history', array( $this, 'clear_history' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( STOREVITALS_FILE ), array( $this, 'plugin_action_links' ) );
	}

	public function menu() {
		$this->hook_suffix = add_submenu_page( 'woocommerce', __( 'StoreVitals', 'storevitals' ), __( 'StoreVitals', 'storevitals' ), 'manage_woocommerce', 'storevitals', array( $this, 'render' ) );
	}

	public function assets( $hook ) {
		if ( $hook !== $this->hook_suffix ) {
			return;
		}
		wp_enqueue_style( 'storevitals-admin', STOREVITALS_URL . 'assets/admin.css', array(), STOREVITALS_VERSION );
		wp_enqueue_script( 'storevitals-admin', STOREVITALS_URL . 'assets/admin.js', array(), STOREVITALS_VERSION, true );
	}

	public function plugin_action_links( $links ) {
		$dashboard = '<a href="' . esc_url( admin_url( 'admin.php?page=storevitals' ) ) . '">' . esc_html__( 'Dashboard', 'storevitals' ) . '</a>';
		array_unshift( $links, $dashboard );
		return $links;
	}

	public function rescan() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to run this scan.', 'storevitals' ) );
		}
		check_admin_referer( 'storevitals_rescan' );
		$this->scanner->clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=storevitals&rescanned=1' ) );
		exit;
	}

	public function clear_history() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to clear scan history.', 'storevitals' ) );
		}
		check_admin_referer( 'storevitals_clear_history' );
		$this->history->clear();
		wp_safe_redirect( admin_url( 'admin.php?page=storevitals&view=history&history_cleared=1' ) );
		exit;
	}

	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$scan = $this->scanner->scan();
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'overview';
		if ( ! in_array( $view, array( 'overview', 'issues', 'catalog', 'operations', 'system', 'history', 'reports' ), true ) ) {
			$view = 'overview';
		}
		?>
		<div class="wrap storevitals-wrap">
			<?php $this->render_header( $scan, $view ); ?>
			<?php $this->render_notices(); ?>
			<main class="storevitals-main">
				<?php
				switch ( $view ) {
					case 'issues':
						$this->render_issue_view( $scan, null, __( 'All diagnostics', 'storevitals' ), __( 'Search and filter every diagnostic result from the current bounded scan.', 'storevitals' ) );
						break;
					case 'catalog':
						$this->render_issue_view( $scan, array( 'products', 'inventory' ), __( 'Catalog & inventory', 'storevitals' ), __( 'Product completeness, variation integrity, stock signals, and bounded catalog quality checks.', 'storevitals' ) );
						break;
					case 'operations':
						$this->render_issue_view( $scan, array( 'orders', 'checkout', 'payments', 'shipping' ), __( 'Store operations', 'storevitals' ), __( 'Order states, checkout configuration, gateways, shipping, HPOS, and operational background tasks.', 'storevitals' ) );
						break;
					case 'system':
						$this->render_issue_view( $scan, array( 'system', 'store' ), __( 'System & configuration', 'storevitals' ), __( 'WordPress, WooCommerce, runtime, email, store configuration, cron, and environment diagnostics.', 'storevitals' ) );
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

	private function render_header( array $scan, $view ) {
		$tabs = array(
			'overview'   => array( 'dashicons-dashboard', __( 'Overview', 'storevitals' ) ),
			'issues'     => array( 'dashicons-list-view', __( 'Diagnostics', 'storevitals' ) ),
			'catalog'    => array( 'dashicons-products', __( 'Catalog', 'storevitals' ) ),
			'operations' => array( 'dashicons-store', __( 'Operations', 'storevitals' ) ),
			'system'     => array( 'dashicons-admin-tools', __( 'System', 'storevitals' ) ),
			'history'    => array( 'dashicons-chart-line', __( 'History', 'storevitals' ) ),
			'reports'    => array( 'dashicons-media-spreadsheet', __( 'Reports', 'storevitals' ) ),
		);
		?>
		<header class="storevitals-app-header">
			<div class="storevitals-brand-row">
				<div class="storevitals-brand">
					<div class="storevitals-brand-mark" aria-hidden="true"><span>SV</span></div>
					<div>
						<div class="storevitals-title-line"><h1><?php echo esc_html__( 'StoreVitals', 'storevitals' ); ?></h1><span class="storevitals-version"><?php echo esc_html( 'v' . STOREVITALS_VERSION ); ?></span></div>
						<p><?php echo esc_html__( 'Store Health Dashboard for WooCommerce', 'storevitals' ); ?></p>
					</div>
				</div>
				<div class="storevitals-actions">
					<a class="button" href="<?php echo esc_url( $this->tab_url( 'reports' ) ); ?>"><?php echo esc_html__( 'Reports', 'storevitals' ); ?></a>
					<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=storevitals_rescan' ), 'storevitals_rescan' ) ); ?>"><?php echo esc_html__( 'Run fresh scan', 'storevitals' ); ?></a>
				</div>
			</div>
			<nav class="storevitals-tabs" aria-label="<?php echo esc_attr__( 'StoreVitals sections', 'storevitals' ); ?>">
				<?php foreach ( $tabs as $id => $config ) : ?>
					<a class="storevitals-tab <?php echo $view === $id ? 'is-active' : ''; ?>" href="<?php echo esc_url( $this->tab_url( $id ) ); ?>" <?php echo $view === $id ? 'aria-current="page"' : ''; ?>>
						<span class="dashicons <?php echo esc_attr( $config[0] ); ?>" aria-hidden="true"></span><span><?php echo esc_html( $config[1] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		</header>
		<?php
	}

	private function render_notices() {
		if ( isset( $_GET['rescanned'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['rescanned'] ) ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Fresh StoreVitals scan completed.', 'storevitals' ) . '</p></div>';
		}
		if ( isset( $_GET['history_cleared'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['history_cleared'] ) ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Local StoreVitals scan history was cleared.', 'storevitals' ) . '</p></div>';
		}
	}

	private function render_overview( array $scan ) {
		$attention = array_values( array_filter( $scan['results'], static function ( $result ) { return in_array( $result['status'], array( Result::CRITICAL, Result::WARNING ), true ); } ) );
		?>
		<section class="storevitals-health-banner storevitals-tone-<?php echo esc_attr( $this->score_tone( $scan['score'] ) ); ?>">
			<div class="storevitals-score-panel">
				<span class="storevitals-eyebrow"><?php echo esc_html__( 'Store health score', 'storevitals' ); ?></span>
				<div class="storevitals-score-number"><strong><?php echo esc_html( (string) $scan['score'] ); ?></strong><span>/100</span></div>
				<strong class="storevitals-score-label"><?php echo esc_html( $this->score_label( $scan['score'] ) ); ?></strong>
				<progress max="100" value="<?php echo esc_attr( (string) $scan['score'] ); ?>"><?php echo esc_html( (string) $scan['score'] ); ?></progress>
			</div>
			<div class="storevitals-banner-content">
				<div><span class="storevitals-eyebrow"><?php echo esc_html__( 'Current diagnostic snapshot', 'storevitals' ); ?></span><h2><?php echo esc_html( $this->headline_for_score( $scan['score'] ) ); ?></h2><p><?php echo esc_html__( 'StoreVitals checks high-value WooCommerce configuration, catalog, inventory, order, checkout, and system signals without modifying store data.', 'storevitals' ); ?></p></div>
				<div class="storevitals-summary-grid">
					<?php $this->summary_card( $scan['counts'][ Result::CRITICAL ], __( 'Critical', 'storevitals' ), 'critical' ); ?>
					<?php $this->summary_card( $scan['counts'][ Result::WARNING ], __( 'Warnings', 'storevitals' ), 'warning' ); ?>
					<?php $this->summary_card( $scan['counts'][ Result::PASSED ], __( 'Passed', 'storevitals' ), 'passed' ); ?>
					<?php $this->summary_card( $scan['counts'][ Result::INFO ], __( 'Info', 'storevitals' ), 'info' ); ?>
				</div>
			</div>
		</section>

		<section class="storevitals-section">
			<div class="storevitals-section-heading"><div><span class="storevitals-eyebrow"><?php echo esc_html__( 'Health areas', 'storevitals' ); ?></span><h2><?php echo esc_html__( 'Where your store stands', 'storevitals' ); ?></h2></div><a href="<?php echo esc_url( $this->tab_url( 'issues' ) ); ?>"><?php echo esc_html__( 'View all diagnostics', 'storevitals' ); ?></a></div>
			<div class="storevitals-area-grid">
				<?php foreach ( $scan['area_scores'] as $area => $score ) : ?>
					<a class="storevitals-area-card" href="<?php echo esc_url( add_query_arg( array( 'page' => 'storevitals', 'view' => 'issues', 'sv_area' => $area ), admin_url( 'admin.php' ) ) ); ?>"><div><span><?php echo esc_html( $this->area_label( $area ) ); ?></span><strong><?php echo esc_html( (string) $score ); ?></strong></div><progress max="100" value="<?php echo esc_attr( (string) $score ); ?>"><?php echo esc_html( (string) $score ); ?></progress></a>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="storevitals-section storevitals-two-column">
			<div class="storevitals-card">
				<div class="storevitals-card-heading"><div><span class="storevitals-eyebrow"><?php echo esc_html__( 'Priority', 'storevitals' ); ?></span><h2><?php echo esc_html__( 'Needs attention', 'storevitals' ); ?></h2></div><span class="storevitals-count-pill"><?php echo esc_html( (string) count( $attention ) ); ?></span></div>
				<?php if ( empty( $attention ) ) : ?><div class="storevitals-empty"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><h3><?php echo esc_html__( 'No critical issues or warnings', 'storevitals' ); ?></h3><p><?php echo esc_html__( 'The current bounded scan did not find any items requiring attention.', 'storevitals' ); ?></p></div><?php else : ?><div class="storevitals-compact-results"><?php foreach ( array_slice( $attention, 0, 6 ) as $result ) : $this->render_compact_result( $result ); endforeach; ?></div><a class="button" href="<?php echo esc_url( $this->tab_url( 'issues' ) ); ?>"><?php echo esc_html__( 'Review all issues', 'storevitals' ); ?></a><?php endif; ?>
			</div>
			<div class="storevitals-card">
				<div class="storevitals-card-heading"><div><span class="storevitals-eyebrow"><?php echo esc_html__( 'Snapshot', 'storevitals' ); ?></span><h2><?php echo esc_html__( 'Scan details', 'storevitals' ); ?></h2></div></div>
				<dl class="storevitals-definition-list"><div><dt><?php echo esc_html__( 'Checks', 'storevitals' ); ?></dt><dd><?php echo esc_html( (string) $scan['check_count'] ); ?></dd></div><div><dt><?php echo esc_html__( 'Scan time', 'storevitals' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $scan['duration_ms'] ) . ' ms' ); ?></dd></div><div><dt><?php echo esc_html__( 'Last scan', 'storevitals' ); ?></dt><dd><?php echo esc_html( wp_date( 'M j, Y g:i a', $scan['scanned_at'] ) ); ?></dd></div><div><dt><?php echo esc_html__( 'Cache', 'storevitals' ); ?></dt><dd><?php echo esc_html__( '5 minutes', 'storevitals' ); ?></dd></div><div><dt><?php echo esc_html__( 'Mode', 'storevitals' ); ?></dt><dd><?php echo esc_html__( 'Read-only diagnostics', 'storevitals' ); ?></dd></div></dl>
				<p class="description"><?php echo esc_html__( 'StoreVitals does not delete, edit, refund, cancel, or automatically repair store data.', 'storevitals' ); ?></p>
			</div>
		</section>
		<?php
	}

	private function render_issue_view( array $scan, $allowed_areas, $title, $description ) {
		$status_filter = isset( $_GET['sv_status'] ) ? sanitize_key( wp_unslash( $_GET['sv_status'] ) ) : '';
		$area_filter   = isset( $_GET['sv_area'] ) ? sanitize_key( wp_unslash( $_GET['sv_area'] ) ) : '';
		$search        = isset( $_GET['sv_search'] ) ? sanitize_text_field( wp_unslash( $_GET['sv_search'] ) ) : '';
		$results       = $this->filter_results( $scan['results'], $allowed_areas, $status_filter, $area_filter, $search );
		$areas         = $this->available_areas( $scan['results'], $allowed_areas );
		$current_view  = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'issues';
		?>
		<section class="storevitals-section">
			<div class="storevitals-section-heading storevitals-section-heading-stacked"><div><span class="storevitals-eyebrow"><?php echo esc_html__( 'Diagnostics', 'storevitals' ); ?></span><h2><?php echo esc_html( $title ); ?></h2><p><?php echo esc_html( $description ); ?></p></div><span class="storevitals-count-pill"><?php echo esc_html( sprintf( _n( '%d result', '%d results', count( $results ), 'storevitals' ), count( $results ) ) ); ?></span></div>
			<form class="storevitals-filterbar" method="get"><input type="hidden" name="page" value="storevitals"><input type="hidden" name="view" value="<?php echo esc_attr( $current_view ); ?>"><label><span class="screen-reader-text"><?php echo esc_html__( 'Search diagnostics', 'storevitals' ); ?></span><input type="search" name="sv_search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr__( 'Search diagnostics...', 'storevitals' ); ?>"></label><label><span class="screen-reader-text"><?php echo esc_html__( 'Filter by status', 'storevitals' ); ?></span><select name="sv_status"><option value=""><?php echo esc_html__( 'All statuses', 'storevitals' ); ?></option><?php foreach ( array( Result::CRITICAL, Result::WARNING, Result::PASSED, Result::INFO ) as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status_filter, $status ); ?>><?php echo esc_html( ucfirst( $status ) ); ?></option><?php endforeach; ?></select></label><label><span class="screen-reader-text"><?php echo esc_html__( 'Filter by area', 'storevitals' ); ?></span><select name="sv_area"><option value=""><?php echo esc_html__( 'All areas', 'storevitals' ); ?></option><?php foreach ( $areas as $area ) : ?><option value="<?php echo esc_attr( $area ); ?>" <?php selected( $area_filter, $area ); ?>><?php echo esc_html( $this->area_label( $area ) ); ?></option><?php endforeach; ?></select></label><button class="button button-primary" type="submit"><?php echo esc_html__( 'Filter', 'storevitals' ); ?></button><a class="button" href="<?php echo esc_url( $this->tab_url( $current_view ) ); ?>"><?php echo esc_html__( 'Reset', 'storevitals' ); ?></a></form>
			<?php $this->render_results( $results ); ?>
		</section>
		<?php
	}

	private function render_results( array $results ) {
		if ( empty( $results ) ) {
			echo '<div class="storevitals-empty storevitals-empty-card"><span class="dashicons dashicons-search" aria-hidden="true"></span><h3>' . esc_html__( 'No matching diagnostics', 'storevitals' ) . '</h3><p>' . esc_html__( 'Try clearing the filters or using a broader search.', 'storevitals' ) . '</p></div>';
			return;
		}
		?>
		<div class="storevitals-results">
			<?php foreach ( $results as $result ) : ?>
				<article class="storevitals-result storevitals-<?php echo esc_attr( $result['status'] ); ?>"><div class="storevitals-result-indicator"><span class="storevitals-status-dot" aria-hidden="true"></span><strong><?php echo esc_html( ucfirst( $result['status'] ) ); ?></strong></div><div class="storevitals-result-body"><div class="storevitals-result-title-row"><h3><?php echo esc_html( $result['title'] ); ?></h3><?php if ( $result['count'] > 0 ) : ?><span class="storevitals-count-pill"><?php echo esc_html( number_format_i18n( $result['count'] ) ); ?></span><?php endif; ?></div><p><?php echo esc_html( $result['message'] ); ?></p><span class="storevitals-area-label"><?php echo esc_html( $this->area_label( $result['area'] ) ); ?></span></div><?php if ( $result['action_url'] && $result['action_label'] ) : ?><div class="storevitals-result-action"><a class="button" href="<?php echo esc_url( $result['action_url'] ); ?>"><?php echo esc_html( $result['action_label'] ); ?></a></div><?php endif; ?></article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private function render_compact_result( array $result ) {
		?><div class="storevitals-compact-result storevitals-<?php echo esc_attr( $result['status'] ); ?>"><span class="storevitals-status-dot" aria-hidden="true"></span><div><strong><?php echo esc_html( $result['title'] ); ?></strong><span><?php echo esc_html( $this->area_label( $result['area'] ) ); ?></span></div><?php if ( $result['count'] > 0 ) : ?><span class="storevitals-count-pill"><?php echo esc_html( number_format_i18n( $result['count'] ) ); ?></span><?php endif; ?></div><?php
	}

	private function render_history() {
		$history = $this->history->get();
		?>
		<section class="storevitals-section"><div class="storevitals-section-heading storevitals-section-heading-stacked"><div><span class="storevitals-eyebrow"><?php echo esc_html__( 'Local history', 'storevitals' ); ?></span><h2><?php echo esc_html__( 'Health score history', 'storevitals' ); ?></h2><p><?php echo esc_html__( 'StoreVitals keeps up to 30 small local summary snapshots. No customer, order, or product records are copied into history.', 'storevitals' ); ?></p></div><?php if ( ! empty( $history ) ) : ?><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=storevitals_clear_history' ), 'storevitals_clear_history' ) ); ?>" data-storevitals-confirm="<?php echo esc_attr__( 'Clear local StoreVitals scan history?', 'storevitals' ); ?>"><?php echo esc_html__( 'Clear history', 'storevitals' ); ?></a><?php endif; ?></div>
		<?php if ( empty( $history ) ) : ?><div class="storevitals-empty storevitals-empty-card"><span class="dashicons dashicons-chart-line" aria-hidden="true"></span><h3><?php echo esc_html__( 'No scan history yet', 'storevitals' ); ?></h3><p><?php echo esc_html__( 'Run a fresh scan to create the first local score snapshot.', 'storevitals' ); ?></p></div><?php else : ?><div class="storevitals-table-wrap"><table class="widefat striped storevitals-history-table"><thead><tr><th><?php echo esc_html__( 'Scanned', 'storevitals' ); ?></th><th><?php echo esc_html__( 'Score', 'storevitals' ); ?></th><th><?php echo esc_html__( 'Critical', 'storevitals' ); ?></th><th><?php echo esc_html__( 'Warnings', 'storevitals' ); ?></th><th><?php echo esc_html__( 'Passed', 'storevitals' ); ?></th><th><?php echo esc_html__( 'Score bar', 'storevitals' ); ?></th></tr></thead><tbody><?php foreach ( $history as $entry ) : ?><tr><td><?php echo esc_html( wp_date( 'M j, Y g:i a', absint( $entry['scanned_at'] ) ) ); ?></td><td><strong><?php echo esc_html( (string) absint( $entry['score'] ) ); ?></strong></td><td><?php echo esc_html( (string) absint( $entry['counts'][ Result::CRITICAL ] ?? 0 ) ); ?></td><td><?php echo esc_html( (string) absint( $entry['counts'][ Result::WARNING ] ?? 0 ) ); ?></td><td><?php echo esc_html( (string) absint( $entry['counts'][ Result::PASSED ] ?? 0 ) ); ?></td><td><progress max="100" value="<?php echo esc_attr( (string) absint( $entry['score'] ) ); ?>"><?php echo esc_html( (string) absint( $entry['score'] ) ); ?></progress></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
		<?php
	}

	private function render_reports( array $scan ) {
		$csv_url  = wp_nonce_url( admin_url( 'admin-post.php?action=storevitals_export_csv' ), 'storevitals_export_csv' );
		$json_url = wp_nonce_url( admin_url( 'admin-post.php?action=storevitals_export_json' ), 'storevitals_export_json' );
		?>
		<section class="storevitals-section"><div class="storevitals-section-heading storevitals-section-heading-stacked"><div><span class="storevitals-eyebrow"><?php echo esc_html__( 'Reports', 'storevitals' ); ?></span><h2><?php echo esc_html__( 'Export and print your health snapshot', 'storevitals' ); ?></h2><p><?php echo esc_html__( 'Exports contain diagnostic summaries and configured action URLs, not customer or order record contents.', 'storevitals' ); ?></p></div></div><div class="storevitals-report-grid"><div class="storevitals-card storevitals-report-card"><span class="dashicons dashicons-media-spreadsheet" aria-hidden="true"></span><h3><?php echo esc_html__( 'CSV report', 'storevitals' ); ?></h3><p><?php echo esc_html__( 'Spreadsheet-friendly list of all diagnostics, counts, areas, details, and action URLs.', 'storevitals' ); ?></p><a class="button button-primary" href="<?php echo esc_url( $csv_url ); ?>"><?php echo esc_html__( 'Download CSV', 'storevitals' ); ?></a></div><div class="storevitals-card storevitals-report-card"><span class="dashicons dashicons-editor-code" aria-hidden="true"></span><h3><?php echo esc_html__( 'JSON report', 'storevitals' ); ?></h3><p><?php echo esc_html__( 'Structured diagnostic snapshot for technical reviews, issue tracking, or internal tooling.', 'storevitals' ); ?></p><a class="button button-primary" href="<?php echo esc_url( $json_url ); ?>"><?php echo esc_html__( 'Download JSON', 'storevitals' ); ?></a></div><div class="storevitals-card storevitals-report-card"><span class="dashicons dashicons-printer" aria-hidden="true"></span><h3><?php echo esc_html__( 'Print report', 'storevitals' ); ?></h3><p><?php echo esc_html__( 'Use the clean print layout to save a PDF from your browser or share a review copy.', 'storevitals' ); ?></p><button type="button" class="button button-primary" data-storevitals-print><?php echo esc_html__( 'Print / Save PDF', 'storevitals' ); ?></button></div></div></section>
		<section class="storevitals-section storevitals-print-report"><div class="storevitals-section-heading"><div><span class="storevitals-eyebrow"><?php echo esc_html__( 'Report preview', 'storevitals' ); ?></span><h2><?php echo esc_html__( 'Current store health snapshot', 'storevitals' ); ?></h2></div><strong class="storevitals-report-score"><?php echo esc_html( (string) $scan['score'] . '/100' ); ?></strong></div><div class="storevitals-report-meta"><span><?php echo esc_html( sprintf( __( 'Scanned: %s', 'storevitals' ), wp_date( 'M j, Y g:i a', $scan['scanned_at'] ) ) ); ?></span><span><?php echo esc_html( sprintf( __( 'Checks: %d', 'storevitals' ), $scan['check_count'] ) ); ?></span><span><?php echo esc_html( sprintf( __( 'Duration: %d ms', 'storevitals' ), $scan['duration_ms'] ) ); ?></span></div><?php $this->render_results( $scan['results'] ); ?></section>
		<?php
	}

	private function filter_results( array $results, $allowed_areas, $status, $area, $search ) {
		return array_values( array_filter( $results, static function ( $result ) use ( $allowed_areas, $status, $area, $search ) { if ( is_array( $allowed_areas ) && ! in_array( $result['area'], $allowed_areas, true ) ) { return false; } if ( $status && $result['status'] !== $status ) { return false; } if ( $area && $result['area'] !== $area ) { return false; } if ( $search ) { $haystack = strtolower( wp_strip_all_tags( $result['title'] . ' ' . $result['message'] . ' ' . $result['area'] ) ); if ( false === strpos( $haystack, strtolower( $search ) ) ) { return false; } } return true; } ) );
	}

	private function available_areas( array $results, $allowed_areas ) {
		$areas = array();
		foreach ( $results as $result ) { if ( is_array( $allowed_areas ) && ! in_array( $result['area'], $allowed_areas, true ) ) { continue; } $areas[ $result['area'] ] = true; }
		$areas = array_keys( $areas );
		sort( $areas );
		return $areas;
	}

	private function summary_card( $count, $label, $status ) {
		?><div class="storevitals-summary-card storevitals-summary-<?php echo esc_attr( $status ); ?>"><span class="storevitals-status-dot" aria-hidden="true"></span><div><strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong><span><?php echo esc_html( $label ); ?></span></div></div><?php
	}

	private function tab_url( $view ) { return add_query_arg( array( 'page' => 'storevitals', 'view' => sanitize_key( $view ) ), admin_url( 'admin.php' ) ); }

	private function area_label( $area ) {
		$labels = array( 'products' => __( 'Products', 'storevitals' ), 'inventory' => __( 'Inventory', 'storevitals' ), 'orders' => __( 'Orders', 'storevitals' ), 'checkout' => __( 'Checkout', 'storevitals' ), 'payments' => __( 'Payments', 'storevitals' ), 'shipping' => __( 'Shipping', 'storevitals' ), 'system' => __( 'System', 'storevitals' ), 'store' => __( 'Store', 'storevitals' ) );
		return isset( $labels[ $area ] ) ? $labels[ $area ] : ucwords( str_replace( '-', ' ', $area ) );
	}

	private function score_label( $score ) { if ( $score >= 90 ) { return __( 'Excellent', 'storevitals' ); } if ( $score >= 75 ) { return __( 'Good', 'storevitals' ); } if ( $score >= 50 ) { return __( 'Needs attention', 'storevitals' ); } return __( 'Critical', 'storevitals' ); }
	private function score_tone( $score ) { if ( $score >= 90 ) { return 'excellent'; } if ( $score >= 75 ) { return 'good'; } if ( $score >= 50 ) { return 'warning'; } return 'critical'; }
	private function headline_for_score( $score ) { if ( $score >= 90 ) { return __( 'Your store is in strong shape.', 'storevitals' ); } if ( $score >= 75 ) { return __( 'Your store looks healthy with a few items to review.', 'storevitals' ); } if ( $score >= 50 ) { return __( 'A few store health signals need attention.', 'storevitals' ); } return __( 'Important store health issues need review.', 'storevitals' ); }
}
