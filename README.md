# StoreVitals

StoreVitals is a read-only WooCommerce store health dashboard. It surfaces bounded catalog, inventory, checkout, payment, shipping, order, scheduled-action, email, and WordPress environment diagnostics without automatically changing store data.

## Development release

**1.1.0** expands the initial diagnostic engine with a responsive application header, health banner, section navigation, deeper store checks, local scan history, JSON export, and print-friendly reporting.

## Main areas

- Overview health score and area scores
- Product and variation diagnostics
- Inventory signals
- Checkout and account configuration
- Payments and shipping
- HPOS-safe order status summaries
- Action Scheduler signals
- WooCommerce email configuration
- WordPress/PHP/WooCommerce environment checks
- Local score history (30 summary snapshots maximum)
- CSV, JSON, and print reports

## Safety model

StoreVitals 1.1 is diagnostic-first. It does not automatically edit products, change stock, alter orders, issue refunds, change payment settings, or repair configuration. It sends no scan telemetry to an external service.

Catalog scans are intentionally bounded to 1,000 products and 2,000 variations per request.

## Requirements

- WordPress 6.9+
- PHP 7.4+
- WooCommerce 10.8+

## Development workflow

GitHub is the development source. WordPress.org should be treated as the stable distribution channel only after the final public brand/slug and release package pass the project review gates.

## License

GPL-2.0-or-later.
