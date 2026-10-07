# Developer Overview

For developers extending the plugin. Store owners do not need this section.

## Layout

The plugin is one class per concern with a `WOG_` / `wog_` prefix. The main class `Woo_Open_Graph` boots and creates the others.

| Class | File | Job |
|---|---|---|
| `Woo_Open_Graph` | `open-graph-for-woocommerce.php` | Bootstrap, WooCommerce check, upgrade routine, cache clearing, robots.txt line |
| `WOG_Settings` | `includes/class-wog-settings.php` | Defaults, validation, one `wog_settings` option |
| `WOG_Meta_Tags` | `includes/class-wog-meta-tags.php` | Open Graph and X card tags, SEO plugin detection |
| `WOG_Schema` | `includes/class-wog-schema.php` | JSON-LD gap-fill, breadcrumb, organization, category, shop |
| `WOG_Social_Share` | `includes/class-wog-social-share.php` | Share buttons, shortcode, share tracking |
| `WOG_Sitemap` | `includes/class-wog-sitemap.php` | Sitemap rewrite rules, output, background jobs |
| `WOG_Meta_Boxes` | `includes/class-wog-meta-boxes.php` | Product meta box and Social column |
| `WOG_Admin` | `admin/class-wog-admin.php` | WB Plugins menu, tabs, settings screen, admin AJAX |

## Constants

| Constant | Value |
|---|---|
| `WOG_VERSION` | `2.1.0` |
| `WOG_PLUGIN_FILE` | Path to the main plugin file |
| `WOG_PLUGIN_DIR` | Plugin directory path |
| `WOG_PLUGIN_URL` | Plugin directory URL |

## Helper functions (`includes/wog-functions.php`)

| Function | Returns |
|---|---|
| `wog()` | The `Woo_Open_Graph` instance |
| `wog_get_settings()` | The settings array |
| `wog_debug_log( $message, $data = null )` | Writes to the PHP error log when the stored `debug_mode` setting is true. Nothing in the plugin calls it, and Debug Mode has no switch in the settings screen. |
| `wog_is_social_enabled_for_product( $product_id )` | Whether social output is allowed for a product. Applies the `wog_social_enabled_for_product` filter. |
| `wog_get_fallback_image_url()` | Default Social Image, else the WooCommerce placeholder at the configured size |
| `wog_get_product_price_text( $product )` | Price HTML, as a range for variable and grouped products whose prices differ |
| `wog_get_product_gtin( $product )`, `wog_get_product_mpn( $product )`, `wog_get_product_brand( $product )` | Shared lookups used by the tag and schema classes |

## Output order on a page

1. `wp_head` priority 0: the tag class starts buffering the head.
2. `wp_head` priority 14: it scans the buffered head for `og:*` and `twitter:*` tags already printed.
3. `wp_head` priority 15: it prints the missing tags between `Woo Open Graph Meta Tags` comments.
4. `wp_head` priority 5 (only while `enable_schema` is on): standalone JSON-LD blocks.

## Rules every social output layer follows

Ask `wog_is_social_enabled_for_product()` before printing anything social for a product. JSON-LD is deliberately not gated by it.

## Reference pages

- [Hooks and Filters](hooks-and-filters.md)
- [Shortcodes](shortcodes.md)
- [Options, Meta and Data Reference](data-reference.md)
