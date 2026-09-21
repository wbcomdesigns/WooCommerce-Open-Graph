# Shortcodes

The plugin registers two shortcodes, both handled by the social share class.

## `[wog_social_share]`

Renders the social share row for the current product. Use it to place the share
buttons somewhere other than the four built-in positions, for example inside a
page-builder block or a product tab.

- On a single product page it uses the current product.
- Elsewhere it uses the current post when that post is a product.
- If no product is found, it outputs nothing.

The rendered row respects the configured button style and enabled platforms.
The shortcode takes no attributes.

```
[wog_social_share]
```

## `[wog_debug_urls]`

An admin-only debugging shortcode. For users with the `manage_options`
capability, it prints the raw and cleaned title, the description, and the
generated share URL for each platform, so you can inspect exactly what each
share button will link to. Non-admins see an access-denied message. Use it on a
single product page.

```
[wog_debug_urls]
```
