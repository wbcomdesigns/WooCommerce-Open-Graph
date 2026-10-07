# Troubleshooting

## Duplicate Open Graph or Twitter tags

If another SEO plugin (for example Yoast, Rank Math, or SEOPress) also emits
Open Graph or Twitter Card tags, you may see two sets of `og:` or `twitter:`
tags on product pages. The plugin includes a scan step that is meant to skip
tags an earlier plugin has already added, but that scan relies on an internal
signal that other plugins do not send, so it does not currently detect tags
from SEO plugins.

What you can do:

- Turn off Open Graph output in one of the two plugins so only one set of tags
  is produced. In this plugin, disable the relevant platform toggles under Core
  Features.
- Or turn off the WooCommerce Open Graph output in your SEO plugin and let this
  plugin own the product tags.
- The Override titles and descriptions advanced option forces this plugin's
  title and description tags even when another plugin set them; it does not
  remove the other plugin's tags.

## The preview is not updating on Facebook or LinkedIn

Social platforms cache link previews. After changing a product's title,
description, or image, re-scrape the URL with the platform's own tool. The
settings sidebar links to the Facebook Sharing Debugger, X (Twitter) Card
Validator, Google Rich Results Test, and LinkedIn Post Inspector.

## The sitemap returns 404

- Confirm the product sitemap is enabled in settings.
- Visit Settings, Permalinks and save once to flush rewrite rules, then reload
  `/wog-sitemap.xml`.
- Use the Test Sitemaps button on the settings page to check the index
  responds with valid XML.

## Share buttons do not appear

- Confirm Enable Share Buttons is on.
- Share buttons render only on single product pages, and their scripts load
  only there.
- If your theme heavily customizes the product template it may not fire the
  WooCommerce hook used by your chosen button position. Try a different Button
  Position, or place the row with the `[wog_social_share]` shortcode.

## No image in the preview

- Set a featured image on the product. The product edit box warns when none is
  set.
- Set a Default Social Image in settings as a fallback for products without an
  image. A 1200x630 image is recommended.

## Turning on debug output

Enable Debug mode under Advanced Settings to write plugin messages to the site
error log. On a single product page, an admin can also use the
`[wog_debug_urls]` shortcode to inspect the generated share URLs.
