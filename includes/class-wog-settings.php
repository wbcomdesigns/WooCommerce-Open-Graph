<?php
/**
 * Enhanced Settings Class
 * 
 * Comprehensive settings management with caching and validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class WOG_Settings {
    
    private static $instance = null;
    private $settings;
    private $default_settings;
    private $cache_key = 'wog_settings_cache';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->set_default_settings();
        $this->load_settings();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Clear cache when settings are updated
        add_action('update_option_wog_settings', array($this, 'clear_settings_cache'));
    }
    
    /**
     * Set default settings
     */
    private function set_default_settings() {
        $this->default_settings = array(
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
        
        // Allow plugins to modify default settings
        $this->default_settings = apply_filters('wog_default_settings', $this->default_settings);
    }
    
    /**
     * Load settings from database with caching
     */
    private function load_settings() {
        // Try to get from cache first
        $cached_settings = wp_cache_get($this->cache_key, 'wog');
        
        if ($cached_settings !== false) {
            $this->settings = $cached_settings;
            return;
        }
        
        // Load from database
        $saved_settings = get_option('wog_settings', array());
        $this->settings = wp_parse_args($saved_settings, $this->default_settings);
        
        // Cache the settings
        wp_cache_set($this->cache_key, $this->settings, 'wog', HOUR_IN_SECONDS);
    }
    
    /**
     * Clear settings cache
     */
    public function clear_settings_cache() {
        wp_cache_delete($this->cache_key, 'wog');
        
        // Reload settings
        $this->load_settings();
        
        do_action('wog_settings_cache_cleared');
    }
    
    /**
     * Get a specific setting
     */
    public function get($key, $default = null) {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        
        return $default !== null ? $default : (isset($this->default_settings[$key]) ? $this->default_settings[$key] : null);
    }
    
    /**
     * Get all settings
     */
    public function get_all_settings() {
        return $this->settings;
    }
    
    /**
     * Update a setting
     */
    public function update($key, $value) {
        $this->settings[$key] = $value;
        $result = update_option('wog_settings', $this->settings);
        
        if ($result) {
            $this->clear_settings_cache();
            do_action('wog_setting_updated', $key, $value);
        }
        
        return $result;
    }
    
    /**
     * Update multiple settings
     */
    public function update_multiple($settings) {
        $this->settings = wp_parse_args($settings, $this->settings);
        $result = update_option('wog_settings', $this->settings);
        
        if ($result) {
            $this->clear_settings_cache();
            do_action('wog_settings_updated', $settings);
        }
        
        return $result;
    }
    
    /**
     * Reset to default settings
     */
    public function reset_to_defaults() {
        $this->settings = $this->default_settings;
        $result = update_option('wog_settings', $this->settings);
        
        if ($result) {
            $this->clear_settings_cache();
            do_action('wog_settings_reset');
        }
        
        return $result;
    }
    
    /**
     * Check if a platform is enabled
     */
    public function is_platform_enabled($platform) {
        return $this->get('enable_' . $platform, false);
    }
    
    /**
     * Get enabled platforms
     */
    public function get_enabled_platforms() {
        $platforms = array('facebook', 'twitter', 'linkedin', 'pinterest', 'whatsapp');
        $enabled = array();
        
        foreach ($platforms as $platform) {
            if ($this->is_platform_enabled($platform)) {
                $enabled[] = $platform;
            }
        }
        
        return $enabled;
    }
    
    /**
     * Schema-related settings
     */
    public function is_schema_enabled() {
        return $this->get('enable_schema', true);
    }
    
    public function is_enhanced_schema_enabled() {
        return $this->get('enable_enhanced_schema', true);
    }
    
    public function is_breadcrumb_schema_enabled() {
        return $this->get('enable_breadcrumb_schema', true);
    }
    
    public function is_organization_schema_enabled() {
        return $this->get('enable_organization_schema', true);
    }
    
    /**
     * Open Graph related settings
     */
    public function get_image_size() {
        return $this->get('image_size', 'large');
    }
    
    public function get_fallback_image() {
        $fallback = $this->get('fallback_image', '');
        
        if (empty($fallback)) {
            return wc_placeholder_img_src($this->get_image_size());
        }
        
        return $fallback;
    }
    
    public function should_disable_title_description() {
        return $this->get('disable_title_description', false);
    }
    
    public function get_facebook_app_id() {
        return $this->get('facebook_app_id', '');
    }
    
    public function get_twitter_username() {
        return $this->get('twitter_username', '');
    }
    
    /**
     * Sitemap related settings
     */
    public function is_sitemap_enabled() {
        return $this->get('enable_product_sitemap', true);
    }
    
    public function get_sitemap_products_per_page() {
        return intval($this->get('sitemap_products_per_page', 500));
    }
    
    /**
     * Social sharing related settings
     */
    public function is_social_share_enabled() {
        return $this->get('enable_social_share', true);
    }
    
    public function get_share_button_style() {
        return $this->get('share_button_style', 'modern');
    }
    
    public function get_share_button_position() {
        return $this->get('share_button_position', 'after_add_to_cart');
    }
    
    /**
     * Advanced settings
     */
    public function is_cache_enabled() {
        return $this->get('cache_meta_tags', true);
    }
    
    public function is_debug_mode() {
        return $this->get('debug_mode', false);
    }
    
    /**
     * Export settings
     */
    public function export_settings() {
        $export_data = array(
            'version' => WOG_VERSION,
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'settings' => $this->settings
        );
        
        return wp_json_encode($export_data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import settings from JSON
     */
    public function import_settings($json_string) {
        $imported_data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON format', 'open-graph-for-woocommerce'));
        }
        
        if (!isset($imported_data['settings']) || !is_array($imported_data['settings'])) {
            return new WP_Error('no_settings', __('No valid settings found in import data', 'open-graph-for-woocommerce'));
        }
        
        // Validate imported settings
        $valid_settings = array();
        foreach ($this->default_settings as $key => $default_value) {
            if (isset($imported_data['settings'][$key])) {
                $valid_settings[$key] = $imported_data['settings'][$key];
            }
        }
        
        if (empty($valid_settings)) {
            return new WP_Error('no_valid_settings', __('No valid settings found in import', 'open-graph-for-woocommerce'));
        }
        
        // Sanitize settings before import
        $sanitized_settings = $this->validate_settings($valid_settings);
        
        $this->update_multiple($sanitized_settings);
        
        do_action('wog_settings_imported', $sanitized_settings, $imported_data);
        
        return count($valid_settings);
    }
    
    /**
     * Validate settings
     */
    public function validate_settings($settings) {
        $validated = array();
        
        // Boolean settings
        $boolean_settings = array(
            'enable_schema', 'enable_enhanced_schema', 'enable_breadcrumb_schema',
            'enable_organization_schema', 'enable_facebook', 'enable_twitter', 
            'enable_linkedin', 'enable_pinterest', 'enable_whatsapp',
            'disable_title_description', 'enable_product_sitemap', 'enable_social_share',
            'cache_meta_tags', 'debug_mode'
        );
        
        foreach ($boolean_settings as $setting) {
            $validated[$setting] = isset($settings[$setting]) ? (bool) $settings[$setting] : false;
        }
        
        // Text settings with sanitization
        $validated['twitter_username'] = sanitize_text_field($settings['twitter_username'] ?? '');
        $validated['facebook_app_id'] = sanitize_text_field($settings['facebook_app_id'] ?? '');
        
        // Remove @ from Twitter username if present
        if (!empty($validated['twitter_username']) && $validated['twitter_username'][0] === '@') {
            $validated['twitter_username'] = substr($validated['twitter_username'], 1);
        }
        
        // Validate Twitter username format
        if (!empty($validated['twitter_username']) && !preg_match('/^[A-Za-z0-9_]{1,15}$/', $validated['twitter_username'])) {
            $validated['twitter_username'] = '';
        }
        
        // Select settings with validation
        $valid_image_sizes = array('thumbnail', 'medium', 'large', 'full');
        $validated['image_size'] = in_array($settings['image_size'] ?? '', $valid_image_sizes) ? 
            $settings['image_size'] : 'large';
        
        $valid_button_styles = array('modern', 'classic', 'minimal');
        $validated['share_button_style'] = in_array($settings['share_button_style'] ?? '', $valid_button_styles) ? 
            $settings['share_button_style'] : 'modern';
        
        $valid_button_positions = array('after_add_to_cart', 'before_add_to_cart', 'after_summary', 'after_tabs');
        $validated['share_button_position'] = in_array($settings['share_button_position'] ?? '', $valid_button_positions) ? 
            $settings['share_button_position'] : 'after_add_to_cart';
        
        // Number settings with validation
        $validated['sitemap_products_per_page'] = intval($settings['sitemap_products_per_page'] ?? 500);
        if ($validated['sitemap_products_per_page'] < 100) {
            $validated['sitemap_products_per_page'] = 100;
        }
        if ($validated['sitemap_products_per_page'] > 1000) {
            $validated['sitemap_products_per_page'] = 1000;
        }
        
        // URL settings
        $validated['fallback_image'] = esc_url_raw($settings['fallback_image'] ?? '');
        
        return apply_filters('wog_validated_settings', $validated, $settings);
    }
    
    /**
     * Get setting with type casting
     */
    public function get_typed($key, $type = 'string', $default = null) {
        $value = $this->get($key, $default);
        
        switch ($type) {
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
                return is_array($value) ? $value : array();
            case 'url':
                return esc_url($value);
            default:
                return (string) $value;
        }
    }
    
    /**
     * Check if any schema feature is enabled
     */
    public function has_schema_enabled() {
        return $this->is_schema_enabled() || 
               $this->is_enhanced_schema_enabled() || 
               $this->is_breadcrumb_schema_enabled() || 
               $this->is_organization_schema_enabled();
    }
    
    /**
     * Check if any Open Graph feature is enabled
     */
    public function has_opengraph_enabled() {
        $platforms = $this->get_enabled_platforms();
        return !empty($platforms);
    }
    
    /**
     * Get settings for a specific section
     */
    public function get_section_settings($section) {
        $all_settings = $this->get_all_settings();
        $section_settings = array();
        
        switch ($section) {
            case 'schema':
                $schema_keys = array('enable_schema', 'enable_enhanced_schema', 'enable_breadcrumb_schema', 'enable_organization_schema');
                foreach ($schema_keys as $key) {
                    $section_settings[$key] = $all_settings[$key] ?? false;
                }
                break;
                
            case 'opengraph':
                $og_keys = array('enable_facebook', 'enable_twitter', 'enable_linkedin', 'enable_pinterest', 'enable_whatsapp', 'disable_title_description', 'image_size', 'fallback_image', 'facebook_app_id', 'twitter_username');
                foreach ($og_keys as $key) {
                    $section_settings[$key] = $all_settings[$key] ?? '';
                }
                break;
                
            case 'sitemap':
                $sitemap_keys = array('enable_product_sitemap', 'sitemap_products_per_page');
                foreach ($sitemap_keys as $key) {
                    $section_settings[$key] = $all_settings[$key] ?? '';
                }
                break;
                
            case 'social_share':
                $share_keys = array('enable_social_share', 'share_button_style', 'share_button_position');
                foreach ($share_keys as $key) {
                    $section_settings[$key] = $all_settings[$key] ?? '';
                }
                break;
                
            case 'advanced':
                $advanced_keys = array('cache_meta_tags', 'debug_mode');
                foreach ($advanced_keys as $key) {
                    $section_settings[$key] = $all_settings[$key] ?? false;
                }
                break;
        }
        
        return apply_filters("wog_{$section}_settings", $section_settings);
    }
    
    /**
     * Get plugin configuration summary
     */
    public function get_config_summary() {
        $summary = array(
            'schema_enabled' => $this->has_schema_enabled(),
            'opengraph_enabled' => $this->has_opengraph_enabled(),
            'sitemap_enabled' => $this->is_sitemap_enabled(),
            'social_share_enabled' => $this->is_social_share_enabled(),
            'platforms' => $this->get_enabled_platforms(),
            'image_size' => $this->get_image_size(),
            'cache_enabled' => $this->is_cache_enabled(),
            'debug_mode' => $this->is_debug_mode()
        );
        
        return apply_filters('wog_config_summary', $summary);
    }
    
    /**
     * Migration helper for old settings
     */
    public function migrate_old_settings() {
        $old_settings = get_option('woo_open_graph_settings', false);
        
        if ($old_settings && is_array($old_settings)) {
            $migration_map = array(
                'enable_facebook' => 'enable_facebook',
                'enable_twitter' => 'enable_twitter',
                'facebook_app_id' => 'facebook_app_id',
                'twitter_username' => 'twitter_username'
            );
            
            $migrated_settings = array();
            foreach ($migration_map as $old_key => $new_key) {
                if (isset($old_settings[$old_key])) {
                    $migrated_settings[$new_key] = $old_settings[$old_key];
                }
            }
            
            if (!empty($migrated_settings)) {
                $this->update_multiple($migrated_settings);
                
                // Mark migration as complete
                update_option('wog_migration_completed', true);
                
                do_action('wog_settings_migrated', $migrated_settings, $old_settings);
            }
        }
    }
}