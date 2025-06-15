<?php
/**
 * Enhanced Meta Tags Handler Class
 * 
 * Comprehensive Open Graph, Twitter Card, and social media meta tags
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWOG_Meta_Tags {
    
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
        add_action('wp_head', array($this, 'output_meta_tags'), 1);
        add_filter('language_attributes', array($this, 'add_opengraph_namespace'));
    }
    
    /**
     * Add Open Graph namespace to html tag
     */
    public function add_opengraph_namespace($output) {
        if ($this->should_add_meta_tags()) {
            $namespaces = array(
                'og: https://ogp.me/ns#',
                'product: https://ogp.me/ns/product#'
            );
            
            if (!empty($this->settings['facebook_app_id'])) {
                $namespaces[] = 'fb: https://www.facebook.com/2008/fbml';
            }
            
            return $output . ' prefix="' . implode(' ', $namespaces) . '"';
        }
        return $output;
    }
    
    /**
     * Output meta tags
     */
    public function output_meta_tags() {
        if (!$this->should_add_meta_tags()) {
            return;
        }
        
        $meta_data = $this->get_meta_data();
        
        if (empty($meta_data)) {
            return;
        }
        
        echo "\n<!-- Enhanced Woo Open Graph Meta Tags -->\n";
        
        // Basic Open Graph tags
        $this->output_basic_og_tags($meta_data);
        
        // Facebook specific tags
        if (!empty($this->settings['enable_facebook'])) {
            $this->output_facebook_tags($meta_data);
        }
        
        // Twitter Card tags
        if (!empty($this->settings['enable_twitter'])) {
            $this->output_twitter_tags($meta_data);
        }
        
        // LinkedIn tags
        if (!empty($this->settings['enable_linkedin'])) {
            $this->output_linkedin_tags($meta_data);
        }
        
        // Pinterest tags
        if (!empty($this->settings['enable_pinterest'])) {
            $this->output_pinterest_tags($meta_data);
        }
        
        // WhatsApp tags
        if (!empty($this->settings['enable_whatsapp'])) {
            $this->output_whatsapp_tags($meta_data);
        }
        
        echo "<!-- End Enhanced Woo Open Graph Meta Tags -->\n\n";
    }
    
    /**
     * Check if meta tags should be added
     */
    private function should_add_meta_tags() {
        if (!function_exists('is_woocommerce')) {
            return false;
        }
        
        return is_product() || is_product_category() || is_product_tag() || is_shop() || is_woocommerce();
    }
    
    /**
     * Get meta data for current page
     */
    private function get_meta_data() {
        global $post;
        
        if (is_product() && $post) {
            return $this->get_enhanced_product_meta_data($post);
        } elseif (is_product_category()) {
            return $this->get_category_meta_data();
        } elseif (is_product_tag()) {
            return $this->get_tag_meta_data();
        } elseif (is_shop()) {
            return $this->get_shop_meta_data();
        }
        
        return array();
    }
    
    /**
     * Get enhanced product meta data
     */
    private function get_enhanced_product_meta_data($post) {
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return array();
        }
        
        $title = $this->should_disable_title_description() ? '' : $this->get_optimized_title($product);
        $description = $this->should_disable_title_description() ? '' : $this->get_optimized_description($product);
        $images = $this->get_optimized_images($product);
        $url = get_permalink($post->ID);
        $site_name = get_bloginfo('name');
        
        $meta_data = array(
            'type' => 'product',
            'title' => $title,
            'description' => $description,
            'images' => $images,
            'url' => $url,
            'site_name' => $site_name,
            'product' => array(
                // Basic product info
                'price' => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'currency' => get_woocommerce_currency(),
                'availability' => $this->get_detailed_availability($product),
                'condition' => $this->get_product_condition($product),
                'brand' => $this->get_product_brand($product),
                'category' => $this->get_primary_category($product),
                'sku' => $product->get_sku(),
                'weight' => $product->get_weight(),
                
                // Enhanced product details
                'retailer_item_id' => $product->get_sku(),
                'item_group_id' => $this->get_item_group_id($product),
                'google_product_category' => $this->get_google_category($product),
                'product_type' => $this->get_product_type_hierarchy($product),
                'age_group' => $this->get_age_group($product),
                'gender' => $this->get_gender($product),
                'color' => $this->get_dominant_color($product),
                'size' => $this->get_size_info($product),
                'material' => $this->get_material_info($product),
                'pattern' => $this->get_pattern_info($product),
                'gtin' => $this->get_gtin($product),
                'mpn' => $this->get_mpn($product),
                
                // Shipping and logistics
                'shipping_cost' => $this->get_shipping_cost($product),
                'shipping_weight' => $product->get_weight(),
                'return_policy_days' => $this->get_return_policy_days($product),
                
                // Inventory and sales data
                'inventory_count' => $this->get_inventory_count($product),
                'sale_price_effective_date' => $this->get_sale_dates($product),
                'expiration_date' => $this->get_expiration_date($product),
                
                // Rating and review data
                'rating_value' => $product->get_average_rating(),
                'review_count' => $product->get_review_count()
            )
        );
        
        return apply_filters('ewog_product_meta_data', $meta_data, $product, $post);
    }
    
    /**
     * Get optimized images for social sharing
     */
    private function get_optimized_images($product) {
        $images = array();
        $image_size = !empty($this->settings['image_size']) ? $this->settings['image_size'] : 'large';
        
        // Featured image
        if ($product->get_image_id()) {
            $image_data = wp_get_attachment_image_src($product->get_image_id(), $image_size);
            if ($image_data) {
                $images[] = array(
                    'url' => $image_data[0],
                    'width' => $image_data[1],
                    'height' => $image_data[2],
                    'alt' => get_post_meta($product->get_image_id(), '_wp_attachment_image_alt', true) ?: $product->get_name(),
                    'type' => get_post_mime_type($product->get_image_id())
                );
            }
        }
        
        // Gallery images (up to 3 additional images)
        $gallery_ids = $product->get_gallery_image_ids();
        foreach (array_slice($gallery_ids, 0, 3) as $image_id) {
            $image_data = wp_get_attachment_image_src($image_id, $image_size);
            if ($image_data) {
                $images[] = array(
                    'url' => $image_data[0],
                    'width' => $image_data[1],
                    'height' => $image_data[2],
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: $product->get_name(),
                    'type' => get_post_mime_type($image_id)
                );
            }
        }
        
        // Fallback image if no images found
        if (empty($images)) {
            $fallback_url = $this->get_fallback_image();
            if ($fallback_url) {
                $images[] = array(
                    'url' => $fallback_url,
                    'width' => 1200,
                    'height' => 630,
                    'alt' => $product->get_name(),
                    'type' => 'image/png'
                );
            }
        }
        
        return $images;
    }
    
    /**
     * Output basic Open Graph tags
     */
    private function output_basic_og_tags($meta_data) {
        if (!empty($meta_data['title'])) {
            echo '<meta property="og:title" content="' . esc_attr($meta_data['title']) . '" />' . "\n";
        }
        
        if (!empty($meta_data['description'])) {
            echo '<meta property="og:description" content="' . esc_attr($meta_data['description']) . '" />' . "\n";
        }
        
        echo '<meta property="og:type" content="' . esc_attr($meta_data['type']) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($meta_data['url']) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($meta_data['site_name']) . '" />' . "\n";
        
        // Multiple images
        if (!empty($meta_data['images'])) {
            foreach ($meta_data['images'] as $image) {
                echo '<meta property="og:image" content="' . esc_url($image['url']) . '" />' . "\n";
                echo '<meta property="og:image:secure_url" content="' . esc_url($image['url']) . '" />' . "\n";
                echo '<meta property="og:image:width" content="' . esc_attr($image['width']) . '" />' . "\n";
                echo '<meta property="og:image:height" content="' . esc_attr($image['height']) . '" />' . "\n";
                echo '<meta property="og:image:type" content="' . esc_attr($image['type']) . '" />' . "\n";
                echo '<meta property="og:image:alt" content="' . esc_attr($image['alt']) . '" />' . "\n";
            }
        }
        
        echo '<meta property="og:locale" content="' . esc_attr(str_replace('-', '_', get_locale())) . '" />' . "\n";
    }
    
    /**
     * Output Facebook specific tags
     */
    private function output_facebook_tags($meta_data) {
        if (!empty($this->settings['facebook_app_id'])) {
            echo '<meta property="fb:app_id" content="' . esc_attr($this->settings['facebook_app_id']) . '" />' . "\n";
        }
        
        // Product specific tags for Facebook
        if ($meta_data['type'] === 'product' && !empty($meta_data['product'])) {
            $product = $meta_data['product'];
            
            if (!empty($product['price'])) {
                echo '<meta property="product:price:amount" content="' . esc_attr($product['price']) . '" />' . "\n";
                echo '<meta property="product:price:currency" content="' . esc_attr($product['currency']) . '" />' . "\n";
            }
            
            if (!empty($product['availability'])) {
                echo '<meta property="product:availability" content="' . esc_attr($product['availability']) . '" />' . "\n";
            }
            
            if (!empty($product['condition'])) {
                echo '<meta property="product:condition" content="' . esc_attr($product['condition']) . '" />' . "\n";
            }
            
            if (!empty($product['brand'])) {
                echo '<meta property="product:brand" content="' . esc_attr($product['brand']) . '" />' . "\n";
            }
            
            if (!empty($product['category'])) {
                echo '<meta property="product:category" content="' . esc_attr($product['category']) . '" />' . "\n";
            }
            
            if (!empty($product['retailer_item_id'])) {
                echo '<meta property="product:retailer_item_id" content="' . esc_attr($product['retailer_item_id']) . '" />' . "\n";
            }
            
            if (!empty($product['item_group_id'])) {
                echo '<meta property="product:item_group_id" content="' . esc_attr($product['item_group_id']) . '" />' . "\n";
            }
            
            if (!empty($product['color'])) {
                echo '<meta property="product:color" content="' . esc_attr($product['color']) . '" />' . "\n";
            }
            
            if (!empty($product['size'])) {
                echo '<meta property="product:size" content="' . esc_attr($product['size']) . '" />' . "\n";
            }
            
            if (!empty($product['gender'])) {
                echo '<meta property="product:gender" content="' . esc_attr($product['gender']) . '" />' . "\n";
            }
            
            if (!empty($product['age_group'])) {
                echo '<meta property="product:age_group" content="' . esc_attr($product['age_group']) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Output enhanced Twitter Card tags
     */
    private function output_twitter_tags($meta_data) {
        // Use product card for products, summary_large_image for others
        $card_type = ($meta_data['type'] === 'product') ? 'product' : 'summary_large_image';
        echo '<meta name="twitter:card" content="' . esc_attr($card_type) . '" />' . "\n";
        
        if (!empty($this->settings['twitter_username'])) {
            echo '<meta name="twitter:site" content="@' . esc_attr($this->settings['twitter_username']) . '" />' . "\n";
            echo '<meta name="twitter:creator" content="@' . esc_attr($this->settings['twitter_username']) . '" />' . "\n";
        }
        
        if (!empty($meta_data['title'])) {
            echo '<meta name="twitter:title" content="' . esc_attr($meta_data['title']) . '" />' . "\n";
        }
        
        if (!empty($meta_data['description'])) {
            echo '<meta name="twitter:description" content="' . esc_attr($meta_data['description']) . '" />' . "\n";
        }
        
        // Twitter image (use first image)
        if (!empty($meta_data['images'])) {
            $first_image = $meta_data['images'][0];
            echo '<meta name="twitter:image" content="' . esc_url($first_image['url']) . '" />' . "\n";
            echo '<meta name="twitter:image:alt" content="' . esc_attr($first_image['alt']) . '" />' . "\n";
        }
        
        // Product specific data for Twitter
        if ($meta_data['type'] === 'product' && !empty($meta_data['product'])) {
            $product = $meta_data['product'];
            
            if (!empty($product['price'])) {
                echo '<meta name="twitter:label1" content="Price" />' . "\n";
                echo '<meta name="twitter:data1" content="' . esc_attr($product['currency'] . ' ' . $product['price']) . '" />' . "\n";
            }
            
            if (!empty($product['availability'])) {
                echo '<meta name="twitter:label2" content="Availability" />' . "\n";
                echo '<meta name="twitter:data2" content="' . esc_attr(ucfirst($product['availability'])) . '" />' . "\n";
            }
            
            if (!empty($product['brand'])) {
                echo '<meta name="twitter:label3" content="Brand" />' . "\n";
                echo '<meta name="twitter:data3" content="' . esc_attr($product['brand']) . '" />' . "\n";
            }
            
            if (!empty($product['rating_value']) && $product['rating_value'] > 0) {
                echo '<meta name="twitter:label4" content="Rating" />' . "\n";
                echo '<meta name="twitter:data4" content="' . esc_attr($product['rating_value'] . '/5 (' . $product['review_count'] . ' reviews)') . '" />' . "\n";
            }
        }
    }
    
    /**
     * Output LinkedIn tags
     */
    private function output_linkedin_tags($meta_data) {
        // LinkedIn primarily uses Open Graph, but we can add some specific tags
        if (!empty($meta_data['title'])) {
            echo '<meta name="linkedin:title" content="' . esc_attr($meta_data['title']) . '" />' . "\n";
        }
        
        if (!empty($meta_data['description'])) {
            echo '<meta name="linkedin:description" content="' . esc_attr($meta_data['description']) . '" />' . "\n";
        }
    }
    
    /**
     * Output Pinterest tags
     */
    private function output_pinterest_tags($meta_data) {
        echo '<meta name="pinterest-rich-pin" content="true" />' . "\n";
        
        if ($meta_data['type'] === 'product' && !empty($meta_data['product'])) {
            $product = $meta_data['product'];
            
            if (!empty($product['price'])) {
                echo '<meta property="product:price:amount" content="' . esc_attr($product['price']) . '" />' . "\n";
                echo '<meta property="product:price:currency" content="' . esc_attr($product['currency']) . '" />' . "\n";
            }
            
            if (!empty($product['availability'])) {
                echo '<meta property="product:availability" content="' . esc_attr($product['availability']) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Output WhatsApp optimization tags
     */
    private function output_whatsapp_tags($meta_data) {
        // WhatsApp uses Open Graph tags, but we can optimize the description
        if (!empty($meta_data['images'])) {
            $first_image = $meta_data['images'][0];
            echo '<meta property="og:image:alt" content="' . esc_attr($first_image['alt']) . '" />' . "\n";
        }
    }
    
    /**
     * Helper methods
     */
    
    private function should_disable_title_description() {
        return !empty($this->settings['disable_title_description']);
    }
    
    private function get_optimized_title($product) {
        $title = $product->get_name();
        
        // Add brand if available
        $brand = $this->get_product_brand($product);
        if (!empty($brand)) {
            $title = $brand . ' ' . $title;
        }
        
        // Truncate for optimal social sharing
        return wp_trim_words($title, 10, '');
    }
    
    private function get_optimized_description($product) {
        $description = $product->get_short_description();
        if (empty($description)) {
            $description = $product->get_description();
        }
        if (empty($description)) {
            $description = get_bloginfo('description');
        }
        
        // Add key product info
        $enhanced_description = wp_strip_all_tags($description);
        
        // Add price info
        if ($product->get_price()) {
            $enhanced_description .= ' Price: ' . wc_price($product->get_price());
        }
        
        // Add availability
        if ($product->is_in_stock()) {
            $enhanced_description .= ' ✓ In Stock';
        }
        
        return wp_trim_words($enhanced_description, 35, '...');
    }
    
    private function get_detailed_availability($product) {
        if ($product->is_in_stock()) {
            if ($product->managing_stock()) {
                $stock_quantity = $product->get_stock_quantity();
                if ($stock_quantity > 10) {
                    return 'in stock';
                } elseif ($stock_quantity > 0) {
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
    
    private function get_product_condition($product) {
        $condition = get_post_meta($product->get_id(), '_condition', true);
        return !empty($condition) ? $condition : 'new';
    }
    
    private function get_product_brand($product) {
        // Check for common brand taxonomies
        $brand_taxonomies = array('product_brand', 'pwb-brand', 'yith_product_brand', 'pa_brand');
        
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
        return !empty($brand) ? $brand : '';
    }
    
    private function get_primary_category($product) {
        $categories = get_the_terms($product->get_id(), 'product_cat');
        if ($categories && !is_wp_error($categories)) {
            return $categories[0]->name;
        }
        return '';
    }
    
    private function get_item_group_id($product) {
        // For variable products, use parent ID
        if ($product->is_type('variation')) {
            return $product->get_parent_id();
        }
        
        return $product->get_id();
    }
    
    private function get_google_category($product) {
        return get_post_meta($product->get_id(), '_google_product_category', true);
    }
    
    private function get_product_type_hierarchy($product) {
        $categories = get_the_terms($product->get_id(), 'product_cat');
        if (!$categories || is_wp_error($categories)) {
            return '';
        }
        
        $hierarchy = array();
        $category = $categories[0];
        $ancestors = get_ancestors($category->term_id, 'product_cat');
        
        foreach (array_reverse($ancestors) as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            $hierarchy[] = $ancestor->name;
        }
        
        $hierarchy[] = $category->name;
        
        return implode(' > ', $hierarchy);
    }
    
    private function get_age_group($product) {
        $age_group = $product->get_attribute('pa_age_group');
        if (!empty($age_group)) {
            return $age_group;
        }
        
        return get_post_meta($product->get_id(), '_age_group', true);
    }
    
    private function get_gender($product) {
        $gender = $product->get_attribute('pa_gender');
        if (!empty($gender)) {
            return $gender;
        }
        
        return get_post_meta($product->get_id(), '_gender', true);
    }
    
    private function get_dominant_color($product) {
        $color = $product->get_attribute('pa_color');
        if (!empty($color)) {
            return $color;
        }
        
        return get_post_meta($product->get_id(), '_color', true);
    }
    
    private function get_size_info($product) {
        $size = $product->get_attribute('pa_size');
        if (!empty($size)) {
            return $size;
        }
        
        return get_post_meta($product->get_id(), '_size', true);
    }
    
    private function get_material_info($product) {
        $material = $product->get_attribute('pa_material');
        if (!empty($material)) {
            return $material;
        }
        
        return get_post_meta($product->get_id(), '_material', true);
    }
    
    private function get_pattern_info($product) {
        return $product->get_attribute('pa_pattern') ?: get_post_meta($product->get_id(), '_pattern', true);
    }
    
    private function get_gtin($product) {
        $gtin_fields = array('_gtin', '_upc', '_ean', '_isbn', '_gtin8', '_gtin12', '_gtin13', '_gtin14');
        
        foreach ($gtin_fields as $field) {
            $gtin = get_post_meta($product->get_id(), $field, true);
            if (!empty($gtin)) {
                return $gtin;
            }
        }
        
        return '';
    }
    
    private function get_mpn($product) {
        $mpn = get_post_meta($product->get_id(), '_mpn', true);
        if (empty($mpn)) {
            $mpn = get_post_meta($product->get_id(), '_manufacturer_part_number', true);
        }
        return $mpn;
    }
    
    private function get_shipping_cost($product) {
        // This would need integration with shipping plugins
        // Return basic shipping info for now
        return '';
    }
    
    private function get_return_policy_days($product) {
        return get_post_meta($product->get_id(), '_return_policy_days', true) ?: '30';
    }
    
    private function get_inventory_count($product) {
        if ($product->managing_stock()) {
            return $product->get_stock_quantity();
        }
        
        return '';
    }
    
    private function get_sale_dates($product) {
        $from = get_post_meta($product->get_id(), '_sale_price_dates_from', true);
        $to = get_post_meta($product->get_id(), '_sale_price_dates_to', true);
        
        if (!$from && !$to) {
            return '';
        }
        
        $dates = array();
        if ($from) {
            $dates[] = date('Y-m-d', $from);
        }
        if ($to) {
            $dates[] = date('Y-m-d', $to);
        }
        
        return implode(' - ', $dates);
    }
    
    private function get_expiration_date($product) {
        return get_post_meta($product->get_id(), '_expiration_date', true);
    }
    
    private function get_fallback_image() {
        if (!empty($this->settings['fallback_image'])) {
            return $this->settings['fallback_image'];
        }
        
        return wc_placeholder_img_src('large');
    }
    
    private function get_category_meta_data() {
        $category = get_queried_object();
        
        if (!$category) {
            return array();
        }
        
        $title = $this->should_disable_title_description() ? '' : $category->name;
        $description = $this->should_disable_title_description() ? '' : $category->description;
        $image = $this->get_category_image($category);
        $url = get_term_link($category);
        
        return array(
            'type' => 'website',
            'title' => $title,
            'description' => $description,
            'images' => array(array(
                'url' => $image,
                'width' => 1200,
                'height' => 630,
                'alt' => $category->name,
                'type' => 'image/png'
            )),
            'url' => $url,
            'site_name' => get_bloginfo('name')
        );
    }
    
    private function get_tag_meta_data() {
        $tag = get_queried_object();
        
        if (!$tag) {
            return array();
        }
        
        return array(
            'type' => 'website',
            'title' => $tag->name,
            'description' => $tag->description,
            'images' => array(array(
                'url' => $this->get_fallback_image(),
                'width' => 1200,
                'height' => 630,
                'alt' => $tag->name,
                'type' => 'image/png'
            )),
            'url' => get_term_link($tag),
            'site_name' => get_bloginfo('name')
        );
    }
    
    private function get_shop_meta_data() {
        $shop_page_id = wc_get_page_id('shop');
        
        return array(
            'type' => 'website',
            'title' => get_the_title($shop_page_id),
            'description' => get_post_meta($shop_page_id, '_yoast_wpseo_metadesc', true) ?: get_bloginfo('description'),
            'images' => array(array(
                'url' => $this->get_fallback_image(),
                'width' => 1200,
                'height' => 630,
                'alt' => get_the_title($shop_page_id),
                'type' => 'image/png'
            )),
            'url' => get_permalink($shop_page_id),
            'site_name' => get_bloginfo('name')
        );
    }
    
    private function get_category_image($category) {
        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        
        if ($thumbnail_id) {
            $image = wp_get_attachment_image_src($thumbnail_id, 'large');
            if ($image) {
                return $image[0];
            }
        }
        
        return $this->get_fallback_image();
    }
}