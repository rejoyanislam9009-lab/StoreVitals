=== StoreVitals - Store Health for WooCommerce ===
Contributors: rejoyan9009
Tags: woocommerce, store health, diagnostics, products, inventory
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Read-only WooCommerce store health diagnostics with a responsive dashboard, catalog checks, operational checks, local score history, and exportable reports.

== Description ==

StoreVitals provides a bounded, read-only health snapshot for WooCommerce stores. It highlights configuration and operational signals without automatically modifying products, orders, inventory, payments, shipping, or customer data.

Current diagnostic areas include:

* Store health score and area scores.
* Product price, image, category, SKU, short-description, downloadable-file, external-product URL, and variation checks.
* Inventory low-stock, negative-stock, out-of-stock, backorder, and stock-management signals.
* Cart, Checkout, My Account, endpoint, guest checkout, payment, and shipping configuration.
* Failed, stale on-hold, stale pending-payment, cancelled, and refunded order summaries using WooCommerce order APIs.
* HPOS detection and compatibility declaration.
* WooCommerce transactional email and sender configuration.
* WordPress/PHP/WooCommerce version, HTTPS, memory, permalinks, cron, uploads, debug display, environment, and object-cache signals.
* Failed and overdue Action Scheduler checks through the public Action Scheduler query function when available.
* Local scan history capped at 30 small summary snapshots.
* CSV and JSON exports plus a print-friendly report.

StoreVitals keeps scans bounded to protect normal admin requests. Catalog scans are capped at 1,000 products and 2,000 variations per request.

StoreVitals does not send telemetry or scan data to an external service.

== Installation ==

1. Upload the `storevitals` folder to `/wp-content/plugins/` or install the plugin ZIP through Plugins > Add New > Upload Plugin.
2. Activate StoreVitals. WooCommerce must already be active.
3. Open WooCommerce > StoreVitals.
4. Review the Overview dashboard or use the Diagnostics, Catalog, Operations, System, History, and Reports tabs.
5. Use Run fresh scan when you want to bypass the short-lived scan cache.

== Frequently Asked Questions ==

= Does StoreVitals change my store data? =

No. Version 1.1 is diagnostic-first and does not automatically edit, delete, refund, cancel, or repair store data.

= Does StoreVitals work with HPOS? =

StoreVitals uses WooCommerce order APIs for order queries and declares HPOS compatibility.

= Does StoreVitals send store data anywhere? =

No external telemetry or scan-data transmission is included.

= Why are catalog scans bounded? =

Admin requests should remain predictable on larger stores. StoreVitals caps each catalog scan at 1,000 products and 2,000 variations in this release.

== Changelog ==

= 1.1.0 =
* Added responsive application header and section navigation.
* Added redesigned responsive health banner and overview.
* Added deeper catalog and variation diagnostics.
* Added low-stock, negative-stock, backorder, and stock-management signals.
* Added stale pending/on-hold order diagnostics and order-status summaries.
* Added checkout endpoint, base location, transactional email, tax, coupon, and guest checkout signals.
* Added PHP, WordPress, cron, uploads, debug-display, environment, and object-cache checks.
* Added overdue Action Scheduler diagnostic.
* Added local capped scan-history summaries.
* Added JSON export and print-friendly reporting.
* Preserved read-only behavior and bounded scan limits.

= 1.0.0 =
* Initial development release with store health scoring, product checks, checkout checks, payment/shipping checks, order diagnostics, HPOS detection, CSV export, and responsive admin UI.
