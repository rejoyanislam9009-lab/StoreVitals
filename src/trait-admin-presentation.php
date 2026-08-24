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
			'overview'   => array( 'dashicons-dashboard', __( 'Overview', 'flow-store-check' ) ),
			'issues'     => array( 'dashicons-list-view', __( 'Diagnostics', 'flow-store-check' ) ),
			'catalog'    => array( 'dashicons-products', __( 'Catalog', 'flow-store-check' ) ),
			'operations' => array( 'dashicons-store', __( 'Operations', 'flow-store-check' ) ),
			'system'     => array( 'dashicons-admin-tools', __( 'System', 'flow-store-check' ) ),
			'history'    => array( 'dashicons-chart-line', __( 'History', 'flow-store-check' ) ),
			'reports'    => array( 'dashicons-media-spreadsheet', __( 'Reports', 'flow-store-check' ) ),
		);
		?>
		<header class="storecheckup-app-header">
			<div class="storecheckup-brand-row">
				<div class="storecheckup-brand">
					<div class="storecheckup-brand-mark" aria-hidden="true"><span>RH</span></div>
					<div>
						<div class="storecheckup-title-line"><h1><?php echo esc_html__( 'Flow Store Check', 'flow-store-check' ); ?></h1><span class="storecheckup-version"><?php echo esc_html( 'v' . STORECHECKUP_VERSION ); ?></span></div>
					<p><?php echo esc_html__( 'Store Health Dashboard for WooCommerce', 'flow-store-check' ); ?></p>
					</div>
				</div>
				<div class="storecheckup-actions">
					<a class="button" href="<?php echo esc_url( $this->tab_url( 'reports' ) ); ?>"><?php echo esc_html__( 'Reports', 'flow-store-check' ); ?></a>
					<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=storecheckup_rescan' ), 'storecheckup_rescan' ) ); ?>"><?php echo esc_html__( 'Run fresh scan', 'flow-store-check' ); ?></a>
				</div>
			</div>
			<nav class="storecheckup-tabs" aria-label="<?php echo esc_attr__( 'Flow Store Check sections', 'flow-store-check' ); ?>">
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
		$notice_key = 'storecheckup_notice_' . get_current_user_id();
		$notice     = get_transient( $notice_key );
		if ( ! $notice ) {
			return;
		}
		delete_transient( $notice_key );
		if ( 'rescanned' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Fresh Flow Store Check scan completed.', 'flow-store-check' ) . '</p></div>';
		} elseif ( 'history_cleared' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Local Flow Store Check scan history was cleared.', 'flow-store-check' ) . '</p></div>';
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
				<span class="storecheckup-eyebrow"><?php echo esc_html__( 'Store health score', 'flow-store-check' ); ?></span>
				<div class="storecheckup-score-number"><strong><?php echo esc_html( (string) $scan['score'] ); ?></strong><span>/100</span></div>
				<strong class="storecheckup-score-label"><?php echo esc_html( $this->score_label( $scan['score'] ) ); ?></strong>
				<progress max="100" value="<?php echo esc_attr( (string) $scan['score'] ); ?>"><?php echo esc_html( (string) $scan['score'] ); ?></progress>
			</div>
			<div class="storecheckup-banner-content">
				<div>
					<span class="storecheckup-eyebrow"><?php echo esc_html__( 'Current diagnostic snapshot', 'flow-store-check' ); ?></span>
					<h2><?php echo esc_html( $this->headline_for_score( $scan['score'] ) ); ?></h2>
					<p><?php echo esc_html__( 'Flow Store Check checks high-value WooCommerce configuration, catalog, inventory, order, checkout, and system signals without modifying store data.', 'flow-store-check' ); ?></p>
				</div>
				<div class="storecheckup-summary-grid">
					<?php $this->summary_card( $scan['counts'][ Result::CRITICAL ], __( 'Critical', 'flow-store-check' ), 'critical' ); ?>
					<?php $this->summary_card( $scan['counts'][ Result::WARNING ], __( 'Warnings', 'flow-store-check' ), 'warning' ); ?>
					<?php $this->summary_card( $scan['counts'][ Result::PASSED ], __( 'Passed', 'flow-store-check' ), 'passed' ); ?>
					<?php $this->summary_card( $scan['counts'][ Result::INFO ], __( 'Info', 'flow-store-check' ), 'info' ); ?>
				</div>
			</div>
		</section>

		<section class="storecheckup-section">
			<div class="storecheckup-section-heading"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Health areas', 'flow-store-check' ); ?></span><h2><?php echo esc_html__( 'Where your store stands', 'flow-store-check' ); ?></h2></div><a href="<?php echo esc_url( $this->tab_url( 'issues' ) ); ?>"><?php echo esc_html__( 'View all diagnostics', 'flow-store-check' ); ?></a></div>
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
				<div class="storecheckup-card-heading"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Priority', 'flow-store-check' ); ?></span><h2><?php echo esc_html__( 'Needs attention', 'flow-store-check' ); ?></h2></div><span class="storecheckup-count-pill"><?php echo esc_html( (string) count( $attention ) ); ?></span></div>
				<?php if ( empty( $attention ) ) : ?>
					<div class="storecheckup-empty"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><h3><?php echo esc_html__( 'No critical issues or warnings', 'flow-store-check' ); ?></h3><p><?php echo esc_html__( 'The current bounded scan did not find any items requiring attention.', 'flow-store-check' ); ?></p></div>
				<?php else : ?>
					<div class="storecheckup-compact-results">
						<?php foreach ( array_slice( $attention, 0, 6 ) as $result ) : ?>
							<?php $this->render_compact_result( $result ); ?>
						<?php endforeach; ?>
					</div>
					<a class="button" href="<?php echo esc_url( $this->tab_url( 'issues' ) ); ?>"><?php echo esc_html__( 'Review all issues', 'flow-store-check' ); ?></a>
				<?php endif; ?>
			</div>

			<div class="storecheckup-card">
				<div class="storecheckup-card-heading"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Snapshot', 'flow-store-check' ); ?></span><h2><?php echo esc_html__( 'Scan details', 'flow-store-check' ); ?></h2></div></div>
				<dl class="storecheckup-definition-list">
					<div><dt><?php echo esc_html__( 'Checks', 'flow-store-check' ); ?></dt><dd><?php echo esc_html( (string) $scan['check_count'] ); ?></dd></div>
					<div><dt><?php echo esc_html__( 'Scan time', 'flow-store-check' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $scan['duration_ms'] ) . ' ms' ); ?></dd></div>
					<?php /* translators: 1: localized scan date/time, 2: human-readable elapsed time. */ ?>
					<div><dt><?php echo esc_html__( 'Last scan', 'flow-store-check' ); ?></dt><dd><?php echo esc_html( sprintf( __( '%1$s (%2$s ago)', 'flow-store-check' ), wp_date( 'M j, Y g:i a T', $scan['scanned_at'] ), human_time_diff( $scan['scanned_at'], time() ) ) ); ?></dd></div>
					<div><dt><?php echo esc_html__( 'Site timezone', 'flow-store-check' ); ?></dt><dd><?php echo esc_html( wp_timezone_string() ); ?></dd></div>
					<?php if ( null !== $score_delta ) : ?><div><dt><?php echo esc_html__( 'Score change', 'flow-store-check' ); ?></dt><dd><?php echo esc_html( ( $score_delta > 0 ? '+' : '' ) . (string) $score_delta ); ?></dd></div><?php endif; ?>
					<div><dt><?php echo esc_html__( 'Cache', 'flow-store-check' ); ?></dt><dd><?php echo esc_html__( '5 minutes', 'flow-store-check' ); ?></dd></div>
					<div><dt><?php echo esc_html__( 'Mode', 'flow-store-check' ); ?></dt><dd><?php echo esc_html__( 'Read-only diagnostics', 'flow-store-check' ); ?></dd></div>
				</dl>
				<p class="description"><?php echo esc_html__( 'Flow Store Check does not delete, edit, refund, cancel, or automatically repair store data.', 'flow-store-check' ); ?></p>
			</div>
		</section>
		<?php
	}

}
