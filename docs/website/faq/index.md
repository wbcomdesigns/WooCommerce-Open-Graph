# Frequently Asked Questions

## Do I need WooCommerce?

Yes. WooCommerce must be installed and active. Without it the plugin
deactivates itself and shows a notice.

## Do I have to configure anything?

No. The plugin works with its defaults as soon as it is active and WooCommerce
products exist. Open the settings only if you want to change which platforms
are enabled, pick a share-button style, or add a Facebook App ID or X (Twitter)
username.

## Which pages get social meta tags?

Single product pages, product category archives, product tag archives, the shop
page, and other WooCommerce pages. Schema is added on product, category, and
shop pages.

## Which platforms can shoppers share to?

Facebook, X (Twitter), LinkedIn, Pinterest, WhatsApp, email, and a copy-link
button. Each platform button appears only when that platform is enabled.

## Does it work with variable products?

Yes. Variable products get one schema offer per purchasable variation, and the
enhanced schema includes variant data.

## Can I set a different title or description for one product?

Yes. Each product has a Social Media Settings box with custom title and
description fields, and a switch to disable the plugin's output for that
product. See [Per-Product Settings](../usage/per-product-settings.md).

## Where does the social image come from?

The product's featured image, then gallery images, up to three by default. If a
product has no image, the plugin uses your configured Default Social Image, or
the WooCommerce placeholder image.

## Does it slow down the store?

The share button assets load only on product pages. Meta tags and schema are
generated inline during page rendering with per-request caching, and sitemaps
are cached and generated in the background.

## Is it translation ready?

Yes. The plugin uses the `woo-open-graph` text domain and ships a `.pot` file,
and includes RTL stylesheets.

## Does it replace my SEO plugin?

No. It adds WooCommerce-specific data that general SEO plugins tend to leave
out. It runs alongside an SEO plugin. If you see duplicate Open Graph tags, see
[Troubleshooting](../troubleshooting/index.md).
