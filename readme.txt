=== StoreVitals - Store Health for WooCommerce ===
Contributors: rejoyan9009
Tags: woocommerce, store health, diagnostics, products, checkout
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Read-only WooCommerce health diagnostics for products, checkout, orders, payments, shipping, and system configuration.

== Description ==
StoreVitals gives WooCommerce store managers a focused health dashboard without changing products, orders, checkout data, or store settings.

Version 1.0 includes an overall health score, product and inventory checks, Cart/Checkout/My Account page checks, payment and shipping checks, recent order diagnostics, HPOS status, HTTPS/permalink/memory checks, failed Scheduled Actions checks, email sender checks, CSV export, and bounded local caching.

No telemetry or external scan service is included. Product scanning is capped at 1,000 products per request to keep admin requests bounded.

== Installation ==
1. Install and activate WooCommerce.
2. Install the StoreVitals ZIP.
3. Activate StoreVitals.
4. Open WooCommerce > StoreVitals.

== Frequently Asked Questions ==
= Does StoreVitals modify my products or orders? =
No. Version 1.0 is diagnostic-first and read-only for WooCommerce business data.

= Does it send store data to an external service? =
No.

= Does it support HPOS? =
Yes. StoreVitals uses WooCommerce order APIs and declares HPOS compatibility.

== Changelog ==
= 1.0.0 =
* Initial diagnostic release.
