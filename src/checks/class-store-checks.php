<?php
/** Store configuration checks. @package StoreVitals */
namespace StoreVitals\Checks;
use StoreVitals\Result;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Store_Checks {
	public function run() {
		$results = array( $this->currency_check() );
		$results = array_merge( $results, $this->page_checks() );
		$results[] = $this->payment_check();
		$results[] = $this->shipping_check();
		$results[] = $this->email_sender_check();
		return $results;
	}
	private function currency_check() {
		$currency = get_woocommerce_currency();
		if ( $currency ) { return new Result( 'currency', 'store', Result::PASSED, __( 'Store currency is configured', 'storevitals' ), sprintf( __( 'Current store currency: %s.', 'storevitals' ), $currency ) ); }
		return new Result( 'currency', 'store', Result::CRITICAL, __( 'Store currency is missing', 'storevitals' ), __( 'WooCommerce needs a store currency for product prices and checkout.', 'storevitals' ), 1, admin_url( 'admin.php?page=wc-settings' ), __( 'Open WooCommerce settings', 'storevitals' ) );
	}
	private function page_checks() {
		$pages = array( 'cart' => array( 'woocommerce_cart_page_id', __( 'Cart page', 'storevitals' ) ), 'checkout' => array( 'woocommerce_checkout_page_id', __( 'Checkout page', 'storevitals' ) ), 'account' => array( 'woocommerce_myaccount_page_id', __( 'My account page', 'storevitals' ) ) );
		$results = array();
		foreach ( $pages as $id => $config ) {
			$page_id = absint( get_option( $config[0] ) ); $page = $page_id ? get_post( $page_id ) : null;
			if ( $page && 'publish' === $page->post_status ) { $results[] = new Result( 'page-' . $id, 'checkout', Result::PASSED, sprintf( __( '%s is published', 'storevitals' ), $config[1] ), sprintf( __( 'Page ID %d is assigned and published.', 'storevitals' ), $page_id ) ); }
			else { $results[] = new Result( 'page-' . $id, 'checkout', Result::CRITICAL, sprintf( __( '%s needs attention', 'storevitals' ), $config[1] ), __( 'The assigned page is missing or not published.', 'storevitals' ), 1, admin_url( 'admin.php?page=wc-settings&tab=advanced' ), __( 'Review page setup', 'storevitals' ) ); }
		}
		return $results;
	}
	private function payment_check() {
		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array(); $enabled = 0;
		foreach ( $gateways as $gateway ) { if ( isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) { ++$enabled; } }
		if ( $enabled ) { return new Result( 'payments', 'payments', Result::PASSED, __( 'At least one payment gateway is enabled', 'storevitals' ), sprintf( _n( '%d gateway is enabled.', '%d gateways are enabled.', $enabled, 'storevitals' ), $enabled ), $enabled, admin_url( 'admin.php?page=wc-settings&tab=checkout' ), __( 'Review payments', 'storevitals' ) ); }
		return new Result( 'payments', 'payments', Result::WARNING, __( 'No payment gateway is enabled', 'storevitals' ), __( 'This can be intentional for free-only stores, but most stores need at least one enabled payment method.', 'storevitals' ), 0, admin_url( 'admin.php?page=wc-settings&tab=checkout' ), __( 'Configure payments', 'storevitals' ) );
	}
	private function shipping_check() {
		$enabled = 0;
		if ( class_exists( 'WC_Shipping_Zones' ) ) {
			foreach ( \WC_Shipping_Zones::get_zones() as $zone ) { foreach ( $zone['shipping_methods'] as $method ) { if ( isset( $method->enabled ) && 'yes' === $method->enabled ) { ++$enabled; } } }
			foreach ( ( new \WC_Shipping_Zone( 0 ) )->get_shipping_methods() as $method ) { if ( isset( $method->enabled ) && 'yes' === $method->enabled ) { ++$enabled; } }
		}
		if ( $enabled ) { return new Result( 'shipping', 'shipping', Result::PASSED, __( 'Shipping methods are configured', 'storevitals' ), sprintf( _n( '%d enabled shipping method was found.', '%d enabled shipping methods were found.', $enabled, 'storevitals' ), $enabled ), $enabled, admin_url( 'admin.php?page=wc-settings&tab=shipping' ), __( 'Review shipping', 'storevitals' ) ); }
		return new Result( 'shipping', 'shipping', Result::WARNING, __( 'No enabled shipping method was found', 'storevitals' ), __( 'This is expected for stores that sell only virtual products. Physical-product stores should review shipping zones.', 'storevitals' ), 0, admin_url( 'admin.php?page=wc-settings&tab=shipping' ), __( 'Review shipping', 'storevitals' ) );
	}
	private function email_sender_check() {
		$name = trim( (string) get_option( 'woocommerce_email_from_name' ) ); $email = trim( (string) get_option( 'woocommerce_email_from_address' ) );
		if ( $name && is_email( $email ) ) { return new Result( 'email-sender', 'store', Result::PASSED, __( 'WooCommerce email sender is configured', 'storevitals' ), sprintf( __( 'Sender: %1$s <%2$s>.', 'storevitals' ), $name, $email ) ); }
		return new Result( 'email-sender', 'store', Result::WARNING, __( 'WooCommerce email sender needs review', 'storevitals' ), __( 'Set a valid sender name and email address for transactional messages.', 'storevitals' ), 1, admin_url( 'admin.php?page=wc-settings&tab=email' ), __( 'Review emails', 'storevitals' ) );
	}
}
