=== Open Graph for WooCommerce ===
Contributors: wbcomdesigns
Donate link: https://wbcomdesigns.com/donate
Tags: woocommerce, open graph, social media, facebook, schema
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Make your WooCommerce products look amazing when shared on social media with beautiful previews and share buttons.

== Description ==

**Open Graph for WooCommerce** helps your products get more attention on social media. When someone shares your product, it shows up with beautiful images, titles, and descriptions that make people want to click and buy.

= What You Get =

* **Better Product Previews** - Your products look professional when shared on Facebook, Twitter, LinkedIn, and Pinterest
* **Share Buttons** - Let customers easily share products with their friends
* **More Sales** - Products that look good on social media get more clicks and sales
* **Easy Setup** - Install, activate, and you're done. No complex configuration needed.

= Share on These Platforms =

* Facebook
* Twitter
* LinkedIn
* Pinterest
* WhatsApp
* Email
* Copy Link

= Three Beautiful Styles =

Choose the style that matches your store:

* **Modern** - Clean buttons with smooth animations
* **Classic** - Traditional rectangular buttons
* **Minimal** - Simple icon-only buttons

= Works With Your Existing Setup =

This plugin works alongside other SEO plugins like Yoast, RankMath, and SEOPress. It detects the Open Graph and Twitter Card tags those plugins already output and skips duplicates, adding only the WooCommerce-specific tags they miss. Product schema is off by default because WooCommerce core already outputs Product structured data; enable it only to gap-fill Woo's schema with extra fields (brand, GTIN, MPN, specifications).

= Perfect For =

* Online stores wanting more social media attention
* Products with strong visual appeal
* Stores targeting younger, social-media-savvy customers
* Anyone wanting to increase product visibility

== Installation ==

1. Install and activate the plugin
2. Go to **WooCommerce → Social Media**
3. Choose which platforms to enable
4. Select your button style
5. Done! Your products are now optimized for social sharing

== Frequently Asked Questions ==

= Do I need WooCommerce? =

Yes, this plugin is designed specifically for WooCommerce stores.

= Will it slow down my site? =

No. The plugin is lightweight and only loads on product pages.

= Can I customize the buttons? =

Yes! Choose from three built-in styles or use CSS to create your own design.

= Does it work with my theme? =

Yes, it works with all WooCommerce-compatible themes.

= How do I know it's working? =

Share a product on Facebook or Twitter and you'll see a beautiful preview with your product image, price, and description.

= Can customers share from mobile? =

Absolutely! All share buttons work perfectly on mobile devices.

= Does it work with variable products? =

Yes, it works with all WooCommerce product types.

= Is it translation ready? =

Yes, the plugin is fully translatable and supports RTL languages.

== Screenshots ==

1. Modern admin settings page - simple and clean
2. Easy platform selection - just click to enable
3. Three button styles to choose from
4. Share buttons on product page
5. Beautiful Facebook preview of your product
6. Twitter card with product details
7. Mobile-friendly share buttons
8. Advanced options for power users

== Changelog ==

= 2.1.0 - October 2026 =

* New      - Organization Logo and Social Profiles settings feed the Organization structured data, with the theme logo and then the Site Icon as fallbacks.
* New      - Optional Email share button.
* New      - Option to delete all plugin data when the plugin is deleted (off by default).
* New      - The [wog_social_share] shortcode accepts a product id, so share buttons can be placed on any page.
* Improve  - X (Twitter) cards use the large-image card when the product has an image, and the summary card otherwise.
* Improve  - Share text shows a price range for variable and grouped products whose prices differ.
* Improve  - The product list Social column shows its state as text, not colour alone.
* Improve  - The per-product switch is now labelled "Enable social sharing output" and states that search structured data is not affected.
* Fix      - Product category pages keep og:title and og:description when "Override titles and descriptions" is enabled.
* Fix      - Sold-out products show share buttons again.
* Fix      - Turning off social output for a product now also removes its share buttons, share assets and Open Graph namespace.
* Fix      - Share assets no longer load on product pages when share buttons are turned off.
* Fix      - The [wog_social_share] shortcode renders on pages other than the product page.
* Fix      - Products without a photo now include an image in their structured data.
* Fix      - Organization structured data no longer publishes an empty logo or empty profile list.
* Fix      - The store address publishes the country and region separately instead of a combined "US:CA" value.
* Fix      - og:image:secure_url is emitted only for HTTPS image URLs.
* Fix      - An invalid X (Twitter) username is rejected with a message instead of being published.
* Fix      - The Social column in the product list appears again.
* Fix      - Social title and description placeholders in the product editor show exactly what will be published.
* Fix      - Generating sitemaps reports that the work is queued, and the Last Generated time is set only when a sitemap is actually written.
* Fix      - The documented wog_social_share JavaScript event now fires on every share, and the readme names the correct global.
* Fix      - Share text with accented letters, CJK or emoji is no longer cut in the middle of a character.
* Fix      - Settings from versions before 2.0 are carried over once on upgrade.
* Fix      - Removed the retired X (Twitter) Card Validator link from Testing Tools.
* Dev      - One settings sanitizer and one defaults array are shared by the settings screen, import and activation; removed the unused cache_meta_tags setting.
* Dev      - New wog_social_enabled_for_product filter decides social output per product.

= 2.0.4 - September 2026 =

* Improve  - Removed CSS and JavaScript files that were shipped in the plugin but never loaded, including a legacy admin stylesheet and the unused minified and RTL copies.
* Fix      - Copy Link button now works on product pages; the share script was present in the source but excluded from the packaged plugin, so it returned a 404 and the button did nothing.
* Fix      - The "Share this product" label now inherits the active theme text color instead of a hardcoded gray, in both light and dark modes.
* Fix      - Share buttons now meet a 40px minimum tap-target size on mobile.
* Fix      - Section-heading styles on the settings screen are scoped to the plugin so they no longer affect other admin headings.
* Fix      - Icon share links now expose a title tooltip, and admin and meta-box styles use logical properties for correct right-to-left layouts.

= 2.0.3 - September 2026 =

* Improve  - Stop duplicating WooCommerce's Product schema; Woo's own product schema is left to stand and the plugin gap-fills only through WooCommerce filters.
* Fix      - Remove dishonest structured-data hints: no fabricated priceValidUntil and no always-new condition; GTIN, MPN and brand now come from real WooCommerce data.
* Fix      - Real de-duplication with Yoast, Rank Math and SEOPress: og: and twitter: tags they already emit are no longer duplicated.
* Fix      - og:image:alt is emitted once instead of twice on every page.
* Fix      - Share image width, height and type are read from the real product image and omitted for placeholder or non-product URLs, instead of hardcoded values.
* Fix      - og:description keeps a fallback to the site name so it is never dropped when the site tagline is empty.

= 2.0.1 =
* Updated for WordPress.org compliance
* Improved compatibility with latest WooCommerce
* Minor bug fixes

= 2.0.0 =
* Complete plugin rewrite
* Added WhatsApp and Email sharing
* Three new button styles (Modern, Classic, Minimal)
* Faster performance - 70% smaller file size
* Better mobile experience
* Improved security
* Modern admin interface

= 1.0.1 =
* Added LinkedIn and Pinterest support
* Basic share buttons
* Simple Open Graph tags

= 1.0.0 =
* Initial release
* Facebook and Twitter support

== Upgrade Notice ==

= 2.0.1 =
Minor update with improved WooCommerce compatibility. Safe to update.

= 2.0.0 =
Major update! Faster, better looking, and more features. Backup your settings before upgrading.
