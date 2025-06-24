<?php
/**
 * Meta Tags Handler Class
 * Generates Open Graph, Twitter Card, and other social media meta tags
 * 
 * @package Woo_Open_Graph
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WOG_Meta_Tags {
    
    private static $instance = null;
    private $settings;
    private $existing_og_tags = array();
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the class
     */
    private function __construct() {
        $this->settings = get_option('wog_settings', array());
        $this->init_hooks();
    }
    
    /**
     * Set up WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_head', array($this, 'scan_existing_tags'), 1);
        add_action('wp_head', array($this, 'output_meta_tags'), 15);
        add_filter('language_attributes', array($this, 'add_opengraph_namespace'));
    }
    
    /**
     * Scan for existing Open Graph tags from other plugins
     */
    public function scan_existing_tags() {
        if (!$this->should_add_meta_tags()) {
            return;
        }
        
        ob_start();
        do_action('wp_head_early_og');
        $early_content = ob_get_clean();
        
        if (preg_match_all('/<meta\s+property=["\']og:([^"\']+)["\'][^>]*>/i', $early_content, $matches)) {
            foreach ($matches[1] as $property) {
                $this->existing_og_tags[] = strtolower($property);
            }
        }
        
        if (preg_match_all('/<meta\s+name=["\']twitter:([^"\']+)["\'][^>]*>/i', $early_content, $matches)) {
            foreach ($matches[1] as $property) {
                $this->existing_og_tags[] = 'twitter:' . strtolower($property);
            }
        }
    }
    
    /**
     * Check if a specific tag already exists
     */
    private function tag_exists($property) {
        $property = strtolower($property);
        return in_array($property, $this->existing_og_tags) || 
               in_array('og:' . $property, $this->existing_og_tags) ||
               in_array('twitter:' . $property, $this->existing_og_tags);
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
     * Output meta tags for social media platforms
     */
    public function output_meta_tags() {
        if (!$this->should_add_meta_tags()) {
            return;
        }
        
        $meta_data = $this->get_meta_data();
        
        if (empty($meta_data)) {
            return;
        }
        
        echo "\n<!-- Woo Open Graph Meta Tags -->\n";
        
        $this->output_basic_og_tags($meta_data);
        
        if (!empty($this->settings['enable_facebook'])) {
            $this->output_facebook_tags($meta_data);
        }
        
        if (!empty($this->settings['enable_twitter'])) {
            $this->output_twitter_tags($meta_data);
        }
        
        if (!empty($this->settings['enable_linkedin'])) {
            $this->output_linkedin_tags($meta_data);
        }
        
        if (!empty($this->settings['enable_pinterest'])) {
            $this->output_pinterest_tags($meta_data);
        }
        
        if (!empty($this->settings['enable_whatsapp'])) {
            $this->output_whatsapp_tags($meta_data);
        }
        
        echo "<!-- End Woo Open Graph Meta Tags -->\n\n";
    }
    
    /**
     * Check if meta tags should be added to current page
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
            return $this->get_product_meta_data($post);
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
     * Get product meta data with static caching for current request
     */
    private function get_product_meta_data($post) {
        static $product_cache = array();
        
        if (isset($product_cache[$post->ID])) {
            return $product_cache[$post->ID];
        }
        
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return array();
        }
        
        // Check if user disabled OG for this product
        $disabled = get_post_meta($post->ID, '_wog_disable_og', true);
        if ($disabled) {
            $product_cache[$post->ID] = array();
            return array();
        }
        
        // Get custom or generated title/description
        $custom_title = get_post_meta($post->ID, '_wog_og_title', true);
        $custom_description = get_post_meta($post->ID, '_wog_og_description', true);
        
        $title = !empty($custom_title) ? $custom_title : $this->get_optimized_title($product);
        $description = !empty($custom_description) ? $custom_description : $this->get_optimized_description($product);
        
        $meta_data = array(
            'type' => 'product',
            'title' => $title,
            'description' => $description,
            'images' => $this->get_optimized_images($product),
            'url' => get_permalink($post->ID),
            'site_name' => get_bloginfo('name'),
            'product' => array(
                'price' => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'currency' => get_woocommerce_currency(),
                'availability' => $this->get_availability($product),
                'condition' => $this->get_product_condition($product),
                'brand' => $this->get_product_brand($product),
                'category' => $this->get_primary_category($product),
                'sku' => $product->get_sku(),
                'weight' => $product->get_weight(),
                'rating_value' => $product->get_average_rating(),
                'review_count' => $product->get_review_count()
            )
        );
        
        if (!empty($this->settings['enable_enhanced_schema'])) {
            $meta_data['product'] = array_merge($meta_data['product'], array(
                'retailer_item_id' => $product->get_sku(),
                'item_group_id' => $this->get_item_group_id($product),
                'color' => $this->get_product_attribute($product, 'color'),
                'size' => $this->get_product_attribute($product, 'size'),
                'material' => $this->get_product_attribute($product, 'material'),
                'gtin' => $this->get_gtin($product),
                'mpn' => $this->get_mpn($product)
            ));
        }
        
        $product_cache[$post->ID] = apply_filters('wog_product_meta_data', $meta_data, $product, $post);
        
        return $product_cache[$post->ID];
    }
    
    /**
     * Get optimized product title
     */
    private function get_optimized_title($product) {
        $title = $product->get_name();
        
        $brand = $this->get_product_brand($product);
        if (!empty($brand)) {
            $title = $brand . ' ' . $title;
        }
        
        return wp_trim_words($title, 10, '');
    }
    
    /**
     * Get optimized product description
     */
    private function get_optimized_description($product) {
        $description = $product->get_short_description();
        if (empty($description)) {
            $description = $product->get_description();
        }
        if (empty($description)) {
            $description = get_bloginfo('description');
        }
        
        $enhanced_description = wp_strip_all_tags($description);
        
        if ($product->get_price()) {
            $enhanced_description .= ' Price: ' . wc_price($product->get_price());
        }
        
        if ($product->is_in_stock()) {
            $enhanced_description .= ' ✓ In Stock';
        }
        
        return wp_trim_words($enhanced_description, 35, '...');
    }
    
    /**
     * Get optimized product images
     */
    private function get_optimized_images($product) {
        static $image_cache = array();
        
        $product_id = $product->get_id();
        
        if (isset($image_cache[$product_id])) {
            return $image_cache[$product_id];
        }
        
        $images = array();
        $image_size = !empty($this->settings['image_size']) ? $this->settings['image_size'] : 'large';
        
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
        
        // Limit gallery images for performance
        $max_images = apply_filters('wog_max_images_per_product', 3);
        $remaining_slots = $max_images - count($images);
        
        if ($remaining_slots > 0) {
            $gallery_ids = $product->get_gallery_image_ids();
            foreach (array_slice($gallery_ids, 0, $remaining_slots) as $image_id) {
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
        }
        
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
        
        $image_cache[$product_id] = $images;
        return $images;
    }
    
    /**
     * Output basic Open Graph tags with duplicate prevention
     */
    private function output_basic_og_tags($meta_data) {
        $should_override = $this->should_disable_title_description();
        
        if ((!$this->tag_exists('title') || $should_override) && !empty($meta_data['title'])) {
            echo '<meta property="og:title" content="' . esc_attr($meta_data['title']) . '" />' . "\n";
        }
        
        if ((!$this->tag_exists('description') || $should_override) && !empty($meta_data['description'])) {
            echo '<meta property="og:description" content="' . esc_attr($meta_data['description']) . '" />' . "\n";
        }
        
        echo '<meta property="og:type" content="' . esc_attr($meta_data['type']) . '" />' . "\n";
        
        if (!$this->tag_exists('url')) {
            echo '<meta property="og:url" content="' . esc_url($meta_data['url']) . '" />' . "\n";
        }
        
        if (!$this->tag_exists('site_name')) {
            echo '<meta property="og:site_name" content="' . esc_attr($meta_data['site_name']) . '" />' . "\n";
        }
        
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
        
        if (!$this->tag_exists('locale')) {
            echo '<meta property="og:locale" content="' . esc_attr(str_replace('-', '_', get_locale())) . '" />' . "\n";
        }
    }
    
    /**
     * Output Facebook specific tags
     */
    private function output_facebook_tags($meta_data) {
        if (!empty($this->settings['facebook_app_id'])) {
            echo '<meta property="fb:app_id" content="' . esc_attr($this->settings['facebook_app_id']) . '" />' . "\n";
        }
        
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
        }
    }
    
    /**
     * Output Twitter Card tags with duplicate prevention
     */
    private function output_twitter_tags($meta_data) {
        $should_override = $this->should_disable_title_description();
        
        $card_type = ($meta_data['type'] === 'product') ? 'product' : 'summary_large_image';
        if (!$this->tag_exists('twitter:card') || $should_override) {
            echo '<meta name="twitter:card" content="' . esc_attr($card_type) . '" />' . "\n";
        }
        
        if (!empty($this->settings['twitter_username'])) {
            if (!$this->tag_exists('twitter:site')) {
                echo '<meta name="twitter:site" content="@' . esc_attr($this->settings['twitter_username']) . '" />' . "\n";
            }
            if (!$this->tag_exists('twitter:creator')) {
                echo '<meta name="twitter:creator" content="@' . esc_attr($this->settings['twitter_username']) . '" />' . "\n";
            }
        }
        
        if ((!$this->tag_exists('twitter:title') || $should_override) && !empty($meta_data['title'])) {
            echo '<meta name="twitter:title" content="' . esc_attr($meta_data['title']) . '" />' . "\n";
        }
        
        if ((!$this->tag_exists('twitter:description') || $should_override) && !empty($meta_data['description'])) {
            echo '<meta name="twitter:description" content="' . esc_attr($meta_data['description']) . '" />' . "\n";
        }
        
        if (!empty($meta_data['images'])) {
            $first_image = $meta_data['images'][0];
            if (!$this->tag_exists('twitter:image')) {
                echo '<meta name="twitter:image" content="' . esc_url($first_image['url']) . '" />' . "\n";
                echo '<meta name="twitter:image:alt" content="' . esc_attr($first_image['alt']) . '" />' . "\n";
            }
        }
        
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
        }
    }
    
    /**
     * Output LinkedIn optimization tags
     */
    private function output_linkedin_tags($meta_data) {
        if (!empty($meta_data['title'])) {
            echo '<meta name="linkedin:title" content="' . esc_attr($meta_data['title']) . '" />' . "\n";
        }
        
        if (!empty($meta_data['description'])) {
            echo '<meta name="linkedin:description" content="' . esc_attr($meta_data['description']) . '" />' . "\n";
        }
    }
    
    /**
     * Output Pinterest Rich Pins tags
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
    
    private function get_availability($product) {
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
        static $brand_cache = array();
        
        $product_id = $product->get_id();
        
        if (isset($brand_cache[$product_id])) {
            return $brand_cache[$product_id];
        }
        
        $brand = '';
        $brand_taxonomies = array('product_brand', 'pwb-brand', 'yith_product_brand', 'pa_brand');
        
        foreach ($brand_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $terms = get_the_terms($product_id, $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    $brand = $terms[0]->name;
                    break;
                }
            }
        }
        
        if (empty($brand)) {
            $brand = get_post_meta($product_id, '_brand', true);
        }
        
        $brand_cache[$product_id] = $brand;
        return $brand;
    }
    
    private function get_primary_category($product) {
        static $category_cache = array();
        
        $product_id = $product->get_id();
        
        if (isset($category_cache[$product_id])) {
            return $category_cache[$product_id];
        }
        
        $categories = get_the_terms($product_id, 'product_cat');
        $category = '';
        if ($categories && !is_wp_error($categories)) {
            $category = $categories[0]->name;
        }
        
        $category_cache[$product_id] = $category;
        return $category;
    }
    
    private function get_item_group_id($product) {
        if ($product->is_type('variation')) {
            return $product->get_parent_id();
        }
        
        return $product->get_id();
    }
    
    private function get_product_attribute($product, $attribute) {
        $value = $product->get_attribute('pa_' . $attribute);
        if (!empty($value)) {
            return $value;
        }
        
        return get_post_meta($product->get_id(), '_' . $attribute, true);
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