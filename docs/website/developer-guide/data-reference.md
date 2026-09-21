# Options and Meta Reference

## Options (`wp_options`)

| Option | Purpose |
|---|---|
| `wog_settings` | The single array holding every plugin setting |
| `wog_version` | Installed plugin version |
| `wog_migration_completed` | Flag set once legacy settings are migrated |
| `wog_sitemap_last_generated` | Timestamp of the last sitemap generation |
| `wog_flush_rewrite_rules` | Transient flag that triggers a rewrite flush after activation |
| `wog_rewrite_rules_flushed_v2` | Guard so the sitemap rewrite rules flush only once |

All plugin settings live inside `wog_settings`. Add new settings to the
defaults array and let `wog_validated_settings` sanitize them rather than
creating sibling options.

## Product meta

Meta keys the plugin owns:

| Meta key | Purpose |
|---|---|
| `_wog_og_title` | Per-product social title override |
| `_wog_og_description` | Per-product social description override |
| `_wog_disable_og` | Set when Open Graph output is disabled for the product |

Meta keys the plugin reads but does not own (used to fill gaps when present):

- Brand: `_brand`, plus the taxonomies `product_brand`, `pwb-brand`,
  `yith_product_brand`, `pa_brand`
- Condition: `_condition`
- GTIN family: `_gtin`, `_upc`, `_ean`, `_isbn`, `_gtin8`, `_gtin12`,
  `_gtin13`, `_gtin14`
- MPN: `_mpn`, `_manufacturer_part_number`
- Manufacturer and model: `_manufacturer`, `_model`
- Attributes: `_color`, `_size`, `_material`, and the `pa_color`, `pa_size`,
  `pa_material` product attributes
- Image alt text: `_wp_attachment_image_alt`
- Yoast description on the shop page: `_yoast_wpseo_metadesc`

## Scheduled events

| Event | Schedule | Purpose |
|---|---|---|
| `wog_generate_sitemaps` | Daily | Rebuild all sitemaps in the background |
| `wog_generate_single_sitemap` | One-off | Rebuild a single sitemap type or page |

Both are cleared on deactivation, along with the plugin's transients.

## AJAX actions

Handled through `admin-ajax.php`:

| Action | Access | Purpose |
|---|---|---|
| `wog_generate_sitemap` | Admin (`manage_woocommerce`) | Trigger a full sitemap regeneration from the settings page |
| `wog_test_sitemap` | Admin (`manage_woocommerce`) | Fetch and validate the sitemap index |
| `wog_track_share` | Public (logged in and logged out) | Report a front-end share event |

The admin actions verify the `wog_admin_nonce` and the `manage_woocommerce`
capability. The share tracking action verifies the `wog_share_nonce`.

## REST API

The plugin does not register any REST API routes.
