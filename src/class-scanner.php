<?php
/**
 * Scanner coordinator.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Scanner {
	const CACHE_TTL = 300;

	private $history;

	public function __construct( History $history ) {
		$this->history = $history;
	}

	public function scan( $force = false ) {
		$key = $this->cache_key();

		if ( ! $force ) {
			$cached = get_transient( $key );
			if ( is_array( $cached ) && isset( $cached['results'], $cached['score'] ) ) {
				return $cached;
			}
		}

		$started = microtime( true );
		$results = array();
		$checks  = array(
			new Checks\System_Checks(),
			new Checks\Store_Checks(),
			new Checks\Product_Checks(),
			new Checks\Order_Checks(),
		);

		foreach ( $checks as $group ) {
			foreach ( $group->run() as $result ) {
				if ( $result instanceof Result ) {
					$results[] = $result->to_array();
				}
			}
		}

		$payload = array(
			'score'        => $this->score( $results ),
			'counts'       => $this->status_counts( $results ),
			'area_scores'  => $this->area_scores( $results ),
			'results'      => $results,
			'scanned_at'   => time(),
			'duration_ms'  => max( 1, (int) round( ( microtime( true ) - $started ) * 1000 ) ),
			'check_count'  => count( $results ),
			'plugin_version' => STORECHECKUP_VERSION,
		);

		set_transient( $key, $payload, self::CACHE_TTL );
		$this->history->add( $payload );

		return $payload;
	}

	public function clear_cache() {
		delete_transient( $this->cache_key() );
	}

	private function cache_key() {
		return 'storecheckup_scan_' . get_current_blog_id() . '_' . get_current_user_id();
	}

	private function score( array $results ) {
		$score = 100;

		foreach ( $results as $result ) {
			$count = isset( $result['count'] ) ? absint( $result['count'] ) : 0;
			if ( Result::CRITICAL === $result['status'] ) {
				$score -= 12 + min( 6, $count );
			} elseif ( Result::WARNING === $result['status'] ) {
				$score -= 4 + min( 3, $count );
			}
		}

		return max( 0, min( 100, $score ) );
	}

	private function status_counts( array $results ) {
		$counts = array(
			Result::CRITICAL => 0,
			Result::WARNING  => 0,
			Result::PASSED   => 0,
			Result::INFO     => 0,
		);

		foreach ( $results as $result ) {
			if ( isset( $counts[ $result['status'] ] ) ) {
				++$counts[ $result['status'] ];
			}
		}
		return $counts;
	}

	private function area_scores( array $results ) {
		$areas = array();
		foreach ( $results as $result ) {
			$area = $result['area'];
			if ( ! isset( $areas[ $area ] ) ) {
				$areas[ $area ] = 100;
			}
			$count = isset( $result['count'] ) ? absint( $result['count'] ) : 0;
			if ( Result::CRITICAL === $result['status'] ) {
				$areas[ $area ] -= 18 + min( 8, $count );
			} elseif ( Result::WARNING === $result['status'] ) {
				$areas[ $area ] -= 7 + min( 4, $count );
			}
		}

		foreach ( $areas as $area => $score ) {
			$areas[ $area ] = max( 0, min( 100, $score ) );
		}
		return $areas;
	}
}
