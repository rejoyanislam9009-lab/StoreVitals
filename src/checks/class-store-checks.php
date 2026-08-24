<?php
/**
 * Store configuration checks.
 *
 * @package StoreCheckup
 */

namespace StoreCheckup\Checks;

use StoreCheckup\Result;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Store_Checks {
	public function run() {
		$results   = array();
		$results[] = $this->currency_check();
		$results[] = $this->base_location_check();
		$results   = array_merge( $results, $this->page_checks() );
		$results[] = $this->checkout_endpoints_check();
		$results[] = $this->payment_check();
		$results[] = $this->shipping_check();
		$results[] = $this->email_sender_check();
		$results[] = $this->transactional_email_check();
		$results[] = $this->stock_management_check();
		$results[] = $this->tax_check();
		$results[] = $this->coupon_check();
		$results[] = $this->guest_checkout_check();
		return $results;
	}

	private function currency_check() {
		$currency = get_woocommerce_currency();
		if ( $currency ) {
			/* translators: %s: WooCommerce currency code, for example USD. */
			return new Result( 'currency', 'store', Result::PASSED, __( 'Store currency is configured', 'rejoyan-store-health' ), sprintf( __( 'Current store currency: %s.', 'rejoyan-store-health' ), $currency ) );
		}
		return new Result( 'currency', 'store', Result::CRITICAL, __( 'Store currency is missing', 'rejoyan-store-health' ), __( 'WooCommerce needs a store currency for product prices and checkout.', 'rejoyan-store-health' ), 1, admin_url( 'admin.php?page=wc-settings' ), __( 'Open WooCommerce settings', 'rejoyan-store-health' ) );
	}

	private function base_location_check() {
		$country = WC()->countries ? WC()->countries->get_base_country() : '';
		$city    = trim( (string) get_option( 'woocommerce_store_city' ) );
		if ( $country && $city ) {
			/* translators: 1: store city, 2: store country code. */
			return new Result( 'base-location', 'store', Result::PASSED, __( 'Store base location is configured', 'rejoyan-store-health' ), sprintf( __( 'Base location: %1$s, %2$s.', 'rejoyan-store-health' ), $city, $country ) );
		}
		return new Result( 'base-location', 'store', Result::WARNING, __( 'Store base location needs review', 'rejoyan-store-health' ), __( 'A complete base location helps WooCommerce calculate taxes, shipping, and store defaults consistently.', 'rejoyan-store-health' ), 1, admin_url( 'admin.php?page=wc-settings' ), __( 'Review store address', 'rejoyan-store-health' ) );
	}

	private function page_checks() {
		$pages = array(
			'cart'     => array( 'woocommerce_cart_page_id', __( 'Cart page', 'rejoyan-store-health' ) ),
			'checkout' => array( 'woocommerce_checkout_page_id', __( 'Checkout page', 'rejoyan-store-health' ) ),
			'account'  => array( 'woocommerce_myaccount_page_id', __( 'My account page', 'rejoyan-store-health' ) ),
		);
		$results = array();

		foreach ( $pages as $id => $config ) {
			$page_id = absint( get_option( $config[0] ) );
			$page    = $page_id ? get_post( $page_id ) : null;
			if ( $page && 'publish' === $page->post_status ) {
				/* translators: 1: WooCommerce page label, 2: WordPress page ID. */
				$results[] = new Result( 'page-' . $id, 'checkout', Result::PASSED, sprintf( __( '%s is published', 'rejoyan-store-health' ), $config[1] ), sprintf( __( 'Page ID %d is assigned and published.', 'rejoyan-store-health' ), $page_id ) );
			} else {
				/* translators: %s: WooCommerce page label, such as Cart or Checkout page. */
				$results[] = new Result( 'page-' . $id, 'checkout', Result::CRITICAL, sprintf( __( '%s needs attention', 'rejoyan-store-health' ), $config[1] ), __( 'The assigned page is missing or not published.', 'rejoyan-store-health' ), 1, admin_url( 'admin.php?page=wc-settings&tab=advanced' ), __( 'Review page setup', 'rejoyan-store-health' ) );
			}
		}
		return $results;
	}

	private function checkout_endpoints_check() {
		$options = array(
			'woocommerce_checkout_pay_endpoint',
			'woocommerce_checkout_order_received_endpoint',
			'woocommerce_myaccount_orders_endpoint',
			'woocommerce_myaccount_view_order_endpoint',
			'woocommerce_myaccount_edit_account_endpoint',
			'woocommerce_myaccount_customer_logout_endpoint',
		);
		$missing = 0;
		foreach ( $options as $option ) {
			if ( '' === trim( (string) get_option( $option ) ) ) {
				++$missing;
			}
		}
		if ( 0 === $missing ) {
			return new Result( 'checkout-endpoints', 'checkout', Result::PASSED, __( 'Checkout and account endpoints are configured', 'rejoyan-store-health' ), __( 'Core WooCommerce checkout and account endpoint slugs are present.', 'rejoyan-store-health' ) );
		}
		/* translators: %d: number of missing core checkout/account endpoint slugs. */
		return new Result( 'checkout-endpoints', 'checkout', Result::WARNING, __( 'Checkout or account endpoints need review', 'rejoyan-store-health' ), sprintf( _n( '%d core endpoint slug is empty.', '%d core endpoint slugs are empty.', $missing, 'rejoyan-store-health' ), $missing ), $missing, admin_url( 'admin.php?page=wc-settings&tab=advanced' ), __( 'Review endpoints', 'rejoyan-store-health' ) );
	}

	private function payment_check() {
		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
		$enabled  = 0;
		foreach ( $gateways as $gateway ) {
			if ( isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) {
				++$enabled;
			}
		}
		if ( $enabled ) {
			/* translators: %d: number of enabled payment gateways. */
			return new Result( 'payments', 'payments', Result::PASSED, __( 'Payment gateways are enabled', 'rejoyan-store-health' ), sprintf( _n( '%d gateway is enabled.', '%d gateways are enabled.', $enabled, 'rejoyan-store-health' ), $enabled ), $enabled, admin_url( 'admin.php?page=wc-settings&tab=checkout' ), __( 'Review payments', 'rejoyan-store-health' ) );
		}
		return new Result( 'payments', 'payments', Result::WARNING, __( 'No payment gateway is enabled', 'rejoyan-store-health' ), __( 'This can be intentional for free-only stores, but most stores need at least one enabled payment method.', 'rejoyan-store-health' ), 0, admin_url( 'admin.php?page=wc-settings&tab=checkout' ), __( 'Configure payments', 'rejoyan-store-health' ) );
	}

	private function shipping_check() {
		$enabled = 0;
		if ( class_exists( 'WC_Shipping_Zones' ) ) {
			foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
				foreach ( $zone['shipping_methods'] as $method ) {
					if ( isset( $method->enabled ) && 'yes' === $method->enabled ) {
						++$enabled;
					}
				}
			}
			foreach ( ( new \WC_Shipping_Zone( 0 ) )->get_shipping_methods() as $method ) {
				if ( isset( $method->enabled ) && 'yes' === $method->enabled ) {
					++$enabled;
				}
			}
		}
		if ( $enabled ) {
			/* translators: %d: number of enabled shipping methods. */
			return new Result( 'shipping', 'shipping', Result::PASSED, __( 'Shipping methods are configured', 'rejoyan-store-health' ), sprintf( _n( '%d enabled shipping method was found.', '%d enabled shipping methods were found.', $enabled, 'rejoyan-store-health' ), $enabled ), $enabled, admin_url( 'admin.php?page=wc-settings&tab=shipping' ), __( 'Review shipping', 'rejoyan-store-health' ) );
		}
		return new Result( 'shipping', 'shipping', Result::WARNING, __( 'No enabled shipping method was found', 'rejoyan-store-health' ), __( 'This is expected for stores that sell only virtual products. Physical-product stores should review shipping zones.', 'rejoyan-store-health' ), 0, admin_url( 'admin.php?page=wc-settings&tab=shipping' ), __( 'Review shipping', 'rejoyan-store-health' ) );
	}

	private function email_sender_check() {
		$name  = trim( (string) get_option( 'woocommerce_email_from_name' ) );
		$email = trim( (string) get_option( 'woocommerce_email_from_address' ) );
		if ( $name && is_email( $email ) ) {
			/* translators: 1: WooCommerce email sender name, 2: WooCommerce sender email address. */
			return new Result( 'email-sender', 'store', Result::PASSED, __( 'WooCommerce email sender is configured', 'rejoyan-store-health' ), sprintf( __( 'Sender: %1$s <%2$s>.', 'rejoyan-store-health' ), $name, $email ) );
		}
		return new Result( 'email-sender', 'store', Result::WARNING, __( 'WooCommerce email sender needs review', 'rejoyan-store-health' ), __( 'Set a valid sender name and email address for transactional messages.', 'rejoyan-store-health' ), 1, admin_url( 'admin.php?page=wc-settings&tab=email' ), __( 'Review emails', 'rejoyan-store-health' ) );
	}

	private function transactional_email_check() {
		$mailer = WC()->mailer();
		$emails = $mailer ? $mailer->get_emails() : array();
		$total  = 0;
		$enabled = 0;
		$new_order_enabled = null;

		foreach ( $emails as $email ) {
			++$total;
			$is_enabled = method_exists( $email, 'is_enabled' ) ? $email->is_enabled() : ( isset( $email->enabled ) && 'yes' === $email->enabled );
			if ( $is_enabled ) {
				++$enabled;
			}
			if ( isset( $email->id ) && 'new_order' === $email->id ) {
				$new_order_enabled = (bool) $is_enabled;
			}
		}

		$url = admin_url( 'admin.php?page=wc-settings&tab=email' );
		if ( $total > 0 && 0 === $enabled ) {
			return new Result( 'transactional-emails', 'store', Result::WARNING, __( 'All WooCommerce emails appear disabled', 'rejoyan-store-health' ), __( 'Customers and store managers may miss important transactional notifications.', 'rejoyan-store-health' ), $total, $url, __( 'Review emails', 'rejoyan-store-health' ) );
		}
		if ( false === $new_order_enabled ) {
			return new Result( 'transactional-emails', 'store', Result::WARNING, __( 'New order email is disabled', 'rejoyan-store-health' ), __( 'The store manager new-order notification is currently disabled.', 'rejoyan-store-health' ), 1, $url, __( 'Review emails', 'rejoyan-store-health' ) );
		}
		/* translators: 1: number of enabled WooCommerce email notifications, 2: total WooCommerce email notifications. */
		return new Result( 'transactional-emails', 'store', Result::PASSED, __( 'Transactional email configuration looks active', 'rejoyan-store-health' ), sprintf( __( '%1$d of %2$d WooCommerce email notifications are enabled.', 'rejoyan-store-health' ), $enabled, $total ), $enabled, $url, __( 'Review emails', 'rejoyan-store-health' ) );
	}

	private function stock_management_check() {
		$enabled = 'yes' === get_option( 'woocommerce_manage_stock', 'yes' );
		return new Result( 'stock-management', 'inventory', Result::INFO, $enabled ? __( 'Global stock management is enabled', 'rejoyan-store-health' ) : __( 'Global stock management is disabled', 'rejoyan-store-health' ), $enabled ? __( 'WooCommerce can manage stock quantities for products that opt into stock management.', 'rejoyan-store-health' ) : __( 'This can be intentional when inventory is managed externally or stock quantities are not tracked.', 'rejoyan-store-health' ), 0, admin_url( 'admin.php?page=wc-settings&tab=products&section=inventory' ), __( 'Review inventory settings', 'rejoyan-store-health' ) );
	}

	private function tax_check() {
		$enabled = wc_tax_enabled();
		return new Result( 'taxes', 'store', Result::INFO, $enabled ? __( 'Taxes are enabled', 'rejoyan-store-health' ) : __( 'Taxes are disabled', 'rejoyan-store-health' ), $enabled ? __( 'WooCommerce tax calculations are enabled for this store.', 'rejoyan-store-health' ) : __( 'Tax calculations are disabled. This can be correct depending on the business and tax setup.', 'rejoyan-store-health' ), 0, admin_url( 'admin.php?page=wc-settings&tab=tax' ), __( 'Review tax settings', 'rejoyan-store-health' ) );
	}

	private function coupon_check() {
		$enabled = wc_coupons_enabled();
		return new Result( 'coupons', 'store', Result::INFO, $enabled ? __( 'Coupons are enabled', 'rejoyan-store-health' ) : __( 'Coupons are disabled', 'rejoyan-store-health' ), $enabled ? __( 'Coupon functionality is available at checkout.', 'rejoyan-store-health' ) : __( 'Coupon functionality is disabled for this store.', 'rejoyan-store-health' ) );
	}

	private function guest_checkout_check() {
		$enabled = 'yes' === get_option( 'woocommerce_enable_guest_checkout', 'yes' );
		return new Result( 'guest-checkout', 'checkout', Result::INFO, $enabled ? __( 'Guest checkout is enabled', 'rejoyan-store-health' ) : __( 'Guest checkout is disabled', 'rejoyan-store-health' ), $enabled ? __( 'Customers can place orders without creating an account.', 'rejoyan-store-health' ) : __( 'Customers must use or create an account to complete checkout.', 'rejoyan-store-health' ), 0, admin_url( 'admin.php?page=wc-settings&tab=account' ), __( 'Review account settings', 'rejoyan-store-health' ) );
	}
}
