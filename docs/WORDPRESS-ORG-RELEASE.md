# WordPress.org release - Flow Store Check

## Approved directory identity

- Display name: `Flow Store Check for WooCommerce`
- Plugin slug: `flow-store-check`
- WordPress.org username: `rejoyan9009`
- SVN repository: `https://plugins.svn.wordpress.org/flow-store-check`
- Public directory URL: `https://wordpress.org/plugins/flow-store-check`
- Initial release version: `1.0.0`

The WordPress.org Plugins Team approved the hosting request on August 30, 2026.

## GitHub release source

The GitHub repository remains the development source. WordPress.org SVN is treated only as a release target.

Runtime package contents are allowlisted by the release workflow:

- `flow-store-check.php`
- `uninstall.php`
- `readme.txt`
- `LICENSE`
- `assets/` (runtime CSS/JS)
- `src/`
- `languages/`

WordPress.org directory artwork is generated separately by `tools/build-wporg-assets.py` and is published to the SVN repository's top-level `assets/` directory, not the plugin runtime `assets/` directory.

## Credentials

The deployment workflow never stores an SVN password in source code. It accepts either of these GitHub Actions secrets:

- `WPORG_SVN_PASSWORD` (preferred), or
- `SVN_PASSWORD` (compatibility fallback).

The SVN username defaults to `rejoyan9009`. An optional `WPORG_SVN_USERNAME` secret can override it.

GitHub does not expose secret values through repository APIs, so secret values should never be copied into issues, commits, pull requests, logs, or documentation.

## Release trigger

The WordPress.org deployment workflow is intentionally not automatic on normal pushes. It is triggered only by creating the exact branch:

`publish/wporg-1.0.0`

Do not create that branch until the release is explicitly approved for publication.

Before any SVN write, the workflow reruns PHP lint, the official WordPress Plugin Check action, builds the installable package, generates WordPress.org assets, validates required files, verifies SVN credentials, and checks that the `1.0.0` tag does not already exist.

## SVN layout after first publication

- `/trunk` - current 1.0.0 plugin files
- `/tags/1.0.0` - immutable 1.0.0 release tag
- `/assets` - icon, banners, and screenshots used by the WordPress.org directory page

## WordPress.org assets

Expected generated files:

- `icon-128x128.png`
- `icon-256x256.png`
- `banner-772x250.png`
- `banner-1544x500.png`
- `screenshot-1.png`
- `screenshot-2.png`
- `screenshot-3.png`

The `readme.txt` Screenshots section must remain aligned with these numbered screenshot files.
