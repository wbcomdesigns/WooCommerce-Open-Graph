<?php
/**
 * Plugin Name: Woo Open Graph
 * Plugin URI: https://wbcomdesigns.com/woo-open-graph
 * Description: Advanced Open Graph meta tags for WooCommerce with support for modern social platforms, schema markup, and enhanced SEO features.
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
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-settings.php';
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-meta-tags.php';
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-schema.php';
        require_once EWOG_PLUGIN_DIR . 'includes/class-ewog-social-share.php';
        require_once EWOG_PLUGIN_DIR . 'admin/class-ewog-admin.php';
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
        EWOG_Social_Share::get_instance();
        
        if (is_admin()) {
            EWOG_Admin::get_instance();
        }
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
            'enable_facebook' => true,
            'enable_twitter' => true,
            'enable_linkedin' => true,
            'enable_pinterest' => true,
            'enable_whatsapp' => true,
            'enable_schema' => true,
            'disable_title_description' => false,
            'image_size' => 'large',
            'fallback_image' => '',
            'twitter_username' => '',
            'facebook_app_id' => '',
            'enable_social_share' => true,
            'share_button_style' => 'modern',
            'share_button_position' => 'after_add_to_cart'
        );
        
        add_option('ewog_settings', $default_options);
        
        // Set plugin version
        add_option('ewog_version', EWOG_VERSION);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
    }
    
    /**
     * Get plugin settings
     */
    public function get_settings() {
        return $this->settings ? $this->settings->get_all_settings() : array();
    }
}

// Initialize the plugin
Enhanced_Woo_Open_Graph::get_instance();