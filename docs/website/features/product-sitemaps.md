# Product Sitemaps

A sitemap is a list of your pages that you give to search engines so they find everything. This plugin makes a sitemap of your products and product categories.

## The sitemap addresses

Replace `yourstore.com` with your own domain.

| Address | What it lists |
|---|---|
| `yourstore.com/wog-sitemap.xml` | The main index. Submit this one. It points to the others. |
| `yourstore.com/product-sitemap-1.xml` | The first batch of products. More batches (`-2`, `-3` and so on) appear as your catalog grows. |
| `yourstore.com/product-category-sitemap.xml` | Product categories that contain products |
| `yourstore.com/product-brand-sitemap.xml` | Brands, only when your store uses a brand taxonomy that has products. It is listed in the index automatically. |

## What is included

- Published products that are visible in your catalog. Hidden products and products hidden from search are left out.
- The product's last-changed date and the product's main image.
- Products per file: set in **Sitemaps > Products Per Sitemap** (100 to 1000, default 500).

Per-product social output being off does not remove a product from the sitemap.

## How it stays up to date

- The sitemap is built when a search engine (or you) requests it, and kept for one hour.
- A daily background job rebuilds it.
- Saving a product or category schedules a rebuild about ten minutes later.
- **Generate Now** on the Sitemaps tab clears the saved copies and rebuilds them in the background. It reports "Sitemap generation queued. Files are rebuilt in the background over the next few minutes." The **Last Generated** time updates when the files are actually written.

Background jobs run on WordPress's scheduled task system, which needs some visits to your site to trigger. A very quiet site may take longer to finish.

## robots.txt

When the sitemap is on and your site is visible to search engines, the plugin adds a `Sitemap:` line pointing to the main index to the virtual robots.txt that WordPress produces. If your site has a real `robots.txt` file on disk, WordPress does not use it, so add the line yourself.

## Test it

On the Sitemaps tab, click **Test Sitemaps**. The plugin fetches the main index and reports "Sitemaps are working correctly!" or what went wrong. See [Submit the Product Sitemap to Google Search Console](../how-to/submit-product-sitemap-to-google.md).

## The sitemap files tell search engines not to list them

The sitemap pages send a "noindex, follow" signal. This is normal for sitemap files: it keeps the XML file out of search results while search engines still follow the links inside.
