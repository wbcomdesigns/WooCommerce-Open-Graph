# Open Graph and Twitter Cards

The plugin writes social meta tags into the page `<head>` on the
`wp_head` hook (priority 15). It also adds the Open Graph namespace prefixes to
the `<html>` tag through the `language_attributes` filter.

## Where tags are added

Tags are emitted on these page types:

- Single product pages
- Product category archives
- Product tag archives
- The shop page
- Any other WooCommerce page

On a single product, the source data comes from the WooCommerce product. On
category, tag, and shop pages the plugin uses the term or page title,
description, and a fallback image.

## Basic Open Graph tags

Emitted on every supported page:

- `og:title`
- `og:description`
- `og:type` (`product` on a product page, `website` elsewhere)
- `og:url`
- `og:site_name`
- `og:image`, plus `og:image:secure_url`, `og:image:width`,
  `og:image:height`, `og:image:type`, and `og:image:alt` for each image
- `og:locale`

On a product, up to three images are emitted: the featured image first, then
gallery images. The number of images can be changed with the
`wog_max_images_per_product` filter. If a product has no image, a fallback
image is used (the configured default social image, or the WooCommerce
placeholder).

## Product Open Graph tags (Facebook)

When Facebook output is enabled and the page is a product, these product tags
are added:

- `fb:app_id` (only when a Facebook App ID is configured)
- `product:price:amount` and `product:price:currency`
- `product:availability` (for example `in stock`, `limited availability`,
  `out of stock`)
- `product:condition` (defaults to `new`, or the `_condition` product meta)
- `product:brand`
- `product:category`

## Twitter Card tags

When X (Twitter) output is enabled:

- `twitter:card` (`product` on a product page, otherwise
  `summary_large_image`)
- `twitter:site` and `twitter:creator` (only when an X/Twitter username is
  configured)
- `twitter:title`
- `twitter:description`
- `twitter:image` and `twitter:image:alt`
- On a product, `twitter:label1` / `twitter:data1` for Price and
  `twitter:label2` / `twitter:data2` for Availability

## LinkedIn, Pinterest, and WhatsApp

- LinkedIn: when enabled, adds `linkedin:title` and `linkedin:description`.
  Note that LinkedIn itself reads standard `og:` tags; these extra tags are
  emitted in addition to the Open Graph tags.
- Pinterest: when enabled, adds `pinterest-rich-pin` and repeats the product
  price and availability tags for Rich Pins.
- WhatsApp: when enabled, adds an `og:image:alt` tag for the first image.

## Custom title and description per product

If a product has a custom social title or description set on its Social Media
Settings meta box, those values are used instead of the generated ones. See
[Per-Product Settings](../usage/per-product-settings.md).

## Enhanced product data

When Enhanced schema is enabled, the product meta data also carries retailer
item id, item group id, color, size, material, GTIN, and MPN. These values are
drawn from product attributes (`pa_color`, `pa_size`, `pa_material`) and common
meta keys, and feed the [Schema.org markup](schema-markup.md).
