=== Flow Store Check for WooCommerce ===
Contributors: rejoyan9009
Tags: woocommerce, store health, diagnostics, inventory, checkout
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Read-only store health diagnostics for WooCommerce catalog, inventory, checkout, orders, payments, shipping, email, and system configuration.

== Description ==

Flow Store Check gives WooCommerce store managers a focused, read-only health dashboard. It highlights high-value configuration and operational signals without automatically changing products, orders, stock, payments, shipping, or customer data.

Flow Store Check is an independent extension for WooCommerce and is not affiliated with or endorsed by WooCommerce.

Current diagnostic areas include:

* Overall store health score and per-area scores.
* Product price, image, category, SKU, short-description, downloadable-file, external-product URL, and variation checks.
* Inventory low-stock, negative-stock, out-of-stock, backorder, and stock-management signals.
* Cart, Checkout, My Account, endpoint, guest-checkout, payment, and shipping configuration.
* Failed, stale on-hold, stale pending-payment, cancelled, and refunded order summaries through WooCommerce order APIs.
* High-Performance Order Storage (HPOS) detection and compatibility.
* WooCommerce transactional email and sender configuration.
* WordPress, PHP, WooCommerce, HTTPS, memory, permalink, cron, uploads, debug-display, environment, and object-cache signals.
* Failed and overdue Action Scheduler checks through its public query function when available.
* Local scan history capped at 30 small summary snapshots.
* CSV and JSON exports plus a print-friendly report.

Catalog scans are intentionally bounded to 1,000 products and 2,000 variations per request to keep normal admin requests predictable on larger stores.

Flow Store Check does not send telemetry or scan data to an external service.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the Flow Store Check ZIP through Plugins > Add New > Upload Plugin, or install it from WordPress.org after publication.
3. Activate Flow Store Check.
4. Open WooCommerce > Flow Store Check.
5. Review Overview, Diagnostics, Catalog, Operations, System, History, and Reports.
6. Use Run fresh scan when you want to bypass the short-lived local scan cache.

== Screenshots ==

1. Overview dashboard with the overall store health score, status summary, and per-area health scores.
2. Diagnostics screen with search, status and area filters, and read-only findings.
3. Reports screen with CSV, JSON, and print/PDF export options plus the current snapshot preview.

== Frequently Asked Questions ==

= Does Flow Store Check change my store data? =

No. Flow Store Check is diagnostic-first and does not automatically edit, delete, refund, cancel, change stock, or repair store data.

= Does Flow Store Check support HPOS? =

Yes. Order diagnostics use WooCommerce order APIs and the plugin declares HPOS compatibility.

= Does Flow Store Check send store data anywhere? =

No. This release contains no telemetry, analytics beacon, remote scan service, or remotely delivered executable code.

= Why are catalog scans bounded? =

Bounded scans keep admin requests predictable on larger stores. Each scan checks up to 1,000 products and 2,000 variations.

= Does the health score guarantee that a store has no problems? =

No. The score summarizes the checks included in the current release. Themes, custom code, third-party integrations, hosting infrastructure, external payment systems, and business-specific requirements may introduce issues outside Flow Store Check's scope.

== Privacy ==

Flow Store Check processes diagnostic information locally in WordPress. It does not transmit scan results, customer records, order contents, product records, or site telemetry to an external service.

The plugin stores up to 30 small local history summaries containing only scan time, health score, status counts, and area scores. Administrators can clear this history from the plugin interface, and uninstalling the plugin removes the stored history option.

CSV and JSON exports are generated only when an authorized WooCommerce manager explicitly requests them.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Renamed the public plugin identity to Flow Store Check for WooCommerce for clear distinction and trademark-safe directory presentation.
* Added responsive Store Health Dashboard with overview, diagnostics, catalog, operations, system, history, and reports sections.
* Added bounded product and variation diagnostics with product-quality and inventory signals.
* Added checkout, payment, shipping, email, order-state, HPOS, Action Scheduler, and system diagnostics.
* Added local capped scan-history summaries and score-change context.
* Added CSV, JSON, and print-friendly reports.
* Added site-timezone-aware scan timestamps and spreadsheet-safe CSV output.
* Preserved read-only behavior, capability checks, nonce protection, and no external telemetry.
