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

trait Admin_Diagnostics {
	private function render_issue_view( array $scan, $allowed_areas, $title, $description ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only diagnostic filter; no state is changed.
		$status_filter = isset( $_GET['storecheckup_status'] ) ? sanitize_key( wp_unslash( $_GET['storecheckup_status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only diagnostic filter; no state is changed.
		$area_filter = isset( $_GET['storecheckup_area'] ) ? sanitize_key( wp_unslash( $_GET['storecheckup_area'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only diagnostic search; no state is changed.
		$search  = isset( $_GET['storecheckup_search'] ) ? sanitize_text_field( wp_unslash( $_GET['storecheckup_search'] ) ) : '';
		$results = $this->filter_results( $scan['results'], $allowed_areas, $status_filter, $area_filter, $search );
		$areas   = $this->available_areas( $scan['results'], $allowed_areas );
		/* translators: %d: number of diagnostic results shown after filtering. */
		$result_count_label = sprintf( _n( '%d result', '%d results', count( $results ), 'storecheckup' ), count( $results ) );
		?>
		<section class="storecheckup-section">
			<div class="storecheckup-section-heading storecheckup-section-heading-stacked"><div><span class="storecheckup-eyebrow"><?php echo esc_html__( 'Diagnostics', 'storecheckup' ); ?></span><h2><?php echo esc_html( $title ); ?></h2><p><?php echo esc_html( $description ); ?></p></div><span class="storecheckup-count-pill"><?php echo esc_html( $result_count_label ); ?></span></div>
			<form class="storecheckup-filterbar" method="get">
				<input type="hidden" name="page" value="storecheckup">
				<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter; no state is changed. ?>
				<input type="hidden" name="view" value="<?php echo esc_attr( isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'issues' ); ?>">
				<label><span class="screen-reader-text"><?php echo esc_html__( 'Search diagnostics', 'storecheckup' ); ?></span><input type="search" name="storecheckup_search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr__( 'Search diagnostics...', 'storecheckup' ); ?>"></label>
				<label><span class="screen-reader-text"><?php echo esc_html__( 'Filter by status', 'storecheckup' ); ?></span><select name="storecheckup_status"><option value=""><?php echo esc_html__( 'All statuses', 'storecheckup' ); ?></option><?php foreach ( array( Result::CRITICAL, Result::WARNING, Result::PASSED, Result::INFO ) as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status_filter, $status ); ?>><?php echo esc_html( ucfirst( $status ) ); ?></option><?php endforeach; ?></select></label>
				<label><span class="screen-reader-text"><?php echo esc_html__( 'Filter by area', 'storecheckup' ); ?></span><select name="storecheckup_area"><option value=""><?php echo esc_html__( 'All areas', 'storecheckup' ); ?></option><?php foreach ( $areas as $area ) : ?><option value="<?php echo esc_attr( $area ); ?>" <?php selected( $area_filter, $area ); ?>><?php echo esc_html( $this->area_label( $area ) ); ?></option><?php endforeach; ?></select></label>
				<button class="button button-primary" type="submit"><?php echo esc_html__( 'Filter', 'storecheckup' ); ?></button>
				<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter; no state is changed. ?>
				<a class="button" href="<?php echo esc_url( $this->tab_url( isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'issues' ) ); ?>"><?php echo esc_html__( 'Reset', 'storecheckup' ); ?></a>
			</form>
			<?php $this->render_results( $results ); ?>
		</section>
		<?php
	}

	private function render_results( array $results ) {
		if ( empty( $results ) ) {
			?>
			<div class="storecheckup-empty storecheckup-empty-card"><span class="dashicons dashicons-search" aria-hidden="true"></span><h3><?php echo esc_html__( 'No matching diagnostics', 'storecheckup' ); ?></h3><p><?php echo esc_html__( 'Try clearing the filters or using a broader search.', 'storecheckup' ); ?></p></div>
			<?php
			return;
		}
		?>
		<div class="storecheckup-results">
			<?php foreach ( $results as $result ) : ?>
				<article class="storecheckup-result storecheckup-<?php echo esc_attr( $result['status'] ); ?>">
					<div class="storecheckup-result-indicator"><span class="storecheckup-status-dot" aria-hidden="true"></span><strong><?php echo esc_html( ucfirst( $result['status'] ) ); ?></strong></div>
					<div class="storecheckup-result-body">
						<div class="storecheckup-result-title-row"><h3><?php echo esc_html( $result['title'] ); ?></h3><?php if ( $result['count'] > 0 ) : ?><span class="storecheckup-count-pill"><?php echo esc_html( number_format_i18n( $result['count'] ) ); ?></span><?php endif; ?></div>
						<p><?php echo esc_html( $result['message'] ); ?></p>
						<span class="storecheckup-area-label"><?php echo esc_html( $this->area_label( $result['area'] ) ); ?></span>
					</div>
					<?php if ( $result['action_url'] && $result['action_label'] ) : ?><div class="storecheckup-result-action"><a class="button" href="<?php echo esc_url( $result['action_url'] ); ?>"><?php echo esc_html( $result['action_label'] ); ?></a></div><?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private function render_compact_result( array $result ) {
		?>
		<div class="storecheckup-compact-result storecheckup-<?php echo esc_attr( $result['status'] ); ?>">
			<span class="storecheckup-status-dot" aria-hidden="true"></span>
			<div><strong><?php echo esc_html( $result['title'] ); ?></strong><span><?php echo esc_html( $this->area_label( $result['area'] ) ); ?></span></div>
			<?php if ( $result['count'] > 0 ) : ?><span class="storecheckup-count-pill"><?php echo esc_html( number_format_i18n( $result['count'] ) ); ?></span><?php endif; ?>
		</div>
		<?php
	}

}
