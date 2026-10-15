# Social Previews (Open Graph and X Cards)

When a shopper pastes your product link into Facebook, LinkedIn, WhatsApp, X or Pinterest, the network reads small hidden tags in your page. The plugin writes those tags so the post shows your product photo, name, description and price.

## What the preview contains

For a single product page:

| Part of the preview | Where it comes from |
|---|---|
| Title | The Social Media Title you typed for the product. If empty: the brand (when the product has one) plus the product name, cut to 10 words. |
| Description | The Social Media Description you typed. If empty: the short description (or the long description if there is no short one), then the price, then "In Stock" when it is in stock, cut to 35 words. If the product has no text at all, your site tagline is used, then your store name. |
| Image | The product's featured image, plus up to two gallery images (three in total). If the product has no image: your Default Social Image, or the WooCommerce placeholder image. |
| Price and stock | Shown as product price, currency, availability and condition tags. |
| Site name | Your site title. |

Category, tag and shop pages use the category (or page) name, its description, and its image. A category without an image uses the Default Social Image.

## Prices for variable and grouped products

The description text shows a range, for example "Price: $42.00 - $45.00", when the prices differ. The separate price tag can hold only one number, so it holds the lowest price. See [Why does Facebook show the lowest price for my variable product?](../faq/common-questions.md#why-does-a-variable-product-show-only-its-lowest-price-in-the-price-tag).

## Which switch controls what

The General tab has one switch per network. They do not all do the same thing, because the networks read different tags.

| Switch | What it adds to the page | Also controls |
|---|---|---|
| Facebook | Facebook App ID tag (if you set one), product price, currency, availability, condition, brand and category tags | The Facebook share button |
| Twitter | The X card tags: card type, account name, title, description, image, price and availability labels | The Twitter share button |
| LinkedIn | Nothing extra on the page (LinkedIn reads the basic tags) | The LinkedIn share button |
| Pinterest | A tag that switches on Pinterest Rich Pins, plus the product price, currency, availability, condition, brand and category tags | The Pinterest share button |
| WhatsApp | Nothing extra on the page (WhatsApp reads the basic tags) | The WhatsApp share button |

Two things to know:

- The basic tags (title, description, image, link, site name, type, language) are always printed, whatever the switches say. Facebook, LinkedIn and WhatsApp read these.
- Facebook and Pinterest share the same product tags. They are printed once when either switch is on, so Rich Pins work with Facebook switched off.

## X (Twitter) cards

- The card is the large-image card when the page has an image (the product photo or your default image). Otherwise it is the small summary card.
- If you enter your X username on the General tab, the card names your account as the site and the author.
- The card also shows the price and availability.

## Turning previews off for one product

Open the product and untick **Enable social sharing output for this product**. That removes the tags, the share buttons and the share scripts for that product only. See [Per-Product Control](per-product-control.md).

## What the plugin does not control

- How each network lays out the post. That is up to Facebook, X and the others.
- How long a network keeps the old preview. After you change a product, ask the network to re-read the page. See [Check How a Product Looks When Shared](../how-to/check-how-a-product-looks-when-shared.md).
