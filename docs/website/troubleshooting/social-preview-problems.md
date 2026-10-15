# Social Preview Problems

## Facebook (or LinkedIn) shows an old image, title or price

**Cause:** the network keeps a saved copy of your page for days.

**Fix:**
1. Clear your caching plugin or CDN cache so the page itself is current.
2. Go to **WB Plugins > Open Graph > Overview** and click **Facebook Debugger** (or **LinkedIn Inspector**).
3. Paste the product link and fetch it again ("Scrape Again" in the Facebook Debugger).

See [Check How a Product Looks When Shared](../how-to/check-how-a-product-looks-when-shared.md).

## Duplicate tags appear with an SEO plugin

**Symptom:** the page source has two `og:title` or `og:description` lines.

**Cause 1:** **Override SEO Titles** is on. It prints this plugin's title and description even when the SEO plugin did.

**Fix:** go to **WB Plugins > Open Graph > Advanced**, switch **Override SEO Titles** off and click **Save Settings**.

**Cause 2:** the SEO plugin prints its tags after this plugin looks at the page. The plugin can only skip tags printed before it runs.

**Fix:** in the SEO plugin, switch off its social tags, or in this plugin switch off the networks you do not want under **General > Social Platforms**. Note that the basic tags print whatever the switches say.

## My custom social title is not used

**Cause:** your SEO plugin already printed `og:title`, so this plugin skips its own.

**Fix:** set the title in the SEO plugin, or switch on **Override SEO Titles**. See [Use with an SEO Plugin](../how-to/use-with-seo-plugins.md).

## No image in the preview

**Cause:** the product has no featured image and no Default Social Image is set, so the plain WooCommerce placeholder is used.

**Fix:** set a featured image on the product, or set a **Default Social Image** on the **General** tab.

## No preview tags at all on one product

**Cause:** **Enable social sharing output for this product** is unticked.

**Fix:** open the product, tick it, click **Update**. The **Social** column on **Products > All Products** shows **Off** for such products.

## The price tag shows a single price for a variable product

This is expected. The price tag holds the lowest price. See the [FAQ](../faq/common-questions.md#why-does-a-variable-product-show-only-its-lowest-price-in-the-price-tag).

## Preview text is cut short

**Cause:** descriptions are limited. The automatic description is cut to 35 words. A custom description is limited to 155 characters, a custom title to 60.

**Fix:** write your own **Social Media Description** that fits.

## The preview shows the wrong language

The language tag follows your site language setting (**Settings > General > Site Language**).
