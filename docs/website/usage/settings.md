# Settings Reference

All store-wide options are on one screen at WooCommerce and then Social Media
(`admin.php?page=woo-open-graph`). The screen is organized into sections. Every
option below is stored in a single `wog_settings` option.

## Core Features

| Option | Default | Effect |
|---|---|---|
| Schema | On | Adds Schema.org structured data to product pages |
| Facebook | On | Generates Facebook Open Graph tags |
| Twitter | On | Generates Twitter Card tags |
| Linkedin | On | Adds LinkedIn sharing tags |
| Pinterest | On | Enables Pinterest Rich Pin tags |
| Whatsapp | On | Adds a WhatsApp image tag |

## Social Sharing Buttons

| Option | Default | Values |
|---|---|---|
| Enable Share Buttons | On | On or off |
| Button Style | Modern | Modern, Classic, Minimal |
| Button Position | After Add to Cart Button | After Add to Cart, Before Add to Cart, After Product Summary, After Product Tabs |

## XML Sitemaps

| Option | Default | Notes |
|---|---|---|
| Enable Product Sitemaps | On | Serves the sitemap URLs |
| Products Per Sitemap | 500 | Allowed range 100 to 1000 |

When the sitemap is enabled, this section also lists the live sitemap URLs, the
last generated time, and two actions: Generate Now (rebuilds all sitemaps in
the background) and Test Sitemaps (fetches the index and checks it returns a
valid sitemap).

## Platform Settings

| Option | Default | Notes |
|---|---|---|
| Facebook App ID | empty | Optional. When set, adds `fb:app_id` and the Facebook namespace |
| Twitter Username | empty | Optional, without the `@`. Adds `twitter:site` and `twitter:creator`. Stored value is validated against the allowed username format |

## Image & Content Settings

| Option | Default | Notes |
|---|---|---|
| Default Social Image | empty | Fallback image used when a product has no image. Recommended 1200x630 |
| Social Image Size | Large | Medium, Large, or Full. Selects which registered image size is used for social images |

## Advanced Settings

| Option | Default | Effect |
|---|---|---|
| Override titles and descriptions | Off | Forces the plugin's title and description tags even when another SEO plugin has set them. Use with caution |
| Enhanced schema | On | Adds GTIN, MPN, brand, and specification data |
| Breadcrumb schema | On | Adds breadcrumb structured data |
| Organization schema | On | Adds store and organization structured data |
| Debug mode | Off | Writes plugin debug messages to the error log |

## Import and export

The plugin can export its settings as JSON and import them back, which is
useful for moving a configuration between sites. These routines live in the
settings class.
