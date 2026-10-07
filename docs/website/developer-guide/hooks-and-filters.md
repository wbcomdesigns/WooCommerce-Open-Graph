# Hooks and Filters

Everything below exists in the 2.1.0 source.

## Actions

| Hook | Arguments | Fires when |
|---|---|---|
| `wog_init` | `$plugin` | The plugin finished starting up |
| `wog_product_meta_saved` | `$post_id` | The product Social Media Settings box was saved |
| `wog_social_share_tracked` | `$platform, $product_id, $url` | The front end reports a share or copy click |
| `wog_product_cache_cleared` | `$product_id` | A product was saved or deleted and its caches cleared |
| `wog_category_cache_cleared` | `$term_id` | A product category was created, edited or deleted |
| `wog_settings_cache_cleared` | none | The `wog_settings` option was updated and the settings cache rebuilt (also on saves from the settings screen) |
| `wog_setting_updated` | `$key, $value` | `WOG_Settings::update()` changed one setting from code |
| `wog_settings_updated` | `$settings` | `WOG_Settings::update_multiple()` saved settings from code. The settings screen does not call it. |
| `wog_settings_reset` | none | `WOG_Settings::reset_to_defaults()` ran |
| `wog_settings_imported` | `$sanitized_settings, $imported_data` | `WOG_Settings::import_settings()` ran |
| `wog_settings_migrated` | `$migrated_settings, $old_settings` | Pre-2.0 settings were carried over once on upgrade |

The reset and import methods have no screen in the admin. They run only if your code calls them.

## Filters

| Hook | Arguments | What it changes |
|---|---|---|
| `wog_social_enabled_for_product` | `$enabled, $product_id` | Whether social output (tags, share buttons, assets, html prefix) is allowed for a product. Default is true unless the product's `_wog_disable_og` meta is set. |
| `wog_product_meta_data` | `$meta_data, $product, $post` | The assembled data for a product page's tags, before they print. See below. |
| `wog_max_images_per_product` | `$max` (default 3) | Number of images printed per product, including the featured image |
| `wog_sitemap_include_images` | `$include` (default true) | Whether product images are listed in the sitemap |
| `wog_admin_tabs` | `$tabs` | The settings screen tabs. Each entry: `label`, `icon`, `group`. A new tab also needs a matching section registry entry in `WOG_Admin::get_sections()` to show fields. |
| `wog_default_settings` | `$defaults` | The defaults array |
| `wog_validated_settings` | `$validated, $settings` | Settings after validation, before saving. Runs for settings screen saves and imports. |
| `wog_{section}_settings` | `$section_settings` | Output of `WOG_Settings::get_section_settings()`. Sections: `schema`, `opengraph`, `sitemap`, `social_share`, `advanced`. |
| `wog_config_summary` | `$summary` | Output of `WOG_Settings::get_config_summary()` |
| `wog_system_info` | `$info` | Output of `Woo_Open_Graph::get_system_info()` |

The last three run only when code calls those methods. The 2.1.0 screens do not.

## JavaScript event

`wog_social_share` is dispatched on `document` for every share or copy click, with `event.detail` holding `platform`, `productId` and `url`.

```js
document.addEventListener( 'wog_social_share', function ( e ) {
	console.log( e.detail.platform, e.detail.productId, e.detail.url );
} );
```

## Examples

### Show a price range in the price tag

For variable products the price tag holds the lowest price, because a tag can hold only one number. The range appears in the description text. To replace the tag value yourself:

```php
add_filter( 'wog_product_meta_data', function ( $meta_data, $product, $post ) {
	if ( isset( $meta_data['product'] ) && $product->is_type( 'variable' ) ) {
		$meta_data['product']['price'] = $product->get_variation_price( 'max', true );
	}
	return $meta_data;
}, 10, 3 );
```

`$meta_data` has these keys: `type`, `title`, `description`, `images` (each with `url`, and when known `width`, `height`, `type`, `alt`), `url`, `site_name` and `product`. `product` holds `price`, `regular_price`, `sale_price`, `currency`, `availability`, `condition`, `brand`, `category`, `sku`, `weight`, `rating_value` and `review_count`. With Enhanced Schema on, `retailer_item_id`, `item_group_id`, `color`, `size`, `material`, `gtin` and `mpn` are added.

The result is kept for the rest of the request.

### Turn social output off for a category of products

```php
add_filter( 'wog_social_enabled_for_product', function ( $enabled, $product_id ) {
	return has_term( 'private-sale', 'product_cat', $product_id ) ? false : $enabled;
}, 10, 2 );
```

### Allow more images per product

```php
add_filter( 'wog_max_images_per_product', function () {
	return 5;
} );
```
