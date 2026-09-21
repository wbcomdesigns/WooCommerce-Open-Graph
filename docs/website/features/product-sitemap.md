# Product XML Sitemap

When the product sitemap is enabled, the plugin serves its own set of XML
sitemaps through rewrite rules, separate from any sitemap an SEO plugin
generates. The sitemaps are built for large catalogs, with pagination,
caching, batching, and memory guards.

## Sitemap URLs

| URL | Contents |
|---|---|
| `/wog-sitemap.xml` | Sitemap index that links to the others |
| `/product-sitemap-1.xml` (and `-2`, `-3`, ...) | Products, paginated |
| `/product-category-sitemap.xml` | Product categories |
| `/product-brand-sitemap.xml` | Product brands, when a brand taxonomy exists |

The index lists one product sitemap page per block of products, plus the
category and brand sitemaps when they have content.

## What is included

- Products: published, non-hidden products (products excluded from catalog or
  search are left out). Each entry has the URL, last-modified date, change
  frequency, priority, and the featured image.
- Categories: non-empty product categories, with last-modified date, change
  frequency, priority, and the category image when set.
- Brands: read from the first available brand taxonomy among `product_brand`,
  `pwb-brand`, `yith_product_brand`, and `pa_brand`.

Change frequency and priority are calculated per item (for example, featured,
on-sale, in-stock, well-reviewed, and highly rated products get a higher
priority).

## Products per sitemap page

The number of products per sitemap page is set by the Products Per Sitemap
option (default 500, allowed range 100 to 1000).

## Generation and caching

- Sitemap output is cached for one hour per page in transients.
- A daily background event, `wog_generate_sitemaps`, regenerates all sitemaps.
- Saving or updating a product or product category schedules a regeneration
  about ten minutes later.
- From the settings page you can trigger an immediate regeneration or test the
  sitemap. See [Settings Reference](../usage/settings.md).

## robots.txt

When the sitemap is enabled, the index URL is added to the site's `robots.txt`
output so search engines can discover it.
