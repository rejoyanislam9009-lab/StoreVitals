# Security

Flow Store Check 1.0 is diagnostic-first and does not automatically modify WooCommerce business data.

State-changing admin actions require the `manage_woocommerce` capability and WordPress nonce verification. Exports require the same authorization boundary. Output shown in the admin interface is escaped for its output context.

The plugin contains no telemetry, remote code loading, or external scan service.
