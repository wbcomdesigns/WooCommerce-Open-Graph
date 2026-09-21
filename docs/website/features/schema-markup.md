# Schema.org Markup

When the Schema setting is enabled, the plugin outputs JSON-LD structured data
in the page head on the `wp_head` hook (priority 5). Each schema block is
wrapped in its own `<script type="application/ld+json">` tag.

## Where schema is added

- Single product: a `Product` block, plus a `BreadcrumbList` block when
  breadcrumb schema is enabled.
- Product category archive: a `CollectionPage` block.
- Shop page: a `Store` block.
- All of the above pages also get an `Organization` block when organization
  schema is enabled.

## Product schema

The `Product` block includes, where available:

- Name, description, URL, and images (featured plus gallery, full size)
- SKU
- `brand` as a `Brand` object
- `category` as the category hierarchy path
- `offers` with price, currency, availability, price valid until, seller, and
  item condition
- `aggregateRating` when the product has ratings and reviews
- `review` with up to five approved reviews that carry a rating
- Weight and dimensions as `QuantitativeValue` objects

### Offers

For a simple product the offer carries a single price, availability, a one-year
`priceValidUntil`, the store as `seller`, and `NewCondition`. On-sale products
add a `priceSpecification` for the regular price and, when set, the sale date
range. A basic shipping-details block is added when the product has a shipping
class, and a 30-day merchant return policy block is included.

For a variable product, one offer is generated per purchasable variation, each
with its own price, currency, availability, and SKU.

### Enhanced product properties

When Enhanced schema is enabled, the product block also includes, when the data
exists: `productID`, `gtin`, `mpn`, `manufacturer`, `model`, `color`, `size`,
`material`, `additionalProperty` (from product attributes), and `hasVariant`
(for variable products).

GTIN is read from the first of these meta keys that has a value: `_gtin`,
`_upc`, `_ean`, `_isbn`, `_gtin8`, `_gtin12`, `_gtin13`, `_gtin14`. MPN is read
from `_mpn`, then `_manufacturer_part_number`.

## Breadcrumb schema

The `BreadcrumbList` block builds the trail Home, Shop, category ancestors, the
current category, and the current product.

## Organization schema

The `Organization` block includes the site name, URL, logo (from the theme
custom logo), and social profile URLs read from the options `facebook_url`,
`twitter_url`, `instagram_url`, `linkedin_url`, and `youtube_url`. It adds a
`contactPoint` from the WooCommerce store phone and email, and a
`PostalAddress` from the WooCommerce store address options.

## Shop and category schema

- Shop page: a `Store` block with the shop page title, description, and URL.
- Category archive: a `CollectionPage` block with the category name,
  description, and URL.
