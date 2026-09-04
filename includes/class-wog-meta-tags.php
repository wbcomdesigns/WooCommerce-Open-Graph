<?php
/**
 * Meta Tags Handler Class.
 *
 * Generates Open Graph, Twitter Card, and other social media meta tags.
 *
 * @package Woo_Open_Graph
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOG_Meta_Tags
 *
 * Handles generation of social media meta tags.
 */
class WOG_Meta_Tags {

	/**
	 * Singleton instance.
	 *
	 * @var WOG_Meta_Tags|null
	 */
	private static $instance = null;

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Existing Open Graph tags from other plugins.
	 *
	 * @var array
	 */
	private $existing_og_tags = array();

	/**
	 * Get singleton instance.
	 *
	 * @return WOG_Meta_Tags
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		$this->settings = get_option( 'wog_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Set up WordPress hooks.
	 */
	private function init_hooks() {
		add_action( 'wp_head', array( $this, 'start_head_buffer' ), 0 );
		add_action( 'wp_head', array( $this, 'scan_existing_tags' ), 14 );
		add_action( 'wp_head', array( $this, 'output_meta_tags' ), 15 );
		add_filter( 'language_attributes', array( $this, 'add_opengraph_namespace' ) );
	}

	/**
	 * Begin buffering <head> so tags already emitted by an SEO plugin can be detected.
	 *
	 * The buffer is intentionally left open; PHP flushes it at the end of wp_head,
	 * so all captured markup (SEO plugin tags plus ours) still reaches the browser.
	 */
	public function start_head_buffer() {
		if ( ! $this->should_add_meta_tags() ) {
			return;
		}

		// ponytail: reads the top output buffer to spot OG/Twitter tags any SEO plugin
		// (Yoast/RankMath/SEOPress/...) already printed; a nested buffer another plugin
		// opens and never closes before priority 14 could hide tags. Rare, acceptable.
		ob_start();
	}

	/**
	 * Scan the buffered <head> for Open Graph / Twitter tags emitted by other plugins.
	 */
	public function scan_existing_tags() {
		if ( ! $this->should_add_meta_tags() ) {
			return;
		}

		$head = ob_get_contents();
		if ( false === $head || '' === $head ) {
			return;
		}

		if ( preg_match_all( '/<meta\s+property=["\']og:([^"\']+)["\'][^>]*>/i', $head, $matches ) ) {
			foreach ( $matches[1] as $property ) {
				$this->existing_og_tags[] = strtolower( $property );
			}
		}

		if ( preg_match_all( '/<meta\s+name=["\']twitter:([^"\']+)["\'][^>]*>/i', $head, $matches ) ) {
			foreach ( $matches[1] as $property ) {
				$this->existing_og_tags[] = 'twitter:' . strtolower( $property );
			}
		}
	}

	/**
	 * Check if a specific tag already exists.
	 *
	 * @param string $property The tag property name.
	 * @return bool
	 */
	private function tag_exists( $property ) {
		$property = strtolower( $property );
		return in_array( $property, $this->existing_og_tags, true ) ||
				in_array( 'og:' . $property, $this->existing_og_tags, true ) ||
				in_array( 'twitter:' . $property, $this->existing_og_tags, true );
	}

	/**
	 * Add Open Graph namespace to html tag.
	 *
	 * @param string $output The language attributes output.
	 * @return string
	 */
	public function add_opengraph_namespace( $output ) {
		if ( $this->should_add_meta_tags() ) {
			$namespaces = array(
				'og: https://ogp.me/ns#',
				'product: https://ogp.me/ns/product#',
			);

			if ( ! empty( $this->settings['facebook_app_id'] ) ) {
				$namespaces[] = 'fb: https://www.facebook.com/2008/fbml';
			}

			return $output . ' prefix="' . implode( ' ', $namespaces ) . '"';
		}
		return $output;
	}

	/**
	 * Output meta tags for social media platforms.
	 */
	public function output_meta_tags() {
		if ( ! $this->should_add_meta_tags() ) {
			return;
		}

		$meta_data = $this->get_meta_data();

		if ( empty( $meta_data ) ) {
			return;
		}

		echo "\n<!-- Woo Open Graph Meta Tags -->\n";

		$this->output_basic_og_tags( $meta_data );

		if ( ! empty( $this->settings['enable_facebook'] ) ) {
			$this->output_facebook_tags( $meta_data );
		}

		if ( ! empty( $this->settings['enable_twitter'] ) ) {
			$this->output_twitter_tags( $meta_data );
		}

		if ( ! empty( $this->settings['enable_linkedin'] ) ) {
			$this->output_linkedin_tags( $meta_data );
		}

		if ( ! empty( $this->settings['enable_pinterest'] ) ) {
			$this->output_pinterest_tags( $meta_data );
		}

		echo "<!-- End Woo Open Graph Meta Tags -->\n\n";
	}

	/**
	 * Check if meta tags should be added to current page.
	 *
	 * @return bool
	 */
	private function should_add_meta_tags() {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return false;
		}

		return is_product() || is_product_category() || is_product_tag() || is_shop() || is_woocommerce();
	}

	/**
	 * Get meta data for current page.
	 *
	 * @return array
	 */
	private function get_meta_data() {
		global $post;

		if ( is_product() && $post ) {
			return $this->get_product_meta_data( $post );
		} elseif ( is_product_category() ) {
			return $this->get_category_meta_data();
		} elseif ( is_product_tag() ) {
			return $this->get_tag_meta_data();
		} elseif ( is_shop() ) {
			return $this->get_shop_meta_data();
		}

		return array();
	}

	/**
	 * Get product meta data with static caching for current request.
	 *
	 * @param WP_Post $post The post object.
	 * @return array
	 */
	private function get_product_meta_data( $post ) {
		static $product_cache = array();

		if ( isset( $product_cache[ $post->ID ] ) ) {
			return $product_cache[ $post->ID ];
		}

		$product = wc_get_product( $post->ID );

		if ( ! $product ) {
			return array();
		}

		// Check if user disabled OG for this product.
		$disabled = get_post_meta( $post->ID, '_wog_disable_og', true );
		if ( $disabled ) {
			$product_cache[ $post->ID ] = array();
			return array();
		}

		// Get custom or generated title and description.
		$custom_title       = get_post_meta( $post->ID, '_wog_og_title', true );
		$custom_description = get_post_meta( $post->ID, '_wog_og_description', true );

		$title       = ! empty( $custom_title ) ? $custom_title : $this->get_optimized_title( $product );
		$description = ! empty( $custom_description ) ? $custom_description : $this->get_optimized_description( $product );

		$meta_data = array(
			'type'        => 'product',
			'title'       => $title,
			'description' => $description,
			'images'      => $this->get_optimized_images( $product ),
			'url'         => get_permalink( $post->ID ),
			'site_name'   => get_bloginfo( 'name' ),
			'product'     => array(
				'price'         => $product->get_price(),
				'regular_price' => $product->get_regular_price(),
				'sale_price'    => $product->get_sale_price(),
				'currency'      => get_woocommerce_currency(),
				'availability'  => $this->get_availability( $product ),
				'condition'     => $this->get_product_condition( $product ),
				'brand'         => $this->get_product_brand( $product ),
				'category'      => $this->get_primary_category( $product ),
				'sku'           => $product->get_sku(),
				'weight'        => $product->get_weight(),
				'rating_value'  => $product->get_average_rating(),
				'review_count'  => $product->get_review_count(),
			),
		);

		if ( ! empty( $this->settings['enable_enhanced_schema'] ) ) {
			$meta_data['product'] = array_merge(
				$meta_data['product'],
				array(
					'retailer_item_id' => $product->get_sku(),
					'item_group_id'    => $this->get_item_group_id( $product ),
					'color'            => $this->get_product_attribute( $product, 'color' ),
					'size'             => $this->get_product_attribute( $product, 'size' ),
					'material'         => $this->get_product_attribute( $product, 'material' ),
					'gtin'             => $this->get_gtin( $product ),
					'mpn'              => $this->get_mpn( $product ),
				)
			);
		}

		$product_cache[ $post->ID ] = apply_filters( 'wog_product_meta_data', $meta_data, $product, $post );

		return $product_cache[ $post->ID ];
	}

	/**
	 * Get optimized product title.
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_optimized_title( $product ) {
		$title = $product->get_name();

		$brand = $this->get_product_brand( $product );
		if ( ! empty( $brand ) ) {
			$title = $brand . ' ' . $title;
		}

		return wp_trim_words( $title, 10, '' );
	}

	/**
	 * Get optimized product description.
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_optimized_description( $product ) {
		$description = $product->get_short_description();
		if ( empty( $description ) ) {
			$description = $product->get_description();
		}

		$enhanced_description = $this->resolve_description( $description );

		if ( $product->get_price() ) {
			$enhanced_description .= ' Price: ' . wc_price( $product->get_price() );
		}

		if ( $product->is_in_stock() ) {
			$enhanced_description .= ' - In Stock';
		}

		return wp_trim_words( $enhanced_description, 35, '...' );
	}

	/**
	 * Resolve a description to a guaranteed non-empty string.
	 *
	 * Falls back through the site tagline to the store name so og:description /
	 * twitter:description is never dropped just because the tagline is blank.
	 *
	 * @param string $primary The preferred description source.
	 * @return string
	 */
	private function resolve_description( $primary ) {
		$candidates = array( $primary, get_bloginfo( 'description' ), get_bloginfo( 'name' ) );

		foreach ( $candidates as $candidate ) {
			$candidate = trim( wp_strip_all_tags( (string) $candidate ) );
			if ( '' !== $candidate ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Get optimized product images.
	 *
	 * @param WC_Product $product The product object.
	 * @return array
	 */
	private function get_optimized_images( $product ) {
		static $image_cache = array();

		$product_id = $product->get_id();

		if ( isset( $image_cache[ $product_id ] ) ) {
			return $image_cache[ $product_id ];
		}

		$images     = array();
		$image_size = ! empty( $this->settings['image_size'] ) ? $this->settings['image_size'] : 'large';

		if ( $product->get_image_id() ) {
			$image_data = wp_get_attachment_image_src( $product->get_image_id(), $image_size );
			if ( $image_data ) {
				$alt_text = get_post_meta( $product->get_image_id(), '_wp_attachment_image_alt', true );
				$images[] = array(
					'url'    => $image_data[0],
					'width'  => $image_data[1],
					'height' => $image_data[2],
					'alt'    => ! empty( $alt_text ) ? $alt_text : $product->get_name(),
					'type'   => get_post_mime_type( $product->get_image_id() ),
				);
			}
		}

		// Limit gallery images for performance.
		$max_images      = apply_filters( 'wog_max_images_per_product', 3 );
		$remaining_slots = $max_images - count( $images );

		if ( $remaining_slots > 0 ) {
			$gallery_ids = $product->get_gallery_image_ids();
			foreach ( array_slice( $gallery_ids, 0, $remaining_slots ) as $image_id ) {
				$image_data = wp_get_attachment_image_src( $image_id, $image_size );
				if ( $image_data ) {
					$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
					$images[] = array(
						'url'    => $image_data[0],
						'width'  => $image_data[1],
						'height' => $image_data[2],
						'alt'    => ! empty( $alt_text ) ? $alt_text : $product->get_name(),
						'type'   => get_post_mime_type( $image_id ),
					);
				}
			}
		}

		if ( empty( $images ) ) {
			$fallback_url = $this->get_fallback_image();
			if ( $fallback_url ) {
				// Bare URL, no attachment: omit width/height/type rather than guess.
				$images[] = array(
					'url' => $fallback_url,
					'alt' => $product->get_name(),
				);
			}
		}

		$image_cache[ $product_id ] = $images;
		return $images;
	}

	/**
	 * Output basic Open Graph tags with duplicate prevention.
	 *
	 * @param array $meta_data The meta data array.
	 */
	private function output_basic_og_tags( $meta_data ) {
		$should_override = $this->should_disable_title_description();

		if ( ( ! $this->tag_exists( 'title' ) || $should_override ) && ! empty( $meta_data['title'] ) ) {
			echo '<meta property="og:title" content="' . esc_attr( $meta_data['title'] ) . '" />' . "\n";
		}

		if ( ( ! $this->tag_exists( 'description' ) || $should_override ) && ! empty( $meta_data['description'] ) ) {
			echo '<meta property="og:description" content="' . esc_attr( $meta_data['description'] ) . '" />' . "\n";
		}

		if ( ! $this->tag_exists( 'type' ) ) {
			echo '<meta property="og:type" content="' . esc_attr( $meta_data['type'] ) . '" />' . "\n";
		}

		if ( ! $this->tag_exists( 'url' ) ) {
			echo '<meta property="og:url" content="' . esc_url( $meta_data['url'] ) . '" />' . "\n";
		}

		if ( ! $this->tag_exists( 'site_name' ) ) {
			echo '<meta property="og:site_name" content="' . esc_attr( $meta_data['site_name'] ) . '" />' . "\n";
		}

		if ( ! empty( $meta_data['images'] ) && ! $this->tag_exists( 'image' ) ) {
			foreach ( $meta_data['images'] as $image ) {
				echo '<meta property="og:image" content="' . esc_url( $image['url'] ) . '" />' . "\n";
				echo '<meta property="og:image:secure_url" content="' . esc_url( $image['url'] ) . '" />' . "\n";
				// Emit dimension/type hints only when read from a real attachment; a wrong hint is worse than none.
				if ( ! empty( $image['width'] ) ) {
					echo '<meta property="og:image:width" content="' . esc_attr( $image['width'] ) . '" />' . "\n";
				}
				if ( ! empty( $image['height'] ) ) {
					echo '<meta property="og:image:height" content="' . esc_attr( $image['height'] ) . '" />' . "\n";
				}
				if ( ! empty( $image['type'] ) ) {
					echo '<meta property="og:image:type" content="' . esc_attr( $image['type'] ) . '" />' . "\n";
				}
				if ( ! empty( $image['alt'] ) ) {
					echo '<meta property="og:image:alt" content="' . esc_attr( $image['alt'] ) . '" />' . "\n";
				}
			}
		}

		if ( ! $this->tag_exists( 'locale' ) ) {
			echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', get_locale() ) ) . '" />' . "\n";
		}
	}

	/**
	 * Output Facebook specific tags.
	 *
	 * @param array $meta_data The meta data array.
	 */
	private function output_facebook_tags( $meta_data ) {
		if ( ! empty( $this->settings['facebook_app_id'] ) ) {
			echo '<meta property="fb:app_id" content="' . esc_attr( $this->settings['facebook_app_id'] ) . '" />' . "\n";
		}

		if ( 'product' === $meta_data['type'] && ! empty( $meta_data['product'] ) ) {
			$product = $meta_data['product'];

			if ( ! empty( $product['price'] ) ) {
				echo '<meta property="product:price:amount" content="' . esc_attr( $product['price'] ) . '" />' . "\n";
				echo '<meta property="product:price:currency" content="' . esc_attr( $product['currency'] ) . '" />' . "\n";
			}

			if ( ! empty( $product['availability'] ) ) {
				echo '<meta property="product:availability" content="' . esc_attr( $product['availability'] ) . '" />' . "\n";
			}

			if ( ! empty( $product['condition'] ) ) {
				echo '<meta property="product:condition" content="' . esc_attr( $product['condition'] ) . '" />' . "\n";
			}

			if ( ! empty( $product['brand'] ) ) {
				echo '<meta property="product:brand" content="' . esc_attr( $product['brand'] ) . '" />' . "\n";
			}

			if ( ! empty( $product['category'] ) ) {
				echo '<meta property="product:category" content="' . esc_attr( $product['category'] ) . '" />' . "\n";
			}
		}
	}

	/**
	 * Output Twitter Card tags with duplicate prevention.
	 *
	 * @param array $meta_data The meta data array.
	 */
	private function output_twitter_tags( $meta_data ) {
		$should_override = $this->should_disable_title_description();

		$card_type = ( 'product' === $meta_data['type'] ) ? 'product' : 'summary_large_image';
		if ( ! $this->tag_exists( 'twitter:card' ) || $should_override ) {
			echo '<meta name="twitter:card" content="' . esc_attr( $card_type ) . '" />' . "\n";
		}

		if ( ! empty( $this->settings['twitter_username'] ) ) {
			if ( ! $this->tag_exists( 'twitter:site' ) ) {
				echo '<meta name="twitter:site" content="@' . esc_attr( $this->settings['twitter_username'] ) . '" />' . "\n";
			}
			if ( ! $this->tag_exists( 'twitter:creator' ) ) {
				echo '<meta name="twitter:creator" content="@' . esc_attr( $this->settings['twitter_username'] ) . '" />' . "\n";
			}
		}

		if ( ( ! $this->tag_exists( 'twitter:title' ) || $should_override ) && ! empty( $meta_data['title'] ) ) {
			echo '<meta name="twitter:title" content="' . esc_attr( $meta_data['title'] ) . '" />' . "\n";
		}

		if ( ( ! $this->tag_exists( 'twitter:description' ) || $should_override ) && ! empty( $meta_data['description'] ) ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $meta_data['description'] ) . '" />' . "\n";
		}

		if ( ! empty( $meta_data['images'] ) ) {
			$first_image = $meta_data['images'][0];
			if ( ! $this->tag_exists( 'twitter:image' ) ) {
				echo '<meta name="twitter:image" content="' . esc_url( $first_image['url'] ) . '" />' . "\n";
				echo '<meta name="twitter:image:alt" content="' . esc_attr( $first_image['alt'] ) . '" />' . "\n";
			}
		}

		if ( 'product' === $meta_data['type'] && ! empty( $meta_data['product'] ) ) {
			$product = $meta_data['product'];

			if ( ! empty( $product['price'] ) ) {
				echo '<meta name="twitter:label1" content="Price" />' . "\n";
				echo '<meta name="twitter:data1" content="' . esc_attr( $product['currency'] . ' ' . $product['price'] ) . '" />' . "\n";
			}

			if ( ! empty( $product['availability'] ) ) {
				echo '<meta name="twitter:label2" content="Availability" />' . "\n";
				echo '<meta name="twitter:data2" content="' . esc_attr( ucfirst( $product['availability'] ) ) . '" />' . "\n";
			}
		}
	}

	/**
	 * Output LinkedIn optimization tags.
	 *
	 * @param array $meta_data The meta data array.
	 */
	private function output_linkedin_tags( $meta_data ) {
		if ( ! empty( $meta_data['title'] ) ) {
			echo '<meta name="linkedin:title" content="' . esc_attr( $meta_data['title'] ) . '" />' . "\n";
		}

		if ( ! empty( $meta_data['description'] ) ) {
			echo '<meta name="linkedin:description" content="' . esc_attr( $meta_data['description'] ) . '" />' . "\n";
		}
	}

	/**
	 * Output Pinterest Rich Pins tags.
	 *
	 * @param array $meta_data The meta data array.
	 */
	private function output_pinterest_tags( $meta_data ) {
		// Pinterest Rich Pins read the standard product:*/og:* tags emitted by
		// output_facebook_tags(); the only Pinterest-specific tag needed is the
		// rich-pin opt-in. Re-emitting product:price:*/availability here produced
		// duplicate meta tags on every product page.
		echo '<meta name="pinterest-rich-pin" content="true" />' . "\n";
	}

	/**
	 * Check if title/description override is enabled.
	 *
	 * @return bool
	 */
	private function should_disable_title_description() {
		return ! empty( $this->settings['disable_title_description'] );
	}

	/**
	 * Get product availability status.
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_availability( $product ) {
		if ( $product->is_in_stock() ) {
			if ( $product->managing_stock() ) {
				$stock_quantity = $product->get_stock_quantity();
				if ( $stock_quantity > 10 ) {
					return 'in stock';
				} elseif ( $stock_quantity > 0 ) {
					return 'limited availability';
				} else {
					return 'out of stock';
				}
			} else {
				return 'in stock';
			}
		} else {
			return 'out of stock';
		}
	}

	/**
	 * Get product condition.
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_product_condition( $product ) {
		$condition = get_post_meta( $product->get_id(), '_condition', true );
		return ! empty( $condition ) ? $condition : 'new';
	}

	/**
	 * Get product brand from various brand taxonomies.
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_product_brand( $product ) {
		return wog_get_product_brand( $product );
	}

	/**
	 * Get primary product category.
	 *
	 * @param WC_Product $product The product object.
	 * @return string
	 */
	private function get_primary_category( $product ) {
		static $category_cache = array();

		$product_id = $product->get_id();

		if ( isset( $category_cache[ $product_id ] ) ) {
			return $category_cache[ $product_id ];
		}

		$categories = get_the_terms( $product_id, 'product_cat' );
		$category   = '';
		if ( $categories && ! is_wp_error( $categories ) ) {
			$category = $categories[0]->name;
		}

		$category_cache[ $product_id ] = $category;
		return $category;
	}

	/**
	 * Get item group ID for variable products.
	 *
	 * @param WC_Product $product The product object.
	 * @return int
	 */
	private function get_item_group_id( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			return $product->get_parent_id();
		}

		return $product->get_id();
	}

	/**
	 * Get a specific product attribute value.
	 *
	 * @param WC_Product $product   The product object.
	 * @param string     $attribute The attribute name.
	 * @return string
	 */
	private function get_product_attribute( $product, $attribute ) {
		$value = $product->get_attribute( 'pa_' . $attribute );
		if ( ! empty( $value ) ) {
			return $value;
		}

		return get_post_meta( $product->get_id(), '_' . $attribute, true );
	}

	/**
	 * Get product GTIN from various meta fields.
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
	 * Get fallback image URL.
	 *
	 * @return string
	 */
	private function get_fallback_image() {
		if ( ! empty( $this->settings['fallback_image'] ) ) {
			return $this->settings['fallback_image'];
		}

		return wc_placeholder_img_src( 'large' );
	}

	/**
	 * Get category meta data.
	 *
	 * @return array
	 */
	private function get_category_meta_data() {
		$category = get_queried_object();

		if ( ! $category ) {
			return array();
		}

		$title       = $this->should_disable_title_description() ? '' : $category->name;
		$description = $this->should_disable_title_description() ? '' : $this->resolve_description( $category->description );
		$image       = $this->get_category_image( $category );
		$url         = get_term_link( $category );

		return array(
			'type'        => 'website',
			'title'       => $title,
			'description' => $description,
			'images'      => $image ? array( $image ) : array(),
			'url'         => $url,
			'site_name'   => get_bloginfo( 'name' ),
		);
	}

	/**
	 * Get tag meta data.
	 *
	 * @return array
	 */
	private function get_tag_meta_data() {
		$tag = get_queried_object();

		if ( ! $tag ) {
			return array();
		}

		return array(
			'type'        => 'website',
			'title'       => $tag->name,
			'description' => $this->resolve_description( $tag->description ),
			'images'      => $this->fallback_image_array( $tag->name ),
			'url'         => get_term_link( $tag ),
			'site_name'   => get_bloginfo( 'name' ),
		);
	}

	/**
	 * Get shop page meta data.
	 *
	 * @return array
	 */
	private function get_shop_meta_data() {
		$shop_page_id = wc_get_page_id( 'shop' );

		$meta_desc = get_post_meta( $shop_page_id, '_yoast_wpseo_metadesc', true );

		return array(
			'type'        => 'website',
			'title'       => get_the_title( $shop_page_id ),
			'description' => $this->resolve_description( $meta_desc ),
			'images'      => $this->fallback_image_array( get_the_title( $shop_page_id ) ),
			'url'         => get_permalink( $shop_page_id ),
			'site_name'   => get_bloginfo( 'name' ),
		);
	}

	/**
	 * Build an images array for a bare fallback URL (no width/height/type hints).
	 *
	 * @param string $alt Alt text for the image.
	 * @return array
	 */
	private function fallback_image_array( $alt ) {
		$url = $this->get_fallback_image();
		if ( ! $url ) {
			return array();
		}

		return array(
			array(
				'url' => $url,
				'alt' => $alt,
			),
		);
	}

	/**
	 * Get category image as an OG image array.
	 *
	 * When the category has a real thumbnail, honest width/height/type are read
	 * from the attachment. Otherwise only the fallback URL is returned (no hints).
	 *
	 * @param WP_Term $category The category term object.
	 * @return array|null
	 */
	private function get_category_image( $category ) {
		$thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );

		if ( $thumbnail_id ) {
			$image = wp_get_attachment_image_src( $thumbnail_id, 'large' );
			if ( $image ) {
				return array(
					'url'    => $image[0],
					'width'  => $image[1],
					'height' => $image[2],
					'type'   => get_post_mime_type( $thumbnail_id ),
					'alt'    => $category->name,
				);
			}
		}

		$fallback = $this->get_fallback_image();
		if ( ! $fallback ) {
			return null;
		}

		return array(
			'url' => $fallback,
			'alt' => $category->name,
		);
	}
}
