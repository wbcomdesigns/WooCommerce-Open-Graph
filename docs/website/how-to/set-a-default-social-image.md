# Set a Default Image for Products Without a Photo

Goal: make sure a shared product always shows an image, even when it has no product photo.

## Steps

1. Go to **WB Plugins > Open Graph** and click the **General** tab.
2. Under **Image & Content**, find **Default Social Image**.
3. Click **Choose Image**, pick or upload an image, and click **Use This Image**. You can also paste an image address.
4. Click **Save Settings**.

A wide image about 1200 by 630 pixels works best.

## Where it is used

- Products with no featured image.
- Product category pages with no category image, and tag and shop pages.
- Google structured data for a product with no image (only when the **Structured Data** switch is on).

If you leave it empty, the WooCommerce placeholder image is used instead.

## Social Image Size

The same section has **Social Image Size**: **Medium (300x300)**, **Large (1024x1024)** or **Full Size**. This is the size of the product photo used in previews. **Large** is the default.

## How to check it worked

Share a product that has no photo using the Facebook Debugger (see [Check How a Product Looks When Shared](check-how-a-product-looks-when-shared.md)). Your default image appears.

## If it doesn't work

See [Social preview problems](../troubleshooting/social-preview-problems.md).
