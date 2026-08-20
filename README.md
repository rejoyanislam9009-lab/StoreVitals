# StoreCheckup

StoreCheckup is a read-only WooCommerce store health and diagnostics plugin prepared for WordPress.org review.

## Release candidate 1.0.0

The release candidate provides a responsive Store Health Dashboard covering catalog, inventory, checkout, payments, shipping, orders, email configuration, HPOS, Action Scheduler, and WordPress/PHP/WooCommerce environment signals.

### Safety model

- No automatic product, order, stock, payment, shipping, or customer-data mutations.
- No telemetry or external scan service.
- Admin mutations such as rescanning, clearing local history, and exports require WooCommerce-management capability and WordPress nonces.
- Catalog work is bounded to 1,000 products and 2,000 variations per scan.
- Local history contains summary scores/counts only and is capped at 30 entries.

### Compatibility target

- WordPress 6.9+
- PHP 7.4+
- WooCommerce 10.8+
- Tested on WordPress 7.1 and WooCommerce 11.0.1 during development.
- HPOS compatibility declared.

## Release workflow

GitHub is the development source. The WordPress.org ZIP is built from an allowlisted runtime package and excludes development-only files. GitHub Actions run PHP syntax validation and the official WordPress Plugin Check action on release branches and pull requests.

## License

GPL-2.0-or-later.
