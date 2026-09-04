<?php
/**
 * Enhanced Schema Markup Class.
 *
 * Comprehensive Schema.org structured data for WooCommerce.
 *
 * @package Woo_Open_Graph
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOG_Schema
 *
 * Generates Schema.org structured data for products and store pages.
 */
class WOG_Schema {

	/**
	 * Singleton instance.
	 *
	 * @var WOG_Schema|null
	 */
	private static $instance = null;

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Get singleton instance.
	 *
	 * @return WOG_Schema
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings = get_option( 'wog_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		if ( ! empty( $this->settings['enable_schema'] ) ) {
			// Standalone nodes WooCommerce does not own (Organization / Breadcrumb / Category / Shop).
			add_action( 'wp_head', array( $this, 'output_schema_markup' ), 5 );

			// Product data is gap-filled onto WooCommerce core's own Product graph
			// instead of emitting a second, competing JSON-LD Product <script>.
			add_filter( 'woocommerce_structured_data_product', array( $this, 'filter_product_structured_data' ), 20, 2 );
		}
	}

	/**
	 * Gap-fill WooCommerce's Product structured data with fields it omits.
	 *
	 * Never overwrites keys WooCommerce already set, and never adds a second
	 * Product graph. GTIN prefers the native global unique id (WC 9.2+).
	 *
	 * @param array      $markup  WooCommerce's product structured data.
	 * @param WC_Product $product The product object.
	 * @return array
	 */
	public function filter_product_structured_data( $markup, $product ) {
		if ( ! is_array( $markup ) || ! $product instanceof WC_Product ) {
			return $markup;
		}

		$gap = array();

		if ( empty( $markup['brand'] ) ) {
			$brand = $this->get_enhanced_brand_schema( $product );
			if ( $brand ) {
				$gap['brand'] = $brand;
			}
		}

		if ( empty( $markup['gtin'] ) ) {
			$gtin = $this->get_gtin( $product );
			if ( $gtin ) {
				$gap['gtin'] = $gtin;
			}
		}

		if ( empty( $markup['mpn'] ) ) {
			$mpn = $this->get_mpn( $product );
			if ( $mpn ) {
				$gap['mpn'] = $mpn;
			}
		}

		if ( ! empty( $this->settings['enable_enhanced_schema'] ) ) {
			if ( empty( $markup['manufacturer'] ) ) {
				$manufacturer = $this->get_manufacturer_schema( $product );
				if ( $manufacturer ) {
					$gap['manufacturer'] = $manufacturer;
				}
			}

			if ( empty( $markup['model'] ) ) {
				$model = $this->get_product_model( $product );
				if ( $model ) {
					$gap['model'] = $model;
				}
			}

			if ( empty( $markup['additionalProperty'] ) ) {
				$specs = $this->get_product_specifications( $product );
				if ( ! empty( $specs ) ) {
					$gap['additionalProperty'] = $specs;
				}
			}
		}

		return array_merge( $markup, $gap );
	}

	/**
	 * Output schema markup.
	 */
	public function output_schema_markup() {
		if ( ! $this->should_add_schema() ) {
			return;
		}

		$schemas = array();

		// Get page-specific schema. Product data is added to WooCommerce's own
		// Product graph via filter_product_structured_data(), not emitted here.
		if ( is_product() ) {
			if ( ! empty( $this->settings['enable_breadcrumb_schema'] ) ) {
				$schemas['breadcrumb'] = $this->get_breadcrumb_schema();
			}
		} elseif ( is_product_category() ) {
			$schemas['category'] = $this->get_category_schema();
		} elseif ( is_shop() ) {
			$schemas['shop'] = $this->get_shop_schema();
		}

		// Add organization schema globally.
		if ( ! empty( $this->settings['enable_organization_schema'] ) ) {
			$schemas['organization'] = $this->get_organization_schema();
		}

		// Output schemas.
		foreach ( $schemas as $type => $schema_data ) {
			if ( ! empty( $schema_data ) ) {
				echo "\n<!-- Enhanced Woo Open Graph Schema: " . esc_html( $type ) . " -->\n";
				echo '<script type="application/ld+json">' . "\n";
				echo wp_json_encode( $schema_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				echo "\n" . '</script>' . "\n";
			}
		}
	}

	/**
	 * Check if schema should be added.
	 *
	 * @return bool
	 */
	private function should_add_schema() {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return false;
		}

		return is_product() || is_product_category() || is_shop() || is_woocommerce();
	}

	/**
	 * Get breadcrumb schema.
	 *
	 * @return array|null
	 */
	private function get_breadcrumb_schema() {
		if ( ! is_product() ) {
			return null;
		}

		global $post;
		$product    = wc_get_product( $post->ID );
		$categories = get_the_terms( $product->get_id(), 'product_cat' );

		if ( ! $categories ) {
			return null;
		}

		$breadcrumbs = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(),
		);

		$position = 1;

		// Add home.
		$breadcrumbs['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => 'Home',
			'item'     => home_url(),
		);

		// Add shop.
		$breadcrumbs['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => 'Shop',
			'item'     => get_permalink( wc_get_page_id( 'shop' ) ),
		);

		// Add category hierarchy.
		$category  = $categories[0];
		$ancestors = get_ancestors( $category->term_id, 'product_cat' );
		$ancestors = array_reverse( $ancestors );

		foreach ( $ancestors as $ancestor_id ) {
			$ancestor                         = get_term( $ancestor_id, 'product_cat' );
			$breadcrumbs['itemListElement'][] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $ancestor->name,
				'item'     => get_term_link( $ancestor ),
			);
		}

		// Add current category.
		$breadcrumbs['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => $category->name,
			'item'     => get_term_link( $category ),
		);

		// Add current product.
		$breadcrumbs['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => get_the_title( $post->ID ),
			'item'     => get_permalink( $post->ID ),
		);

		return $breadcrumbs;
	}

	/**
	 * Get organization schema.
	 *
	 * @return array
	 */
	private function get_organization_schema() {
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url(),
			'logo'     => $this->get_site_logo(),
			'sameAs'   => $this->get_social_profiles(),
		);

		// Add contact information.
		$phone = get_option( 'woocommerce_store_phone', '' );
		$email = get_option( 'woocommerce_store_email', get_option( 'admin_email' ) );

		if ( $phone || $email ) {
			$contact_point = array( '@type' => 'ContactPoint' );

			if ( $phone ) {
				$contact_point['telephone'] = $phone;
			}

			if ( $email ) {
				$contact_point['email'] = $email;
			}

			$contact_point['contactType'] = 'customer service';
			$schema['contactPoint']       = $contact_point;
		}

		// Add store address.
		$address = $this->get_store_address_schema();
		if ( $address ) {
			$schema['address'] = $address;
		}

		return $schema;
	}

	/**
	 * Get product GTIN (native global unique id preferred, then custom meta).
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_gtin( $product ) {
		return wog_get_product_gtin( $product );
	}

	/**
	 * Get product MPN.
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_mpn( $product ) {
		return wog_get_product_mpn( $product );
	}

	/**
	 * Get enhanced brand schema object.
	 *
	 * @param WC_Product $product The product object.
	 * @return array|null
	 */
	private function get_enhanced_brand_schema( $product ) {
		$brand_name = $this->get_product_brand( $product );

		if ( empty( $brand_name ) ) {
			return null;
		}

		return array(
			'@type' => 'Brand',
			'name'  => $brand_name,
		);
	}

	/**
	 * Get product brand name from brand taxonomies.
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_product_brand( $product ) {
		return wog_get_product_brand( $product );
	}

	/**
	 * Get site logo URL.
	 *
	 * @return string
	 */
	private function get_site_logo() {
		$custom_logo_id = get_theme_mod( 'custom_logo' );

		if ( $custom_logo_id ) {
			$logo = wp_get_attachment_image_src( $custom_logo_id, 'full' );
			if ( $logo ) {
				return $logo[0];
			}
		}

		return '';
	}

	/**
	 * Get social media profile URLs.
	 *
	 * @return array
	 */
	private function get_social_profiles() {
		$profiles = array();

		// Get social media URLs from common locations.
		$social_fields = array(
			'facebook_url',
			'twitter_url',
			'instagram_url',
			'linkedin_url',
			'youtube_url',
		);

		foreach ( $social_fields as $field ) {
			$url = get_option( $field );
			if ( ! empty( $url ) ) {
				$profiles[] = $url;
			}
		}

		return $profiles;
	}

	/**
	 * Get store address schema.
	 *
	 * @return array|null
	 */
	private function get_store_address_schema() {
		$address_fields = array(
			'street'     => get_option( 'woocommerce_store_address' ),
			'city'       => get_option( 'woocommerce_store_city' ),
			'state'      => get_option( 'woocommerce_default_state' ),
			'postalCode' => get_option( 'woocommerce_store_postcode' ),
			'country'    => get_option( 'woocommerce_default_country' ),
		);

		$address_fields = array_filter( $address_fields );

		if ( empty( $address_fields ) ) {
			return null;
		}

		$address = array( '@type' => 'PostalAddress' );

		if ( ! empty( $address_fields['street'] ) ) {
			$address['streetAddress'] = $address_fields['street'];
		}

		if ( ! empty( $address_fields['city'] ) ) {
			$address['addressLocality'] = $address_fields['city'];
		}

		if ( ! empty( $address_fields['state'] ) ) {
			$address['addressRegion'] = $address_fields['state'];
		}

		if ( ! empty( $address_fields['postalCode'] ) ) {
			$address['postalCode'] = $address_fields['postalCode'];
		}

		if ( ! empty( $address_fields['country'] ) ) {
			$address['addressCountry'] = $address_fields['country'];
		}

		return $address;
	}

	/**
	 * Get manufacturer schema object.
	 *
	 * @param WC_Product $product The product object.
	 * @return array|null
	 */
	private function get_manufacturer_schema( $product ) {
		$manufacturer = get_post_meta( $product->get_id(), '_manufacturer', true );
		if ( empty( $manufacturer ) ) {
			return null;
		}

		return array(
			'@type' => 'Organization',
			'name'  => $manufacturer,
		);
	}

	/**
	 * Get product model.
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_product_model( $product ) {
		return get_post_meta( $product->get_id(), '_model', true );
	}

	/**
	 * Get product specifications as PropertyValue array.
	 *
	 * @param WC_Product $product The product object.
	 * @return array
	 */
	private function get_product_specifications( $product ) {
		$specifications = array();

		// Get all product attributes.
		$attributes = $product->get_attributes();

		foreach ( $attributes as $attribute ) {
			if ( $attribute->is_taxonomy() ) {
				$terms = get_the_terms( $product->get_id(), $attribute->get_name() );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$specifications[] = array(
						'@type' => 'PropertyValue',
						'name'  => wc_attribute_label( $attribute->get_name() ),
						'value' => implode( ', ', wp_list_pluck( $terms, 'name' ) ),
					);
				}
			} else {
				$specifications[] = array(
					'@type' => 'PropertyValue',
					'name'  => $attribute->get_name(),
					'value' => implode( ', ', $attribute->get_options() ),
				);
			}
		}

		return $specifications;
	}

	/**
	 * Get category schema.
	 *
	 * @return array
	 */
	private function get_category_schema() {
		$category = get_queried_object();

		if ( ! $category ) {
			return array();
		}

		return array(
			'@context'    => 'https://schema.org',
			'@type'       => 'CollectionPage',
			'name'        => $category->name,
			'description' => $category->description,
			'url'         => get_term_link( $category ),
		);
	}

	/**
	 * Get shop page schema.
	 *
	 * @return array
	 */
	private function get_shop_schema() {
		$shop_page_id = wc_get_page_id( 'shop' );

		$meta_desc = get_post_meta( $shop_page_id, '_yoast_wpseo_metadesc', true );

		return array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Store',
			'name'        => get_the_title( $shop_page_id ),
			'description' => ! empty( $meta_desc ) ? $meta_desc : get_bloginfo( 'description' ),
			'url'         => get_permalink( $shop_page_id ),
		);
	}
}
