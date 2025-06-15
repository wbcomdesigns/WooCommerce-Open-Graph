<?php
/**
 * Meta Tags Handler Class
 * 
 * Handles generation of Open Graph, Twitter Card, and other meta tags
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
        
        return is_product() || is_product_category() || is_product_tag() || is_shop();
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
     * Get product meta data
     */
    private function get_product_meta_data($post) {
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return array();
        }
        
        $title = $this->should_disable_title_description() ? '' : get_the_title($post->ID);
        $description = $this->should_disable_title_description() ? '' : $this->get_product_description($product);
        $image = $this->get_product_image($product);
        $url = get_permalink($post->ID);
        $site_name = get_bloginfo('name');
        
        $meta_data = array(
            'type' => 'product',
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'url' => $url,
            'site_name' => $site_name,
            'product' => array(
                'price' => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'currency' => get_woocommerce_currency(),
                'availability' => $product->is_in_stock() ? 'instock' : 'outofstock',
                'condition' => 'new',
                'brand' => $this->get_product_brand($product),
                'sku' => $product->get_sku(),
                'weight' => $product->get_weight(),
                'category' => $this->get_product_categories($product)
            )
        );
        
        return apply_filters('ewog_product_meta_data', $meta_data, $product, $post);
    }
    
    /**
     * Get category meta data
     */
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
            'image' => $image,
            'url' => $url,
            'site_name' => get_bloginfo('name')
        );
    }
    
    /**
     * Get tag meta data
     */
    private function get_tag_meta_data() {
        $tag = get_queried_object();
        
        if (!$tag) {
            return array();
        }
        
        return array(
            'type' => 'website',
            'title' => $tag->name,
            'description' => $tag->description,
            'image' => $this->get_fallback_image(),
            'url' => get_term_link($tag),
            'site_name' => get_bloginfo('name')
        );
    }
    
    /**
     * Get shop meta data
     */
    private function get_shop_meta_data() {
        $shop_page_id = wc_get_page_id('shop');
        
        return array(
            'type' => 'website',
            'title' => get_the_title($shop_page_id),
            'description' => get_post_meta($shop_page_id, '_yoast_wpseo_metadesc', true) ?: get_bloginfo('description'),
            'image' => $this->get_fallback_image(),
            'url' => get_permalink($shop_page_id),
            'site_name' => get_bloginfo('name')
        );
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
        
        if (!empty($meta_data['image'])) {
            echo '<meta property="og:image" content="' . esc_url($meta_data['image']) . '" />' . "\n";
            echo '<meta property="og:image:secure_url" content="' . esc_url($meta_data['image']) . '" />' . "\n";
            
            // Get image dimensions
            $image_data = $this->get_image_data($meta_data['image']);
            if ($image_data) {
                echo '<meta property="og:image:width" content="' . esc_attr($image_data['width']) . '" />' . "\n";
                echo '<meta property="og:image:height" content="' . esc_attr($image_data['height']) . '" />' . "\n";
                echo '<meta property="og:image:type" content="' . esc_attr($image_data['mime_type']) . '" />' . "\n";
            }
        }
        
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '" />' . "\n";
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
        }
    }
    
    /**
     * Output Twitter Card tags
     */
    private function output_twitter_tags($meta_data) {
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        
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
        
        if (!empty($meta_data['image'])) {
            echo '<meta name="twitter:image" content="' . esc_url($meta_data['image']) . '" />' . "\n";
        }
        
        // Product specific tags for Twitter
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
     * Output LinkedIn tags
     */
    private function output_linkedin_tags($meta_data) {
        // LinkedIn uses Open Graph tags, but we can add some specific ones
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
        
        if ($meta_data['type'] === 'product') {
            echo '<meta property="og:type" content="product" />' . "\n";
            
            if (!empty($meta_data['product']['price'])) {
                echo '<meta property="product:price:amount" content="' . esc_attr($meta_data['product']['price']) . '" />' . "\n";
                echo '<meta property="product:price:currency" content="' . esc_attr($meta_data['product']['currency']) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Output WhatsApp tags
     */
    private function output_whatsapp_tags($meta_data) {
        // WhatsApp uses Open Graph tags primarily
        if (!empty($meta_data['image'])) {
            echo '<meta property="og:image:alt" content="' . esc_attr($meta_data['title']) . '" />' . "\n";
        }
    }
    
    /**
     * Helper methods
     */
    
    private function should_disable_title_description() {
        return !empty($this->settings['disable_title_description']);
    }
    
    private function get_product_description($product) {
        $description = $product->get_short_description();
        if (empty($description)) {
            $description = $product->get_description();
        }
        if (empty($description)) {
            $description = get_bloginfo('description');
        }
        
        return wp_trim_words(wp_strip_all_tags($description), 55);
    }
    
    private function get_product_image($product) {
        $image_size = !empty($this->settings['image_size']) ? $this->settings['image_size'] : 'large';
        
        if ($product->get_image_id()) {
            $image = wp_get_attachment_image_src($product->get_image_id(), $image_size);
            if ($image) {
                return $image[0];
            }
        }
        
        // Gallery images fallback
        $gallery_images = $product->get_gallery_image_ids();
        if (!empty($gallery_images)) {
            $image = wp_get_attachment_image_src($gallery_images[0], $image_size);
            if ($image) {
                return $image[0];
            }
        }
        
        return $this->get_fallback_image();
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
    
    private function get_fallback_image() {
        if (!empty($this->settings['fallback_image'])) {
            return $this->settings['fallback_image'];
        }
        
        // Use WooCommerce placeholder
        return wc_placeholder_img_src('large');
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
        
        return '';
    }
    
    private function get_product_categories($product) {
        $categories = get_the_terms($product->get_id(), 'product_cat');
        if ($categories && !is_wp_error($categories)) {
            return array_map(function($cat) { return $cat->name; }, $categories);
        }
        return array();
    }
    
    private function get_image_data($image_url) {
        $attachment_id = attachment_url_to_postid($image_url);
        
        if ($attachment_id) {
            $metadata = wp_get_attachment_metadata($attachment_id);
            if ($metadata) {
                return array(
                    'width' => $metadata['width'],
                    'height' => $metadata['height'],
                    'mime_type' => get_post_mime_type($attachment_id)
                );
            }
        }
        
        return false;
    }
}