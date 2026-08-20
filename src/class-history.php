<?php
/**
 * Local scan history.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class History {
	const OPTION_KEY  = 'storecheckup_scan_history';
	const MAX_ENTRIES = 30;

	public function add( array $scan ) {
		if ( empty( $scan['scanned_at'] ) || ! isset( $scan['score'], $scan['counts'], $scan['area_scores'] ) ) {
			return;
		}

		$history = $this->get();
		$entry   = array(
			'scanned_at'  => absint( $scan['scanned_at'] ),
			'score'       => max( 0, min( 100, absint( $scan['score'] ) ) ),
			'counts'      => $this->sanitize_counts( $scan['counts'] ),
			'area_scores' => $this->sanitize_area_scores( $scan['area_scores'] ),
		);

		if ( ! empty( $history[0]['scanned_at'] ) && (int) $history[0]['scanned_at'] === (int) $entry['scanned_at'] ) {
			return;
		}

		array_unshift( $history, $entry );
		$history = array_slice( $history, 0, self::MAX_ENTRIES );

		if ( false === get_option( self::OPTION_KEY, false ) ) {
			add_option( self::OPTION_KEY, $history, '', 'no' );
		} else {
			update_option( self::OPTION_KEY, $history, false );
		}
	}

	public function get() {
		$history = get_option( self::OPTION_KEY, array() );
		return is_array( $history ) ? array_values( $history ) : array();
	}

	public function clear() {
		delete_option( self::OPTION_KEY );
	}

	private function sanitize_counts( $counts ) {
		$output = array();
		foreach ( array( Result::CRITICAL, Result::WARNING, Result::PASSED, Result::INFO ) as $status ) {
			$output[ $status ] = isset( $counts[ $status ] ) ? absint( $counts[ $status ] ) : 0;
		}
		return $output;
	}

	private function sanitize_area_scores( $scores ) {
		$output = array();
		if ( ! is_array( $scores ) ) {
			return $output;
		}
		foreach ( $scores as $area => $score ) {
			$output[ sanitize_key( $area ) ] = max( 0, min( 100, absint( $score ) ) );
		}
		return $output;
	}
}
