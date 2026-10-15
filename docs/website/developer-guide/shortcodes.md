# Shortcodes

## `[wog_social_share]`

Prints the share buttons.

| Attribute | Default | Meaning |
|---|---|---|
| `id` | the product being viewed | A product ID. Any page or post can use it. |

```
[wog_social_share]
[wog_social_share id="123"]
```

Behavior:

- Uses the Button Style and the network switches from the settings. It does not check **Enable Share Buttons**, so it works even when automatic placement is off.
- Prints nothing for a product that is not published, is password protected, or has social output switched off.
- Given a variation ID, the visibility check uses the parent product.
- With no product (no `id` and the page is not a product), it prints the HTML comment `<!-- wog_social_share: no product in scope; use [wog_social_share id="PRODUCT_ID"] -->`.
- It loads the share script and styles where it prints, on any page.

## `[wog_debug_urls]`

For administrators only (the `manage_options` capability). Shows the share links the plugin builds for the current product, for testing. Other visitors see "Access denied - admin only". Not intended for production pages.
