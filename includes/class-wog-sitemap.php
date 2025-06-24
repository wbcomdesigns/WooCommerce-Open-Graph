<?php
/**
 * Enhanced WooCommerce Sitemap Generation Class
 * 
 * Optimized for large catalogs (1000+ products) with memory management,
 * background processing, caching, and comprehensive error handling.
 * 
 * @package Enhanced_Woo_Open_Graph
 * @version 2.0.0
 * @author  Wbcom Designs
 */

if (!defined('ABSPATH')) {
    exit;
}

class WOG_Sitemap {
    
    private static $instance = null;
    private $settings;
    private $cache_duration = 3600; // 1 hour
    private $max_memory_usage = 0.8; // 80% of available memory
    private $batch_size = 50; // Products per batch
    
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
     * Constructor
     */
    private function __construct() {
        $this->settings = get_option('wog_settings', array());
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        if (!empty($this->settings['enable_product_sitemap'])) {
            add_action('init', array($this, 'add_sitemap_rewrite_rules'));
            add_action('template_redirect', array($this, 'handle_sitemap_requests'));
            
            // Optimized update hooks for large catalogs
            add_action('save_post', array($this, 'maybe_update_sitemap'), 10, 2);
            add_action('woocommerce_update_product', array($this, 'schedule_sitemap_update'));
            add_action('created_product_cat', array($this, 'schedule_sitemap_update'));
            add_action('edited_product_cat', array($this, 'schedule_sitemap_update'));
            
            // Background processing
            add_action('wp', array($this, 'schedule_sitemap_generation'));
            add_action('wog_generate_sitemaps', array($this, 'generate_all_sitemaps_background'));
            add_action('wog_generate_single_sitemap', array($this, 'generate_single_sitemap_background'), 10, 2);
            
            // Cleanup hooks
            add_action('wp_scheduled_delete', array($this, 'cleanup_old_cache'));
        }
    }
    
    /**
     * Add sitemap rewrite rules
     */
    public function add_sitemap_rewrite_rules() {
        // Main sitemap index
        add_rewrite_rule(
            '^wog-sitemap\.xml$',
            'index.php?wog_sitemap=index',
            'top'
        );
        
        // Product sitemaps (paginated)
        add_rewrite_rule(
            '^product-sitemap\.xml$',
            'index.php?wog_sitemap=products&wog_sitemap_page=1',
            'top'
        );
        
        add_rewrite_rule(
            '^product-sitemap-([0-9]+)\.xml$',
            'index.php?wog_sitemap=products&wog_sitemap_page=$matches[1]',
            'top'
        );
        
        // Category sitemaps
        add_rewrite_rule(
            '^product-category-sitemap\.xml$',
            'index.php?wog_sitemap=categories',
            'top'
        );
        
        // Brand sitemaps
        add_rewrite_rule(
            '^product-brand-sitemap\.xml$',
            'index.php?wog_sitemap=brands',
            'top'
        );
        
        add_rewrite_tag('%wog_sitemap%', '([^&]+)');
        add_rewrite_tag('%wog_sitemap_page%', '([0-9]+)');
        
        // Ensure rewrite rules are flushed
        if (!get_option('wog_rewrite_rules_flushed_v2')) {
            flush_rewrite_rules();
            update_option('wog_rewrite_rules_flushed_v2', true);
        }
    }
    
    /**
     * Handle sitemap requests
     */
    public function handle_sitemap_requests() {
        $sitemap = get_query_var('wog_sitemap');
        
        if (empty($sitemap)) {
            return;
        }
        
        // Optimize for large catalogs
        $this->optimize_environment();
        
        // Set XML headers with caching
        $this->set_xml_headers();
        
        // Check for cached version
        $page = get_query_var('wog_sitemap_page') ?: 1;
        $cache_key = $this->get_cache_key($sitemap, $page);
        $cached_sitemap = $this->get_cached_sitemap($cache_key);
        
        if ($cached_sitemap !== false) {
            echo $cached_sitemap;
            exit;
        }
        
        // Generate sitemap
        ob_start();
        
        try {
            switch ($sitemap) {
                case 'index':
                    $this->generate_sitemap_index();
                    break;
                case 'products':
                    $this->generate_product_sitemap($page);
                    break;
                case 'categories':
                    $this->generate_category_sitemap();
                    break;
                case 'brands':
                    $this->generate_brand_sitemap();
                    break;
                default:
                    $this->output_404();
                    exit;
            }
        } catch (Exception $e) {
            $this->handle_generation_error($e);
            exit;
        }
        
        $sitemap_content = ob_get_clean();
        
        // Cache and output
        $this->cache_sitemap($cache_key, $sitemap_content);
        echo $sitemap_content;
        
        exit;
    }
    
    /**
     * Generate sitemap index
     */
    private function generate_sitemap_index() {
        $xml = $this->get_xml_header();
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Product sitemaps
        $product_count = $this->get_published_product_count();
        $products_per_sitemap = $this->get_products_per_sitemap();
        $sitemap_pages = max(1, ceil($product_count / $products_per_sitemap));
        
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
        
        // Brand sitemap
        if ($this->has_product_brands()) {
            $xml .= "\t<sitemap>\n";
            $xml .= "\t\t<loc>" . esc_url(home_url('/product-brand-sitemap.xml')) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . esc_html(date('c', time())) . "</lastmod>\n";
            $xml .= "\t</sitemap>\n";
        }
        
        $xml .= '</sitemapindex>';
        
        echo $xml;
    }
    
    /**
     * Generate product sitemap with optimizations
     */
    private function generate_product_sitemap($page = 1) {
        $products_per_page = $this->get_products_per_sitemap();
        $offset = ($page - 1) * $products_per_page;
        
        // Optimized query for large catalogs
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $products_per_page,
            'offset' => $offset,
            'orderby' => 'ID',
            'order' => 'ASC',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );
        
        // Exclude hidden products
        $visibility_terms = $this->get_excluded_visibility_terms();
        if (!empty($visibility_terms)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_visibility',
                    'field' => 'term_taxonomy_id',
                    'terms' => $visibility_terms,
                    'operator' => 'NOT IN'
                )
            );
        }
        
        $products_query = new WP_Query($args);
        $products = $products_query->posts;
        
        // Start XML output
        $xml = $this->get_xml_header();
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
                        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        
        // Process products in batches
        $product_batches = array_chunk($products, $this->batch_size);
        
        foreach ($product_batches as $product_batch) {
            foreach ($product_batch as $product_post) {
                // Memory management
                if ($this->is_memory_limit_approaching()) {
                    $this->log_debug("Memory limit approaching, stopping at product ID {$product_post->ID}");
                    break 2;
                }
                
                $product = wc_get_product($product_post->ID);
                
                if (!$product || !$this->is_product_visible($product)) {
                    continue;
                }
                
                $xml .= $this->generate_product_url_entry($product, $product_post);
                
                // Clear product from memory
                unset($product);
            }
            
            // Garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        $xml .= '</urlset>';
        
        echo $xml;
        
        // Cleanup
        wp_reset_postdata();
    }
    
    /**
     * Generate category sitemap
     */
    private function generate_category_sitemap() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC'
        ));
        
        if (empty($categories) || is_wp_error($categories)) {
            echo $this->get_empty_sitemap();
            return;
        }
        
        $xml = $this->get_xml_header();
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
    
    /**
     * Generate brand sitemap
     */
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
                    break;
                }
            }
        }
        
        if (empty($brands)) {
            echo $this->get_empty_sitemap();
            return;
        }
        
        $xml = $this->get_xml_header();
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
     * Generate individual product URL entry
     */
    private function generate_product_url_entry($product, $product_post) {
        $xml = "\t<url>\n";
        $xml .= "\t\t<loc>" . esc_url(get_permalink($product_post->ID)) . "</loc>\n";
        $xml .= "\t\t<lastmod>" . esc_html(date('c', strtotime($product_post->post_modified))) . "</lastmod>\n";
        $xml .= "\t\t<changefreq>" . esc_html($this->get_product_changefreq($product)) . "</changefreq>\n";
        $xml .= "\t\t<priority>" . esc_html($this->get_product_priority($product)) . "</priority>\n";
        
        // Add product images (optimized for large catalogs)
        if ($this->should_include_images()) {
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
        }
        
        $xml .= "\t</url>\n";
        
        return $xml;
    }
    
    /**
     * Background sitemap generation
     */
    public function generate_all_sitemaps_background() {
        $this->optimize_environment();
        
        // Clear existing cache
        $this->clear_sitemap_cache();
        
        // Generate index
        $this->schedule_single_sitemap('index', 0);
        
        // Schedule product sitemaps
        $total_products = $this->get_published_product_count();
        $products_per_sitemap = $this->get_products_per_sitemap();
        $total_pages = ceil($total_products / $products_per_sitemap);
        
        for ($page = 1; $page <= $total_pages; $page++) {
            wp_schedule_single_event(
                time() + ($page * 30), 
                'wog_generate_single_sitemap', 
                array('products', $page)
            );
        }
        
        // Schedule other sitemaps
        wp_schedule_single_event(
            time() + (($total_pages + 1) * 30), 
            'wog_generate_single_sitemap', 
            array('categories', 0)
        );
        
        if ($this->has_product_brands()) {
            wp_schedule_single_event(
                time() + (($total_pages + 2) * 30), 
                'wog_generate_single_sitemap', 
                array('brands', 0)
            );
        }
        
        update_option('wog_sitemap_last_generated', time());
    }
    
    /**
     * Generate single sitemap in background
     */
    public function generate_single_sitemap_background($type, $page) {
        $this->optimize_environment();
        
        $cache_key = $this->get_cache_key($type, $page);
        
        ob_start();
        
        try {
            switch ($type) {
                case 'index':
                    $this->generate_sitemap_index();
                    break;
                case 'products':
                    $this->generate_product_sitemap($page);
                    break;
                case 'categories':
                    $this->generate_category_sitemap();
                    break;
                case 'brands':
                    $this->generate_brand_sitemap();
                    break;
            }
        } catch (Exception $e) {
            $this->log_debug("Background generation failed for {$type} page {$page}: " . $e->getMessage());
            ob_end_clean();
            return;
        }
        
        $sitemap_content = ob_get_clean();
        $this->cache_sitemap($cache_key, $sitemap_content);
    }
    
    /**
     * Helper Methods
     */
    
    private function optimize_environment() {
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        ignore_user_abort(true);
    }
    
    private function set_xml_headers() {
        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex, follow', true);
        
        // Cache headers
        $cache_time = $this->cache_duration;
        header('Cache-Control: public, max-age=' . $cache_time);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_time) . ' GMT');
    }
    
    private function get_xml_header() {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    }
    
    private function get_empty_sitemap() {
        return $this->get_xml_header() . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    }
    
    private function output_404() {
        status_header(404);
        echo $this->get_xml_header() . '<error>Sitemap not found</error>';
    }
    
    private function handle_generation_error($exception) {
        $this->log_debug("Sitemap generation error: " . $exception->getMessage());
        status_header(500);
        echo $this->get_xml_header() . '<error>Sitemap generation failed</error>';
    }
    
    private function get_cache_key($sitemap_type, $page = 0) {
        $key = "wog_sitemap_{$sitemap_type}";
        if ($page > 0) {
            $key .= "_page_{$page}";
        }
        return $key;
    }
    
    private function is_product_visible($product) {
        if (!$product) {
            return false;
        }
        
        if (get_post_status($product->get_id()) !== 'publish') {
            return false;
        }
        
        // Use cached visibility if available
        $cache_key = "product_visible_{$product->get_id()}";
        $cached_visibility = wp_cache_get($cache_key, 'wog');
        
        if ($cached_visibility !== false) {
            return $cached_visibility;
        }
        
        $is_visible = method_exists($product, 'is_visible') ? $product->is_visible() : true;
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $is_visible, 'wog', 3600);
        
        return $is_visible;
    }
    
    private function get_published_product_count() {
        $cache_key = 'published_product_count';
        $cached_count = wp_cache_get($cache_key, 'wog');
        
        if ($cached_count !== false) {
            return $cached_count;
        }
        
        global $wpdb;
        
        $visibility_terms = $this->get_excluded_visibility_terms();
        
        if (empty($visibility_terms)) {
            $count = $wpdb->get_var(
                "SELECT COUNT(*) 
                 FROM {$wpdb->posts} 
                 WHERE post_type = 'product' 
                 AND post_status = 'publish'"
            );
        } else {
            $terms_in = implode(',', array_map('intval', $visibility_terms));
            $count = $wpdb->get_var(
                "SELECT COUNT(DISTINCT p.ID) 
                 FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id 
                 LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 WHERE p.post_type = 'product' 
                 AND p.post_status = 'publish'
                 AND (tt.term_taxonomy_id NOT IN ({$terms_in}) OR tt.term_taxonomy_id IS NULL)"
            );
        }
        
        $count = intval($count);
        wp_cache_set($cache_key, $count, 'wog', 1800); // 30 minutes
        
        return $count;
    }
    
    private function get_excluded_visibility_terms() {
        static $excluded_terms = null;
        
        if ($excluded_terms === null) {
            $excluded_terms = array();
            
            if (function_exists('wc_get_product_visibility_term_ids')) {
                $visibility_terms = wc_get_product_visibility_term_ids();
                
                if (!empty($visibility_terms['exclude-from-catalog'])) {
                    $excluded_terms[] = $visibility_terms['exclude-from-catalog'];
                }
                
                if (!empty($visibility_terms['exclude-from-search'])) {
                    $excluded_terms[] = $visibility_terms['exclude-from-search'];
                }
            }
        }
        
        return $excluded_terms;
    }
    
    private function get_products_per_sitemap() {
        $default = 500;
        $setting = !empty($this->settings['sitemap_products_per_page']) ? 
                   (int) $this->settings['sitemap_products_per_page'] : $default;
        
        return max(100, min(1000, $setting));
    }
    
    private function get_products_last_modified() {
        $cache_key = 'products_last_modified';
        $cached_date = wp_cache_get($cache_key, 'wog');
        
        if ($cached_date !== false) {
            return $cached_date;
        }
        
        global $wpdb;
        
        $last_modified = $wpdb->get_var(
            "SELECT post_modified 
             FROM {$wpdb->posts} 
             WHERE post_type = 'product' 
             AND post_status = 'publish' 
             ORDER BY post_modified DESC 
             LIMIT 1"
        );
        
        $formatted_date = $last_modified ? date('c', strtotime($last_modified)) : date('c');
        wp_cache_set($cache_key, $formatted_date, 'wog', 3600);
        
        return $formatted_date;
    }
    
    private function get_categories_last_modified() {
        $cache_key = 'categories_last_modified';
        $cached_date = wp_cache_get($cache_key, 'wog');
        
        if ($cached_date !== false) {
            return $cached_date;
        }
        
        global $wpdb;
        
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
        
        $formatted_date = $last_modified ? date('c', strtotime($last_modified)) : date('c');
        wp_cache_set($cache_key, $formatted_date, 'wog', 3600);
        
        return $formatted_date;
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
    
    private function should_include_images() {
        return apply_filters('wog_sitemap_include_images', true);
    }
    
    private function get_product_sitemap_images($product) {
        $images = array();
        
        // Featured image only for large catalogs
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
        
        return $images;
    }
    
    private function get_product_priority($product) {
        $priority = 0.5;
        
        if ($product->is_featured()) $priority += 0.2;
        if ($product->is_on_sale()) $priority += 0.1;
        if ($product->is_in_stock()) $priority += 0.1;
        
        $review_count = $product->get_review_count();
        if ($review_count > 50) $priority += 0.2;
        elseif ($review_count > 10) $priority += 0.1;
        
        $rating = $product->get_average_rating();
        if ($rating >= 4.5) $priority += 0.1;
        elseif ($rating >= 4.0) $priority += 0.05;
        
        return number_format(min(1.0, max(0.1, $priority)), 1);
    }
    
    private function get_product_changefreq($product) {
        if ($product->is_type('variable')) return 'weekly';
        if ($product->is_on_sale()) return 'weekly';
        if ($product->managing_stock() && $product->get_stock_quantity() <= 5) return 'daily';
        
        return 'monthly';
    }
    
    private function get_category_priority($category) {
        $priority = 0.6;
        
        if ($category->count > 100) $priority += 0.3;
        elseif ($category->count > 20) $priority += 0.2;
        elseif ($category->count > 5) $priority += 0.1;
        
        if ($category->parent == 0) $priority += 0.1;
        
        return number_format(min(1.0, $priority), 1);
    }
    
    private function get_category_changefreq($category) {
        if ($category->count > 50) return 'weekly';
        elseif ($category->count > 10) return 'monthly';
        
        return 'yearly';
    }
    
    private function get_category_last_modified($category) {
        global $wpdb;
        
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
        
        if ($brand->count > 50) $priority += 0.3;
        elseif ($brand->count > 10) $priority += 0.2;
        elseif ($brand->count > 3) $priority += 0.1;
        
        return number_format(min(1.0, $priority), 1);
    }
    
    private function get_brand_last_modified($brand) {
        global $wpdb;
        
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
     * Memory Management
     */
    
    private function is_memory_limit_approaching() {
        $memory_limit = $this->get_memory_limit_bytes();
        $current_usage = memory_get_usage(true);
        
        return ($current_usage / $memory_limit) > $this->max_memory_usage;
    }
    
    private function get_memory_limit_bytes() {
        $memory_limit = ini_get('memory_limit');
        
        if ($memory_limit == -1) {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int) $memory_limit;
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Caching Methods
     */
    
    private function get_cached_sitemap($cache_key) {
        return get_transient($cache_key);
    }
    
    private function cache_sitemap($cache_key, $content) {
        set_transient($cache_key, $content, $this->cache_duration);
    }
    
    private function clear_sitemap_cache() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_wog_%' 
             OR option_name LIKE '_transient_timeout_wog_%'"
        );
        
        wp_cache_flush_group('wog');
    }
    
    public function cleanup_old_cache() {
        // Cleanup runs automatically via wp_scheduled_delete
        $this->clear_sitemap_cache();
    }
    
    /**
     * Update Scheduling
     */
    
    public function maybe_update_sitemap($post_id, $post) {
        if ($post->post_type === 'product' && $post->post_status === 'publish') {
            $this->schedule_sitemap_update();
        }
    }
    
    private function schedule_sitemap_update() {
        if (!wp_next_scheduled('wog_generate_sitemaps')) {
            wp_schedule_single_event(time() + 600, 'wog_generate_sitemaps'); // 10 minutes
        }
    }
    
    public function schedule_sitemap_generation() {
        if (!wp_next_scheduled('wog_generate_sitemaps')) {
            wp_schedule_event(time(), 'daily', 'wog_generate_sitemaps');
        }
    }
    
    private function schedule_single_sitemap($type, $page) {
        wp_schedule_single_event(time() + 30, 'wog_generate_single_sitemap', array($type, $page));
    }
    
    /**
     * Public API Methods
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
    
    public function force_regenerate_all() {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        $this->clear_sitemap_cache();
        delete_option('wog_rewrite_rules_flushed_v2');
        
        $this->add_sitemap_rewrite_rules();
        flush_rewrite_rules();
        
        $this->generate_all_sitemaps_background();
        
        return true;
    }
    
    public function get_sitemap_stats() {
        return array(
            'total_products' => $this->get_published_product_count(),
            'products_per_sitemap' => $this->get_products_per_sitemap(),
            'total_sitemap_pages' => ceil($this->get_published_product_count() / $this->get_products_per_sitemap()),
            'last_generated' => get_option('wog_sitemap_last_generated', 0),
            'cache_enabled' => true,
            'memory_limit' => ini_get('memory_limit'),
            'urls' => array(
                'main_index' => home_url('/wog-sitemap.xml'),
                'products_page_1' => home_url('/product-sitemap-1.xml'),
                'categories' => home_url('/product-category-sitemap.xml'),
            )
        );
    }
    
    /**
     * Debug and Logging
     */
    
    private function log_debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("EWOG Sitemap: " . $message);
        }
    }
    
    public function debug_sitemap_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $stats = $this->get_sitemap_stats();
        
        echo "<div class='wog-debug-info'>";
        echo "<h3>EWOG Sitemap Debug Information</h3>";
        echo "<table class='widefat'>";
        
        foreach ($stats as $key => $value) {
            if (is_array($value)) {
                $value = '<ul><li>' . implode('</li><li>', array_map('esc_html', $value)) . '</li></ul>';
            } else {
                $value = esc_html($value);
            }
            echo "<tr><td><strong>" . esc_html(ucwords(str_replace('_', ' ', $key))) . "</strong></td><td>{$value}</td></tr>";
        }
        
        echo "</table>";
        
        // Test buttons
        echo "<p>";
        echo "<a href='" . home_url('/wog-sitemap.xml') . "' target='_blank' class='button'>Test Main Index</a> ";
        echo "<a href='" . home_url('/product-sitemap-1.xml') . "' target='_blank' class='button'>Test Products</a> ";
        echo "<button onclick='wogClearCache()' class='button'>Clear Cache</button>";
        echo "</p>";
        
        echo "<script>
        function wogClearCache() {
            if (confirm('Clear all sitemap cache?')) {
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=wog_clear_cache&nonce=' + wogAdmin.nonce
                }).then(() => location.reload());
            }
        }
        </script>";
        
        echo "</div>";
    }
    
    /**
     * Robots.txt Integration
     */
    
    public function get_sitemap_urls() {
        $urls = array();
        
        if (!empty($this->settings['enable_product_sitemap'])) {
            $urls[] = home_url('/wog-sitemap.xml');
        }
        
        return $urls;
    }
    
    public function add_sitemap_to_robots($output) {
        if (!empty($this->settings['enable_product_sitemap'])) {
            $output .= "\nSitemap: " . home_url('/wog-sitemap.xml') . "\n";
        }
        
        return $output;
    }
}