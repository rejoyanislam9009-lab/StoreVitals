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

trait Admin_Reports {
	private function render_history() {
		$history = $this->history->get();
		?>
		<section class="storecheckup-section">
			<div class="storecheckup-section-heading storecheckup-section-heading-stacked"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Local history', 'storecheckup' ); ?></span><h2><?php echo esc_html__( 'Health score history', 'storecheckup' ); ?></h2><p><?php echo esc_html__( 'StoreCheckup keeps up to 30 small local summary snapshots. No customer, order, or product records are copied into history.', 'storecheckup' ); ?></p></div><?php if ( ! empty( $history ) ) : ?><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=storecheckup_clear_history' ), 'storecheckup_clear_history' ) ); ?>" data-storecheckup-confirm="<?php echo esc_attr__( 'Clear local StoreCheckup scan history?', 'storecheckup' ); ?>"><?php echo esc_html__( 'Clear history', 'storecheckup' ); ?></a><?php endif; ?></div>
			<?php if ( empty( $history ) ) : ?>
				<div class="storecheckup-empty storecheckup-empty-card"><span class="dashicons dashicons-chart-line" aria-hidden="true"></span><h3><?php echo esc_html__( 'No scan history yet', 'storecheckup' ); ?></h3><p><?php echo esc_html__( 'Run a fresh scan to create the first local score snapshot.', 'storecheckup' ); ?></p></div>
			<?php else : ?>
				<div class="storecheckup-table-wrap"><table class="widefat striped storecheckup-history-table"><thead><tr><th><?php echo esc_html__( 'Scanned', 'storecheckup' ); ?></th><th><?php echo esc_html__( 'Score', 'storecheckup' ); ?></th><th><?php echo esc_html__( 'Critical', 'storecheckup' ); ?></th><th><?php echo esc_html__( 'Warnings', 'storecheckup' ); ?></th><th><?php echo esc_html__( 'Passed', 'storecheckup' ); ?></th><th><?php echo esc_html__( 'Score bar', 'storecheckup' ); ?></th></tr></thead><tbody>
				<?php foreach ( $history as $entry ) : ?>
					<tr><td><?php echo esc_html( wp_date( 'M j, Y g:i a T', absint( $entry['scanned_at'] ) ) ); ?></td><td><strong><?php echo esc_html( (string) absint( $entry['score'] ) ); ?></strong></td><td><?php echo esc_html( (string) absint( $entry['counts'][ Result::CRITICAL ] ?? 0 ) ); ?></td><td><?php echo esc_html( (string) absint( $entry['counts'][ Result::WARNING ] ?? 0 ) ); ?></td><td><?php echo esc_html( (string) absint( $entry['counts'][ Result::PASSED ] ?? 0 ) ); ?></td><td><progress max="100" value="<?php echo esc_attr( (string) absint( $entry['score'] ) ); ?>"><?php echo esc_html( (string) absint( $entry['score'] ) ); ?></progress></td></tr>
				<?php endforeach; ?>
				</tbody></table></div>
			<?php endif; ?>
		</section>
		<?php
	}

	private function render_reports( array $scan ) {
		$csv_url  = wp_nonce_url( admin_url( 'admin-post.php?action=storecheckup_export_csv' ), 'storecheckup_export_csv' );
		$json_url = wp_nonce_url( admin_url( 'admin-post.php?action=storecheckup_export_json' ), 'storecheckup_export_json' );
		?>
		<section class="storecheckup-section">
			<div class="storecheckup-section-heading storecheckup-section-heading-stacked"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Reports', 'storecheckup' ); ?></span><h2><?php echo esc_html__( 'Export and print your health snapshot', 'storecheckup' ); ?></h2><p><?php echo esc_html__( 'Exports contain diagnostic summaries and configured action URLs, not customer or order record contents.', 'storecheckup' ); ?></p></div></div>
			<div class="storecheckup-report-grid">
				<div class="storecheckup-card storecheckup-report-card"><span class="dashicons dashicons-media-spreadsheet" aria-hidden="true"></span><h3><?php echo esc_html__( 'CSV report', 'storecheckup' ); ?></h3><p><?php echo esc_html__( 'Spreadsheet-friendly list of all diagnostics, counts, areas, details, and action URLs.', 'storecheckup' ); ?></p><a class="button button-primary" href="<?php echo esc_url( $csv_url ); ?>"><?php echo esc_html__( 'Download CSV', 'storecheckup' ); ?></a></div>
				<div class="storecheckup-card storecheckup-report-card"><span class="dashicons dashicons-editor-code" aria-hidden="true"></span><h3><?php echo esc_html__( 'JSON report', 'storecheckup' ); ?></h3><p><?php echo esc_html__( 'Structured diagnostic snapshot for technical reviews, issue tracking, or internal tooling.', 'storecheckup' ); ?></p><a class="button button-primary" href="<?php echo esc_url( $json_url ); ?>"><?php echo esc_html__( 'Download JSON', 'storecheckup' ); ?></a></div>
				<div class="storecheckup-card storecheckup-report-card"><span class="dashicons dashicons-printer" aria-hidden="true"></span><h3><?php echo esc_html__( 'Print report', 'storecheckup' ); ?></h3><p><?php echo esc_html__( 'Use the clean print layout to save a PDF from your browser or share a review copy.', 'storecheckup' ); ?></p><button type="button" class="button button-primary" data-storecheckup-print><?php echo esc_html__( 'Print / Save PDF', 'storecheckup' ); ?></button></div>
			</div>
		</section>
		<section class="storecheckup-section storecheckup-print-report">
			<div class="storecheckup-section-heading"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Report preview', 'storecheckup' ); ?></span><h2><?php echo esc_html__( 'Current store health snapshot', 'storecheckup' ); ?></h2></div><strong class="storecheckup-report-score"><?php echo esc_html( (string) $scan['score'] . '/100' ); ?></strong></div>
			<div class="storecheckup-report-meta"><span><?php echo esc_html( sprintf( __( 'Scanned: %s', 'storecheckup' ), wp_date( 'M j, Y g:i a T', $scan['scanned_at'] ) ) ); ?></span><span><?php echo esc_html( sprintf( __( 'Checks: %d', 'storecheckup' ), $scan['check_count'] ) ); ?></span><span><?php echo esc_html( sprintf( __( 'Duration: %d ms', 'storecheckup' ), $scan['duration_ms'] ) ); ?></span></div>
			<?php $this->render_results( $scan['results'] ); ?>
		</section>
		<?php
	}

}
