<?php
/** HPOS-safe order checks. @package StoreVitals */
namespace StoreVitals\Checks;
use StoreVitals\Result;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Order_Checks {
	public function run() { return array( $this->failed_orders_check(), $this->on_hold_orders_check(), $this->scheduled_actions_check() ); }
	private function failed_orders_check() {
		$count = $this->order_count( 'wc-failed', 30 ); $url = admin_url( 'admin.php?page=wc-orders&status=wc-failed' );
		if ( $count ) { return new Result( 'failed-orders', 'orders', Result::WARNING, __( 'Failed orders in the last 30 days', 'storevitals' ), sprintf( _n( '%d failed order was found in the last 30 days.', '%d failed orders were found in the last 30 days.', $count, 'storevitals' ), $count ), $count, $url, __( 'Review orders', 'storevitals' ) ); }
		return new Result( 'failed-orders', 'orders', Result::PASSED, __( 'No recent failed orders found', 'storevitals' ), __( 'No failed orders were found in the last 30 days.', 'storevitals' ), 0, $url, __( 'Open orders', 'storevitals' ) );
	}
	private function on_hold_orders_check() {
		$count = $this->order_count( 'wc-on-hold', 30 );
		return new Result( 'on-hold-orders', 'orders', Result::INFO, __( 'On-hold orders in the last 30 days', 'storevitals' ), sprintf( _n( '%d on-hold order was found.', '%d on-hold orders were found.', $count, 'storevitals' ), $count ), $count, admin_url( 'admin.php?page=wc-orders&status=wc-on-hold' ), __( 'Review orders', 'storevitals' ) );
	}
	private function order_count( $status, $days ) {
		$query = wc_get_orders( array( 'status'=>$status, 'date_created'=>'>' . gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS * absint( $days ) ), 'limit'=>1, 'paginate'=>true, 'return'=>'ids' ) );
		return isset( $query->total ) ? absint( $query->total ) : 0;
	}
	private function scheduled_actions_check() {
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) { return new Result( 'scheduled-actions', 'system', Result::INFO, __( 'Scheduled Actions API unavailable', 'storevitals' ), __( 'Action Scheduler was not available through its public query function.', 'storevitals' ) ); }
		$failed = as_get_scheduled_actions( array( 'status'=>'failed', 'per_page'=>20, 'orderby'=>'date', 'order'=>'DESC' ), 'ids' ); $count = is_array( $failed ) ? count( $failed ) : 0; $url = admin_url( 'admin.php?page=wc-status&tab=action-scheduler&s=failed' );
		if ( $count ) { return new Result( 'scheduled-actions', 'system', Result::WARNING, __( 'Failed scheduled actions were found', 'storevitals' ), sprintf( __( 'The bounded query found %d recent failed actions (up to 20 shown).', 'storevitals' ), $count ), $count, $url, __( 'Open Scheduled Actions', 'storevitals' ) ); }
		return new Result( 'scheduled-actions', 'system', Result::PASSED, __( 'No failed scheduled actions found', 'storevitals' ), __( 'No failed actions were returned by the bounded Action Scheduler query.', 'storevitals' ), 0, $url, __( 'Open Scheduled Actions', 'storevitals' ) );
	}
}
