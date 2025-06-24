<?php
/**
 * Enhanced Woo Open Graph Admin Class
 * Clean WordPress-native admin interface
 * 
 * @package Enhanced_Woo_Open_Graph
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWOG_Admin {
    
    private static $instance = null;
    
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(EWOG_PLUGIN_FILE), array($this, 'add_action_links'));
        
        // AJAX handlers
        add_action('wp_ajax_ewog_generate_sitemap', array($this, 'ajax_generate_sitemap'));
        add_action('wp_ajax_ewog_test_sitemap', array($this, 'ajax_test_sitemap'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Enhanced Open Graph', EWOG_TEXT_DOMAIN),
            __('Social Media', EWOG_TEXT_DOMAIN),
            'manage_woocommerce',
            'enhanced-woo-open-graph',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('ewog_settings_group', 'ewog_settings', array($this, 'sanitize_settings'));
        
        // Core Features Section
        add_settings_section(
            'ewog_core_section',
            __('Core Features', EWOG_TEXT_DOMAIN),
            array($this, 'core_section_callback'),
            'ewog_settings'
        );
        
        $core_fields = array(
            'enable_schema' => __('Add Schema.org structured data to product pages for better search results', EWOG_TEXT_DOMAIN),
            'enable_facebook' => __('Generate Facebook Open Graph meta tags for better sharing', EWOG_TEXT_DOMAIN),
            'enable_twitter' => __('Generate Twitter Card meta tags for better sharing', EWOG_TEXT_DOMAIN),
            'enable_linkedin' => __('Optimize sharing for LinkedIn professional network', EWOG_TEXT_DOMAIN),
            'enable_pinterest' => __('Enable Pinterest Rich Pins with product data', EWOG_TEXT_DOMAIN),
            'enable_whatsapp' => __('Optimize sharing for WhatsApp mobile messaging', EWOG_TEXT_DOMAIN)
        );
        
        foreach ($core_fields as $id => $description) {
            $title = ucwords(str_replace(array('enable_', '_'), array('', ' '), $id));
            add_settings_field($id, $title, array($this, 'checkbox_field'), 'ewog_settings', 'ewog_core_section', 
                array('id' => $id, 'description' => $description));
        }
        
        // Social Sharing Section
        add_settings_section(
            'ewog_sharing_section',
            __('Social Sharing Buttons', EWOG_TEXT_DOMAIN),
            array($this, 'sharing_section_callback'),
            'ewog_settings'
        );
        
        add_settings_field('enable_social_share', __('Enable Share Buttons', EWOG_TEXT_DOMAIN), 
            array($this, 'checkbox_field'), 'ewog_settings', 'ewog_sharing_section',
            array('id' => 'enable_social_share', 'description' => __('Add social share buttons to product pages', EWOG_TEXT_DOMAIN)));
        
        add_settings_field('share_button_style', __('Button Style', EWOG_TEXT_DOMAIN), 
            array($this, 'select_field'), 'ewog_settings', 'ewog_sharing_section',
            array(
                'id' => 'share_button_style',
                'options' => array(
                    'modern' => __('Modern', EWOG_TEXT_DOMAIN),
                    'classic' => __('Classic', EWOG_TEXT_DOMAIN),
                    'minimal' => __('Minimal', EWOG_TEXT_DOMAIN)
                ),
                'description' => __('Visual style for share buttons', EWOG_TEXT_DOMAIN)
            ));
        
        add_settings_field('share_button_position', __('Button Position', EWOG_TEXT_DOMAIN), 
            array($this, 'select_field'), 'ewog_settings', 'ewog_sharing_section',
            array(
                'id' => 'share_button_position',
                'options' => array(
                    'after_add_to_cart' => __('After Add to Cart Button', EWOG_TEXT_DOMAIN),
                    'before_add_to_cart' => __('Before Add to Cart Button', EWOG_TEXT_DOMAIN),
                    'after_summary' => __('After Product Summary', EWOG_TEXT_DOMAIN),
                    'after_tabs' => __('After Product Tabs', EWOG_TEXT_DOMAIN)
                ),
                'description' => __('Where to display social share buttons on product pages', EWOG_TEXT_DOMAIN)
            ));
        
        // Sitemaps Section
        add_settings_section(
            'ewog_sitemap_section',
            __('XML Sitemaps', EWOG_TEXT_DOMAIN),
            array($this, 'sitemap_section_callback'),
            'ewog_settings'
        );
        
        add_settings_field('enable_product_sitemap', __('Enable Product Sitemaps', EWOG_TEXT_DOMAIN), 
            array($this, 'sitemap_field'), 'ewog_settings', 'ewog_sitemap_section',
            array('id' => 'enable_product_sitemap', 'description' => __('Generate XML sitemaps for products and categories', EWOG_TEXT_DOMAIN)));
        
        add_settings_field('sitemap_products_per_page', __('Products Per Sitemap', EWOG_TEXT_DOMAIN), 
            array($this, 'number_field'), 'ewog_settings', 'ewog_sitemap_section',
            array(
                'id' => 'sitemap_products_per_page',
                'min' => 100, 'max' => 1000, 'default' => 500,
                'description' => __('Number of products per sitemap file (recommended: 500)', EWOG_TEXT_DOMAIN)
            ));
        
        // Platform Settings Section
        add_settings_section(
            'ewog_platform_section',
            __('Platform Settings', EWOG_TEXT_DOMAIN),
            array($this, 'platform_section_callback'),
            'ewog_settings'
        );
        
        add_settings_field('facebook_app_id', __('Facebook App ID', EWOG_TEXT_DOMAIN), 
            array($this, 'text_field'), 'ewog_settings', 'ewog_platform_section',
            array(
                'id' => 'facebook_app_id',
                'placeholder' => '123456789012345',
                'description' => __('Optional: Your Facebook App ID for better analytics and insights', EWOG_TEXT_DOMAIN)
            ));
        
        add_settings_field('twitter_username', __('Twitter Username', EWOG_TEXT_DOMAIN), 
            array($this, 'text_field'), 'ewog_settings', 'ewog_platform_section',
            array(
                'id' => 'twitter_username',
                'placeholder' => 'yourstore',
                'description' => __('Optional: Your Twitter username (without @) for attribution', EWOG_TEXT_DOMAIN)
            ));
        
        // Image & Content Settings Section
        add_settings_section(
            'ewog_content_section',
            __('Image & Content Settings', EWOG_TEXT_DOMAIN),
            array($this, 'content_section_callback'),
            'ewog_settings'
        );
        
        add_settings_field('fallback_image', __('Default Social Image', EWOG_TEXT_DOMAIN), 
            array($this, 'image_field'), 'ewog_settings', 'ewog_content_section',
            array(
                'id' => 'fallback_image',
                'description' => __('Default image when products don\'t have featured images (recommended: 1200x630px)', EWOG_TEXT_DOMAIN)
            ));
        
        add_settings_field('image_size', __('Social Image Size', EWOG_TEXT_DOMAIN), 
            array($this, 'select_field'), 'ewog_settings', 'ewog_content_section',
            array(
                'id' => 'image_size',
                'options' => array(
                    'medium' => __('Medium (300x300)', EWOG_TEXT_DOMAIN),
                    'large' => __('Large (1024x1024)', EWOG_TEXT_DOMAIN),
                    'full' => __('Full Size', EWOG_TEXT_DOMAIN)
                ),
                'description' => __('Size of product images used for social sharing', EWOG_TEXT_DOMAIN)
            ));
        
        // Advanced Settings Section
        add_settings_section(
            'ewog_advanced_section',
            __('Advanced Settings', EWOG_TEXT_DOMAIN),
            array($this, 'advanced_section_callback'),
            'ewog_settings'
        );
        
        $advanced_fields = array(
            'disable_title_description' => __('Override titles and descriptions from other SEO plugins (use with caution)', EWOG_TEXT_DOMAIN),
            'enable_enhanced_schema' => __('Include advanced product properties (GTIN, MPN, brand, specifications)', EWOG_TEXT_DOMAIN),
            'enable_breadcrumb_schema' => __('Add breadcrumb navigation schema markup', EWOG_TEXT_DOMAIN),
            'enable_organization_schema' => __('Add organization and store information schema', EWOG_TEXT_DOMAIN),
            'cache_meta_tags' => __('Cache generated meta tags for better performance', EWOG_TEXT_DOMAIN),
            'debug_mode' => __('Enable debug mode for troubleshooting (adds HTML comments)', EWOG_TEXT_DOMAIN)
        );
        
        foreach ($advanced_fields as $id => $description) {
            $title = ucwords(str_replace(array('enable_', 'disable_', '_'), array('', '', ' '), $id));
            add_settings_field($id, $title, array($this, 'checkbox_field'), 'ewog_settings', 'ewog_advanced_section', 
                array('id' => $id, 'description' => $description));
        }
    }
    
    public function admin_page() {
        ?>
        <div class="wrap ewog-admin-wrap">
            <div class="ewog-admin-header">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <p class="description"><?php _e('Configure how your WooCommerce products appear when shared on social media platforms. This plugin works alongside your existing SEO plugin to fill any gaps.', EWOG_TEXT_DOMAIN); ?></p>
            </div>
            
            <div class="ewog-admin-layout">
                <div class="ewog-main-content">
                    <form method="post" action="options.php" class="ewog-settings-form">
                        <?php
                        settings_fields('ewog_settings_group');
                        do_settings_sections('ewog_settings');
                        submit_button(__('Save Settings', EWOG_TEXT_DOMAIN), 'primary', 'submit', true, array('class' => 'button-primary button-large'));
                        ?>
                    </form>
                </div>
                
                <div class="ewog-sidebar">
                    <div class="ewog-sidebar-box">
                        <div class="ewog-card-header">
                            <h3><span class="dashicons dashicons-dashboard"></span> <?php _e('Current Status', EWOG_TEXT_DOMAIN); ?></h3>
                        </div>
                        <div class="ewog-card-body">
                            <?php $this->display_quick_status(); ?>
                        </div>
                    </div>
                    
                    <div class="ewog-sidebar-box">
                        <div class="ewog-card-header">
                            <h3><span class="dashicons dashicons-external"></span> <?php _e('Testing Tools', EWOG_TEXT_DOMAIN); ?></h3>
                        </div>
                        <div class="ewog-card-body">
                            <p><?php _e('Test how your products look when shared:', EWOG_TEXT_DOMAIN); ?></p>
                            <a href="https://developers.facebook.com/tools/debug/" target="_blank" class="ewog-btn-block">
                                <span class="dashicons dashicons-facebook"></span>
                                <?php _e('Facebook Debugger', EWOG_TEXT_DOMAIN); ?>
                            </a>
                            <a href="https://cards-dev.twitter.com/validator" target="_blank" class="ewog-btn-block">
                                <span class="dashicons dashicons-twitter"></span>
                                <?php _e('Twitter Validator', EWOG_TEXT_DOMAIN); ?>
                            </a>
                            <a href="https://search.google.com/test/rich-results" target="_blank" class="ewog-btn-block">
                                <span class="dashicons dashicons-google"></span>
                                <?php _e('Google Rich Results', EWOG_TEXT_DOMAIN); ?>
                            </a>
                            <a href="https://www.linkedin.com/post-inspector/" target="_blank" class="ewog-btn-block">
                                <span class="dashicons dashicons-linkedin"></span>
                                <?php _e('LinkedIn Inspector', EWOG_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="ewog-sidebar-box">
                        <div class="ewog-card-header">
                            <h3><span class="dashicons dashicons-info"></span> <?php _e('Plugin Information', EWOG_TEXT_DOMAIN); ?></h3>
                        </div>
                        <div class="ewog-card-body">
                            <p><strong><?php _e('Version:', EWOG_TEXT_DOMAIN); ?></strong> <?php echo EWOG_VERSION; ?></p>
                            <p><strong><?php _e('Compatible with:', EWOG_TEXT_DOMAIN); ?></strong> Yoast, RankMath, SEOPress</p>
                            <p><strong><?php _e('Auto-generates:', EWOG_TEXT_DOMAIN); ?></strong> <?php _e('Schema, Open Graph, Twitter Cards', EWOG_TEXT_DOMAIN); ?></p>
                            
                            <hr>
                            
                            <h4><?php _e('Important Notes', EWOG_TEXT_DOMAIN); ?></h4>
                            <ul>
                                <li><?php _e('Uses product featured images automatically', EWOG_TEXT_DOMAIN); ?></li>
                                <li><?php _e('Individual products can have custom titles/descriptions', EWOG_TEXT_DOMAIN); ?></li>
                                <li><?php _e('Works alongside existing SEO plugins', EWOG_TEXT_DOMAIN); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Sitemap generation functions
        function ewogGenerateSitemap(button) {
            button.disabled = true;
            button.textContent = '<?php _e('Generating...', EWOG_TEXT_DOMAIN); ?>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=ewog_generate_sitemap&nonce=<?php echo wp_create_nonce('ewog_admin_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                const results = document.getElementById('ewog-sitemap-results');
                if (data.success) {
                    results.innerHTML = '<div class="notice notice-success"><p>' + data.data.message + '</p></div>';
                } else {
                    results.innerHTML = '<div class="notice notice-error"><p>' + (data.data ? data.data.message : 'Error occurred') + '</p></div>';
                }
            })
            .catch(error => {
                document.getElementById('ewog-sitemap-results').innerHTML = '<div class="notice notice-error"><p>Request failed</p></div>';
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = '<?php _e('Generate Now', EWOG_TEXT_DOMAIN); ?>';
            });
        }
        
        function ewogTestSitemap(button) {
            button.disabled = true;
            button.textContent = '<?php _e('Testing...', EWOG_TEXT_DOMAIN); ?>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=ewog_test_sitemap&nonce=<?php echo wp_create_nonce('ewog_admin_nonce'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                const results = document.getElementById('ewog-sitemap-results');
                if (data.success) {
                    results.innerHTML = '<div class="notice notice-success"><p>' + data.data.message + '</p></div>';
                } else {
                    results.innerHTML = '<div class="notice notice-error"><p>' + (data.data ? data.data.message : 'Test failed') + '</p></div>';
                }
            })
            .catch(error => {
                document.getElementById('ewog-sitemap-results').innerHTML = '<div class="notice notice-error"><p>Test failed</p></div>';
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = '<?php _e('Test Sitemaps', EWOG_TEXT_DOMAIN); ?>';
            });
        }
        
        // Image selector function
        function ewogSelectImage(button) {
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
                
                var existingPreview = button.parentNode.querySelector(".ewog-image-preview");
                if (existingPreview) {
                    existingPreview.src = attachment.url;
                } else {
                    var img = document.createElement("img");
                    img.src = attachment.url;
                    img.className = "ewog-image-preview";
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
    
    // Section callbacks
    public function core_section_callback() {
        echo '<p>' . __('Enable the core social media optimization features for your WooCommerce products.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function sharing_section_callback() {
        echo '<p>' . __('Configure social sharing buttons that appear on your product pages.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function sitemap_section_callback() {
        echo '<p>' . __('Generate XML sitemaps to help search engines discover your products and categories.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function platform_section_callback() {
        echo '<p>' . __('Optional platform-specific settings for enhanced integration.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function content_section_callback() {
        echo '<p>' . __('Configure how images and content appear when shared on social media.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced options for power users and specific use cases.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    // Field rendering methods
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
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        
        echo '<input type="text" ';
        echo 'name="ewog_settings[' . esc_attr($args['id']) . ']" ';
        echo 'value="' . esc_attr($value) . '" ';
        echo 'placeholder="' . esc_attr($placeholder) . '" ';
        echo 'class="regular-text" />';
        
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
    
    public function number_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : ($args['default'] ?? '');
        $min = isset($args['min']) ? 'min="' . esc_attr($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . esc_attr($args['max']) . '"' : '';
        
        echo '<input type="number" ';
        echo 'name="ewog_settings[' . esc_attr($args['id']) . ']" ';
        echo 'value="' . esc_attr($value) . '" ';
        echo $min . ' ' . $max . ' ';
        echo 'class="small-text" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function image_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : '';
        
        echo '<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">';
        echo '<input type="url" ';
        echo 'name="ewog_settings[' . esc_attr($args['id']) . ']" ';
        echo 'value="' . esc_attr($value) . '" ';
        echo 'placeholder="https://example.com/image.jpg" ';
        echo 'class="regular-text" />';
        
        echo '<button type="button" class="button" onclick="ewogSelectImage(this)">' . __('Choose Image', EWOG_TEXT_DOMAIN) . '</button>';
        
        if ($value) {
            echo '<img src="' . esc_url($value) . '" class="ewog-image-preview" style="max-width: 80px; height: auto; border: 1px solid #ddd; border-radius: 3px;" />';
        }
        echo '</div>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
    public function sitemap_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : false;
        $last_generated = get_option('ewog_sitemap_last_generated', 0);
        
        echo '<label>';
        echo '<input type="checkbox" name="ewog_settings[' . esc_attr($args['id']) . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . esc_html($args['description']);
        echo '</label>';
        
        if ($value) {
            echo '<div style="margin-top: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #00a32a; border-radius: 0 3px 3px 0;">';
            
            if ($last_generated) {
                echo '<p><strong>' . __('Last Generated:', EWOG_TEXT_DOMAIN) . '</strong> ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_generated) . '</p>';
            }
            
            echo '<p><strong>' . __('Sitemap URLs:', EWOG_TEXT_DOMAIN) . '</strong></p>';
            echo '<ul>';
            echo '<li><a href="' . home_url('/ewog-sitemap.xml') . '" target="_blank">' . home_url('/ewog-sitemap.xml') . '</a> (Main Index)</li>';
            echo '<li><a href="' . home_url('/product-sitemap-1.xml') . '" target="_blank">' . home_url('/product-sitemap-1.xml') . '</a> (Products)</li>';
            echo '<li><a href="' . home_url('/product-category-sitemap.xml') . '" target="_blank">' . home_url('/product-category-sitemap.xml') . '</a> (Categories)</li>';
            echo '</ul>';
            
            echo '<p>';
            echo '<button type="button" class="button" onclick="ewogGenerateSitemap(this)">' . __('Generate Now', EWOG_TEXT_DOMAIN) . '</button> ';
            echo '<button type="button" class="button" onclick="ewogTestSitemap(this)">' . __('Test Sitemaps', EWOG_TEXT_DOMAIN) . '</button>';
            echo '</p>';
            
            echo '<div id="ewog-sitemap-results"></div>';
            echo '</div>';
        }
    }
    
    private function display_quick_status() {
        $settings = get_option('ewog_settings', array());
        
        // Core Features
        echo '<div style="margin-bottom: 20px;">';
        echo '<h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #666;">' . __('Core Features', EWOG_TEXT_DOMAIN) . '</h4>';
        $core_features = array(
            'enable_schema' => __('Schema Markup', EWOG_TEXT_DOMAIN),
            'enable_facebook' => __('Facebook', EWOG_TEXT_DOMAIN),
            'enable_twitter' => __('Twitter', EWOG_TEXT_DOMAIN),
        );
        
        foreach ($core_features as $key => $label) {
            $enabled = !empty($settings[$key]);
            $this->render_status_item($label, $enabled);
        }
        echo '</div>';
        
        // Additional Platforms
        echo '<div style="margin-bottom: 20px;">';
        echo '<h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #666;">' . __('Additional Platforms', EWOG_TEXT_DOMAIN) . '</h4>';
        $additional_platforms = array(
            'enable_linkedin' => __('LinkedIn', EWOG_TEXT_DOMAIN),
            'enable_pinterest' => __('Pinterest', EWOG_TEXT_DOMAIN),
            'enable_whatsapp' => __('WhatsApp', EWOG_TEXT_DOMAIN),
        );
        
        foreach ($additional_platforms as $key => $label) {
            $enabled = !empty($settings[$key]);
            $this->render_status_item($label, $enabled);
        }
        echo '</div>';
        
        // Tools
        echo '<div>';
        echo '<h4 style="margin: 0 0 10px 0; font-size: 13px; text-transform: uppercase; color: #666;">' . __('Tools', EWOG_TEXT_DOMAIN) . '</h4>';
        $tools = array(
            'enable_social_share' => __('Share Buttons', EWOG_TEXT_DOMAIN),
            'enable_product_sitemap' => __('XML Sitemaps', EWOG_TEXT_DOMAIN),
        );
        
        foreach ($tools as $key => $label) {
            $enabled = !empty($settings[$key]);
            $this->render_status_item($label, $enabled);
        }
        echo '</div>';
    }
    
    private function render_status_item($label, $enabled) {
        $status_class = $enabled ? 'enabled' : 'disabled';
        $status_text = $enabled ? __('On', EWOG_TEXT_DOMAIN) : __('Off', EWOG_TEXT_DOMAIN);
        
        echo '<div class="ewog-status-item">';
        echo '<span class="ewog-status-icon ' . $status_class . '"></span>';
        echo '<span class="ewog-status-label">' . esc_html($label) . '</span>';
        echo '<span class="ewog-status-text">' . esc_html($status_text) . '</span>';
        echo '</div>';
    }
    
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=enhanced-woo-open-graph') . '">' . __('Settings', EWOG_TEXT_DOMAIN) . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_enhanced-woo-open-graph' !== $hook) {
            return;
        }
        
        // Enqueue media library for image selection
        wp_enqueue_media();
        
        // Enqueue clean admin styles
        wp_enqueue_style(
            'ewog-admin',
            EWOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EWOG_VERSION
        );
    }
    
    public function ajax_generate_sitemap() {
        check_ajax_referer('ewog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Unauthorized', EWOG_TEXT_DOMAIN)));
        }
        
        if (class_exists('EWOG_Sitemap')) {
            $sitemap = EWOG_Sitemap::get_instance();
            $sitemap->generate_all_sitemaps_background();
            update_option('ewog_sitemap_last_generated', time());
            
            wp_send_json_success(array(
                'message' => __('Sitemaps generated successfully!', EWOG_TEXT_DOMAIN)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Sitemap generation not available', EWOG_TEXT_DOMAIN)
            ));
        }
    }
    
    public function ajax_test_sitemap() {
        check_ajax_referer('ewog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Unauthorized', EWOG_TEXT_DOMAIN)));
        }
        
        $sitemap_url = home_url('/ewog-sitemap.xml');
        $response = wp_remote_get($sitemap_url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => __('Failed to fetch sitemap: ', EWOG_TEXT_DOMAIN) . $response->get_error_message()
            ));
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code !== 200) {
            wp_send_json_error(array(
                'message' => sprintf(__('Sitemap returned HTTP %d', EWOG_TEXT_DOMAIN), $code)
            ));
        }
        
        if (strpos($body, '<sitemapindex') === false && strpos($body, '<urlset') === false) {
            wp_send_json_error(array(
                'message' => __('Invalid sitemap format', EWOG_TEXT_DOMAIN)
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Sitemaps are working correctly!', EWOG_TEXT_DOMAIN)
        ));
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Boolean settings
        $booleans = array(
            'enable_schema', 'enable_facebook', 'enable_twitter', 'enable_linkedin',
            'enable_pinterest', 'enable_whatsapp', 'enable_social_share', 
            'enable_product_sitemap', 'disable_title_description', 'enable_enhanced_schema',
            'enable_breadcrumb_schema', 'enable_organization_schema', 'cache_meta_tags', 'debug_mode'
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
        $existing = get_option('ewog_settings', array());
        $sanitized = array_merge($existing, $sanitized);
        
        return $sanitized;
    }
}