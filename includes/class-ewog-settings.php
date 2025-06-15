<?php
/**
 * Settings Class
 * 
 * Handles plugin settings and configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWOG_Settings {
    
    private static $instance = null;
    private $settings;
    private $default_settings;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->set_default_settings();
        $this->load_settings();
    }
    
    /**
     * Set default settings
     */
    private function set_default_settings() {
        $this->default_settings = array(
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
    }
    
    /**
     * Load settings from database
     */
    private function load_settings() {
        $saved_settings = get_option('ewog_settings', array());
        $this->settings = wp_parse_args($saved_settings, $this->default_settings);
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
        return update_option('ewog_settings', $this->settings);
    }
    
    /**
     * Update multiple settings
     */
    public function update_multiple($settings) {
        $this->settings = wp_parse_args($settings, $this->settings);
        return update_option('ewog_settings', $this->settings);
    }
    
    /**
     * Reset to default settings
     */
    public function reset_to_defaults() {
        $this->settings = $this->default_settings;
        return update_option('ewog_settings', $this->settings);
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
     * Get image size setting
     */
    public function get_image_size() {
        return $this->get('image_size', 'large');
    }
    
    /**
     * Get fallback image URL
     */
    public function get_fallback_image() {
        $fallback = $this->get('fallback_image', '');
        
        if (empty($fallback)) {
            return wc_placeholder_img_src($this->get_image_size());
        }
        
        return $fallback;
    }
    
    /**
     * Should disable title and description from other plugins
     */
    public function should_disable_title_description() {
        return $this->get('disable_title_description', false);
    }
    
    /**
     * Is schema markup enabled
     */
    public function is_schema_enabled() {
        return $this->get('enable_schema', true);
    }
    
    /**
     * Is social sharing enabled
     */
    public function is_social_share_enabled() {
        return $this->get('enable_social_share', true);
    }
    
    /**
     * Get social share button style
     */
    public function get_share_button_style() {
        return $this->get('share_button_style', 'modern');
    }
    
    /**
     * Get social share button position
     */
    public function get_share_button_position() {
        return $this->get('share_button_position', 'after_add_to_cart');
    }
    
    /**
     * Get Twitter username
     */
    public function get_twitter_username() {
        return $this->get('twitter_username', '');
    }
    
    /**
     * Get Facebook App ID
     */
    public function get_facebook_app_id() {
        return $this->get('facebook_app_id', '');
    }
    
    /**
     * Export settings
     */
    public function export_settings() {
        return wp_json_encode($this->settings, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import settings from JSON
     */
    public function import_settings($json_string) {
        $imported_settings = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON format', EWOG_TEXT_DOMAIN));
        }
        
        // Validate imported settings
        $valid_settings = array();
        foreach ($this->default_settings as $key => $default_value) {
            if (isset($imported_settings[$key])) {
                $valid_settings[$key] = $imported_settings[$key];
            }
        }
        
        if (empty($valid_settings)) {
            return new WP_Error('no_valid_settings', __('No valid settings found in import', EWOG_TEXT_DOMAIN));
        }
        
        $this->update_multiple($valid_settings);
        
        return true;
    }
    
    /**
     * Validate settings
     */
    public function validate_settings($settings) {
        $validated = array();
        
        // Boolean settings
        $boolean_settings = array(
            'enable_facebook', 'enable_twitter', 'enable_linkedin', 
            'enable_pinterest', 'enable_whatsapp', 'enable_schema',
            'disable_title_description', 'enable_social_share'
        );
        
        foreach ($boolean_settings as $setting) {
            $validated[$setting] = isset($settings[$setting]) ? (bool) $settings[$setting] : false;
        }
        
        // Text settings
        $validated['twitter_username'] = sanitize_text_field($settings['twitter_username'] ?? '');
        $validated['facebook_app_id'] = sanitize_text_field($settings['facebook_app_id'] ?? '');
        
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
        
        // URL settings
        $validated['fallback_image'] = esc_url_raw($settings['fallback_image'] ?? '');
        
        return $validated;
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
            default:
                return (string) $value;
        }
    }
}