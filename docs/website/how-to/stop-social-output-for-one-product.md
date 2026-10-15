# Stop Social Output for One Product

Goal: make one product send no social preview tags and show no share buttons, for example a private-sale or test product.

## Steps

1. Go to **Products > All Products** and open the product.
2. In the **Social Media Settings** box, untick **Enable social sharing output for this product**.
3. Click **Update**.

## What changes

- No social preview tags for that product.
- No share buttons on its page, and the shortcode shows nothing for it.
- Google structured data is not changed. The product stays eligible for product results in search.
- The product stays in your sitemap.

## How to check it worked

- **Products > All Products**: the **Social** column says **Off** for the product.
- **WB Plugins > Open Graph > Overview**: the **Social Output Off** count goes up by one.
- On the product page the "Share this product:" buttons are gone.

## To turn it back on

Tick **Enable social sharing output for this product** and click **Update**.

## If it doesn't work

See [Share button problems](../troubleshooting/share-button-problems.md).
