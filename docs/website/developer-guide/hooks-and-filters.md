# Hooks and Filters

Every hook below is fired or applied by the plugin itself. Signatures reflect
the source.

## Action hooks

| Action | Arguments | Fired when |
|---|---|---|
| `wog_init` | `$plugin` | The plugin has finished bootstrapping |
| `wog_setting_updated` | `$key, $value` | A single setting is updated |
| `wog_settings_updated` | `$settings` | Multiple settings are updated at once |
| `wog_settings_reset` | none | Settings are reset to defaults |
| `wog_settings_imported` | `$sanitized_settings, $imported_data` | Settings are imported from JSON |
| `wog_settings_migrated` | `$migrated_settings, $old_settings` | Legacy settings are migrated |
| `wog_settings_cache_cleared` | none | The settings cache is cleared |
| `wog_product_meta_saved` | `$post_id` | The per-product meta box is saved |
| `wog_product_cache_cleared` | `$product_id` | A product's object cache is cleared |
| `wog_category_cache_cleared` | `$term_id` | A category's object cache is cleared |
| `wog_social_share_tracked` | `$platform, $product_id, $url` | A share event is reported from the front end |

## Filter hooks

| Filter | Filtered value | Purpose |
|---|---|---|
| `wog_default_settings` | array | Modify the default settings array |
| `wog_validated_settings` | `$validated, $settings` | Adjust settings after validation, before save |
| `wog_{section}_settings` | array | Per-section settings, where section is `schema`, `opengraph`, `sitemap`, `social_share`, or `advanced` |
| `wog_config_summary` | array | The configuration summary shown in admin |
| `wog_product_meta_data` | `$meta_data, $product, $post` | The assembled product meta data before it is turned into tags |
| `wog_max_images_per_product` | int (default 3) | Cap on Open Graph images emitted per product |
| `wog_sitemap_include_images` | bool (default true) | Whether product images are included in the sitemap |
| `wog_system_info` | array | The system information block |

## Example: change the image cap

```php
add_filter(
	'wog_max_images_per_product',
	function ( $max ) {
		return 5;
	}
);
```

## Example: adjust product meta before output

```php
add_filter(
	'wog_product_meta_data',
	function ( $meta_data, $product, $post ) {
		$meta_data['title'] = 'Sale: ' . $meta_data['title'];
		return $meta_data;
	},
	10,
	3
);
```

## Note on `wp_head_early_og`

The meta-tags class fires an internal `wp_head_early_og` action to scan for
Open Graph tags emitted by other code before its own output. Nothing in the
plugin or WordPress core hooks this action by default, so the scan currently
finds no earlier tags. See
[Troubleshooting](../troubleshooting/index.md) for the practical effect on
duplicate tags.
