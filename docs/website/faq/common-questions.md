# Frequently Asked Questions

## Setup

### Do I need WooCommerce?

Yes. Without WooCommerce the plugin cannot activate. If WooCommerce is deactivated later, the plugin switches itself off and shows a notice with an **Install WooCommerce** link.

### Do I have to configure anything?

No. It works with its defaults. The most useful things to set are a Default Social Image and your X username. See [Set Up in 5 Minutes](../how-to/set-up-in-5-minutes.md).

### Where did the old Social Media menu under WooCommerce go?

Version 2.1.0 moved the settings to **WB Plugins > Open Graph**. WB Plugins is a shared menu for Wbcom Designs plugins. Your saved settings carry over unchanged.

### Who can change the settings?

Users with the WooCommerce management permission: Administrators and Shop Managers.

### Does saving one tab change the other tabs?

No. Each tab saves only its own fields.

## Social previews

### Why does a variable product show only its lowest price in the price tag?

The price tag used by Facebook and Pinterest (`product:price:amount`) can hold only one number, so for a variable product it holds the lowest ("from") price. The full range still appears where it fits: in the description text ("Price: $42.00 - $45.00") and in the share text. Google's product data from WooCommerce also lists the range. A developer can change the tag value with the `wog_product_meta_data` filter. See [Hooks and Filters](../developer-guide/hooks-and-filters.md).

### Facebook still shows my old image or price. Why?

Facebook keeps a copy of your page. Ask it to fetch the page again with the Facebook Debugger. See [Check How a Product Looks When Shared](../how-to/check-how-a-product-looks-when-shared.md).

### Can I use a different title for Facebook than the product name?

Yes. Write a **Social Media Title** in the product's **Social Media Settings** box. See [Write a Custom Social Title and Description](../how-to/custom-social-title-and-description.md).

### Where does the preview image come from?

The product's featured image, then up to two gallery images. If there is none, your Default Social Image, or the WooCommerce placeholder.

### Which pages get preview tags?

Single products, product categories, product tags and the shop page. Not the cart, checkout, My Account, regular pages or posts.

### Does the plugin post to my social accounts?

No. It only prepares how your links look when people share them.

### Do I need a Facebook App ID?

No. It is optional and only used for Facebook analytics and insights.

### Can I switch off previews for one product?

Yes. Untick **Enable social sharing output for this product** in the product's Social Media Settings box. See [Stop Social Output for One Product](../how-to/stop-social-output-for-one-product.md). Google structured data for that product is not removed.

## Share buttons

### Which networks can shoppers share to?

Facebook, X (labelled Twitter), LinkedIn, Pinterest, WhatsApp, Email (off by default) and Copy Link. A network's button shows only when its switch is on in **General > Social Platforms**.

### Can I put the buttons on a blog post or landing page?

Yes, with `[wog_social_share id="123"]`. See [Put Share Buttons on Any Page](../how-to/share-buttons-with-shortcode.md).

### Do the buttons show on sold-out products?

Yes.

### Can I change the button colors?

Choose between three styles on the Share Buttons tab. Further changes need custom CSS in your theme. The buttons use the classes `wog-social-share`, `wog-share-btn` and `wog-style-modern`, `wog-style-classic` or `wog-style-minimal`.

## SEO and Google

### Does it replace Yoast, Rank Math or SEOPress?

No. It checks which tags those plugins already print and adds only the missing ones. See [Use with an SEO Plugin](../how-to/use-with-seo-plugins.md).

### Why is the Structured Data switch off by default?

WooCommerce already outputs Product structured data. Turning this on only adds fields WooCommerce leaves out (image fallback, brand, GTIN, MPN, specifications). It is also the master switch for the logo, social profiles and breadcrumbs, so turn it on if you want those. See [Structured Data](../features/structured-data.md).

### Do I need the product sitemap if my SEO plugin has one?

No. Use whichever you prefer. See [Submit the Product Sitemap to Google Search Console](../how-to/submit-product-sitemap-to-google.md).

### Does it slow my store down?

Tags are small and built during page loading. The share script loads only where buttons show. Sitemaps are saved for one hour and rebuilt in the background.

## Data and privacy

### What happens to my data when I uninstall?

By default your settings and per-product text stay. Switch on **Delete Data On Uninstall** (Advanced tab) before deleting to remove them. See [Remove All Plugin Data](../how-to/remove-all-plugin-data-on-uninstall.md).

### Does the plugin track shoppers?

When a shopper clicks a share or copy button, the page sends a share event to your own site (`wog_track_share`) and fires a `wog_social_share` browser event. The plugin itself stores nothing from it. Developers can listen to either.

### Is it translation ready?

Yes. The text domain is `woo-open-graph`.
