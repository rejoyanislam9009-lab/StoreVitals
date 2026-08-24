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

trait Admin_Helpers {
	private function filter_results( array $results, $allowed_areas, $status, $area, $search ) {
		return array_values(
			array_filter(
				$results,
				static function ( $result ) use ( $allowed_areas, $status, $area, $search ) {
					if ( is_array( $allowed_areas ) && ! in_array( $result['area'], $allowed_areas, true ) ) {
						return false;
					}
					if ( $status && $result['status'] !== $status ) {
						return false;
					}
					if ( $area && $result['area'] !== $area ) {
						return false;
					}
					if ( $search ) {
						$haystack = strtolower( wp_strip_all_tags( $result['title'] . ' ' . $result['message'] . ' ' . $result['area'] ) );
						if ( false === strpos( $haystack, strtolower( $search ) ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);
	}

	private function available_areas( array $results, $allowed_areas ) {
		$areas = array();
		foreach ( $results as $result ) {
			if ( is_array( $allowed_areas ) && ! in_array( $result['area'], $allowed_areas, true ) ) {
				continue;
			}
			$areas[ $result['area'] ] = true;
		}
		$areas = array_keys( $areas );
		sort( $areas );
		return $areas;
	}

	private function summary_card( $count, $label, $status ) {
		?><div class="storecheckup-summary-card storecheckup-summary-<?php echo esc_attr( $status ); ?>"><span class="storecheckup-status-dot" aria-hidden="true"></span><div><strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong><span><?php echo esc_html( $label ); ?></span></div></div><?php
	}

	private function tab_url( $view ) {
		return add_query_arg( array( 'page' => 'storecheckup', 'view' => sanitize_key( $view ) ), admin_url( 'admin.php' ) );
	}

	private function area_label( $area ) {
		$labels = array(
			'products'  => __( 'Products', 'flow-store-check' ),
			'inventory' => __( 'Inventory', 'flow-store-check' ),
			'orders'    => __( 'Orders', 'flow-store-check' ),
			'checkout'  => __( 'Checkout', 'flow-store-check' ),
			'payments'  => __( 'Payments', 'flow-store-check' ),
			'shipping'  => __( 'Shipping', 'flow-store-check' ),
			'system'    => __( 'System', 'flow-store-check' ),
			'store'     => __( 'Store', 'flow-store-check' ),
		);
		return isset( $labels[ $area ] ) ? $labels[ $area ] : ucwords( str_replace( '-', ' ', $area ) );
	}

	private function score_label( $score ) {
		if ( $score >= 90 ) {
			return __( 'Excellent', 'flow-store-check' );
		}
		if ( $score >= 75 ) {
			return __( 'Good', 'flow-store-check' );
		}
		if ( $score >= 50 ) {
			return __( 'Needs attention', 'flow-store-check' );
		}
		return __( 'Critical', 'flow-store-check' );
	}

	private function score_tone( $score ) {
		if ( $score >= 90 ) {
			return 'excellent';
		}
		if ( $score >= 75 ) {
			return 'good';
		}
		if ( $score >= 50 ) {
			return 'warning';
		}
		return 'critical';
	}

	private function headline_for_score( $score ) {
		if ( $score >= 90 ) {
			return __( 'Your store is in strong shape.', 'flow-store-check' );
		}
		if ( $score >= 75 ) {
			return __( 'Your store looks healthy with a few items to review.', 'flow-store-check' );
		}
		if ( $score >= 50 ) {
			return __( 'A few store health signals need attention.', 'flow-store-check' );
		}
		return __( 'Important store health issues need review.', 'flow-store-check' );
	}
}
