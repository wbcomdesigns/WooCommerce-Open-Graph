# Developer Overview

This section documents the extension points, shortcodes, and stored data a
developer needs when working with or extending the plugin.

## Architecture at a glance

The plugin uses a class-per-concern layout with a `WOG_` / `wog_` prefix. The
main `Woo_Open_Graph` class bootstraps and instantiates the component classes
directly.

| Class | File | Responsibility |
|---|---|---|
| `Woo_Open_Graph` | `open-graph-for-woocommerce.php` | Bootstrap, WooCommerce guard, constants, cache clearing |
| `WOG_Settings` | `includes/class-wog-settings.php` | Settings storage, defaults, validation, import and export |
| `WOG_Meta_Tags` | `includes/class-wog-meta-tags.php` | Open Graph and Twitter Card output |
| `WOG_Schema` | `includes/class-wog-schema.php` | Schema.org JSON-LD output |
| `WOG_Social_Share` | `includes/class-wog-social-share.php` | Share buttons, shortcodes, share tracking |
| `WOG_Sitemap` | `includes/class-wog-sitemap.php` | XML sitemap generation and rewrite rules |
| `WOG_Meta_Boxes` | `includes/class-wog-meta-boxes.php` | Per-product meta box and list column |
| `WOG_Admin` | `admin/class-wog-admin.php` | Settings screen, menu, admin AJAX |

## Constants

| Constant | Value |
|---|---|
| `WOG_VERSION` | `2.0.2` |
| `WOG_PLUGIN_FILE` | Absolute path to the main file |
| `WOG_PLUGIN_DIR` | Plugin directory path |
| `WOG_PLUGIN_URL` | Plugin directory URL |

## Helper functions

Defined in `includes/wog-functions.php`:

- `wog()` returns the main plugin instance.
- `wog_get_settings()` returns the current settings array.
- `wog_debug_log( $message, $data = null )` logs when debug mode is on.

## Reference pages

- [Hooks and Filters](hooks-and-filters.md)
- [Shortcodes](shortcodes.md)
- [Options and Meta Reference](data-reference.md)
