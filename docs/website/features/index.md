# Features Overview

The plugin is built as a set of independent components, each toggled by its own
setting. Everything below reflects what the code actually emits.

## The four components

| Component | What it produces | Where it runs |
|---|---|---|
| [Open Graph and Twitter Cards](open-graph-tags.md) | Social meta tags in the page head | Product, product category, product tag, shop, and other WooCommerce pages |
| [Schema.org markup](schema-markup.md) | JSON-LD structured data | Product, category, and shop pages |
| [Social share buttons](social-share-buttons.md) | Share buttons for shoppers | Single product pages |
| [Product XML sitemap](product-sitemap.md) | XML sitemaps for products, categories, brands | Front-end sitemap URLs |

## How output is gated

- Meta tags render on product, product category, product tag, shop, and any
  WooCommerce page. Each platform (Facebook, X/Twitter, LinkedIn, Pinterest,
  WhatsApp) has its own on/off setting.
- Schema renders only when the Schema.org setting is on, and only on product,
  category, and shop pages.
- Share buttons render only when share buttons are enabled, and only on single
  product pages.
- The sitemap responds only when the product sitemap setting is on.

## Works next to other SEO plugins

The plugin focuses on WooCommerce-specific data (price, availability, brand,
GTIN, MPN, ratings) that generic SEO plugins do not add. It does emit its own
`og:` and `twitter:` tags, so if another SEO plugin also outputs Open Graph
tags you may see both. See
[Troubleshooting](../troubleshooting/index.md) for how to handle duplicate
tags.
