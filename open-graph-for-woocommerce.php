<?php
/**
 * Plugin Name: Open Graph for WooCommerce
 * Plugin URI: https://wbcomdesigns.com/downloads/woo-open-graph/
 * Description: Comprehensive Schema.org markup, Open Graph optimization, and social sharing for WooCommerce. Fill the gaps that free SEO plugins miss.
 * Version: 2.0.1
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: open-graph-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.1
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// WooCommerce compatibility declarations
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
        
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
});

// Define plugin constants
define('WOG_VERSION', '2.0.1');
define('WOG_PLUGIN_FILE', __FILE__);
define('WOG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOG_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Woo_Open_Graph {
    
    private static $instance = null;
    private $settings;
    
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
     * Initialize plugin
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Set up WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        add_filter('robots_txt', array($this, 'add_sitemap_to_robots'), 10, 2);
        add_action('wp_loaded', array($this, 'flush_rewrite_rules_maybe'));
    }
    
    /**
     * Load required class files
     */
    private function load_dependencies() {
        $includes_dir = WOG_PLUGIN_DIR . 'includes/';
        $admin_dir = WOG_PLUGIN_DIR . 'admin/';
        
        $required_files = array(
            $includes_dir . 'class-wog-settings.php',
            $includes_dir . 'class-wog-meta-tags.php',
            $includes_dir . 'class-wog-schema.php',
            $includes_dir . 'class-wog-sitemap.php',
            $includes_dir . 'class-wog-social-share.php',
            $includes_dir . 'class-wog-meta-boxes.php',
            $admin_dir . 'class-wog-admin.php'
        );
        
        foreach ($required_files as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
    
    /**
     * Initialize plugin components after WordPress and WooCommerce are loaded
     */
    public function init() {
        // Check WooCommerce dependency
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialize components
        if (class_exists('WOG_Settings')) {
            $this->settings = WOG_Settings::get_instance();
        }
        
        if (class_exists('WOG_Meta_Tags')) {
            WOG_Meta_Tags::get_instance();
        }
        
        if (class_exists('WOG_Schema')) {
            WOG_Schema::get_instance();
        }
        
        if (class_exists('WOG_Sitemap')) {
            WOG_Sitemap::get_instance();
        }
        
        if (class_exists('WOG_Social_Share')) {
            WOG_Social_Share::get_instance();
        }
        
        if (class_exists('WOG_Meta_Boxes')) {
            WOG_Meta_Boxes::get_instance();
        }
        
        if (is_admin() && class_exists('WOG_Admin')) {
            WOG_Admin::get_instance();
        }
        
        $this->add_custom_hooks();
    }
    
    /**
     * Set up custom hooks for cache clearing and extensibility
     */
    private function add_custom_hooks() {
        do_action('wog_init', $this);
        
        // Clear WordPress object cache when products/terms are updated
        add_action('woocommerce_update_product', array($this, 'clear_product_object_cache'));
        add_action('woocommerce_new_product', array($this, 'clear_product_object_cache'));
        add_action('woocommerce_delete_product', array($this, 'clear_product_object_cache'));
        
        add_action('edited_product_cat', array($this, 'clear_category_object_cache'));
        add_action('created_product_cat', array($this, 'clear_category_object_cache'));
        add_action('delete_product_cat', array($this, 'clear_category_object_cache'));
    }
    
    /**
     * Load translation files
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'open-graph-for-woocommerce',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * Check if WooCommerce is installed and active
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    /**
     * Show admin notice when WooCommerce is missing
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                echo sprintf(
                    __('Open Graph for WooCommerce requires WooCommerce to be installed and active. %s', 'open-graph-for-woocommerce'),
                    '<a href="' . admin_url('plugin-install.php?s=woocommerce&tab=search&type=term') . '">' . __('Install WooCommerce', 'open-graph-for-woocommerce') . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Plugin activation setup
     */
    public function activate() {
        if (!$this->is_woocommerce_active()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('Open Graph for WooCommerce requires WooCommerce to be installed and active.', 'open-graph-for-woocommerce'),
                __('Plugin Activation Error', 'open-graph-for-woocommerce'),
                array('back_link' => true)
            );
        }
        
        // Set default plugin options
        $default_options = array(
            'enable_schema' => true,
            'enable_enhanced_schema' => true,
            'enable_breadcrumb_schema' => true,
            'enable_organization_schema' => true,
            'enable_facebook' => true,
            'enable_twitter' => true,
            'enable_linkedin' => true,
            'enable_pinterest' => true,
            'enable_whatsapp' => true,
            'disable_title_description' => false,
            'image_size' => 'large',
            'fallback_image' => '',
            'facebook_app_id' => '',
            'twitter_username' => '',
            'enable_product_sitemap' => true,
            'sitemap_products_per_page' => 500,
            'enable_social_share' => true,
            'share_button_style' => 'modern',
            'share_button_position' => 'after_add_to_cart',
            'debug_mode' => false
        );
        
        add_option('wog_settings', $default_options);
        add_option('wog_version', WOG_VERSION);
        add_option('wog_flush_rewrite_rules', true);
        
        // Schedule daily sitemap generation
        if (!wp_next_scheduled('wog_generate_sitemaps')) {
            wp_schedule_event(time(), 'daily', 'wog_generate_sitemaps');
        }
    }
    
    /**
     * Plugin deactivation cleanup
     */
    public function deactivate() {
        wp_clear_scheduled_hook('wog_generate_sitemaps');
        flush_rewrite_rules();
        $this->cleanup_transients();
    }
    
    /**
     * Flush rewrite rules if needed after activation
     */
    public function flush_rewrite_rules_maybe() {
        if (get_option('wog_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('wog_flush_rewrite_rules');
        }
    }
    
    /**
     * Add sitemap URL to robots.txt
     */
    public function add_sitemap_to_robots($output, $public) {
        if ('1' == $public) {
            $settings = get_option('wog_settings', array());
            
            if (!empty($settings['enable_product_sitemap'])) {
                $output .= "\nSitemap: " . home_url('/wog-sitemap.xml') . "\n";
            }
        }
        
        return $output;
    }
    
    /**
     * Clear WordPress object cache for a product
     */
    public function clear_product_object_cache($product_id) {
        if (!$product_id) {
            return;
        }
        
        wp_cache_delete($product_id, 'posts');
        wp_cache_delete($product_id, 'post_meta');
        
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients($product_id);
        }
        
        delete_transient('wog_product_meta_' . $product_id);
        delete_transient('wog_product_schema_' . $product_id);
        
        do_action('wog_product_cache_cleared', $product_id);
    }
    
    /**
     * Clear WordPress object cache for a category
     */
    public function clear_category_object_cache($term_id) {
        if (!$term_id) {
            return;
        }
        
        wp_cache_delete($term_id, 'terms');
        delete_transient('wog_category_meta_' . $term_id);
        
        do_action('wog_category_cache_cleared', $term_id);
    }
    
    /**
     * Clean up plugin transients
     */
    private function cleanup_transients() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_wog_%' 
             OR option_name LIKE '_transient_timeout_wog_%'"
        );
    }
    
    /**
     * Get plugin settings
     */
    public function get_settings() {
        return $this->settings ? $this->settings->get_all_settings() : array();
    }
    
    /**
     * Get plugin version
     */
    public static function get_version() {
        return WOG_VERSION;
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function is_debug_mode() {
        $settings = get_option('wog_settings', array());
        return !empty($settings['debug_mode']);
    }
    
    /**
     * Log debug messages to error log
     */
    public function debug_log($message, $data = null) {
        if ($this->is_debug_mode() && function_exists('error_log')) {
            $log_message = '[Open Graph for WooCommerce] ' . $message;
            
            if ($data !== null) {
                $log_message .= ' | Data: ' . print_r($data, true);
            }
            
            error_log($log_message);
        }
    }
    
    /**
     * Get system information for debugging
     */
    public function get_system_info() {
        global $wpdb;
        
        $info = array(
            'plugin_version' => WOG_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Not installed',
            'php_version' => PHP_VERSION,
            'mysql_version' => $wpdb->db_version(),
            'server_info' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'active_plugins' => get_option('active_plugins'),
            'active_theme' => get_template(),
            'multisite' => is_multisite() ? 'Yes' : 'No',
            'settings' => get_option('wog_settings', array())
        );
        
        return apply_filters('wog_system_info', $info);
    }
    
    /**
     * Export plugin settings as base64 encoded JSON
     */
    public function export_settings() {
        $settings = get_option('wog_settings', array());
        $export_data = array(
            'version' => WOG_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $settings
        );
        
        return base64_encode(json_encode($export_data));
    }
    
    /**
     * Import plugin settings from base64 encoded JSON
     */
    public function import_settings($import_data) {
        try {
            $data = json_decode(base64_decode($import_data), true);
            
            if (!$data || !isset($data['settings'])) {
                return new WP_Error('invalid_data', __('Invalid import data', 'open-graph-for-woocommerce'));
            }
            
            if (class_exists('WOG_Admin')) {
                $admin = WOG_Admin::get_instance();
                $sanitized_settings = $admin->sanitize_settings($data['settings']);
            } else {
                $sanitized_settings = array_map('sanitize_text_field', $data['settings']);
            }
            
            update_option('wog_settings', $sanitized_settings);
            
            return true;
            
        } catch (Exception $e) {
            return new WP_Error('import_error', $e->getMessage());
        }
    }
    
    // Legacy method names for backward compatibility
    public function clear_product_cache($product_id) {
        $this->clear_product_object_cache($product_id);
    }
    
    public function clear_category_cache($term_id) {
        $this->clear_category_object_cache($term_id);
    }
}

// Initialize the plugin
Woo_Open_Graph::get_instance();

/**
 * Get plugin instance
 */
function wog() {
    return Woo_Open_Graph::get_instance();
}

/**
 * Get plugin settings
 */
function wog_get_settings() {
    return wog()->get_settings();
}

/**
 * Log debug message
 */
function wog_debug_log($message, $data = null) {
    wog()->debug_log($message, $data);
}