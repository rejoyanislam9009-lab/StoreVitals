<?php
/**
 * Diagnostic report exporters.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Exporter {
	private $scanner;

	public function __construct( Scanner $scanner ) {
		$this->scanner = $scanner;
	}

	public function hooks() {
		add_action( 'admin_post_storecheckup_export_csv', array( $this, 'export_csv' ) );
		add_action( 'admin_post_storecheckup_export_json', array( $this, 'export_json' ) );
	}

	public function export_csv() {
		$this->authorize( 'storecheckup_export_csv' );
		$scan     = $this->scanner->scan();
		$filename = 'flow-store-check-' . gmdate( 'Y-m-d-His' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Could not open the CSV output stream.', 'flow-store-check' ) );
		}

		$this->write_csv_row( $output, array( 'Flow Store Check score', (int) $scan['score'] ) );
		$this->write_csv_row( $output, array( 'Scanned at (UTC)', gmdate( 'Y-m-d H:i:s', (int) $scan['scanned_at'] ) ) );
		$this->write_csv_row( $output, array( 'Checks', (int) $scan['check_count'] ) );
		$this->write_csv_row( $output, array( 'Scan duration (ms)', (int) $scan['duration_ms'] ) );
		$this->write_csv_row( $output, array() );
		$this->write_csv_row( $output, array( 'Area', 'Status', 'Check', 'Count', 'Details', 'Action URL' ) );
		foreach ( $scan['results'] as $result ) {
			$this->write_csv_row( $output, array( $result['area'], $result['status'], $result['title'], $result['count'], $result['message'], $result['action_url'] ) );
		}
		exit;
	}

	public function export_json() {
		$this->authorize( 'storecheckup_export_json' );
		$scan     = $this->scanner->scan();
		$filename = 'flow-store-check-' . gmdate( 'Y-m-d-His' ) . '.json';
		$payload  = array(
			'generated_at_utc' => gmdate( 'c' ),
			'plugin_version'   => STORECHECKUP_VERSION,
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
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This endpoint intentionally returns JSON, not HTML.
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/**
	 * Write a spreadsheet-safe CSV row.
	 *
	 * @param resource $output CSV output stream.
	 * @param array    $cells  Row values.
	 */
	private function write_csv_row( $output, array $cells ) {
		$safe = array_map(
			static function ( $value ) {
				$value = (string) $value;
				if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@' ), true ) ) {
					$value = "'" . $value;
				}
				return $value;
			},
			$cells
		);
		fputcsv( $output, $safe );
	}

	private function authorize( $nonce_action ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export this report.', 'flow-store-check' ) );
		}
		check_admin_referer( $nonce_action );
	}
}
