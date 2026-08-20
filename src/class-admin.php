<?php
/** Admin dashboard. @package StoreVitals */
namespace StoreVitals;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Admin {
	private $scanner; private $hook_suffix = '';
	public function __construct( Scanner $scanner ) { $this->scanner = $scanner; }
	public function hooks() { add_action( 'admin_menu', array( $this, 'menu' ), 60 ); add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) ); add_action( 'admin_post_storevitals_rescan', array( $this, 'rescan' ) ); }
	public function menu() { $this->hook_suffix = add_submenu_page( 'woocommerce', __( 'StoreVitals', 'storevitals' ), __( 'StoreVitals', 'storevitals' ), 'manage_woocommerce', 'storevitals', array( $this, 'render' ) ); }
	public function assets( $hook ) { if ( $hook === $this->hook_suffix ) { wp_enqueue_style( 'storevitals-admin', STOREVITALS_URL . 'assets/admin.css', array(), STOREVITALS_VERSION ); } }
	public function rescan() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'You do not have permission to run this scan.', 'storevitals' ) ); }
		check_admin_referer( 'storevitals_rescan' ); $this->scanner->clear_cache(); wp_safe_redirect( admin_url( 'admin.php?page=storevitals&rescanned=1' ) ); exit;
	}
	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
		$scan = $this->scanner->scan();
		$status_filter = isset( $_GET['sv_status'] ) ? sanitize_key( wp_unslash( $_GET['sv_status'] ) ) : '';
		$area_filter = isset( $_GET['sv_area'] ) ? sanitize_key( wp_unslash( $_GET['sv_area'] ) ) : '';
		$results = array_filter( $scan['results'], static function( $r ) use ( $status_filter, $area_filter ) { return ( ! $status_filter || $r['status'] === $status_filter ) && ( ! $area_filter || $r['area'] === $area_filter ); } );
		$areas = array_keys( $scan['area_scores'] ); sort( $areas );
		?>
		<div class="wrap storevitals-wrap">
		<div class="storevitals-header"><div><h1><?php echo esc_html__( 'StoreVitals', 'storevitals' ); ?></h1><p><?php echo esc_html__( 'Read-only health diagnostics for your WooCommerce store.', 'storevitals' ); ?></p></div><div class="storevitals-actions"><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=storevitals_export_csv' ), 'storevitals_export_csv' ) ); ?>"><?php echo esc_html__( 'Export CSV', 'storevitals' ); ?></a><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=storevitals_rescan' ), 'storevitals_rescan' ) ); ?>"><?php echo esc_html__( 'Run fresh scan', 'storevitals' ); ?></a></div></div>
		<div class="storevitals-hero"><div class="storevitals-score"><strong><?php echo esc_html( (string) $scan['score'] ); ?></strong><span>/100</span><small><?php echo esc_html( $this->score_label( $scan['score'] ) ); ?></small></div><div class="storevitals-summary"><?php foreach ( array( Result::CRITICAL=>'Critical', Result::WARNING=>'Warnings', Result::PASSED=>'Passed', Result::INFO=>'Info' ) as $key=>$label ) : ?><div><strong><?php echo esc_html( (string) $scan['counts'][$key] ); ?></strong><span><?php echo esc_html( $label ); ?></span></div><?php endforeach; ?></div></div>
		<div class="storevitals-areas"><?php foreach ( $scan['area_scores'] as $area=>$score ) : ?><a href="<?php echo esc_url( add_query_arg( array( 'page'=>'storevitals', 'sv_area'=>$area ), admin_url( 'admin.php' ) ) ); ?>"><span><?php echo esc_html( ucwords( str_replace( '-', ' ', $area ) ) ); ?></span><strong><?php echo esc_html( (string) $score ); ?></strong></a><?php endforeach; ?></div>
		<form class="storevitals-filters" method="get"><input type="hidden" name="page" value="storevitals"><select name="sv_status"><option value=""><?php echo esc_html__( 'All statuses', 'storevitals' ); ?></option><?php foreach ( array(Result::CRITICAL,Result::WARNING,Result::PASSED,Result::INFO) as $status ) : ?><option value="<?php echo esc_attr($status); ?>" <?php selected($status_filter,$status); ?>><?php echo esc_html( ucfirst($status) ); ?></option><?php endforeach; ?></select><select name="sv_area"><option value=""><?php echo esc_html__( 'All areas', 'storevitals' ); ?></option><?php foreach ( $areas as $area ) : ?><option value="<?php echo esc_attr($area); ?>" <?php selected($area_filter,$area); ?>><?php echo esc_html( ucwords(str_replace('-',' ',$area)) ); ?></option><?php endforeach; ?></select><button class="button" type="submit"><?php echo esc_html__( 'Filter', 'storevitals' ); ?></button><a class="button-link" href="<?php echo esc_url( admin_url('admin.php?page=storevitals') ); ?>"><?php echo esc_html__( 'Clear', 'storevitals' ); ?></a></form>
		<div class="storevitals-results"><?php foreach ( $results as $result ) : ?><article class="storevitals-result storevitals-<?php echo esc_attr($result['status']); ?>"><div class="storevitals-result-status"><?php echo esc_html( ucfirst($result['status']) ); ?></div><div class="storevitals-result-body"><h2><?php echo esc_html($result['title']); ?></h2><p><?php echo esc_html($result['message']); ?></p><span class="storevitals-area-label"><?php echo esc_html( ucwords(str_replace('-',' ',$result['area'])) ); ?></span></div><?php if ( $result['action_url'] && $result['action_label'] ) : ?><a class="button" href="<?php echo esc_url($result['action_url']); ?>"><?php echo esc_html($result['action_label']); ?></a><?php endif; ?></article><?php endforeach; ?></div>
		<p class="description storevitals-footnote"><?php echo esc_html( sprintf( __( 'Last scan: %s. Results are cached for five minutes and are diagnostic guidance, not a guarantee that every store issue has been detected.', 'storevitals' ), wp_date( 'Y-m-d H:i:s', $scan['scanned_at'] ) ) ); ?></p></div><?php
	}
	private function score_label( $score ) { if ( $score >= 90 ) return __( 'Excellent','storevitals' ); if ( $score >= 75 ) return __( 'Good','storevitals' ); if ( $score >= 50 ) return __( 'Needs attention','storevitals' ); return __( 'Critical','storevitals' ); }
}
