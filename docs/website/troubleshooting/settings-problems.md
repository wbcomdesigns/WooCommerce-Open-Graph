# Settings Problems

## I cannot find the settings page

1. Go to **WB Plugins > Open Graph**. The old location under the WooCommerce menu no longer exists.
2. If **WB Plugins** is missing, check **Plugins > Installed Plugins**. The plugin needs WooCommerce active. If it is inactive you see "Open Graph for WooCommerce requires WooCommerce to be installed and active."
3. Check your user role. You need the WooCommerce management permission (Administrator or Shop Manager).
4. You can also use the **Settings** link under the plugin name on the Plugins screen.

## My X (Twitter) username was rejected

You see a message that the name is not a valid X (Twitter) username, and the old username is kept. The username must be 1 to 15 letters, numbers or underscores. Remove spaces, dots and dashes. A leading @ is fine. Fix it and save again.

## My settings did not save

- Each tab saves only its own fields. Click **Save Settings** on the tab you changed.
- If the page reloads with an error, check that no security plugin or firewall is blocking the save.

## I turned on a setting but my page did not change

- A caching plugin or CDN is serving an old copy. Clear it.
- The product has **Enable social sharing output for this product** unticked.
- For structured data, the **Structured Data** switch must be on. See [Structured Data and Sitemap Problems](structured-data-and-sitemap-problems.md).

## The sitemap box is missing on the Sitemaps tab

The Sitemap URLs, Generate Now and Test Sitemaps buttons appear only after you save with **Enable Product Sitemaps** on.

## The Social column is missing from the product list

Click **Screen Options** at the top right of **Products > All Products** and make sure **Social** is ticked.
