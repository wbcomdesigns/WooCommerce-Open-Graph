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
