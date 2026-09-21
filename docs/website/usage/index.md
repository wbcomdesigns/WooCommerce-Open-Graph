# Using the Plugin

Most stores need no configuration. After activation the plugin already emits
meta tags and schema, shows share buttons, and serves the sitemap using its
defaults. This section covers what you can change.

## Where to configure

- Store-wide options live on one settings screen at WooCommerce and then
  Social Media. See [Settings Reference](settings.md).
- Per-product overrides live in a Social Media Settings box on the product edit
  screen. See [Per-Product Settings](per-product-settings.md).

## Verifying your output

Share a product URL through a platform's own preview tool to confirm the tags
are read correctly. The settings sidebar links to:

- Facebook Sharing Debugger
- X (Twitter) Card Validator
- Google Rich Results Test
- LinkedIn Post Inspector

You can also view a product page's source and look for the block between the
`Woo Open Graph Meta Tags` comments, and the JSON-LD `<script>` blocks.

## Access

All admin screens require the `manage_woocommerce` capability. Per-product
overrides require the ability to edit that product.
