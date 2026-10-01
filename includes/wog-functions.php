<?php
/**
 * Plugin helper functions.
 *
 * @package Woo_Open_Graph
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin instance.
 *
 * @return Woo_Open_Graph
 */
function wog() {
	return Woo_Open_Graph::get_instance();
}

/**
 * Get plugin settings.
 *
 * @return array
 */
function wog_get_settings() {
	return wog()->get_settings();
}

/**
 * Log debug message.
 *
 * @param string $message The debug message.
 * @param mixed  $data    Optional data to log.
 */
function wog_debug_log( $message, $data = null ) {
	wog()->debug_log( $message, $data );
}

/**
 * Get a product GTIN.
 *
 * Prefers WooCommerce's native global unique id (WC 9.2+) before custom meta.
 * Single source of truth used by both the meta-tag and schema classes.
 *
 * @param WC_Product $product The product object.
 * @return string
 */
function wog_get_product_gtin( $product ) {
	if ( is_callable( array( $product, 'get_global_unique_id' ) ) ) {
		$gtin = $product->get_global_unique_id();
		if ( ! empty( $gtin ) ) {
			return $gtin;
		}
	}

	$gtin_fields = array( '_gtin', '_upc', '_ean', '_isbn', '_gtin8', '_gtin12', '_gtin13', '_gtin14' );

	foreach ( $gtin_fields as $field ) {
		$gtin = get_post_meta( $product->get_id(), $field, true );
		if ( ! empty( $gtin ) ) {
			return $gtin;
		}
	}

	return '';
}

/**
 * Get a product MPN from custom meta.
 *
 * @param WC_Product $product The product object.
 * @return string
 */
function wog_get_product_mpn( $product ) {
	$mpn = get_post_meta( $product->get_id(), '_mpn', true );
	if ( empty( $mpn ) ) {
		$mpn = get_post_meta( $product->get_id(), '_manufacturer_part_number', true );
	}
	return $mpn;
}

/**
 * Get a product brand name from common brand taxonomies, then meta.
 *
 * @param WC_Product $product The product object.
 * @return string
 */
function wog_get_product_brand( $product ) {
	$brand_taxonomies = array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'pa_brand' );

	foreach ( $brand_taxonomies as $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			$terms = get_the_terms( $product->get_id(), $taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				return $terms[0]->name;
			}
		}
	}

	return (string) get_post_meta( $product->get_id(), '_brand', true );
}

/**
 * Whether social output is allowed for a product.
 *
 * Covers the Open Graph / Twitter tags, the share buttons, their assets and the
 * html prefix. Product JSON-LD is search markup and is deliberately not gated.
 * Every output layer asks this one function, so the per-product switch cannot
 * drift again when a layer is added.
 *
 * @since 2.1.0
 * @param int $product_id Product ID.
 * @return bool
 */
function wog_is_social_enabled_for_product( $product_id ) {
	$enabled = ! get_post_meta( $product_id, '_wog_disable_og', true );

	/**
	 * Filter whether social output is allowed for a product.
	 *
	 * @param bool $enabled    Whether social output is allowed.
	 * @param int  $product_id Product ID.
	 */
	return (bool) apply_filters( 'wog_social_enabled_for_product', $enabled, $product_id );
}

/**
 * Image URL used when a product or archive has no image of its own.
 *
 * The settings fallback image, else the WooCommerce placeholder at the
 * configured image size. Shared by the Open Graph and JSON-LD layers.
 *
 * @since 2.1.0
 * @return string
 */
function wog_get_fallback_image_url() {
	$settings = wog_get_settings();

	if ( ! empty( $settings['fallback_image'] ) ) {
		return $settings['fallback_image'];
	}

	return wc_placeholder_img_src( ! empty( $settings['image_size'] ) ? $settings['image_size'] : 'large' );
}

/**
 * Product price for share text: a range when a variable or grouped product's
 * children differ in price, otherwise the single price.
 *
 * @since 2.1.0
 * @param WC_Product $product The product object.
 * @return string Price HTML (wc_price), or '' when the product has no price.
 */
function wog_get_product_price_text( $product ) {
	if ( $product instanceof WC_Product_Variable ) {
		$min = $product->get_variation_price( 'min', true );
		$max = $product->get_variation_price( 'max', true );
	} elseif ( $product instanceof WC_Product_Grouped ) {
		$prices = array();
		foreach ( array_filter( array_map( 'wc_get_product', $product->get_children() ) ) as $child ) {
			if ( '' !== $child->get_price() ) {
				$prices[] = (float) $child->get_price();
			}
		}
		$min = $prices ? min( $prices ) : '';
		$max = $prices ? max( $prices ) : '';
	} else {
		$min = $product->get_price();
		$max = $min;
	}

	if ( '' === $min ) {
		return '';
	}

	return (float) $min === (float) $max ? wc_price( $min ) : wc_price( $min ) . ' – ' . wc_price( $max );
}
