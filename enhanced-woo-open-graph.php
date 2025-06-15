<?php
/**
 * Plugin Name: Enhanced Woo Open Graph
 * Plugin URI: https://wbcomdesigns.com/enhanced-woo-open-graph
 * Description: Comprehensive Schema.org markup, Open Graph optimization, and XML sitemaps for WooCommerce. Fill the gaps that free SEO plugins miss.
 * Version: 2.0.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: enhanced-woo-open-graph
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EWOG_VERSION', '2.0.0');
define('EWOG_PLUGIN_FILE', __FILE__);
define('EWOG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EWOG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EWOG_TEXT_DOMAIN', 'enhanced-woo-open-graph');

/**
 * Main Enhanced Woo Open Graph Class
 */
class Enhanced_Woo_Open_Graph {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Plugin settings
     */
    private $settings;
    
    /**
     * Get instance
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
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        
        // Add robots.txt sitemap entries
        add_filter('robots_txt', array($this, 'add_sitemap_to_robots'), 10, 2);
        
        // Add rewrite rules flush on activation
        add_action('wp_loaded', array($this, 'flush_rewrite_rules_maybe'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-settings.php';
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-meta-tags.php';
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-schema.php';
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-sitemap.php';
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-social-share.php';
        require_once EWOG_PLUGIN_DIR . 'admin/class-ewog-admin.php';
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-meta-boxes.php';

    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialize components
        $this->settings = EWOG_Settings::get_instance();
        EWOG_Meta_Tags::get_instance();
        EWOG_Schema::get_instance();
        EWOG_Sitemap::get_instance();
        EWOG_Social_Share::get_instance();
        
        if (is_admin()) {
            EWOG_Admin::get_instance();
        }
        
        // Add custom hooks
        $this->add_custom_hooks();
    }
    
    /**
     * Add custom hooks for extensibility
     */
    private function add_custom_hooks() {
        // Allow other plugins to hook into our functionality
        do_action('ewog_init', $this);
        
        // Product save hooks for cache clearing
        add_action('woocommerce_update_product', array($this, 'clear_product_cache'));
        add_action('woocommerce_new_product', array($this, 'clear_product_cache'));
        
        // Term update hooks
        add_action('edited_product_cat', array($this, 'clear_category_cache'));
        add_action('created_product_cat', array($this, 'clear_category_cache'));
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            EWOG_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                echo sprintf(
                    __('Enhanced Woo Open Graph requires WooCommerce to be installed and active. %s', EWOG_TEXT_DOMAIN),
                    '<a href="' . admin_url('plugin-install.php?s=woocommerce&tab=search&type=term') . '">' . __('Install WooCommerce', EWOG_TEXT_DOMAIN) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        if (!$this->is_woocommerce_active()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('Enhanced Woo Open Graph requires WooCommerce to be installed and active.', EWOG_TEXT_DOMAIN),
                __('Plugin Activation Error', EWOG_TEXT_DOMAIN),
                array('back_link' => true)
            );
        }
        
        // Set default options
        $default_options = array(
            // Schema settings
            'enable_schema' => true,
            'enable_enhanced_schema' => true,
            'enable_breadcrumb_schema' => true,
            'enable_organization_schema' => true,
            
            // Open Graph settings
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
            
            // Sitemap settings
            'enable_product_sitemap' => true,
            'sitemap_products_per_page' => 500,
            
            // Social sharing settings
            'enable_social_share' => true,
            'share_button_style' => 'modern',
            'share_button_position' => 'after_add_to_cart',
            
            // Advanced settings
            'cache_meta_tags' => true,
            'debug_mode' => false
        );
        
        add_option('ewog_settings', $default_options);
        
        // Set plugin version
        add_option('ewog_version', EWOG_VERSION);
        
        // Set activation flag for rewrite rules flush
        add_option('ewog_flush_rewrite_rules', true);
        
        // Schedule sitemap generation
        if (!wp_next_scheduled('ewog_generate_sitemaps')) {
            wp_schedule_event(time(), 'daily', 'ewog_generate_sitemaps');
        }
        
        // Create cache table if caching is enabled
        $this->create_cache_table();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('ewog_generate_sitemaps');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clean up transients
        $this->cleanup_transients();
    }
    
    /**
     * Create cache table for meta tags
     */
    private function create_cache_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ewog_meta_cache';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            meta_type varchar(50) NOT NULL,
            meta_content longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY meta_type (meta_type),
            UNIQUE KEY post_meta_type (post_id, meta_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Flush rewrite rules if needed
     */
    public function flush_rewrite_rules_maybe() {
        if (get_option('ewog_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('ewog_flush_rewrite_rules');
        }
    }
    
    /**
     * Add sitemap to robots.txt
     */
    public function add_sitemap_to_robots($output, $public) {
        if ('1' == $public) {
            $settings = get_option('ewog_settings', array());
            
            if (!empty($settings['enable_product_sitemap'])) {
                $output .= "\nSitemap: " . home_url('/ewog-sitemap.xml') . "\n";
            }
        }
        
        return $output;
    }
    
    /**
     * Clear product cache
     */
    public function clear_product_cache($product_id) {
        $settings = get_option('ewog_settings', array());
        
        if (!empty($settings['cache_meta_tags'])) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'ewog_meta_cache';
            $wpdb->delete(
                $table_name,
                array('post_id' => $product_id),
                array('%d')
            );
        }
        
        // Clear related transients
        delete_transient('ewog_product_meta_' . $product_id);
        delete_transient('ewog_product_schema_' . $product_id);
        
        do_action('ewog_product_cache_cleared', $product_id);
    }
    
    /**
     * Clear category cache
     */
    public function clear_category_cache($term_id) {
        // Clear category-related transients
        delete_transient('ewog_category_meta_' . $term_id);
        delete_transient('ewog_category_products_' . $term_id);
        
        do_action('ewog_category_cache_cleared', $term_id);
    }
    
    /**
     * Cleanup transients on deactivation
     */
    private function cleanup_transients() {
        global $wpdb;
        
        // Delete all plugin transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_ewog_%' 
             OR option_name LIKE '_transient_timeout_ewog_%'"
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
        return EWOG_VERSION;
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function is_debug_mode() {
        $settings = get_option('ewog_settings', array());
        return !empty($settings['debug_mode']);
    }
    
    /**
     * Log debug messages
     */
    public function debug_log($message, $data = null) {
        if ($this->is_debug_mode() && function_exists('error_log')) {
            $log_message = '[Enhanced Woo Open Graph] ' . $message;
            
            if ($data !== null) {
                $log_message .= ' | Data: ' . print_r($data, true);
            }
            
            error_log($log_message);
        }
    }
    
    /**
     * Get cache from database
     */
    public function get_cache($post_id, $meta_type) {
        $settings = get_option('ewog_settings', array());
        
        if (empty($settings['cache_meta_tags'])) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ewog_meta_cache';
        
        $cached = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_content FROM $table_name 
             WHERE post_id = %d AND meta_type = %s 
             AND updated_at > DATE_SUB(NOW(), INTERVAL 1 DAY)",
            $post_id,
            $meta_type
        ));
        
        return $cached ? maybe_unserialize($cached) : false;
    }
    
    /**
     * Set cache in database
     */
    public function set_cache($post_id, $meta_type, $data) {
        $settings = get_option('ewog_settings', array());
        
        if (empty($settings['cache_meta_tags'])) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ewog_meta_cache';
        
        $wpdb->replace(
            $table_name,
            array(
                'post_id' => $post_id,
                'meta_type' => $meta_type,
                'meta_content' => maybe_serialize($data),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        return true;
    }
    
    /**
     * Get system info for debugging
     */
    public function get_system_info() {
        global $wpdb;
        
        $info = array(
            'plugin_version' => EWOG_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Not installed',
            'php_version' => PHP_VERSION,
            'mysql_version' => $wpdb->db_version(),
            'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'active_plugins' => get_option('active_plugins'),
            'active_theme' => get_template(),
            'multisite' => is_multisite() ? 'Yes' : 'No',
            'settings' => get_option('ewog_settings', array())
        );
        
        return apply_filters('ewog_system_info', $info);
    }
    
    /**
     * Export settings for backup/migration
     */
    public function export_settings() {
        $settings = get_option('ewog_settings', array());
        $export_data = array(
            'version' => EWOG_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $settings
        );
        
        return base64_encode(json_encode($export_data));
    }
    
    /**
     * Import settings from backup
     */
    public function import_settings($import_data) {
        try {
            $data = json_decode(base64_decode($import_data), true);
            
            if (!$data || !isset($data['settings'])) {
                return new WP_Error('invalid_data', __('Invalid import data', EWOG_TEXT_DOMAIN));
            }
            
            // Validate settings
            $admin = EWOG_Admin::get_instance();
            $sanitized_settings = $admin->sanitize_settings($data['settings']);
            
            // Update settings
            update_option('ewog_settings', $sanitized_settings);
            
            return true;
            
        } catch (Exception $e) {
            return new WP_Error('import_error', $e->getMessage());
        }
    }
}

// Initialize the plugin
Enhanced_Woo_Open_Graph::get_instance();

/**
 * Global function to get plugin instance
 */
function ewog() {
    return Enhanced_Woo_Open_Graph::get_instance();
}

// Declare WooCommerce compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

/**
 * Backward compatibility functions
 */
if (!function_exists('ewog_get_settings')) {
    function ewog_get_settings() {
        return ewog()->get_settings();
    }
}

if (!function_exists('ewog_debug_log')) {
    function ewog_debug_log($message, $data = null) {
        ewog()->debug_log($message, $data);
    }
}