<?php
/**
 * Schema Markup Class
 * 
 * Generates structured data for better SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWOG_Schema {
    
    private static $instance = null;
    private $settings;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->settings = get_option('ewog_settings', array());
        $this->init_hooks();
    }
    
    private function init_hooks() {
        if (!empty($this->settings['enable_schema'])) {
            add_action('wp_head', array($this, 'output_schema_markup'), 5);
        }
    }
    
    /**
     * Output schema markup
     */
    public function output_schema_markup() {
        if (!$this->should_add_schema()) {
            return;
        }
        
        $schema_data = $this->get_schema_data();
        
        if (empty($schema_data)) {
            return;
        }
        
        echo "\n<!-- Enhanced Woo Open Graph Schema Markup -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "\n" . '</script>' . "\n";
        echo "<!-- End Enhanced Woo Open Graph Schema Markup -->\n\n";
    }
    
    /**
     * Check if schema should be added
     */
    private function should_add_schema() {
        if (!function_exists('is_woocommerce')) {
            return false;
        }
        
        return is_product() || is_product_category() || is_shop();
    }
    
    /**
     * Get schema data for current page
     */
    private function get_schema_data() {
        global $post;
        
        if (is_product() && $post) {
            return $this->get_product_schema($post);
        } elseif (is_product_category()) {
            return $this->get_category_schema();
        } elseif (is_shop()) {
            return $this->get_shop_schema();
        }
        
        return array();
    }
    
    /**
     * Get product schema
     */
    private function get_product_schema($post) {
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return array();
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title($post->ID),
            'description' => $this->get_product_description($product),
            'url' => get_permalink($post->ID),
            'image' => $this->get_product_images($product),
            'sku' => $product->get_sku(),
            'gtin' => $this->get_product_gtin($product),
            'brand' => $this->get_product_brand_schema($product),
            'category' => $this->get_product_categories_schema($product),
            'offers' => $this->get_product_offers_schema($product),
            'aggregateRating' => $this->get_product_rating_schema($product),
            'review' => $this->get_product_reviews_schema($product)
        );
        
        // Remove empty values
        $schema = array_filter($schema, function($value) {
            return !empty($value);
        });
        
        // Add additional product properties
        if ($product->get_weight()) {
            $schema['weight'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $product->get_weight(),
                'unitCode' => get_option('woocommerce_weight_unit', 'kg')
            );
        }
        
        if ($product->get_dimensions(false)) {
            $dimensions = $product->get_dimensions(false);
            $schema['depth'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $dimensions['length'],
                'unitCode' => get_option('woocommerce_dimension_unit', 'cm')
            );
            $schema['width'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $dimensions['width'],
                'unitCode' => get_option('woocommerce_dimension_unit', 'cm')
            );
            $schema['height'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $dimensions['height'],
                'unitCode' => get_option('woocommerce_dimension_unit', 'cm')
            );
        }
        
        return apply_filters('ewog_product_schema', $schema, $product, $post);
    }
    
    /**
     * Get category schema
     */
    private function get_category_schema() {
        $category = get_queried_object();
        
        if (!$category) {
            return array();
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'ProductCategory',
            'name' => $category->name,
            'description' => $category->description,
            'url' => get_term_link($category),
            'image' => $this->get_category_image_schema($category)
        );
        
        return array_filter($schema);
    }
    
    /**
     * Get shop schema
     */
    private function get_shop_schema() {
        $shop_page_id = wc_get_page_id('shop');
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => get_the_title($shop_page_id),
            'description' => get_post_meta($shop_page_id, '_yoast_wpseo_metadesc', true) ?: get_bloginfo('description'),
            'url' => get_permalink($shop_page_id),
            'image' => $this->get_shop_image_schema()
        );
        
        return array_filter($schema);
    }
    
    /**
     * Helper methods for schema generation
     */
    
    private function get_product_description($product) {
        $description = $product->get_short_description();
        if (empty($description)) {
            $description = $product->get_description();
        }
        return wp_strip_all_tags($description);
    }
    
    private function get_product_images($product) {
        $images = array();
        
        // Featured image
        if ($product->get_image_id()) {
            $image_url = wp_get_attachment_image_src($product->get_image_id(), 'full');
            if ($image_url) {
                $images[] = $image_url[0];
            }
        }
        
        // Gallery images
        $gallery_images = $product->get_gallery_image_ids();
        foreach ($gallery_images as $image_id) {
            $image_url = wp_get_attachment_image_src($image_id, 'full');
            if ($image_url) {
                $images[] = $image_url[0];
            }
        }
        
        return !empty($images) ? $images : array(wc_placeholder_img_src('full'));
    }
    
    private function get_product_gtin($product) {
        // Check for common GTIN meta fields
        $gtin_fields = array('_gtin', '_upc', '_ean', '_isbn');
        
        foreach ($gtin_fields as $field) {
            $gtin = get_post_meta($product->get_id(), $field, true);
            if (!empty($gtin)) {
                return $gtin;
            }
        }
        
        return '';
    }
    
    private function get_product_brand_schema($product) {
        $brand_name = $this->get_product_brand($product);
        
        if (empty($brand_name)) {
            return null;
        }
        
        return array(
            '@type' => 'Brand',
            'name' => $brand_name
        );
    }
    
    private function get_product_brand($product) {
        // Check for common brand attributes/taxonomies
        $brand_taxonomies = array('product_brand', 'pwb-brand', 'yith_product_brand');
        
        foreach ($brand_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $terms = get_the_terms($product->get_id(), $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    return $terms[0]->name;
                }
            }
        }
        
        // Check for brand meta field
        $brand = get_post_meta($product->get_id(), '_brand', true);
        if (!empty($brand)) {
            return $brand;
        }
        
        return '';
    }
    
    private function get_product_categories_schema($product) {
        $categories = get_the_terms($product->get_id(), 'product_cat');
        
        if (!$categories || is_wp_error($categories)) {
            return '';
        }
        
        return $categories[0]->name;
    }
    
    private function get_product_offers_schema($product) {
        $offers = array(
            '@type' => 'Offer',
            'url' => get_permalink($product->get_id()),
            'priceCurrency' => get_woocommerce_currency(),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'priceValidUntil' => date('Y-m-d', strtotime('+1 year')),
            'seller' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            )
        );
        
        if ($product->get_price()) {
            $offers['price'] = $product->get_price();
        }
        
        if ($product->is_on_sale() && $product->get_regular_price()) {
            $offers['priceSpecification'] = array(
                '@type' => 'UnitPriceSpecification',
                'price' => $product->get_regular_price(),
                'priceCurrency' => get_woocommerce_currency()
            );
        }
        
        // Add condition
        $offers['itemCondition'] = 'https://schema.org/NewCondition';
        
        return $offers;
    }
    
    private function get_product_rating_schema($product) {
        $average_rating = $product->get_average_rating();
        $review_count = $product->get_review_count();
        
        if (!$average_rating || !$review_count) {
            return null;
        }
        
        return array(
            '@type' => 'AggregateRating',
            'ratingValue' => $average_rating,
            'reviewCount' => $review_count,
            'bestRating' => '5',
            'worstRating' => '1'
        );
    }
    
    private function get_product_reviews_schema($product) {
        $reviews = get_comments(array(
            'post_id' => $product->get_id(),
            'status' => 'approve',
            'type' => 'review',
            'number' => 5, // Limit to 5 reviews for performance
            'meta_query' => array(
                array(
                    'key' => 'rating',
                    'value' => 0,
                    'compare' => '>'
                )
            )
        ));
        
        if (empty($reviews)) {
            return null;
        }
        
        $schema_reviews = array();
        
        foreach ($reviews as $review) {
            $rating = get_comment_meta($review->comment_ID, 'rating', true);
            
            if (!$rating) {
                continue;
            }
            
            $schema_reviews[] = array(
                '@type' => 'Review',
                'reviewRating' => array(
                    '@type' => 'Rating',
                    'ratingValue' => $rating,
                    'bestRating' => '5',
                    'worstRating' => '1'
                ),
                'author' => array(
                    '@type' => 'Person',
                    'name' => $review->comment_author
                ),
                'reviewBody' => $review->comment_content,
                'datePublished' => get_comment_date('c', $review->comment_ID)
            );
        }
        
        return $schema_reviews;
    }
    
    private function get_category_image_schema($category) {
        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        
        if ($thumbnail_id) {
            $image = wp_get_attachment_image_src($thumbnail_id, 'full');
            if ($image) {
                return $image[0];
            }
        }
        
        return wc_placeholder_img_src('full');
    }
    
    private function get_shop_image_schema() {
        $custom_logo_id = get_theme_mod('custom_logo');
        
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo) {
                return $logo[0];
            }
        }
        
        return wc_placeholder_img_src('full');
    }
}