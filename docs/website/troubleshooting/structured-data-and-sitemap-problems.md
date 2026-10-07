# Structured Data and Sitemap Problems

## Logo, social profiles or breadcrumbs do not appear in Google's test

**Cause:** The **Structured Data** switch is off. It is the master switch for everything this plugin adds to structured data.

**Fix:**
1. Go to **WB Plugins > Open Graph > Structured Data**.
2. Switch **Structured Data** on.
3. Check **Organization Schema** (and **Breadcrumb Schema**) are on.
4. Click **Save Settings**, then test again with **Google Rich Results** on the Overview tab.

Also check that you entered an **Organization Logo** or have a theme logo or Site Icon, and that **Social Profiles** has one full address per line.

## A product has no breadcrumbs

Breadcrumbs need the product to belong to a category.

## The sitemap shows a 404 page

**Cause:** WordPress has not loaded the sitemap address rules yet.

**Fix:**
1. Make sure **WB Plugins > Open Graph > Sitemaps > Enable Product Sitemaps** is on and saved.
2. Go to **Settings > Permalinks** and click **Save Changes** without changing anything.
3. Open `yourstore.com/wog-sitemap.xml` again.

## Test Sitemaps fails

| Message | Cause | Fix |
|---|---|---|
| Sitemap returned HTTP 404 | Rewrite rules not loaded | Save **Settings > Permalinks** once, then test again |
| Sitemap returned HTTP 500 or similar | A server error while building the sitemap | Check your PHP error log. Lower **Products Per Sitemap** if your server runs out of memory |
| Failed to fetch sitemap: ... | Your server cannot reach its own address (firewall, password protection, local or staging site) | Open the sitemap address in your browser instead. If it loads, the sitemap works |
| Invalid sitemap format | Another plugin or a cache is returning a different page | Clear caches and test again |

## Generate Now says "queued" but nothing changes

The rebuild happens in the background over the next few minutes, using WordPress's scheduled tasks. Reload the Sitemaps tab later to see **Last Generated** update. On a very quiet site, scheduled tasks run only when someone visits, so visit your site and wait.

## The sitemap lacks a product

Hidden products (hidden from the catalog or from search) and products that are not published are not listed. The sitemap is also kept for up to one hour, and a product change takes about ten minutes to schedule a rebuild.

## robots.txt does not show the sitemap line

The line is added to the virtual robots.txt WordPress produces. If your site has a real `robots.txt` file, WordPress does not use it. Add `Sitemap: https://yourstore.com/wog-sitemap.xml` to that file yourself. The line is also not added when WordPress is set to discourage search engines.
