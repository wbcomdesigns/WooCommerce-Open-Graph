# Use with Yoast SEO, Rank Math or SEOPress

Goal: run this plugin next to an SEO plugin without printing the same tag twice.

## What the plugin does

On product, category, tag and shop pages, the plugin first looks at the page's head to see which social tags are already there. It then prints only the ones that are missing. It checks each tag one by one.

It skips a tag when the SEO plugin already printed it:

- Open Graph: title, description, type, link, site name, image (and its size and alt details), language.
- X (Twitter): card type, account (site and creator), title, description, image.

It always prints its own shop-specific tags, because SEO plugins do not print them: product price, availability, condition, brand and category tags, the X price and availability labels, the Pinterest Rich Pins tag, and the LinkedIn tags.

The check sees tags that the SEO plugin prints before this plugin runs, which is the normal case for Yoast, Rank Math and SEOPress.

## Steps

You do not need to do anything. To check:

1. View the source of a product page (right-click, **View Page Source**).
2. Search for `og:title`. You should find it once.

## Your custom social title

When the SEO plugin already printed `og:title`, this plugin's social title (the **Social Media Title** you typed for the product) is not used, because the SEO plugin's tag wins. To use your own wording from this plugin, either:

- Set the wording in the SEO plugin for that product instead, or
- Switch on **Override SEO Titles** (see below).

## Override SEO Titles

1. Go to **WB Plugins > Open Graph > Advanced**.
2. Switch on **Override SEO Titles** and click **Save Settings**.

With this on, the plugin prints its own title and description tags even if the SEO plugin already did. That covers the Open Graph title and description and the X card type, title and description. The SEO plugin's tags stay in the page too, so both appear in the source. Use it only if you want this plugin's wording to be present. Leave it off if you want exactly one tag of each kind.

## How to check it worked

View the page source and search for `og:title`, `og:description` and `og:image`. With Override off, each appears once.

## If it doesn't work

See [Duplicate tags with an SEO plugin](../troubleshooting/social-preview-problems.md#duplicate-tags-appear-with-an-seo-plugin).
