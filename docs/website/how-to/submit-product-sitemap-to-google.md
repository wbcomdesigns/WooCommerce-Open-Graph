# Submit the Product Sitemap to Google Search Console

Goal: give Google a list of all your products and categories.

## Steps

1. Go to **WB Plugins > Open Graph** and click the **Sitemaps** tab.
2. Make sure **Enable Product Sitemaps** is on. Under it you see the **Sitemap URLs** list.
3. Click **Test Sitemaps**. You should see "Sitemaps are working correctly!".
4. Copy the **Main Index** address. It looks like `https://yourstore.com/wog-sitemap.xml`.
5. Sign in to Google Search Console and choose your site.
6. Open **Sitemaps**, paste `wog-sitemap.xml` into the "Add a new sitemap" box, and submit.

## Notes

- If your SEO plugin already lists your products in its own sitemap, you do not need both. Pick one.
- Click **Generate Now** after a large catalog change to rebuild the sitemap in the background. It takes a few minutes. The **Last Generated** time shows when files were last written.
- Submit only the main index. It points Google to the product and category files.

## How to check it worked

- **Test Sitemaps** shows "Sitemaps are working correctly!".
- Opening the main index address in your browser shows XML with a list of sitemaps.
- In Search Console, the sitemap shows the status "Success" after Google reads it.

## If it doesn't work

See [Structured data and sitemap problems](../troubleshooting/structured-data-and-sitemap-problems.md).
