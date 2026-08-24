<?php
/**
 * HPOS-safe order and background task checks.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup\Checks;

use StoreCheckup\Result;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Order_Checks {
	public function run() {
		return array(
			$this->failed_orders_check(),
			$this->stale_on_hold_orders_check(),
			$this->stale_pending_orders_check(),
			$this->cancelled_orders_check(),
			$this->refunded_orders_check(),
			$this->scheduled_actions_check(),
			$this->overdue_actions_check(),
		);
	}

	private function failed_orders_check() {
		$count = $this->order_count_since( 'wc-failed', 30 );
		$url   = admin_url( 'admin.php?page=wc-orders&status=wc-failed' );
		if ( $count ) {
			/* translators: %d: number of failed orders found in the last 30 days. */
			return new Result( 'failed-orders', 'orders', Result::WARNING, __( 'Failed orders in the last 30 days', 'flow-store-check' ), sprintf( _n( '%d failed order was found in the last 30 days.', '%d failed orders were found in the last 30 days.', $count, 'flow-store-check' ), $count ), $count, $url, __( 'Review orders', 'flow-store-check' ) );
		}
		return new Result( 'failed-orders', 'orders', Result::PASSED, __( 'No recent failed orders found', 'flow-store-check' ), __( 'No failed orders were found in the last 30 days.', 'flow-store-check' ), 0, $url, __( 'Open orders', 'flow-store-check' ) );
	}

	private function stale_on_hold_orders_check() {
		$count = $this->order_count_older_than( 'wc-on-hold', 7 );
		$url   = admin_url( 'admin.php?page=wc-orders&status=wc-on-hold' );
		if ( $count ) {
			/* translators: %d: number of on-hold orders older than seven days. */
			return new Result( 'stale-on-hold-orders', 'orders', Result::WARNING, __( 'On-hold orders older than 7 days', 'flow-store-check' ), sprintf( _n( '%d on-hold order is older than 7 days.', '%d on-hold orders are older than 7 days.', $count, 'flow-store-check' ), $count ), $count, $url, __( 'Review orders', 'flow-store-check' ) );
		}
		return new Result( 'stale-on-hold-orders', 'orders', Result::PASSED, __( 'No stale on-hold orders found', 'flow-store-check' ), __( 'No on-hold orders older than 7 days were found.', 'flow-store-check' ), 0, $url, __( 'Open orders', 'flow-store-check' ) );
	}

	private function stale_pending_orders_check() {
		$count = $this->order_count_older_than( 'wc-pending', 1 );
		$url   = admin_url( 'admin.php?page=wc-orders&status=wc-pending' );
		if ( $count ) {
			/* translators: %d: number of pending-payment orders older than 24 hours. */
			return new Result( 'stale-pending-orders', 'orders', Result::WARNING, __( 'Pending-payment orders older than 24 hours', 'flow-store-check' ), sprintf( _n( '%d pending-payment order is older than 24 hours.', '%d pending-payment orders are older than 24 hours.', $count, 'flow-store-check' ), $count ), $count, $url, __( 'Review orders', 'flow-store-check' ) );
		}
		return new Result( 'stale-pending-orders', 'orders', Result::PASSED, __( 'No stale pending-payment orders found', 'flow-store-check' ), __( 'No pending-payment orders older than 24 hours were found.', 'flow-store-check' ), 0, $url, __( 'Open orders', 'flow-store-check' ) );
	}

	private function cancelled_orders_check() {
		$count = $this->order_count_since( 'wc-cancelled', 30 );
		/* translators: %d: number of cancelled orders found in the last 30 days. */
		return new Result( 'cancelled-orders', 'orders', Result::INFO, __( 'Cancelled orders in the last 30 days', 'flow-store-check' ), sprintf( _n( '%d cancelled order was found.', '%d cancelled orders were found.', $count, 'flow-store-check' ), $count ), $count, admin_url( 'admin.php?page=wc-orders&status=wc-cancelled' ), __( 'Review orders', 'flow-store-check' ) );
	}

	private function refunded_orders_check() {
		$count = $this->order_count_since( 'wc-refunded', 30 );
		/* translators: %d: number of refunded orders found in the last 30 days. */
		return new Result( 'refunded-orders', 'orders', Result::INFO, __( 'Refunded orders in the last 30 days', 'flow-store-check' ), sprintf( _n( '%d refunded order was found.', '%d refunded orders were found.', $count, 'flow-store-check' ), $count ), $count, admin_url( 'admin.php?page=wc-orders&status=wc-refunded' ), __( 'Review orders', 'flow-store-check' ) );
	}

	private function order_count_since( $status, $days ) {
		$query = wc_get_orders(
			array(
				'status'       => $status,
				'date_created' => '>' . gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * absint( $days ) ),
				'limit'        => 1,
				'paginate'     => true,
				'return'       => 'ids',
			)
		);
		return isset( $query->total ) ? absint( $query->total ) : 0;
	}

	private function order_count_older_than( $status, $days ) {
		$query = wc_get_orders(
			array(
				'status'       => $status,
				'date_created' => '<' . gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * absint( $days ) ),
				'limit'        => 1,
				'paginate'     => true,
				'return'       => 'ids',
			)
		);
		return isset( $query->total ) ? absint( $query->total ) : 0;
	}

	private function scheduled_actions_check() {
		$url = admin_url( 'admin.php?page=wc-status&tab=action-scheduler' );
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return new Result( 'scheduled-actions', 'system', Result::INFO, __( 'Scheduled Actions API unavailable', 'flow-store-check' ), __( 'Action Scheduler was not available through its public query function.', 'flow-store-check' ) );
		}

		$failed = as_get_scheduled_actions(
			array(
				'status'   => 'failed',
				'per_page' => 50,
				'orderby'  => 'date',
				'order'    => 'DESC',
			),
			'ids'
		);
		$count = is_array( $failed ) ? count( $failed ) : 0;
		if ( $count ) {
			/* translators: %d: number of failed Action Scheduler actions returned by the bounded query. */
			return new Result( 'scheduled-actions', 'system', Result::WARNING, __( 'Failed scheduled actions were found', 'flow-store-check' ), sprintf( __( 'The bounded query found %d failed actions (up to 50 returned).', 'flow-store-check' ), $count ), $count, $url, __( 'Open Scheduled Actions', 'flow-store-check' ) );
		}
		return new Result( 'scheduled-actions', 'system', Result::PASSED, __( 'No failed scheduled actions found', 'flow-store-check' ), __( 'No failed actions were returned by the bounded Action Scheduler query.', 'flow-store-check' ), 0, $url, __( 'Open Scheduled Actions', 'flow-store-check' ) );
	}

	private function overdue_actions_check() {
		$url = admin_url( 'admin.php?page=wc-status&tab=action-scheduler' );
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return new Result( 'overdue-actions', 'system', Result::INFO, __( 'Overdue action check unavailable', 'flow-store-check' ), __( 'Action Scheduler was not available through its public query function.', 'flow-store-check' ) );
		}

		$actions = as_get_scheduled_actions(
			array(
				'status'       => 'pending',
				'date'         => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
				'date_compare' => '<',
				'per_page'     => 50,
				'orderby'      => 'date',
				'order'        => 'ASC',
			),
			'ids'
		);
		$count = is_array( $actions ) ? count( $actions ) : 0;
		if ( $count ) {
			/* translators: %d: number of overdue pending Action Scheduler actions returned by the bounded query. */
			return new Result( 'overdue-actions', 'system', Result::WARNING, __( 'Overdue pending actions were found', 'flow-store-check' ), sprintf( __( 'The bounded query found %d pending actions scheduled more than one hour ago (up to 50 returned).', 'flow-store-check' ), $count ), $count, $url, __( 'Open Scheduled Actions', 'flow-store-check' ) );
		}
		return new Result( 'overdue-actions', 'system', Result::PASSED, __( 'No overdue pending actions found', 'flow-store-check' ), __( 'No pending actions more than one hour overdue were returned by the bounded query.', 'flow-store-check' ), 0, $url, __( 'Open Scheduled Actions', 'flow-store-check' ) );
	}
}
