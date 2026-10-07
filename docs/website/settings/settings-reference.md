# Settings Reference

Every setting, tab by tab. All settings are at **WB Plugins > Open Graph**. Each tab has its own **Save Settings** button and saves only its own fields.

Switches are on or off. "Default" is what a fresh install uses.

## General tab

### Social Platforms

Choose which networks get product-aware tags when a product link is shared. Each switch also controls that network's share button. See [Social Previews](../features/social-previews.md) for what each one adds to the page.

| Setting | What it does | Default |
|---|---|---|
| Facebook | Adds Facebook tags: Facebook App ID (if set), product price, currency, availability, condition, brand and category. Also shows the Facebook share button. | On |
| Twitter | Adds X (Twitter) card tags and shows the Twitter share button | On |
| LinkedIn | Adds two LinkedIn tags and shows the LinkedIn share button | On |
| Pinterest | Adds the Pinterest Rich Pins tag and shows the Pinterest share button | On |
| WhatsApp | Shows the WhatsApp share button | On |

### Platform Accounts

| Setting | What it does | Default |
|---|---|---|
| Facebook App ID | Optional. Adds your Facebook App ID to the page for Facebook analytics and insights | Empty |
| Twitter Username | Optional. Your X username without the @. The card names your account. | Empty |

The username must be 1 to 15 letters, numbers or underscores. A leading @ is removed for you. If you type something else, you see the message that it is not a valid X (Twitter) username and the previously saved username is kept.

### Image & Content

| Setting | What it does | Default |
|---|---|---|
| Default Social Image | The image used when a product, category or page has no image of its own. Recommended 1200 by 630 pixels. | Empty (WooCommerce placeholder image) |
| Social Image Size | Size of the product photo used in previews: Medium (300x300), Large (1024x1024) or Full Size | Large |

## Share Buttons tab

| Setting | What it does | Default |
|---|---|---|
| Enable Share Buttons | Adds share buttons to product pages automatically | On |
| Email Button | Adds an Email button that opens the shopper's email app with the product link | Off |
| Button Style | Modern, Classic or Minimal (icons only) | Modern |
| Button Position | After Add to Cart Button, Before Add to Cart Button, After Product Summary, or After Product Tabs | After Add to Cart Button |

## Structured Data tab

The plugin adds structured data only while the **Structured Data** switch is on. See [Structured Data](../features/structured-data.md).

### Product Schema

| Setting | What it does | Default |
|---|---|---|
| Structured Data | Master switch. Fills in details WooCommerce leaves out of its own Product data (image, brand, GTIN, MPN, specifications). Also turns on the breadcrumb, organization, category and shop data below. Leave off unless you need these. | Off |
| Enhanced Schema | Adds manufacturer, model and specifications from product attributes | On |
| Breadcrumb Schema | Adds the breadcrumb trail on product pages | On |

### Organization

| Setting | What it does | Default |
|---|---|---|
| Organization Schema | Tells search engines about your store: name, logo, contact, address | On |
| Organization Logo | Your logo for search engines. Empty means your theme logo, then your Site Icon. | Empty |
| Social Profiles | One profile web address per line, so search engines link your store to its profiles | Empty |

## Sitemaps tab

| Setting | What it does | Default |
|---|---|---|
| Enable Product Sitemaps | Serves the XML sitemaps. When on, the tab shows the **Sitemap URLs**, the **Last Generated** time, a **Generate Now** button and a **Test Sitemaps** button. | On |
| Products Per Sitemap | How many products go in each sitemap file. Allowed 100 to 1000. Values outside the range are changed to the nearest limit. | 500 |

The sitemap box appears after you have saved with **Enable Product Sitemaps** on.

## Advanced tab

| Setting | What it does | Default |
|---|---|---|
| Override SEO Titles | Prints this plugin's social title and description even when an SEO plugin already printed its own. Use with caution. See [Use with an SEO Plugin](../how-to/use-with-seo-plugins.md). | Off |
| Delete Data On Uninstall | Deletes all settings and per-product social text when you delete the plugin. See [Remove All Plugin Data](../how-to/remove-all-plugin-data-on-uninstall.md). | Off |

## Per-product settings

On each product, the **Social Media Settings** box: see [Per-Product Control](../features/per-product-control.md).

## Who can change settings

Users with the WooCommerce management permission (Administrator and Shop Manager).
