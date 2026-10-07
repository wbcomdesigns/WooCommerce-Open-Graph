# Requirements

The plugin declares the following minimums in its main file header and readme.

| Requirement | Value |
|---|---|
| WordPress | 5.0 or later |
| PHP | 7.4 or later |
| WooCommerce | 4.0 or later (required) |
| Tested up to (WordPress) | 6.9 |
| Tested up to (WooCommerce) | 8.5 |
| Plugin version | 2.0.2 |

## WooCommerce is a hard dependency

WooCommerce must be installed and active. The plugin checks for the
`WooCommerce` class:

- On activation, if WooCommerce is not active the plugin deactivates itself and
  shows an error explaining that WooCommerce is required.
- On every load, if WooCommerce is absent the plugin shows an admin notice with
  a link to install WooCommerce and does not register any of its features.

## WooCommerce feature compatibility

The plugin declares compatibility with two WooCommerce features through
`FeaturesUtil::declare_compatibility()`:

- High-Performance Order Storage (`custom_order_tables`).
- Cart and Checkout Blocks (`cart_checkout_blocks`).
