<?php
/** CSV report exporter. @package StoreVitals */
namespace StoreVitals;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Exporter {
	private $scanner;
	public function __construct( Scanner $scanner ) { $this->scanner = $scanner; }
	public function hooks() { add_action( 'admin_post_storevitals_export_csv', array( $this, 'export_csv' ) ); }
	public function export_csv() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'You do not have permission to export this report.', 'storevitals' ) ); }
		check_admin_referer( 'storevitals_export_csv' );
		$scan = $this->scanner->scan( true ); $filename = 'storevitals-' . gmdate( 'Y-m-d-His' ) . '.csv';
		nocache_headers(); header( 'Content-Type: text/csv; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		$output = fopen( 'php://output', 'w' ); if ( false === $output ) { wp_die( esc_html__( 'Could not open the CSV output stream.', 'storevitals' ) ); }
		fputcsv( $output, array( 'StoreVitals score', (int) $scan['score'] ) ); fputcsv( $output, array( 'Scanned at (UTC)', gmdate( 'Y-m-d H:i:s', (int) $scan['scanned_at'] ) ) ); fputcsv( $output, array() ); fputcsv( $output, array( 'Area', 'Status', 'Check', 'Count', 'Details' ) );
		foreach ( $scan['results'] as $result ) { fputcsv( $output, array( $result['area'], $result['status'], $result['title'], $result['count'], $result['message'] ) ); }
		fclose( $output ); exit;
	}
}
