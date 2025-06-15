<?php
/**
 * Admin Settings Class
 * 
 * Modern admin interface with better UX and organization
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWOG_Admin {
    
    private static $instance = null;
    private $settings;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('plugin_action_links_' . plugin_basename(EWOG_PLUGIN_FILE), array($this, 'add_action_links'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Enhanced Open Graph', EWOG_TEXT_DOMAIN),
            __('Open Graph', EWOG_TEXT_DOMAIN),
            'manage_woocommerce',
            'enhanced-woo-open-graph',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ewog_settings_group', 'ewog_settings', array($this, 'sanitize_settings'));
        
        // General Settings Section
        add_settings_section(
            'ewog_general_section',
            __('General Settings', EWOG_TEXT_DOMAIN),
            array($this, 'general_section_callback'),
            'ewog_settings'
        );
        
        // Social Platforms Section
        add_settings_section(
            'ewog_platforms_section',
            __('Social Platforms', EWOG_TEXT_DOMAIN),
            array($this, 'platforms_section_callback'),
            'ewog_settings'
        );
        
        // Advanced Settings Section
        add_settings_section(
            'ewog_advanced_section',
            __('Advanced Settings', EWOG_TEXT_DOMAIN),
            array($this, 'advanced_section_callback'),
            'ewog_settings'
        );
        
        // Social Sharing Section
        add_settings_section(
            'ewog_sharing_section',
            __('Social Sharing', EWOG_TEXT_DOMAIN),
            array($this, 'sharing_section_callback'),
            'ewog_settings'
        );
        
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // General Settings
        add_settings_field(
            'disable_title_description',
            __('Override Other Plugins', EWOG_TEXT_DOMAIN),
            array($this, 'checkbox_field'),
            'ewog_settings',
            'ewog_general_section',
            array(
                'id' => 'disable_title_description',
                'description' => __('Disable title and description from other SEO plugins (Yoast, RankMath, etc.)', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'image_size',
            __('Image Size', EWOG_TEXT_DOMAIN),
            array($this, 'select_field'),
            'ewog_settings',
            'ewog_general_section',
            array(
                'id' => 'image_size',
                'options' => array(
                    'thumbnail' => __('Thumbnail (150x150)', EWOG_TEXT_DOMAIN),
                    'medium' => __('Medium (300x300)', EWOG_TEXT_DOMAIN),
                    'large' => __('Large (1024x1024)', EWOG_TEXT_DOMAIN),
                    'full' => __('Full Size', EWOG_TEXT_DOMAIN)
                ),
                'description' => __('Select the image size for Open Graph images', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'fallback_image',
            __('Fallback Image', EWOG_TEXT_DOMAIN),
            array($this, 'image_field'),
            'ewog_settings',
            'ewog_general_section',
            array(
                'id' => 'fallback_image',
                'description' => __('Default image when product has no featured image', EWOG_TEXT_DOMAIN)
            )
        );
        
        // Social Platforms
        $platforms = array(
            'facebook' => array(
                'label' => __('Facebook', EWOG_TEXT_DOMAIN),
                'description' => __('Enable Facebook Open Graph tags', EWOG_TEXT_DOMAIN)
            ),
            'twitter' => array(
                'label' => __('Twitter', EWOG_TEXT_DOMAIN),
                'description' => __('Enable Twitter Card tags', EWOG_TEXT_DOMAIN)
            ),
            'linkedin' => array(
                'label' => __('LinkedIn', EWOG_TEXT_DOMAIN),
                'description' => __('Enable LinkedIn specific tags', EWOG_TEXT_DOMAIN)
            ),
            'pinterest' => array(
                'label' => __('Pinterest', EWOG_TEXT_DOMAIN),
                'description' => __('Enable Pinterest Rich Pins', EWOG_TEXT_DOMAIN)
            ),
            'whatsapp' => array(
                'label' => __('WhatsApp', EWOG_TEXT_DOMAIN),
                'description' => __('Enable WhatsApp sharing optimization', EWOG_TEXT_DOMAIN)
            )
        );
        
        foreach ($platforms as $platform => $data) {
            add_settings_field(
                'enable_' . $platform,
                $data['label'],
                array($this, 'checkbox_field'),
                'ewog_settings',
                'ewog_platforms_section',
                array(
                    'id' => 'enable_' . $platform,
                    'description' => $data['description']
                )
            );
        }
        
        // Platform specific settings
        add_settings_field(
            'facebook_app_id',
            __('Facebook App ID', EWOG_TEXT_DOMAIN),
            array($this, 'text_field'),
            'ewog_settings',
            'ewog_platforms_section',
            array(
                'id' => 'facebook_app_id',
                'description' => __('Your Facebook App ID for better analytics', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'twitter_username',
            __('Twitter Username', EWOG_TEXT_DOMAIN),
            array($this, 'text_field'),
            'ewog_settings',
            'ewog_platforms_section',
            array(
                'id' => 'twitter_username',
                'description' => __('Twitter username (without @)', EWOG_TEXT_DOMAIN)
            )
        );
        
        // Advanced Settings
        add_settings_field(
            'enable_schema',
            __('Enable Schema Markup', EWOG_TEXT_DOMAIN),
            array($this, 'checkbox_field'),
            'ewog_settings',
            'ewog_advanced_section',
            array(
                'id' => 'enable_schema',
                'description' => __('Add structured data for better SEO', EWOG_TEXT_DOMAIN)
            )
        );
        
        // Social Sharing Settings
        add_settings_field(
            'enable_social_share',
            __('Enable Social Share Buttons', EWOG_TEXT_DOMAIN),
            array($this, 'checkbox_field'),
            'ewog_settings',
            'ewog_sharing_section',
            array(
                'id' => 'enable_social_share',
                'description' => __('Add social sharing buttons to product pages', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'share_button_style',
            __('Button Style', EWOG_TEXT_DOMAIN),
            array($this, 'select_field'),
            'ewog_settings',
            'ewog_sharing_section',
            array(
                'id' => 'share_button_style',
                'options' => array(
                    'modern' => __('Modern', EWOG_TEXT_DOMAIN),
                    'classic' => __('Classic', EWOG_TEXT_DOMAIN),
                    'minimal' => __('Minimal', EWOG_TEXT_DOMAIN)
                ),
                'description' => __('Choose the style for share buttons', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'share_button_position',
            __('Button Position', EWOG_TEXT_DOMAIN),
            array($this, 'select_field'),
            'ewog_settings',
            'ewog_sharing_section',
            array(
                'id' => 'share_button_position',
                'options' => array(
                    'after_add_to_cart' => __('After Add to Cart Button', EWOG_TEXT_DOMAIN),
                    'before_add_to_cart' => __('Before Add to Cart Button', EWOG_TEXT_DOMAIN),
                    'after_summary' => __('After Product Summary', EWOG_TEXT_DOMAIN),
                    'after_tabs' => __('After Product Tabs', EWOG_TEXT_DOMAIN)
                ),
                'description' => __('Where to display the share buttons', EWOG_TEXT_DOMAIN)
            )
        );
    }
    
    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general Open Graph settings for your WooCommerce store.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function platforms_section_callback() {
        echo '<p>' . __('Enable or disable Open Graph tags for specific social media platforms.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced settings for power users and developers.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function sharing_section_callback() {
        echo '<p>' . __('Configure social sharing buttons for your products.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    /**
     * Field rendering methods
     */
    public function checkbox_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : false;
        
        echo '<label>';
        echo '<input type="checkbox" name="ewog_settings[' . esc_attr($args['id']) . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html($args['description']);
        echo '</label>';
    }
    
    public function text_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        
        echo '<input type="text" name="ewog_settings[' . esc_attr($args['id']) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function select_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        
        echo '<select name="ewog_settings[' . esc_attr($args['id']) . ']">';
        foreach ($args['options'] as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>';
            echo esc_html($option_label);
            echo '</option>';
        }
        echo '</select>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function image_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        
        echo '<div class="ewog-image-field">';
        echo '<input type="text" name="ewog_settings[' . esc_attr($args['id']) . ']" value="' . esc_attr($value) . '" class="regular-text ewog-image-url" />';
        echo '<button type="button" class="button ewog-upload-image">' . __('Choose Image', EWOG_TEXT_DOMAIN) . '</button>';
        
        if ($value) {
            echo '<div class="ewog-image-preview">';
            echo '<img src="' . esc_url($value) . '" style="max-width: 200px; height: auto; margin-top: 10px;" />';
            echo '<br><button type="button" class="button ewog-remove-image">' . __('Remove Image', EWOG_TEXT_DOMAIN) . '</button>';
            echo '</div>';
        }
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
        echo '</div>';
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap ewog-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ewog-admin-header">
                <p><?php _e('Optimize your WooCommerce store for social media sharing with enhanced Open Graph meta tags.', EWOG_TEXT_DOMAIN); ?></p>
            </div>
            
            <div class="ewog-admin-content">
                <form action="options.php" method="post">
                    <?php
                    settings_fields('ewog_settings_group');
                    do_settings_sections('ewog_settings');
                    submit_button(__('Save Settings', EWOG_TEXT_DOMAIN));
                    ?>
                </form>
                
                <div class="ewog-sidebar">
                    <div class="ewog-sidebar-box">
                        <h3><?php _e('Quick Test', EWOG_TEXT_DOMAIN); ?></h3>
                        <p><?php _e('Test your Open Graph tags with these tools:', EWOG_TEXT_DOMAIN); ?></p>
                        <ul>
                            <li><a href="https://developers.facebook.com/tools/debug/" target="_blank"><?php _e('Facebook Debugger', EWOG_TEXT_DOMAIN); ?></a></li>
                            <li><a href="https://cards-dev.twitter.com/validator" target="_blank"><?php _e('Twitter Card Validator', EWOG_TEXT_DOMAIN); ?></a></li>
                            <li><a href="https://www.linkedin.com/post-inspector/" target="_blank"><?php _e('LinkedIn Post Inspector', EWOG_TEXT_DOMAIN); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="ewog-sidebar-box">
                        <h3><?php _e('Need Help?', EWOG_TEXT_DOMAIN); ?></h3>
                        <p><?php _e('Check out our documentation and support resources.', EWOG_TEXT_DOMAIN); ?></p>
                        <a href="#" class="button button-secondary"><?php _e('View Documentation', EWOG_TEXT_DOMAIN); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('woocommerce_page_enhanced-woo-open-graph' !== $hook) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script(
            'ewog-admin',
            EWOG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-media-utils'),
            EWOG_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ewog-admin',
            EWOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EWOG_VERSION
        );
        
        wp_localize_script('ewog-admin', 'ewogAdmin', array(
            'chooseImage' => __('Choose Image', EWOG_TEXT_DOMAIN),
            'useImage' => __('Use this Image', EWOG_TEXT_DOMAIN)
        ));
    }
    
    /**
     * Add action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=enhanced-woo-open-graph') . '">' . __('Settings', EWOG_TEXT_DOMAIN) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Checkboxes
        $checkboxes = array(
            'disable_title_description', 'enable_facebook', 'enable_twitter', 
            'enable_linkedin', 'enable_pinterest', 'enable_whatsapp', 
            'enable_schema', 'enable_social_share'
        );
        
        foreach ($checkboxes as $checkbox) {
            $sanitized[$checkbox] = isset($input[$checkbox]) ? true : false;
        }
        
        // Text fields
        $sanitized['facebook_app_id'] = sanitize_text_field($input['facebook_app_id'] ?? '');
        $sanitized['twitter_username'] = sanitize_text_field($input['twitter_username'] ?? '');
        
        // Select fields
        $sanitized['image_size'] = sanitize_text_field($input['image_size'] ?? 'large');
        $sanitized['share_button_style'] = sanitize_text_field($input['share_button_style'] ?? 'modern');
        $sanitized['share_button_position'] = sanitize_text_field($input['share_button_position'] ?? 'after_add_to_cart');
        
        // URL fields
        $sanitized['fallback_image'] = esc_url_raw($input['fallback_image'] ?? '');
        
        return $sanitized;
    }
}