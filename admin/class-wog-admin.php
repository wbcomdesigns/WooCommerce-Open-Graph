<?php
/**
 * Admin Interface Class
 * Handles the WordPress admin interface for plugin settings
 * 
 * @package Woo_Open_Graph
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WOG_Admin {
    
    private static $instance = null;
    
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
     * Initialize admin interface
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Set up WordPress admin hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(WOG_PLUGIN_FILE), array($this, 'add_action_links'));
        
        add_action('wp_ajax_wog_generate_sitemap', array($this, 'ajax_generate_sitemap'));
        add_action('wp_ajax_wog_test_sitemap', array($this, 'ajax_test_sitemap'));
    }
    
    /**
     * Add admin menu under WooCommerce
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Woo Open Graph', 'woo-open-graph'),
            __('Social Media', 'woo-open-graph'),
            'manage_woocommerce',
            'woo-open-graph',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register plugin settings and fields
     */
    public function register_settings() {
        register_setting('wog_settings_group', 'wog_settings', array($this, 'sanitize_settings'));
        
        // Core Features Section
        add_settings_section(
            'wog_core_section',
            __('Core Features', 'woo-open-graph'),
            array($this, 'core_section_callback'),
            'wog_settings'
        );
        
        $core_fields = array(
            'enable_schema' => __('Add Schema.org structured data to product pages for better search results', 'woo-open-graph'),
            'enable_facebook' => __('Generate Facebook Open Graph meta tags for better sharing', 'woo-open-graph'),
            'enable_twitter' => __('Generate Twitter Card meta tags for better sharing', 'woo-open-graph'),
            'enable_linkedin' => __('Optimize sharing for LinkedIn professional network', 'woo-open-graph'),
            'enable_pinterest' => __('Enable Pinterest Rich Pins with product data', 'woo-open-graph'),
            'enable_whatsapp' => __('Optimize sharing for WhatsApp mobile messaging', 'woo-open-graph')
        );
        
        foreach ($core_fields as $id => $description) {
            $title = ucwords(str_replace(array('enable_', '_'), array('', ' '), $id));
            add_settings_field($id, $title, array($this, 'checkbox_field'), 'wog_settings', 'wog_core_section', 
                array('id' => $id, 'description' => $description));
        }
        
        // Social Sharing Section
        add_settings_section(
            'wog_sharing_section',
            __('Social Sharing Buttons', 'woo-open-graph'),
            array($this, 'sharing_section_callback'),
            'wog_settings'
        );
        
        add_settings_field('enable_social_share', __('Enable Share Buttons', 'woo-open-graph'), 
            array($this, 'checkbox_field'), 'wog_settings', 'wog_sharing_section',
            array('id' => 'enable_social_share', 'description' => __('Add social share buttons to product pages', 'woo-open-graph')));
        
        add_settings_field('share_button_style', __('Button Style', 'woo-open-graph'), 
            array($this, 'select_field'), 'wog_settings', 'wog_sharing_section',
            array(
                'id' => 'share_button_style',
                'options' => array(
                    'modern' => __('Modern', 'woo-open-graph'),
                    'classic' => __('Classic', 'woo-open-graph'),
                    'minimal' => __('Minimal', 'woo-open-graph')
                ),
                'description' => __('Visual style for share buttons', 'woo-open-graph')
            ));
        
        add_settings_field('share_button_position', __('Button Position', 'woo-open-graph'), 
            array($this, 'select_field'), 'wog_settings', 'wog_sharing_section',
            array(
                'id' => 'share_button_position',
                'options' => array(
                    'after_add_to_cart' => __('After Add to Cart Button', 'woo-open-graph'),
                    'before_add_to_cart' => __('Before Add to Cart Button', 'woo-open-graph'),
                    'after_summary' => __('After Product Summary', 'woo-open-graph'),
                    'after_tabs' => __('After Product Tabs', 'woo-open-graph')
                ),
                'description' => __('Where to display social share buttons on product pages', 'woo-open-graph')
            ));
        
        // Sitemaps Section
        add_settings_section(
            'wog_sitemap_section',
            __('XML Sitemaps', 'woo-open-graph'),
            array($this, 'sitemap_section_callback'),
            'wog_settings'
        );
        
        add_settings_field('enable_product_sitemap', __('Enable Product Sitemaps', 'woo-open-graph'), 
            array($this, 'sitemap_field'), 'wog_settings', 'wog_sitemap_section',
            array('id' => 'enable_product_sitemap', 'description' => __('Generate XML sitemaps for products and categories', 'woo-open-graph')));
        
        add_settings_field('sitemap_products_per_page', __('Products Per Sitemap', 'woo-open-graph'), 
            array($this, 'number_field'), 'wog_settings', 'wog_sitemap_section',
            array(
                'id' => 'sitemap_products_per_page',
                'min' => 100, 'max' => 1000, 'default' => 500,
                'description' => __('Number of products per sitemap file (recommended: 500)', 'woo-open-graph')
            ));
        
        // Platform Settings Section
        add_settings_section(
            'wog_platform_section',
            __('Platform Settings', 'woo-open-graph'),
            array($this, 'platform_section_callback'),
            'wog_settings'
        );
        
        add_settings_field('facebook_app_id', __('Facebook App ID', 'woo-open-graph'), 
            array($this, 'text_field'), 'wog_settings', 'wog_platform_section',
            array(
                'id' => 'facebook_app_id',
                'placeholder' => '123456789012345',
                'description' => __('Optional: Your Facebook App ID for better analytics and insights', 'woo-open-graph')
            ));
        
        add_settings_field('twitter_username', __('Twitter Username', 'woo-open-graph'), 
            array($this, 'text_field'), 'wog_settings', 'wog_platform_section',
            array(
                'id' => 'twitter_username',
                'placeholder' => 'yourstore',
                'description' => __('Optional: Your Twitter username (without @) for attribution', 'woo-open-graph')
            ));
        
        // Image & Content Settings Section
        add_settings_section(
            'wog_content_section',
            __('Image & Content Settings', 'woo-open-graph'),
            array($this, 'content_section_callback'),
            'wog_settings'
        );
        
        add_settings_field('fallback_image', __('Default Social Image', 'woo-open-graph'), 
            array($this, 'image_field'), 'wog_settings', 'wog_content_section',
            array(
                'id' => 'fallback_image',
                'description' => __('Default image when products don\'t have featured images (recommended: 1200x630px)', 'woo-open-graph')
            ));
        
        add_settings_field('image_size', __('Social Image Size', 'woo-open-graph'), 
            array($this, 'select_field'), 'wog_settings', 'wog_content_section',
            array(
                'id' => 'image_size',
                'options' => array(
                    'medium' => __('Medium (300x300)', 'woo-open-graph'),
                    'large' => __('Large (1024x1024)', 'woo-open-graph'),
                    'full' => __('Full Size', 'woo-open-graph')
                ),
                'description' => __('Size of product images used for social sharing', 'woo-open-graph')
            ));
        
        // Advanced Settings Section
        add_settings_section(
            'wog_advanced_section',
            __('Advanced Settings', 'woo-open-graph'),
            array($this, 'advanced_section_callback'),
            'wog_settings'
        );
        
        $advanced_fields = array(
            'disable_title_description' => __('Override titles and descriptions from other SEO plugins (use with caution)', 'woo-open-graph'),
            'enable_enhanced_schema' => __('Include advanced product properties (GTIN, MPN, brand, specifications)', 'woo-open-graph'),
            'enable_breadcrumb_schema' => __('Add breadcrumb navigation schema markup', 'woo-open-graph'),
            'enable_organization_schema' => __('Add organization and store information schema', 'woo-open-graph'),
            'debug_mode' => __('Enable debug mode for troubleshooting (adds HTML comments)', 'woo-open-graph')
        );
        
        foreach ($advanced_fields as $id => $description) {
            $title = ucwords(str_replace(array('enable_', 'disable_', '_'), array('', '', ' '), $id));
            add_settings_field($id, $title, array($this, 'checkbox_field'), 'wog_settings', 'wog_advanced_section', 
                array('id' => $id, 'description' => $description));
        }
    }
    
    /**
     * Render the main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap wog-admin-wrap">
            <div class="wog-admin-header">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <p class="description"><?php esc_html_e('Configure how your WooCommerce products appear when shared on social media platforms. This plugin works alongside your existing SEO plugin to fill any gaps.', 'woo-open-graph'); ?></p>
            </div>
            
            <div class="wog-admin-layout">
                <div class="wog-main-content">
                    <form method="post" action="options.php" class="wog-settings-form">
                        <?php
                        settings_fields('wog_settings_group');
                        do_settings_sections('wog_settings');
                        submit_button(__('Save Settings', 'woo-open-graph'), 'primary', 'submit', true, array('class' => 'button-primary button-large'));
                        ?>
                    </form>
                </div>
                
                <div class="wog-sidebar">
                    <div class="wog-sidebar-box">
                        <div class="wog-card-header">
                            <h3><span class="dashicons dashicons-dashboard"></span> <?php esc_html_e('Current Status', 'woo-open-graph'); ?></h3>
                        </div>
                        <div class="wog-card-body">
                            <?php $this->display_quick_status(); ?>
                        </div>
                    </div>
                    
                    <div class="wog-sidebar-box">
                        <div class="wog-card-header">
                            <h3><span class="dashicons dashicons-external"></span> <?php esc_html_e('Testing Tools', 'woo-open-graph'); ?></h3>
                        </div>
                        <div class="wog-card-body">
                            <p><?php esc_html_e('Test how your products look when shared:', 'woo-open-graph'); ?></p>
                            <a href="https://developers.facebook.com/tools/debug/" target="_blank" class="wog-btn-block">
                                <span class="dashicons dashicons-facebook"></span>
                                <?php esc_html_e('Facebook Debugger', 'woo-open-graph'); ?>
                            </a>
                            <a href="https://cards-dev.twitter.com/validator" target="_blank" class="wog-btn-block">
                                <span class="dashicons dashicons-twitter"></span>
                                <?php esc_html_e('Twitter Validator', 'woo-open-graph'); ?>
                            </a>
                            <a href="https://search.google.com/test/rich-results" target="_blank" class="wog-btn-block">
                                <span class="dashicons dashicons-google"></span>
                                <?php esc_html_e('Google Rich Results', 'woo-open-graph'); ?>
                            </a>
                            <a href="https://www.linkedin.com/post-inspector/" target="_blank" class="wog-btn-block">
                                <span class="dashicons dashicons-linkedin"></span>
                                <?php esc_html_e('LinkedIn Inspector', 'woo-open-graph'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="wog-sidebar-box">
                        <div class="wog-card-header">
                            <h3><span class="dashicons dashicons-info"></span> <?php esc_html_e('Plugin Information', 'woo-open-graph'); ?></h3>
                        </div>
                        <div class="wog-card-body">
                            <p><strong><?php esc_html_e('Version:', 'woo-open-graph'); ?></strong> <?php echo WOG_VERSION; ?></p>
                            <p><strong><?php esc_html_e('Compatible with:', 'woo-open-graph'); ?></strong> Yoast, RankMath, SEOPress</p>
                            <p><strong><?php esc_html_e('Auto-generates:', 'woo-open-graph'); ?></strong> <?php esc_html_e('Schema, Open Graph, Twitter Cards', 'woo-open-graph'); ?></p>
                            
                            <hr>
                            
                            <h4><?php esc_html_e('Important Notes', 'woo-open-graph'); ?></h4>
                            <ul>
                                <li><?php esc_html_e('Uses product featured images automatically', 'woo-open-graph'); ?></li>
                                <li><?php esc_html_e('Individual products can have custom titles/descriptions', 'woo-open-graph'); ?></li>
                                <li><?php esc_html_e('Works alongside existing SEO plugins', 'woo-open-graph'); ?></li>
                                <li><?php esc_html_e('Uses WordPress built-in caching for performance', 'woo-open-graph'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Sitemap generation functions
        function wogGenerateSitemap(button) {
            button.disabled = true;
            button.textContent = '<?php esc_html_e('Generating...', 'woo-open-graph'); ?>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=wog_generate_sitemap&nonce=<?php echo wp_create_nonce('wog_admin_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                const results = document.getElementById('wog-sitemap-results');
                if (data.success) {
                    results.innerHTML = '<div class="notice notice-success"><p>' + data.data.message + '</p></div>';
                } else {
                    results.innerHTML = '<div class="notice notice-error"><p>' + (data.data ? data.data.message : 'Error occurred') + '</p></div>';
                }
            })
            .catch(error => {
                document.getElementById('wog-sitemap-results').innerHTML = '<div class="notice notice-error"><p>Request failed</p></div>';
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = '<?php esc_html_e('Generate Now', 'woo-open-graph'); ?>';
            });
        }
        
        function wogTestSitemap(button) {
            button.disabled = true;
            button.textContent = '<?php esc_html_e('Testing...', 'woo-open-graph'); ?>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=wog_test_sitemap&nonce=<?php echo wp_create_nonce('wog_admin_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                const results = document.getElementById('wog-sitemap-results');
                if (data.success) {
                    results.innerHTML = '<div class="notice notice-success"><p>' + data.data.message + '</p></div>';
                } else {
                    results.innerHTML = '<div class="notice notice-error"><p>' + (data.data ? data.data.message : 'Test failed') + '</p></div>';
                }
            })
            .catch(error => {
                document.getElementById('wog-sitemap-results').innerHTML = '<div class="notice notice-error"><p>Test failed</p></div>';
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = '<?php esc_html_e('Test Sitemaps', 'woo-open-graph'); ?>';
            });
        }
        
        // Image selector function
        function wogSelectImage(button) {
            if (typeof wp === "undefined" || !wp.media) {
                alert("WordPress media library not available");
                return;
            }
            
            var mediaUploader = wp.media({
                title: "Choose Image",
                button: { text: "Use This Image" },
                multiple: false,
                library: { type: "image" }
            });
            
            mediaUploader.on("select", function() {
                var attachment = mediaUploader.state().get("selection").first().toJSON();
                var input = button.previousElementSibling;
                input.value = attachment.url;
                
                var existingPreview = button.parentNode.querySelector(".wog-image-preview");
                if (existingPreview) {
                    existingPreview.src = attachment.url;
                } else {
                    var img = document.createElement("img");
                    img.src = attachment.url;
                    img.className = "wog-image-preview";
                    img.style.maxWidth = "80px";
                    img.style.height = "auto";
                    img.style.border = "1px solid #ddd";
                    img.style.borderRadius = "3px";
                    img.style.marginTop = "10px";
                    button.parentNode.appendChild(img);
                }
            });
            
            mediaUploader.open();
        }
        </script>
        <?php
    }
    
    /**
     * Section callback functions
     */
    public function core_section_callback() {
        echo '<p>' . __('Enable the core social media optimization features for your WooCommerce products.', 'woo-open-graph') . '</p>';
    }
    
    public function sharing_section_callback() {
        echo '<p>' . __('Configure social sharing buttons that appear on your product pages.', 'woo-open-graph') . '</p>';
    }
    
    public function sitemap_section_callback() {
        echo '<p>' . __('Generate XML sitemaps to help search engines discover your products and categories.', 'woo-open-graph') . '</p>';
    }
    
    public function platform_section_callback() {
        echo '<p>' . __('Optional platform-specific settings for enhanced integration.', 'woo-open-graph') . '</p>';
    }
    
    public function content_section_callback() {
        echo '<p>' . __('Configure how images and content appear when shared on social media.', 'woo-open-graph') . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced options for power users and specific use cases.', 'woo-open-graph') . '</p>';
    }
    
    /**
     * Field rendering methods
     */
    public function checkbox_field($args) {
        $settings = get_option('wog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : false;
        
        echo '<label>';
        echo '<input type="checkbox" name="wog_settings[' . esc_attr($args['id']) . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html($args['description']);
        echo '</label>';
    }
    
    public function text_field($args) {
        $settings = get_option('wog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        
        echo '<input type="text" ';
        echo 'name="wog_settings[' . esc_attr($args['id']) . ']" ';
        echo 'value="' . esc_attr($value) . '" ';
        echo 'placeholder="' . esc_attr($placeholder) . '" ';
        echo 'class="regular-text" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function select_field($args) {
        $settings = get_option('wog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        
        echo '<select name="wog_settings[' . esc_attr($args['id']) . ']">';
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
    
    public function number_field($args) {
        $settings = get_option('wog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : ($args['default'] ?? '');
        $min = isset($args['min']) ? 'min="' . esc_attr($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . esc_attr($args['max']) . '"' : '';
        
        echo '<input type="number" ';
        echo 'name="wog_settings[' . esc_attr($args['id']) . ']" ';
        echo 'value="' . esc_attr($value) . '" ';
        echo $min . ' ' . $max . ' ';
        echo 'class="small-text" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function image_field($args) {
        $settings = get_option('wog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        
        echo '<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">';
        echo '<input type="url" ';
        echo 'name="wog_settings[' . esc_attr($args['id']) . ']" ';
        echo 'value="' . esc_attr($value) . '" ';
        echo 'placeholder="https://example.com/image.jpg" ';
        echo 'class="regular-text" />';
        
        echo '<button type="button" class="button" onclick="wogSelectImage(this)">' . __('Choose Image', 'woo-open-graph') . '</button>';
        
        if ($value) {
            echo '<img src="' . esc_url($value) . '" class="wog-image-preview" style="max-width: 80px; height: auto; border: 1px solid #ddd; border-radius: 3px;" />';
        }
        echo '</div>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function sitemap_field($args) {
        $settings = get_option('wog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : false;
        $last_generated = get_option('wog_sitemap_last_generated', 0);
        
        echo '<label>';
        echo '<input type="checkbox" name="wog_settings[' . esc_attr($args['id']) . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html($args['description']);
        echo '</label>';
        
        if ($value) {
            echo '<div style="margin-top: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #00a32a; border-radius: 0 3px 3px 0;">';
            
            if ($last_generated) {
                echo '<p><strong>' . __('Last Generated:', 'woo-open-graph') . '</strong> ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_generated) . '</p>';
            }
            
            echo '<p><strong>' . __('Sitemap URLs:', 'woo-open-graph') . '</strong></p>';
            echo '<ul>';
            echo '<li><a href="' . home_url('/wog-sitemap.xml') . '" target="_blank">' . home_url('/wog-sitemap.xml') . '</a> (Main Index)</li>';
            echo '<li><a href="' . home_url('/product-sitemap-1.xml') . '" target="_blank">' . home_url('/product-sitemap-1.xml') . '</a> (Products)</li>';
            echo '<li><a href="' . home_url('/product-category-sitemap.xml') . '" target="_blank">' . home_url('/product-category-sitemap.xml') . '</a> (Categories)</li>';
            echo '</ul>';
            
            echo '<p>';
            echo '<button type="button" class="button" onclick="wogGenerateSitemap(this)">' . __('Generate Now', 'woo-open-graph') . '</button> ';
            echo '<button type="button" class="button" onclick="wogTestSitemap(this)">' . __('Test Sitemaps', 'woo-open-graph') . '</button>';
            echo '</p>';
            
            echo '<div id="wog-sitemap-results"></div>';
            echo '</div>';
        }
    }
    
    /**
     * Display quick status overview
     */
    private function display_quick_status() {
        $settings = get_option('wog_settings', array());
        
        // Core Features
        echo '<div style="margin-bottom: 20px;">';
        echo '<h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #666;">' . __('Core Features', 'woo-open-graph') . '</h4>';
        $core_features = array(
            'enable_schema' => __('Schema Markup', 'woo-open-graph'),
            'enable_facebook' => __('Facebook', 'woo-open-graph'),
            'enable_twitter' => __('Twitter', 'woo-open-graph'),
        );
        
        foreach ($core_features as $key => $label) {
            $enabled = !empty($settings[$key]);
            $this->render_status_item($label, $enabled);
        }
        echo '</div>';
        
        // Additional Platforms
        echo '<div style="margin-bottom: 20px;">';
        echo '<h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #666;">' . __('Additional Platforms', 'woo-open-graph') . '</h4>';
        $additional_platforms = array(
            'enable_linkedin' => __('LinkedIn', 'woo-open-graph'),
            'enable_pinterest' => __('Pinterest', 'woo-open-graph'),
            'enable_whatsapp' => __('WhatsApp', 'woo-open-graph'),
        );
        
        foreach ($additional_platforms as $key => $label) {
            $enabled = !empty($settings[$key]);
            $this->render_status_item($label, $enabled);
        }
        echo '</div>';
        
        // Tools
        echo '<div>';
        echo '<h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #666;">' . __('Tools', 'woo-open-graph') . '</h4>';
        $tools = array(
            'enable_social_share' => __('Share Buttons', 'woo-open-graph'),
            'enable_product_sitemap' => __('XML Sitemaps', 'woo-open-graph'),
        );
        
        foreach ($tools as $key => $label) {
            $enabled = !empty($settings[$key]);
            $this->render_status_item($label, $enabled);
        }
        echo '</div>';
        
        // Performance note
        echo '<div style="margin-top: 20px; padding: 10px; background: #f0f8f0; border-left: 4px solid #00a32a; border-radius: 0 3px 3px 0;">';
        echo '<p style="margin: 0; font-size: 12px; color: #1e4620;">';
        echo '<strong>' . __('Performance:', 'woo-open-graph') . '</strong> ';
        echo __('This plugin uses WordPress built-in caching and works with all cache plugins automatically.', 'woo-open-graph');
        echo '</p>';
        echo '</div>';
    }
    
    /**
     * Render individual status item
     */
    private function render_status_item($label, $enabled) {
        $status_class = $enabled ? 'enabled' : 'disabled';
        $status_text = $enabled ? __('On', 'woo-open-graph') : __('Off', 'woo-open-graph');
        
        echo '<div class="wog-status-item">';
        echo '<span class="wog-status-icon ' . $status_class . '"></span>';
        echo '<span class="wog-status-label">' . esc_html($label) . '</span>';
        echo '<span class="wog-status-text">' . esc_html($status_text) . '</span>';
        echo '</div>';
    }
    
    /**
     * Add settings link to plugin actions
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=woo-open-graph') . '">' . __('Settings', 'woo-open-graph') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_woo-open-graph' !== $hook) {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_style(
            'wog-admin',
            WOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WOG_VERSION
        );
    }
    
    /**
     * AJAX handler for sitemap generation
     */
    public function ajax_generate_sitemap() {
        check_ajax_referer('wog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'woo-open-graph')));
        }
        
        if (class_exists('WOG_Sitemap')) {
            $sitemap = WOG_Sitemap::get_instance();
            $sitemap->generate_all_sitemaps_background();
            update_option('wog_sitemap_last_generated', time());
            
            wp_send_json_success(array(
                'message' => __('Sitemaps generated successfully!', 'woo-open-graph')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Sitemap generation not available', 'woo-open-graph')
            ));
        }
    }
    
    /**
     * AJAX handler for sitemap testing
     */
    public function ajax_test_sitemap() {
        check_ajax_referer('wog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'woo-open-graph')));
        }
        
        $sitemap_url = home_url('/wog-sitemap.xml');
        $response = wp_remote_get($sitemap_url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => __('Failed to fetch sitemap: ', 'woo-open-graph') . $response->get_error_message()
            ));
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code !== 200) {
            wp_send_json_error(array(
                'message' => sprintf(__('Sitemap returned HTTP %d', 'woo-open-graph'), $code)
            ));
        }
        
        if (strpos($body, '<sitemapindex') === false && strpos($body, '<urlset') === false) {
            wp_send_json_error(array(
                'message' => __('Invalid sitemap format', 'woo-open-graph')
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Sitemaps are working correctly!', 'woo-open-graph')
        ));
    }
    
    /**
     * Sanitize and validate settings input
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Boolean settings
        $booleans = array(
            'enable_schema', 'enable_facebook', 'enable_twitter', 'enable_linkedin',
            'enable_pinterest', 'enable_whatsapp', 'enable_social_share', 
            'enable_product_sitemap', 'disable_title_description', 'enable_enhanced_schema',
            'enable_breadcrumb_schema', 'enable_organization_schema', 'debug_mode'
        );
        
        foreach ($booleans as $key) {
            $sanitized[$key] = !empty($input[$key]);
        }
        
        // Text fields
        $sanitized['facebook_app_id'] = sanitize_text_field($input['facebook_app_id'] ?? '');
        $sanitized['twitter_username'] = sanitize_text_field($input['twitter_username'] ?? '');
        
        // Remove @ from Twitter username if present
        if (!empty($sanitized['twitter_username']) && $sanitized['twitter_username'][0] === '@') {
            $sanitized['twitter_username'] = substr($sanitized['twitter_username'], 1);
        }
        
        // Number fields
        $sanitized['sitemap_products_per_page'] = intval($input['sitemap_products_per_page'] ?? 500);
        if ($sanitized['sitemap_products_per_page'] < 100) {
            $sanitized['sitemap_products_per_page'] = 100;
        }
        if ($sanitized['sitemap_products_per_page'] > 1000) {
            $sanitized['sitemap_products_per_page'] = 1000;
        }
        
        // Select fields
        $valid_positions = array('after_add_to_cart', 'before_add_to_cart', 'after_summary', 'after_tabs');
        $sanitized['share_button_position'] = in_array($input['share_button_position'] ?? '', $valid_positions) ? 
            $input['share_button_position'] : 'after_add_to_cart';
            
        $valid_styles = array('modern', 'classic', 'minimal');
        $sanitized['share_button_style'] = in_array($input['share_button_style'] ?? '', $valid_styles) ? 
            $input['share_button_style'] : 'modern';
            
        $valid_sizes = array('medium', 'large', 'full');
        $sanitized['image_size'] = in_array($input['image_size'] ?? '', $valid_sizes) ? 
            $input['image_size'] : 'large';
        
        // URL field
        $sanitized['fallback_image'] = esc_url_raw($input['fallback_image'] ?? '');
        
        // Keep other existing settings that might not be in the form
        $existing = get_option('wog_settings', array());
        $sanitized = array_merge($existing, $sanitized);
        
        return $sanitized;
    }
}