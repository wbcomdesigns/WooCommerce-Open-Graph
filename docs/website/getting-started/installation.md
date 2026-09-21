# Installation

## Install and activate

1. Make sure WooCommerce is installed and active first. The plugin will not
   activate without it.
2. Upload the plugin to `wp-content/plugins/open-graph-for-woocommerce`, or
   install it from the Plugins screen.
3. Activate the plugin from Plugins in wp-admin.

On activation the plugin sets its default options, records its version, and
schedules a daily background task that regenerates the product sitemap.

## Open the settings

After activation, go to WooCommerce and then Social Media in the wp-admin menu.
The direct URL is:

```
/wp-admin/admin.php?page=woo-open-graph
```

A Settings link is also added next to the plugin on the Plugins screen. Access
requires the `manage_woocommerce` capability.

## Defaults are ready to go

The plugin ships with sensible defaults, so meta tags, schema, share buttons,
and the sitemap all start working as soon as WooCommerce products exist. You
only need to open the settings if you want to change which platforms are
enabled, pick a share-button style, or add a Facebook App ID or X (Twitter)
username.

See [Settings Reference](../usage/settings.md) for every option.
