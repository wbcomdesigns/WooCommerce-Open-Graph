<?php
/**
 * Sitemap Generation Class
 * 
 * Comprehensive WooCommerce-specific XML sitemaps
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWOG_Sitemap {
    
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
        if (!empty($this->settings['enable_product_sitemap'])) {
            add_action('init', array($this, 'add_sitemap_rewrite_rules'));
            add_action('template_redirect', array($this, 'handle_sitemap_requests'));
            
            // Update sitemaps on product changes
            add_action('save_post', array($this, 'maybe_update_sitemap'), 10, 2);
            add_action('woocommerce_update_product', array($this, 'update_product_sitemap'));
            add_action('created_product_cat', array($this, 'update_category_sitemap'));
            add_action('edited_product_cat', array($this, 'update_category_sitemap'));
            
            // Schedule sitemap generation
            add_action('wp', array($this, 'schedule_sitemap_generation'));
            add_action('ewog_generate_sitemaps', array($this, 'generate_all_sitemaps'));
        }
    }
    
    public function add_sitemap_rewrite_rules() {
        // Product sitemaps (paginated)
        add_rewrite_rule(
            '^product-sitemap\.xml,
            'index.php?ewog_sitemap=products',
            'top'
        );
        
        add_rewrite_rule(
            '^product-sitemap-([0-9]+)\.xml,
            'index.php?ewog_sitemap=products&ewog_sitemap_page=$matches[1]',
            'top'
        );
        
        // Category sitemaps
        add_rewrite_rule(
            '^product-category-sitemap\.xml,
            'index.php?ewog_sitemap=categories',
            'top'
        );
        
        // Brand sitemaps
        add_rewrite_rule(
            '^product-brand-sitemap\.xml,
            'index.php?ewog_sitemap=brands',
            'top'
        );
        
        // Main sitemap index
        add_rewrite_rule(
            '^ewog-sitemap\.xml,
            'index.php?ewog_sitemap=index',
            'top'
        );
        
        add_rewrite_tag('%ewog_sitemap%', '([^&]+)');
        add_rewrite_tag('%ewog_sitemap_page%', '([0-9]+)');
    }
    
    public function handle_sitemap_requests() {
        $sitemap = get_query_var('ewog_sitemap');
        
        if (empty($sitemap)) {
            return;
        }
        
        // Set proper headers
        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex, follow', true);
        
        switch ($sitemap) {
            case 'sitemap_index':
                $this->generate_sitemap_index();
                break;
            case 'products':
                $page = get_query_var('ewog_sitemap_page') ?: 1;
                $this->generate_product_sitemap($page);
                break;
            case 'categories':
                $this->generate_category_sitemap();
                break;
            case 'brands':
                $this->generate_brand_sitemap();
                break;
        }
        
        exit;
    }
    
    public function maybe_update_sitemap($post_id, $post) {
        if ($post->post_type === 'product' && $post->post_status === 'publish') {
            $this->schedule_sitemap_update();
        }
    }
    
    public function update_product_sitemap($product_id) {
        $this->schedule_sitemap_update();
    }
    
    public function update_category_sitemap($term_id) {
        $this->schedule_sitemap_update();
    }
    
    public function schedule_sitemap_generation() {
        if (!wp_next_scheduled('ewog_generate_sitemaps')) {
            wp_schedule_event(time(), 'daily', 'ewog_generate_sitemaps');
        }
    }
    
    private function schedule_sitemap_update() {
        // Schedule update for 5 minutes from now to batch updates
        if (!wp_next_scheduled('ewog_generate_sitemaps')) {
            wp_schedule_single_event(time() + 300, 'ewog_generate_sitemaps');
        }
    }
    
    public function generate_all_sitemaps() {
        // Clear any existing scheduled events
        wp_clear_scheduled_hook('ewog_generate_sitemaps');
        
        // Generate sitemaps
        $this->generate_sitemap_index();
        $this->generate_product_sitemap();
        $this->generate_category_sitemap();
        
        if ($this->has_product_brands()) {
            $this->generate_brand_sitemap();
        }
        
        // Update last generation time
        update_option('ewog_sitemap_last_generated', time());
    }
    
    private function generate_sitemap_index() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Product sitemaps (paginated)
        $product_count = $this->get_published_product_count();
        $products_per_sitemap = $this->get_products_per_sitemap();
        $sitemap_pages = ceil($product_count / $products_per_sitemap);
        
        for ($page = 1; $page <= $sitemap_pages; $page++) {
            $xml .= "\t<sitemap>\n";
            $xml .= "\t\t<loc>" . esc_url(home_url("/product-sitemap-{$page}.xml")) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . esc_html($this->get_products_last_modified()) . "</lastmod>\n";
            $xml .= "\t</sitemap>\n";
        }
        
        // Category sitemap
        if ($this->has_product_categories()) {
            $xml .= "\t<sitemap>\n";
            $xml .= "\t\t<loc>" . esc_url(home_url('/product-category-sitemap.xml')) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . esc_html($this->get_categories_last_modified()) . "</lastmod>\n";
            $xml .= "\t</sitemap>\n";
        }
        
        // Brand sitemap (if brands exist)
        if ($this->has_product_brands()) {
            $xml .= "\t<sitemap>\n";
            $xml .= "\t\t<loc>" . esc_url(home_url('/product-brand-sitemap.xml')) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . esc_html(date('c', time())) . "</lastmod>\n";
            $xml .= "\t</sitemap>\n";
        }
        
        $xml .= '</sitemapindex>';
        
        echo $xml;
    }
    
    private function generate_product_sitemap($page = 1) {
        $products_per_page = $this->get_products_per_sitemap();
        $offset = ($page - 1) * $products_per_page;
        
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $products_per_page,
            'offset' => $offset,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array('hidden', 'search'),
                    'compare' => 'NOT IN'
                )
            )
        ));
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
                        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            
            if (!$product || !$product->is_visible()) {
                continue;
            }
            
            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . esc_url(get_permalink($product_post->ID)) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . esc_html(date('c', strtotime($product_post->post_modified))) . "</lastmod>\n";
            $xml .= "\t\t<changefreq>" . esc_html($this->get_product_changefreq($product)) . "</changefreq>\n";
            $xml .= "\t\t<priority>" . esc_html($this->get_product_priority($product)) . "</priority>\n";
            
            // Add product images
            $images = $this->get_product_sitemap_images($product);
            foreach ($images as $image) {
                $xml .= "\t\t<image:image>\n";
                $xml .= "\t\t\t<image:loc>" . esc_url($image['url']) . "</image:loc>\n";
                if (!empty($image['title'])) {
                    $xml .= "\t\t\t<image:title>" . esc_html($image['title']) . "</image:title>\n";
                }
                if (!empty($image['caption'])) {
                    $xml .= "\t\t\t<image:caption>" . esc_html($image['caption']) . "</image:caption>\n";
                }
                $xml .= "\t\t</image:image>\n";
            }
            
            $xml .= "\t</url>\n";
        }
        
        $xml .= '</urlset>';
        
        echo $xml;
    }
    
    private function generate_category_sitemap() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC'
        ));
        
        if (empty($categories) || is_wp_error($categories)) {
            return;
        }
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
                        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        
        foreach ($categories as $category) {
            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . esc_url(get_term_link($category)) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . esc_html($this->get_category_last_modified($category)) . "</lastmod>\n";
            $xml .= "\t\t<changefreq>" . esc_html($this->get_category_changefreq($category)) . "</changefreq>\n";
            $xml .= "\t\t<priority>" . esc_html($this->get_category_priority($category)) . "</priority>\n";
            
            // Add category image if available
            $image = $this->get_category_sitemap_image($category);
            if ($image) {
                $xml .= "\t\t<image:image>\n";
                $xml .= "\t\t\t<image:loc>" . esc_url($image['url']) . "</image:loc>\n";
                $xml .= "\t\t\t<image:title>" . esc_html($category->name) . "</image:title>\n";
                if (!empty($category->description)) {
                    $xml .= "\t\t\t<image:caption>" . esc_html(wp_trim_words($category->description, 20)) . "</image:caption>\n";
                }
                $xml .= "\t\t</image:image>\n";
            }
            
            $xml .= "\t</url>\n";
        }
        
        $xml .= '</urlset>';
        
        echo $xml;
    }
    
    private function generate_brand_sitemap() {
        $brand_taxonomies = array('product_brand', 'pwb-brand', 'yith_product_brand', 'pa_brand');
        $brands = array();
        
        foreach ($brand_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => true,
                    'orderby' => 'count',
                    'order' => 'DESC'
                ));
                
                if (!empty($terms) && !is_wp_error($terms)) {
                    $brands = array_merge($brands, $terms);
                    break; // Use the first available brand taxonomy
                }
            }
        }
        
        if (empty($brands)) {
            return;
        }
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($brands as $brand) {
            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . esc_url(get_term_link($brand)) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . esc_html($this->get_brand_last_modified($brand)) . "</lastmod>\n";
            $xml .= "\t\t<changefreq>monthly</changefreq>\n";
            $xml .= "\t\t<priority>" . esc_html($this->get_brand_priority($brand)) . "</priority>\n";
            $xml .= "\t</url>\n";
        }
        
        $xml .= '</urlset>';
        
        echo $xml;
    }
    
    /**
     * Helper methods for sitemap generation
     */
    
    private function get_products_per_sitemap() {
        return !empty($this->settings['sitemap_products_per_page']) ? 
               (int) $this->settings['sitemap_products_per_page'] : 500;
    }
    
    private function get_published_product_count() {
        $count = wp_count_posts('product');
        return $count->publish;
    }
    
    private function get_products_last_modified() {
        global $wpdb;
        
        $last_modified = $wpdb->get_var(
            "SELECT post_modified 
             FROM {$wpdb->posts} 
             WHERE post_type = 'product' 
             AND post_status = 'publish' 
             ORDER BY post_modified DESC 
             LIMIT 1"
        );
        
        return $last_modified ? date('c', strtotime($last_modified)) : date('c');
    }
    
    private function get_categories_last_modified() {
        global $wpdb;
        
        // Get the last modified product in each category to determine category freshness
        $last_modified = $wpdb->get_var(
            "SELECT p.post_modified 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE p.post_type = 'product' 
             AND p.post_status = 'publish'
             AND tt.taxonomy = 'product_cat'
             ORDER BY p.post_modified DESC 
             LIMIT 1"
        );
        
        return $last_modified ? date('c', strtotime($last_modified)) : date('c');
    }
    
    private function has_product_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'number' => 1
        ));
        
        return !empty($categories) && !is_wp_error($categories);
    }
    
    private function has_product_brands() {
        $brand_taxonomies = array('product_brand', 'pwb-brand', 'yith_product_brand', 'pa_brand');
        
        foreach ($brand_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $terms = get_terms(array(
                    'taxonomy' => $taxonomy,
                    'hide_empty' => true,
                    'number' => 1
                ));
                
                if (!empty($terms) && !is_wp_error($terms)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function get_product_priority($product) {
        $priority = 0.5; // Default priority
        
        // Increase priority for featured products
        if ($product->is_featured()) {
            $priority += 0.2;
        }
        
        // Increase priority for products on sale
        if ($product->is_on_sale()) {
            $priority += 0.1;
        }
        
        // Increase priority based on stock status
        if ($product->is_in_stock()) {
            $priority += 0.1;
        } else {
            $priority -= 0.2;
        }
        
        // Increase priority based on review count
        $review_count = $product->get_review_count();
        if ($review_count > 50) {
            $priority += 0.2;
        } elseif ($review_count > 10) {
            $priority += 0.1;
        }
        
        // Increase priority based on rating
        $rating = $product->get_average_rating();
        if ($rating >= 4.5) {
            $priority += 0.1;
        } elseif ($rating >= 4.0) {
            $priority += 0.05;
        }
        
        return number_format(min(1.0, max(0.1, $priority)), 1);
    }
    
    private function get_product_changefreq($product) {
        // More frequent updates for variable products and frequently updated products
        if ($product->is_type('variable')) {
            return 'weekly';
        }
        
        if ($product->is_on_sale()) {
            return 'weekly';
        }
        
        if ($product->managing_stock() && $product->get_stock_quantity() <= 5) {
            return 'daily';
        }
        
        return 'monthly';
    }
    
    private function get_product_sitemap_images($product) {
        $images = array();
        
        // Featured image
        if ($product->get_image_id()) {
            $image_data = wp_get_attachment_image_src($product->get_image_id(), 'large');
            $attachment = get_post($product->get_image_id());
            
            if ($image_data && $attachment) {
                $images[] = array(
                    'url' => $image_data[0],
                    'title' => $attachment->post_title ?: $product->get_name(),
                    'caption' => $attachment->post_excerpt ?: ''
                );
            }
        }
        
        // Gallery images (limit to 5 for performance)
        $gallery_ids = array_slice($product->get_gallery_image_ids(), 0, 5);
        foreach ($gallery_ids as $image_id) {
            $image_data = wp_get_attachment_image_src($image_id, 'large');
            $attachment = get_post($image_id);
            
            if ($image_data && $attachment) {
                $images[] = array(
                    'url' => $image_data[0],
                    'title' => $attachment->post_title ?: $product->get_name(),
                    'caption' => $attachment->post_excerpt ?: ''
                );
            }
        }
        
        return $images;
    }
    
    private function get_category_priority($category) {
        $priority = 0.6; // Default for categories
        
        // Increase priority based on product count
        if ($category->count > 100) {
            $priority += 0.3;
        } elseif ($category->count > 20) {
            $priority += 0.2;
        } elseif ($category->count > 5) {
            $priority += 0.1;
        }
        
        // Top-level categories get higher priority
        if ($category->parent == 0) {
            $priority += 0.1;
        }
        
        return number_format(min(1.0, $priority), 1);
    }
    
    private function get_category_changefreq($category) {
        // More frequent updates for categories with more products
        if ($category->count > 50) {
            return 'weekly';
        } elseif ($category->count > 10) {
            return 'monthly';
        }
        
        return 'yearly';
    }
    
    private function get_category_last_modified($category) {
        global $wpdb;
        
        // Get the last modified product in this category
        $last_modified = $wpdb->get_var($wpdb->prepare(
            "SELECT p.post_modified 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE p.post_type = 'product' 
             AND p.post_status = 'publish'
             AND tt.taxonomy = 'product_cat'
             AND tt.term_id = %d
             ORDER BY p.post_modified DESC 
             LIMIT 1",
            $category->term_id
        ));
        
        return $last_modified ? date('c', strtotime($last_modified)) : date('c');
    }
    
    private function get_category_sitemap_image($category) {
        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        
        if ($thumbnail_id) {
            $image_data = wp_get_attachment_image_src($thumbnail_id, 'large');
            if ($image_data) {
                return array(
                    'url' => $image_data[0],
                    'title' => $category->name,
                    'caption' => $category->description
                );
            }
        }
        
        return null;
    }
    
    private function get_brand_priority($brand) {
        $priority = 0.6;
        
        // Increase priority based on product count
        if ($brand->count > 50) {
            $priority += 0.3;
        } elseif ($brand->count > 10) {
            $priority += 0.2;
        } elseif ($brand->count > 3) {
            $priority += 0.1;
        }
        
        return number_format(min(1.0, $priority), 1);
    }
    
    private function get_brand_last_modified($brand) {
        global $wpdb;
        
        // Get the last modified product for this brand
        $last_modified = $wpdb->get_var($wpdb->prepare(
            "SELECT p.post_modified 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE p.post_type = 'product' 
             AND p.post_status = 'publish'
             AND tt.term_id = %d
             ORDER BY p.post_modified DESC 
             LIMIT 1",
            $brand->term_id
        ));
        
        return $last_modified ? date('c', strtotime($last_modified)) : date('c');
    }
    
    /**
     * Public methods for manual sitemap generation
     */
    
    public function manual_generate_sitemap_index() {
        ob_start();
        $this->generate_sitemap_index();
        return ob_get_clean();
    }
    
    public function manual_generate_product_sitemap($page = 1) {
        ob_start();
        $this->generate_product_sitemap($page);
        return ob_get_clean();
    }
    
    public function manual_generate_category_sitemap() {
        ob_start();
        $this->generate_category_sitemap();
        return ob_get_clean();
    }
    
    public function manual_generate_brand_sitemap() {
        ob_start();
        $this->generate_brand_sitemap();
        return ob_get_clean();
    }
    
    /**
     * Get sitemap URLs for robots.txt
     */
    public function get_sitemap_urls() {
        $urls = array();
        
        if (!empty($this->settings['enable_product_sitemap'])) {
            $urls[] = home_url('/ewog-sitemap.xml');
        }
        
        return $urls;
    }
    
    /**
     * Add sitemaps to robots.txt
     */
    public function add_sitemap_to_robots($output) {
        if (!empty($this->settings['enable_product_sitemap'])) {
            $output .= "\nSitemap: " . home_url('/ewog-sitemap.xml') . "\n";
        }
        
        return $output;
    }