<?php
/**
 * Diagnostic report exporters.
 *
 * @package StoreVitals
 */

namespace StoreVitals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Exporter {
	private $scanner;

	public function __construct( Scanner $scanner ) {
		$this->scanner = $scanner;
	}

	public function hooks() {
		add_action( 'admin_post_storevitals_export_csv', array( $this, 'export_csv' ) );
		add_action( 'admin_post_storevitals_export_json', array( $this, 'export_json' ) );
	}

	public function export_csv() {
		$this->authorize( 'storevitals_export_csv' );
		$scan     = $this->scanner->scan();
		$filename = 'storevitals-' . gmdate( 'Y-m-d-His' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Could not open the CSV output stream.', 'storevitals' ) );
		}

		fputcsv( $output, array( 'StoreVitals score', (int) $scan['score'] ) );
		fputcsv( $output, array( 'Scanned at (UTC)', gmdate( 'Y-m-d H:i:s', (int) $scan['scanned_at'] ) ) );
		fputcsv( $output, array( 'Checks', (int) $scan['check_count'] ) );
		fputcsv( $output, array( 'Scan duration (ms)', (int) $scan['duration_ms'] ) );
		fputcsv( $output, array() );
		fputcsv( $output, array( 'Area', 'Status', 'Check', 'Count', 'Details', 'Action URL' ) );
		foreach ( $scan['results'] as $result ) {
			fputcsv( $output, array( $result['area'], $result['status'], $result['title'], $result['count'], $result['message'], $result['action_url'] ) );
		}
		fclose( $output );
		exit;
	}

	public function export_json() {
		$this->authorize( 'storevitals_export_json' );
		$scan     = $this->scanner->scan();
		$filename = 'storevitals-' . gmdate( 'Y-m-d-His' ) . '.json';
		$payload  = array(
			'generated_at_utc' => gmdate( 'c' ),
			'plugin_version'   => STOREVITALS_VERSION,
			'score'            => (int) $scan['score'],
			'counts'           => $scan['counts'],
			'area_scores'      => $scan['area_scores'],
			'check_count'      => (int) $scan['check_count'],
			'duration_ms'      => (int) $scan['duration_ms'],
			'results'          => $scan['results'],
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	private function authorize( $nonce_action ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export this report.', 'storevitals' ) );
		}
		check_admin_referer( $nonce_action );
	}
}
