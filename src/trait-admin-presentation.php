<?php
/**
 * Admin presentation helpers.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Admin_Presentation {
	private function render_header( array $scan, $view ) {
		$tabs = array(
			'overview'   => array( 'dashicons-dashboard', __( 'Overview', 'storecheckup' ) ),
			'issues'     => array( 'dashicons-list-view', __( 'Diagnostics', 'storecheckup' ) ),
			'catalog'    => array( 'dashicons-products', __( 'Catalog', 'storecheckup' ) ),
			'operations' => array( 'dashicons-store', __( 'Operations', 'storecheckup' ) ),
			'system'     => array( 'dashicons-admin-tools', __( 'System', 'storecheckup' ) ),
			'history'    => array( 'dashicons-chart-line', __( 'History', 'storecheckup' ) ),
			'reports'    => array( 'dashicons-media-spreadsheet', __( 'Reports', 'storecheckup' ) ),
		);
		?>
		<header class="storecheckup-app-header">
			<div class="storecheckup-brand-row">
				<div class="storecheckup-brand">
					<div class="storecheckup-brand-mark" aria-hidden="true"><span>SC</span></div>
					<div>
						<div class="storecheckup-title-line"><h1><?php echo esc_html__( 'StoreCheckup', 'storecheckup' ); ?></h1><span class="storecheckup-version"><?php echo esc_html( 'v' . STORECHECKUP_VERSION ); ?></span></div>
					<p><?php echo esc_html__( 'Store Health Dashboard for WooCommerce', 'storecheckup' ); ?></p>
					</div>
				</div>
				<div class="storecheckup-actions">
					<a class="button" href="<?php echo esc_url( $this->tab_url( 'reports' ) ); ?>"><?php echo esc_html__( 'Reports', 'storecheckup' ); ?></a>
					<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=storecheckup_rescan' ), 'storecheckup_rescan' ) ); ?>"><?php echo esc_html__( 'Run fresh scan', 'storecheckup' ); ?></a>
				</div>
			</div>
			<nav class="storecheckup-tabs" aria-label="<?php echo esc_attr__( 'StoreCheckup sections', 'storecheckup' ); ?>">
				<?php foreach ( $tabs as $id => $config ) : ?>
					<a class="storecheckup-tab <?php echo esc_attr( $view === $id ? 'is-active' : '' ); ?>" href="<?php echo esc_url( $this->tab_url( $id ) ); ?>"<?php if ( $view === $id ) : ?> aria-current="page"<?php endif; ?>>
						<span class="dashicons <?php echo esc_attr( $config[0] ); ?>" aria-hidden="true"></span>
						<span><?php echo esc_html( $config[1] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		</header>
		<?php
	}

	private function render_notices() {
		if ( isset( $_GET['rescanned'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['rescanned'] ) ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Fresh StoreCheckup scan completed.', 'storecheckup' ) . '</p></div>';
		}
		if ( isset( $_GET['history_cleared'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['history_cleared'] ) ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Local StoreCheckup scan history was cleared.', 'storecheckup' ) . '</p></div>';
		}
	}

	private function render_overview( array $scan ) {
		$history     = $this->history->get();
		$score_delta = null;
		if ( isset( $history[0]['score'], $history[1]['score'] ) ) {
			$score_delta = (int) $history[0]['score'] - (int) $history[1]['score'];
		}

		$attention = array_values(
			array_filter(
				$scan['results'],
				static function ( $result ) {
					return in_array( $result['status'], array( Result::CRITICAL, Result::WARNING ), true );
				}
			)
		);
		?>
		<section class="storecheckup-health-banner storecheckup-tone-<?php echo esc_attr( $this->score_tone( $scan['score'] ) ); ?>">
			<div class="storecheckup-score-panel">
				<span class="storecheckup-eyebrow"><?php echo esc_html__( 'Store health score', 'storecheckup' ); ?></span>
				<div class="storecheckup-score-number"><strong><?php echo esc_html( (string) $scan['score'] ); ?></strong><span>/100</span></div>
				<strong class="storecheckup-score-label"><?php echo esc_html( $this->score_label( $scan['score'] ) ); ?></strong>
				<progress max="100" value="<?php echo esc_attr( (string) $scan['score'] ); ?>"><?php echo esc_html( (string) $scan['score'] ); ?></progress>
			</div>
			<div class="storecheckup-banner-content">
				<div>
					<span class="storecheckup-eyebrow"><?php echo esc_html__( 'Current diagnostic snapshot', 'storecheckup' ); ?></span>
					<h2><?php echo esc_html( $this->headline_for_score( $scan['score'] ) ); ?></h2>
					<p><?php echo esc_html__( 'StoreCheckup checks high-value WooCommerce configuration, catalog, inventory, order, checkout, and system signals without modifying store data.', 'storecheckup' ); ?></p>
				</div>
				<div class="storecheckup-summary-grid">
					<?php $this->summary_card( $scan['counts'][ Result::CRITICAL ], __( 'Critical', 'storecheckup' ), 'critical' ); ?>
					<?php $this->summary_card( $scan['counts'][ Result::WARNING ], __( 'Warnings', 'storecheckup' ), 'warning' ); ?>
					<?php $this->summary_card( $scan['counts'][ Result::PASSED ], __( 'Passed', 'storecheckup' ), 'passed' ); ?>
					<?php $this->summary_card( $scan['counts'][ Result::INFO ], __( 'Info', 'storecheckup' ), 'info' ); ?>
				</div>
			</div>
		</section>

		<section class="storecheckup-section">
			<div class="storecheckup-section-heading"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Health areas', 'storecheckup' ); ?></span><h2><?php echo esc_html__( 'Where your store stands', 'storecheckup' ); ?></h2></div><a href="<?php echo esc_url( $this->tab_url( 'issues' ) ); ?>"><?php echo esc_html__( 'View all diagnostics', 'storecheckup' ); ?></a></div>
			<div class="storecheckup-area-grid">
				<?php foreach ( $scan['area_scores'] as $area => $score ) : ?>
					<a class="storecheckup-area-card" href="<?php echo esc_url( add_query_arg( array( 'page' => 'storecheckup', 'view' => 'issues', 'storecheckup_area' => $area ), admin_url( 'admin.php' ) ) ); ?>">
						<div><span><?php echo esc_html( $this->area_label( $area ) ); ?></span><strong><?php echo esc_html( (string) $score ); ?></strong></div>
						<progress max="100" value="<?php echo esc_attr( (string) $score ); ?>"><?php echo esc_html( (string) $score ); ?></progress>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="storecheckup-section storecheckup-two-column">
			<div class="storecheckup-card">
				<div class="storecheckup-card-heading"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Priority', 'storecheckup' ); ?></span><h2><?php echo esc_html__( 'Needs attention', 'storecheckup' ); ?></h2></div><span class="storecheckup-count-pill"><?php echo esc_html( (string) count( $attention ) ); ?></span></div>
				<?php if ( empty( $attention ) ) : ?>
					<div class="storecheckup-empty"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><h3><?php echo esc_html__( 'No critical issues or warnings', 'storecheckup' ); ?></h3><p><?php echo esc_html__( 'The current bounded scan did not find any items requiring attention.', 'storecheckup' ); ?></p></div>
				<?php else : ?>
					<div class="storecheckup-compact-results">
						<?php foreach ( array_slice( $attention, 0, 6 ) as $result ) : ?>
							<?php $this->render_compact_result( $result ); ?>
						<?php endforeach; ?>
					</div>
					<a class="button" href="<?php echo esc_url( $this->tab_url( 'issues' ) ); ?>"><?php echo esc_html__( 'Review all issues', 'storecheckup' ); ?></a>
				<?php endif; ?>
			</div>

			<div class="storecheckup-card">
				<div class="storecheckup-card-heading"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Snapshot', 'storecheckup' ); ?></span><h2><?php echo esc_html__( 'Scan details', 'storecheckup' ); ?></h2></div></div>
				<dl class="storecheckup-definition-list">
					<div><dt><?php echo esc_html__( 'Checks', 'storecheckup' ); ?></dt><dd><?php echo esc_html( (string) $scan['check_count'] ); ?></dd></div>
					<div><dt><?php echo esc_html__( 'Scan time', 'storecheckup' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $scan['duration_ms'] ) . ' ms' ); ?></dd></div>
					<div><dt><?php echo esc_html__( 'Last scan', 'storecheckup' ); ?></dt><dd><?php echo esc_html( sprintf( __( '%1$s (%2$s ago)', 'storecheckup' ), wp_date( 'M j, Y g:i a T', $scan['scanned_at'] ), human_time_diff( $scan['scanned_at'], time() ) ) ); ?></dd></div>
					<div><dt><?php echo esc_html__( 'Site timezone', 'storecheckup' ); ?></dt><dd><?php echo esc_html( wp_timezone_string() ); ?></dd></div>
					<?php if ( null !== $score_delta ) : ?><div><dt><?php echo esc_html__( 'Score change', 'storecheckup' ); ?></dt><dd><?php echo esc_html( ( $score_delta > 0 ? '+' : '' ) . (string) $score_delta ); ?></dd></div><?php endif; ?>
					<div><dt><?php echo esc_html__( 'Cache', 'storecheckup' ); ?></dt><dd><?php echo esc_html__( '5 minutes', 'storecheckup' ); ?></dd></div>
					<div><dt><?php echo esc_html__( 'Mode', 'storecheckup' ); ?></dt><dd><?php echo esc_html__( 'Read-only diagnostics', 'storecheckup' ); ?></dd></div>
				</dl>
				<p class="description"><?php echo esc_html__( 'StoreCheckup does not delete, edit, refund, cancel, or automatically repair store data.', 'storecheckup' ); ?></p>
			</div>
		</section>
		<?php
	}

}
