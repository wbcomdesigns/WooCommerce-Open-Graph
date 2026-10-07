# Options, Meta and Data Reference

## Options

| Option | Holds |
|---|---|
| `wog_settings` | One array with every setting (keys below) |
| `wog_version` | Installed plugin version. A change triggers the upgrade routine once. |
| `wog_migration_completed` | Set once pre-2.0 settings were carried over |
| `wog_sitemap_last_generated` | Timestamp of the last time a sitemap file was written |
| `wog_flush_rewrite_rules` | Set on activation so rewrite rules flush once |
| `wog_rewrite_rules_flushed_v2` | Set once the sitemap rewrite rules were flushed |

### `wog_settings` keys

| Key | Type | Default | Screen label |
|---|---|---|---|
| `enable_facebook` | bool | true | General > Facebook |
| `enable_twitter` | bool | true | General > Twitter |
| `enable_linkedin` | bool | true | General > LinkedIn |
| `enable_pinterest` | bool | true | General > Pinterest |
| `enable_whatsapp` | bool | true | General > WhatsApp |
| `facebook_app_id` | string | empty | General > Facebook App ID |
| `twitter_username` | string, 1 to 15 of A-Z a-z 0-9 _ | empty | General > Twitter Username |
| `fallback_image` | URL | empty | General > Default Social Image |
| `image_size` | `medium`, `large`, `full` | `large` | General > Social Image Size |
| `enable_social_share` | bool | true | Share Buttons > Enable Share Buttons |
| `enable_email` | bool | false | Share Buttons > Email Button |
| `share_button_style` | `modern`, `classic`, `minimal` | `modern` | Share Buttons > Button Style |
| `share_button_position` | `after_add_to_cart`, `before_add_to_cart`, `after_summary`, `after_tabs` | `after_add_to_cart` | Share Buttons > Button Position |
| `enable_schema` | bool | false | Structured Data > Structured Data (master switch) |
| `enable_enhanced_schema` | bool | true | Structured Data > Enhanced Schema |
| `enable_breadcrumb_schema` | bool | true | Structured Data > Breadcrumb Schema |
| `enable_organization_schema` | bool | true | Structured Data > Organization Schema |
| `organization_logo` | URL | empty | Structured Data > Organization Logo |
| `social_profiles` | array of URLs | empty array | Structured Data > Social Profiles |
| `enable_product_sitemap` | bool | true | Sitemaps > Enable Product Sitemaps |
| `sitemap_products_per_page` | int 100 to 1000 | 500 | Sitemaps > Products Per Sitemap |
| `disable_title_description` | bool | false | Advanced > Override SEO Titles |
| `debug_mode` | bool | false | No switch in the settings screen; read only by `wog_debug_log()` |
| `delete_data_on_uninstall` | bool | false | Advanced > Delete Data On Uninstall |

The settings screen saves each tab by merging that tab's fields over the stored option, so saving one tab never resets another. Imports and code that call the validator without a tab rebuild every key.

## Post meta (products)

| Key | Holds |
|---|---|
| `_wog_og_title` | Custom social title. Deleted when empty. |
| `_wog_og_description` | Custom social description. Deleted when empty. |
| `_wog_disable_og` | `1` when social output is off for the product. Deleted when on. |

The plugin also reads these when present: GTIN (`_gtin`, `_upc`, `_ean`, `_isbn`, `_gtin8`, `_gtin12`, `_gtin13`, `_gtin14`, after WooCommerce's own GTIN field), `_mpn`, `_manufacturer_part_number`, `_brand`, `_manufacturer`, `_model`, `_condition`, and the product attributes `pa_color`, `pa_size`, `pa_material`.

## Scheduled events

| Event | When | Job |
|---|---|---|
| `wog_generate_sitemaps` | Daily, and once about ten minutes after a product or category change | Clears saved sitemap copies and queues the rebuild |
| `wog_generate_single_sitemap` | One-off, queued by the job above | Writes one sitemap (`index`, `products` page, `categories`, `brands`) |

## Admin AJAX actions

| Action | Who | Job |
|---|---|---|
| `wog_generate_sitemap` | Users with `manage_woocommerce`, nonce `wog_admin_nonce` | Queues sitemap generation (Generate Now) |
| `wog_test_sitemap` | Users with `manage_woocommerce`, nonce `wog_admin_nonce` | Fetches the main index and checks it (Test Sitemaps) |
| `wog_track_share` | Anyone, nonce `wog_share_nonce` | Fires `wog_social_share_tracked` |

## Transients

Saved sitemap copies are stored as transients named `wog_sitemap_{type}` and `wog_sitemap_{type}_page_{n}` for one hour.

## Menu and screen

| Item | Value |
|---|---|
| Parent menu | `wbcomplugins` (WB Plugins, shared with other Wbcom plugins) |
| Page slug | `woo-open-graph` (`admin.php?page=woo-open-graph`) |
| Tab parameter | `&tab=` with `overview`, `general`, `sharing`, `structured-data`, `sitemaps`, `advanced` or `discover` |
| Capability | `manage_woocommerce` |

## Uninstall

`uninstall.php` always removes the scheduled sitemap jobs and `wog_` transients. When `delete_data_on_uninstall` is on, it also deletes the options `wog_settings`, `wog_version`, `wog_migration_completed`, `wog_sitemap_last_generated`, `wog_flush_rewrite_rules` and `wog_rewrite_rules_flushed_v2`, and the post meta keys `_wog_og_title`, `_wog_og_description` and `_wog_disable_og`.
