# Share Button Problems

## Share buttons do not show on product pages

Work through this list in order.

1. **Enable Share Buttons is off.** Go to **WB Plugins > Open Graph > Share Buttons**, switch **Enable Share Buttons** on and click **Save Settings**.
2. **The product has social output off.** Open the product. If **Enable social sharing output for this product** is unticked, tick it and click **Update**.
3. **Every network is off.** The **Copy** button is always shown, but check **General > Social Platforms** to make sure the networks you want are on.
4. **Your theme does not run the position you chose.** The buttons attach to standard WooCommerce positions. Choose a different **Button Position** on the Share Buttons tab, or place them with the shortcode `[wog_social_share]`.
5. **You use a block theme or a page builder template for products.** These templates may not run the standard positions. Use the shortcode in your product template. See [Put Share Buttons on Any Page](../how-to/share-buttons-with-shortcode.md).
6. **A caching plugin or CDN is showing an old copy.** Clear the cache.

## The shortcode shows nothing

- The product is a draft, private or password protected. The shortcode shows nothing for these on purpose.
- The product has **Enable social sharing output for this product** unticked.
- The `id` is wrong or missing on a page that is not a product. View the page source and look for a comment starting `wog_social_share: no product in scope`.

## Buttons show but look unstyled

A caching or optimization plugin may be serving an old stylesheet. Clear all caches and reload.

## Copy button does nothing

Clear caches and reload. When copying fails the button shows "Failed to copy link". When it works it shows "Link copied!".

## The Email button is missing

It is off by default. Go to **Share Buttons > Email Button**, switch it on and click **Save Settings**.

## A network button is missing

Its switch is off under **General > Social Platforms**. Turn it on and save.
