# Structured Data (for Google)

Structured data is hidden information in your pages that helps Google understand your store: who you are, what you sell, and how pages relate. It can unlock richer search results.

WooCommerce already adds its own Product structured data. This plugin does not add a second copy. It fills in what WooCommerce leaves out and adds store-level facts.

## One master switch

Everything this plugin adds to structured data is controlled by the **Structured Data** switch on the **Structured Data** tab. It is off by default.

While the Structured Data switch is off, the plugin adds nothing to your structured data. This is true even when Breadcrumb Schema, Organization Schema, Organization Logo or Social Profiles are filled in. Turn the Structured Data switch on to publish them.

## What gets added when the Structured Data switch is on

### Extra fields on products

These are added to WooCommerce's own Product data, only when WooCommerce left them empty. Nothing WooCommerce already set is changed.

| Field | Where the plugin finds it |
|---|---|
| Image | If the product has no photo: your Default Social Image, or the WooCommerce placeholder image. Google requires an image. |
| Brand | A brand taxonomy (Product Brands, Perfect Brands, YITH Brands, or a "Brand" attribute), otherwise a custom field named `_brand` |
| GTIN (barcode number) | The GTIN, UPC, EAN or ISBN field on the product's Inventory tab (WooCommerce 9.2 or later), otherwise a custom field such as `_gtin`, `_upc`, `_ean` or `_isbn` |
| MPN (manufacturer part number) | A custom field named `_mpn` or `_manufacturer_part_number` |

With **Enhanced Schema** on (default), these are also added when present:

| Field | Where the plugin finds it |
|---|---|
| Manufacturer | A custom field named `_manufacturer` |
| Model | A custom field named `_model` |
| Specifications | The product's attributes (Attributes tab), listed as name and value |

### Breadcrumbs

With **Breadcrumb Schema** on (default), product pages get a trail: Home, Shop, the product's category path, and the product. Products with no category get no breadcrumb.

### Organization

With **Organization Schema** on (default), WooCommerce pages (shop, product, category and others) get an Organization block with:

| Item | Where it comes from |
|---|---|
| Name and web address | Your site title and home URL |
| Logo | The **Organization Logo** you set. If empty: your theme logo, then your Site Icon. If none exists, no logo is published. |
| Social profiles | The URLs you enter in **Social Profiles**. If you enter none, nothing is published. |
| Contact | Your WooCommerce store phone and email (falls back to the WordPress admin email), shown as customer service |
| Address | Your WooCommerce store address, with country and state published separately |

### Category and shop pages

Category pages get a "collection page" block and the shop page gets a "store" block, each with name, description and web address.

## What the per-product switch does

Turning off **Enable social sharing output** for a product does not remove its structured data. Google results are not affected.

## Check it

Use the Google Rich Results test. See [Check How a Product Looks When Shared](../how-to/check-how-a-product-looks-when-shared.md) and [Add Your Store Logo and Social Profiles for Google](../how-to/add-logo-and-social-profiles-for-google.md).
