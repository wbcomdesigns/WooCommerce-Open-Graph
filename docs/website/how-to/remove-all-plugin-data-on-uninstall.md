# Remove All Plugin Data When Uninstalling

Goal: leave nothing of this plugin behind in your database when you delete it.

By default the plugin keeps your settings and per-product social text, so deleting and reinstalling it brings your setup back.

## Steps

1. Go to **WB Plugins > Open Graph** and click the **Advanced** tab.
2. Switch on **Delete Data On Uninstall**.
3. Click **Save Settings**.
4. Go to **Plugins > Installed Plugins**, deactivate **Open Graph for WooCommerce**, then click **Delete**.

## What is removed

When Delete Data On Uninstall is on:

- All the plugin's settings and its version and housekeeping records.
- Every product's Social Media Title, Social Media Description and "output off" choice.
- Scheduled sitemap jobs and saved sitemap copies.

When it is off, only the scheduled sitemap jobs and saved sitemap copies are removed. Your settings and per-product text stay.

Deactivating without deleting never removes your settings.

## How to check it worked

After deleting, reinstalling the plugin starts from the defaults and the **Overview** tab shows 0 products with custom social text.

## If it doesn't work

See [Settings problems](../troubleshooting/settings-problems.md).
