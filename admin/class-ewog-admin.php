<?php
/**
 * Enhanced Admin Settings Class
 * 
 * Modern admin interface with comprehensive settings and meta box support
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
        
        // AJAX handlers
        add_action('wp_ajax_ewog_test_sitemap', array($this, 'ajax_test_sitemap'));
        add_action('wp_ajax_ewog_generate_sitemap', array($this, 'ajax_generate_sitemap'));
        add_action('wp_ajax_ewog_validate_schema', array($this, 'ajax_validate_schema'));
        add_action('wp_ajax_ewog_quick_test', array($this, 'ajax_quick_test'));
        add_action('wp_ajax_ewog_clear_cache', array($this, 'ajax_clear_cache'));
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
        
        // Essential Settings Section
        add_settings_section(
            'ewog_essential_section',
            __('Essential Settings', EWOG_TEXT_DOMAIN),
            array($this, 'essential_section_callback'),
            'ewog_settings'
        );
        
        // Schema Settings Section
        add_settings_section(
            'ewog_schema_section',
            __('Schema.org Structured Data', EWOG_TEXT_DOMAIN),
            array($this, 'schema_section_callback'),
            'ewog_settings'
        );
        
        // Open Graph Settings Section
        add_settings_section(
            'ewog_opengraph_section',
            __('Open Graph Meta Tags', EWOG_TEXT_DOMAIN),
            array($this, 'opengraph_section_callback'),
            'ewog_settings'
        );
        
        // Social Platforms Section
        add_settings_section(
            'ewog_platforms_section',
            __('Social Media Platforms', EWOG_TEXT_DOMAIN),
            array($this, 'platforms_section_callback'),
            'ewog_settings'
        );
        
        // Sitemap Settings Section
        add_settings_section(
            'ewog_sitemap_section',
            __('XML Sitemaps', EWOG_TEXT_DOMAIN),
            array($this, 'sitemap_section_callback'),
            'ewog_settings'
        );
        
        // Social Sharing Section
        add_settings_section(
            'ewog_sharing_section',
            __('Social Sharing Buttons', EWOG_TEXT_DOMAIN),
            array($this, 'sharing_section_callback'),
            'ewog_settings'
        );
        
        // Advanced Settings Section
        add_settings_section(
            'ewog_advanced_section',
            __('Advanced Settings', EWOG_TEXT_DOMAIN),
            array($this, 'advanced_section_callback'),
            'ewog_settings'
        );
        
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // Essential toggle fields (for dashboard/modern interface)
        $essential_fields = array(
            'enable_schema' => array(
                'title' => __('Schema Markup', EWOG_TEXT_DOMAIN),
                'label' => __('Enable Schema.org structured data', EWOG_TEXT_DOMAIN),
                'description' => __('Add rich snippets for better search results', EWOG_TEXT_DOMAIN)
            ),
            'enable_facebook' => array(
                'title' => __('Facebook Integration', EWOG_TEXT_DOMAIN),
                'label' => __('Enable Facebook Open Graph', EWOG_TEXT_DOMAIN),
                'description' => __('Optimize sharing on Facebook', EWOG_TEXT_DOMAIN)
            ),
            'enable_twitter' => array(
                'title' => __('Twitter Integration', EWOG_TEXT_DOMAIN),
                'label' => __('Enable Twitter Cards', EWOG_TEXT_DOMAIN), 
                'description' => __('Optimize sharing on Twitter', EWOG_TEXT_DOMAIN)
            ),
            'enable_social_share' => array(
                'title' => __('Social Share Buttons', EWOG_TEXT_DOMAIN),
                'label' => __('Enable social share buttons', EWOG_TEXT_DOMAIN),
                'description' => __('Add share buttons to product pages', EWOG_TEXT_DOMAIN)
            ),
            'enable_product_sitemap' => array(
                'title' => __('XML Sitemaps', EWOG_TEXT_DOMAIN),
                'label' => __('Enable XML sitemaps', EWOG_TEXT_DOMAIN),
                'description' => __('Generate sitemaps for better SEO', EWOG_TEXT_DOMAIN)
            )
        );
        
        foreach ($essential_fields as $field_id => $field_data) {
            add_settings_field(
                $field_id,
                $field_data['title'],
                array($this, 'toggle_field'),
                'ewog_settings',
                'ewog_essential_section',
                array_merge(array('id' => $field_id), $field_data)
            );
        }
        
        // Schema Settings (for full settings tab)
        $this->add_schema_settings_fields();
        
        // Open Graph Settings (for full settings tab)
        $this->add_opengraph_settings_fields();
        
        // Social Platform Settings (for full settings tab) 
        $this->add_platform_settings_fields();
        
        // Sitemap Settings (for full settings tab)
        $this->add_sitemap_settings_fields();
        
        // Social Sharing Settings (for full settings tab)
        $this->add_sharing_settings_fields();
        
        // Advanced Settings (for full settings tab)
        $this->add_advanced_settings_fields();
        
        // Platform selection field (for dashboard)
        add_settings_field(
            'social_platforms',
            __('Select Platforms', EWOG_TEXT_DOMAIN),
            array($this, 'platforms_field'),
            'ewog_settings',
            'ewog_platforms_section',
            array()
        );
        
        // Advanced options field (for dashboard)
        add_settings_field(
            'advanced_options',
            __('Advanced Options', EWOG_TEXT_DOMAIN),
            array($this, 'advanced_options_field'),
            'ewog_settings',
            'ewog_advanced_section',
            array()
        );
    }
    
    // Full meta box support methods
    private function add_schema_settings_fields() {
        add_settings_field(
            'enable_enhanced_schema',
            __('Enhanced Product Schema', EWOG_TEXT_DOMAIN),
            array($this, 'checkbox_field'),
            'ewog_settings',
            'ewog_schema_section',
            array(
                'id' => 'enable_enhanced_schema',
                'description' => __('Include advanced product properties (GTIN, MPN, brand, specifications)', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'enable_breadcrumb_schema',
            __('Breadcrumb Schema', EWOG_TEXT_DOMAIN),
            array($this, 'checkbox_field'),
            'ewog_settings',
            'ewog_schema_section',
            array(
                'id' => 'enable_breadcrumb_schema',
                'description' => __('Add breadcrumb navigation schema markup', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'enable_organization_schema',
            __('Organization Schema', EWOG_TEXT_DOMAIN),
            array($this, 'checkbox_field'),
            'ewog_settings',
            'ewog_schema_section',
            array(
                'id' => 'enable_organization_schema',
                'description' => __('Add organization and store information schema', EWOG_TEXT_DOMAIN)
            )
        );
    }
    
    private function add_opengraph_settings_fields() {
        add_settings_field(
            'disable_title_description',
            __('Override Other Plugins', EWOG_TEXT_DOMAIN),
            array($this, 'checkbox_field'),
            'ewog_settings',
            'ewog_opengraph_section',
            array(
                'id' => 'disable_title_description',
                'description' => __('Override title and description from other SEO plugins', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'image_size',
            __('Image Size', EWOG_TEXT_DOMAIN),
            array($this, 'select_field'),
            'ewog_settings',
            'ewog_opengraph_section',
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
            'ewog_opengraph_section',
            array(
                'id' => 'fallback_image',
                'description' => __('Default image when product has no featured image', EWOG_TEXT_DOMAIN)
            )
        );
    }
    
    private function add_platform_settings_fields() {
        $platforms = array(
            'linkedin' => array(
                'label' => __('LinkedIn', EWOG_TEXT_DOMAIN),
                'description' => __('Enable LinkedIn specific optimization', EWOG_TEXT_DOMAIN)
            ),
            'pinterest' => array(
                'label' => __('Pinterest', EWOG_TEXT_DOMAIN),
                'description' => __('Enable Pinterest Rich Pins with product data', EWOG_TEXT_DOMAIN)
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
                'description' => __('Your Facebook App ID for better analytics and insights', EWOG_TEXT_DOMAIN)
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
                'description' => __('Twitter username (without @) for attribution', EWOG_TEXT_DOMAIN)
            )
        );
    }
    
    private function add_sitemap_settings_fields() {
        add_settings_field(
            'sitemap_products_per_page',
            __('Products per Sitemap', EWOG_TEXT_DOMAIN),
            array($this, 'number_field'),
            'ewog_settings',
            'ewog_sitemap_section',
            array(
                'id' => 'sitemap_products_per_page',
                'min' => 100,
                'max' => 1000,
                'default' => 500,
                'description' => __('Number of products per sitemap file (recommended: 500)', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'sitemap_test_button',
            __('Sitemap Management', EWOG_TEXT_DOMAIN),
            array($this, 'sitemap_management_field'),
            'ewog_settings',
            'ewog_sitemap_section',
            array()
        );
    }
    
    private function add_sharing_settings_fields() {
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
                'description' => __('Choose the visual style for share buttons', EWOG_TEXT_DOMAIN)
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
                'description' => __('Where to display the share buttons on product pages', EWOG_TEXT_DOMAIN)
            )
        );
    }
    
    private function add_advanced_settings_fields() {
        add_settings_field(
            'cache_meta_tags',
            __('Cache Meta Tags', EWOG_TEXT_DOMAIN),
            array($this, 'checkbox_field'),
            'ewog_settings',
            'ewog_advanced_section',
            array(
                'id' => 'cache_meta_tags',
                'description' => __('Cache generated meta tags for better performance', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'debug_mode',
            __('Debug Mode', EWOG_TEXT_DOMAIN),
            array($this, 'checkbox_field'),
            'ewog_settings',
            'ewog_advanced_section',
            array(
                'id' => 'debug_mode',
                'description' => __('Enable debug mode for troubleshooting (adds HTML comments)', EWOG_TEXT_DOMAIN)
            )
        );
        
        add_settings_field(
            'schema_validation',
            __('Schema Validation', EWOG_TEXT_DOMAIN),
            array($this, 'schema_validation_field'),
            'ewog_settings',
            'ewog_advanced_section',
            array()
        );
    }
    
    /**
     * Admin page with tabbed interface
     */
    public function admin_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        ?>
        <div class="wrap ewog-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper">
                <a href="?page=enhanced-woo-open-graph&tab=dashboard" 
                   class="nav-tab <?php echo $current_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-dashboard"></span>
                    <?php _e('Dashboard', EWOG_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=enhanced-woo-open-graph&tab=settings" 
                   class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Settings', EWOG_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=enhanced-woo-open-graph&tab=tools" 
                   class="nav-tab <?php echo $current_tab === 'tools' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Tools', EWOG_TEXT_DOMAIN); ?>
                </a>
            </nav>
            
            <!-- Tab Content -->
            <div class="ewog-tab-content">
                <?php
                switch ($current_tab) {
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    case 'tools':
                        $this->render_tools_tab();
                        break;
                    default:
                        $this->render_dashboard_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render dashboard tab
     */
    private function render_dashboard_tab() {
        $status = $this->get_plugin_status();
        ?>
        <div class="ewog-dashboard">
            <div class="ewog-row">
                <!-- Status Overview -->
                <div class="ewog-col-8">
                    <div class="ewog-card">
                        <div class="ewog-card-header">
                            <h2><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Plugin Status', EWOG_TEXT_DOMAIN); ?></h2>
                        </div>
                        <div class="ewog-card-body">
                            <div class="ewog-status-grid">
                                <?php $this->render_status_item('Schema Markup', $status['schema']); ?>
                                <?php $this->render_status_item('Open Graph', $status['opengraph']); ?>
                                <?php $this->render_status_item('Social Sharing', $status['social_share']); ?>
                                <?php $this->render_status_item('XML Sitemaps', $status['sitemap']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="ewog-col-4">
                    <div class="ewog-card">
                        <div class="ewog-card-header">
                            <h3><?php _e('Quick Actions', EWOG_TEXT_DOMAIN); ?></h3>
                        </div>
                        <div class="ewog-card-body">
                            <div class="ewog-actions-list">
                                <button type="button" class="button button-primary ewog-btn-block ewog-quick-test">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php _e('Test Product Page', EWOG_TEXT_DOMAIN); ?>
                                </button>
                                <button type="button" class="button ewog-btn-block ewog-clear-cache">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php _e('Clear Cache', EWOG_TEXT_DOMAIN); ?>
                                </button>
                                <a href="?page=enhanced-woo-open-graph&tab=tools" class="button ewog-btn-block">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <?php _e('Validation Tools', EWOG_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Active Platforms -->
            <div class="ewog-card">
                <div class="ewog-card-header">
                    <h3><?php _e('Active Social Platforms', EWOG_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="ewog-card-body">
                    <?php $this->render_active_platforms(); ?>
                </div>
            </div>
        </div>
        
        <!-- Test Results Container -->
        <div class="ewog-test-results"></div>
        <?php
    }
    
    /**
     * Render settings tab - Full settings with all meta boxes
     */
    private function render_settings_tab() {
        ?>
        <form action="options.php" method="post" class="ewog-settings-form">
            <?php
            settings_fields('ewog_settings_group');
            do_settings_sections('ewog_settings');
            submit_button(__('Save Settings', EWOG_TEXT_DOMAIN), 'primary', 'submit', true, array('class' => 'button-large'));
            ?>
        </form>
        
        <div class="ewog-sidebar">
            <div class="ewog-sidebar-box">
                <h3><?php _e('Quick Validation', EWOG_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Test your markup with these tools:', EWOG_TEXT_DOMAIN); ?></p>
                <ul>
                    <li><a href="https://developers.facebook.com/tools/debug/" target="_blank"><?php _e('Facebook Debugger', EWOG_TEXT_DOMAIN); ?></a></li>
                    <li><a href="https://cards-dev.twitter.com/validator" target="_blank"><?php _e('Twitter Card Validator', EWOG_TEXT_DOMAIN); ?></a></li>
                    <li><a href="https://search.google.com/test/rich-results" target="_blank"><?php _e('Google Rich Results Test', EWOG_TEXT_DOMAIN); ?></a></li>
                    <li><a href="https://www.linkedin.com/post-inspector/" target="_blank"><?php _e('LinkedIn Post Inspector', EWOG_TEXT_DOMAIN); ?></a></li>
                </ul>
            </div>
            
            <div class="ewog-sidebar-box">
                <h3><?php _e('Documentation', EWOG_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Learn more about optimizing your store:', EWOG_TEXT_DOMAIN); ?></p>
                <ul>
                    <li><a href="#" target="_blank"><?php _e('Setup Guide', EWOG_TEXT_DOMAIN); ?></a></li>
                    <li><a href="#" target="_blank"><?php _e('Schema.org Guide', EWOG_TEXT_DOMAIN); ?></a></li>
                    <li><a href="#" target="_blank"><?php _e('Open Graph Best Practices', EWOG_TEXT_DOMAIN); ?></a></li>
                    <li><a href="#" target="_blank"><?php _e('Sitemap Optimization', EWOG_TEXT_DOMAIN); ?></a></li>
                </ul>
            </div>
            
            <div class="ewog-sidebar-box">
                <h3><?php _e('Plugin Status', EWOG_TEXT_DOMAIN); ?></h3>
                <?php $this->display_plugin_status(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render tools tab
     */
    private function render_tools_tab() {
        ?>
        <div class="ewog-tools">
            <div class="ewog-row">
                <div class="ewog-col-6">
                    <div class="ewog-card">
                        <div class="ewog-card-header">
                            <h3><?php _e('Social Media Validation', EWOG_TEXT_DOMAIN); ?></h3>
                        </div>
                        <div class="ewog-card-body">
                            <p><?php _e('Test how your products appear on social media platforms:', EWOG_TEXT_DOMAIN); ?></p>
                            <div class="ewog-tool-buttons">
                                <button type="button" class="button button-primary ewog-test-facebook">
                                    <?php _e('Test Facebook', EWOG_TEXT_DOMAIN); ?>
                                </button>
                                <button type="button" class="button button-primary ewog-test-twitter">
                                    <?php _e('Test Twitter', EWOG_TEXT_DOMAIN); ?>
                                </button>
                                <button type="button" class="button button-primary ewog-test-schema">
                                    <?php _e('Test Schema', EWOG_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                            <div class="ewog-test-results"></div>
                        </div>
                    </div>
                </div>
                
                <div class="ewog-col-6">
                    <div class="ewog-card">
                        <div class="ewog-card-header">
                            <h3><?php _e('Sitemap Management', EWOG_TEXT_DOMAIN); ?></h3>
                        </div>
                        <div class="ewog-card-body">
                            <?php $this->render_sitemap_tools(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Section callbacks
     */
    public function essential_section_callback() {
        echo '<p class="description">' . __('Enable the core features you need for better SEO and social media optimization.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function schema_section_callback() {
        echo '<p>' . __('Configure Schema.org structured data to help search engines understand your products better and display rich snippets in search results.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function opengraph_section_callback() {
        echo '<p>' . __('Configure Open Graph meta tags for optimal social media sharing appearance across all platforms.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function platforms_section_callback() {
        echo '<p class="description">' . __('Choose which social media platforms to optimize for. Click on platform cards to enable them.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function sitemap_section_callback() {
        echo '<p>' . __('Generate comprehensive XML sitemaps specifically optimized for WooCommerce products, categories, and brands.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function sharing_section_callback() {
        echo '<p>' . __('Configure social sharing buttons to encourage customers to share your products on social media.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p class="description">' . __('Advanced options for power users. Most users can leave these as default.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    /**
     * Field rendering methods - Both modern and traditional
     */
    public function toggle_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : false;
        
        ?>
        <div class="ewog-toggle-wrapper">
            <label class="ewog-toggle-label">
                <input type="checkbox" 
                       name="ewog_settings[<?php echo esc_attr($args['id']); ?>]" 
                       value="1" 
                       <?php checked(1, $value); ?> 
                       class="ewog-toggle-input" />
                <div class="ewog-toggle-content">
                    <strong><?php echo esc_html($args['label']); ?></strong>
                    <small><?php echo esc_html($args['description']); ?></small>
                </div>
            </label>
        </div>
        <?php
    }
    
    public function checkbox_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : false;
        
        echo '<label class="ewog-checkbox-label">';
        echo '<input type="checkbox" name="ewog_settings[' . esc_attr($args['id']) . ']" value="1" ' . checked(1, $value, false) . ' />';
        echo '<span class="ewog-checkbox-text">' . esc_html($args['description']) . '</span>';
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
    
    public function number_field($args) {
        $settings = get_option('ewog_settings', array());
        $value = isset($settings[$args['id']]) ? $settings[$args['id']] : $args['default'];
        
        $min = isset($args['min']) ? 'min="' . esc_attr($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . esc_attr($args['max']) . '"' : '';
        
        echo '<input type="number" name="ewog_settings[' . esc_attr($args['id']) . ']" value="' . esc_attr($value) . '" ' . $min . ' ' . $max . ' class="small-text" />';
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
    
    public function sitemap_management_field($args) {
        $settings = get_option('ewog_settings', array());
        $last_generated = get_option('ewog_sitemap_last_generated', 0);
        
        echo '<div class="ewog-sitemap-management">';
        
        if ($last_generated) {
            echo '<p><strong>' . __('Last Generated:', EWOG_TEXT_DOMAIN) . '</strong> ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_generated) . '</p>';
        }
        
        echo '<p>';
        echo '<button type="button" class="button ewog-generate-sitemap" data-action="generate">' . __('Generate Now', EWOG_TEXT_DOMAIN) . '</button> ';
        echo '<button type="button" class="button ewog-test-sitemap" data-action="test">' . __('Test Sitemap', EWOG_TEXT_DOMAIN) . '</button>';
        echo '</p>';
        
        if (!empty($settings['enable_product_sitemap'])) {
            echo '<p class="description">';
            echo __('Sitemap URLs:', EWOG_TEXT_DOMAIN) . '<br>';
            echo '<code>' . home_url('/ewog-sitemap.xml') . '</code> (Main Index)<br>';
            echo '<code>' . home_url('/product-sitemap.xml') . '</code> (Products)<br>';
            echo '<code>' . home_url('/product-category-sitemap.xml') . '</code> (Categories)';
            echo '</p>';
        }
        
        echo '<div class="ewog-sitemap-results"></div>';
        echo '</div>';
    }
    
    public function schema_validation_field($args) {
        echo '<div class="ewog-schema-validation">';
        echo '<p>';
        echo '<button type="button" class="button ewog-validate-schema">' . __('Test Schema Markup', EWOG_TEXT_DOMAIN) . '</button>';
        echo '</p>';
        echo '<p class="description">' . __('Test your Schema.org markup with Google\'s Rich Results Test', EWOG_TEXT_DOMAIN) . '</p>';
        echo '<div class="ewog-schema-results"></div>';
        echo '</div>';
    }
    
    public function platforms_field($args) {
        $settings = get_option('ewog_settings', array());
        $platforms = array(
            'facebook' => array('name' => 'Facebook', 'icon' => '📘', 'desc' => 'World\'s largest social network'),
            'twitter' => array('name' => 'Twitter', 'icon' => '🐦', 'desc' => 'Real-time social updates'),
            'linkedin' => array('name' => 'LinkedIn', 'icon' => '💼', 'desc' => 'Professional networking'),
            'pinterest' => array('name' => 'Pinterest', 'icon' => '📌', 'desc' => 'Visual discovery platform'),
            'whatsapp' => array('name' => 'WhatsApp', 'icon' => '💬', 'desc' => 'Mobile messaging app')
        );
        
        echo '<div class="ewog-platforms-grid">';
        foreach ($platforms as $key => $platform) {
            $checked = !empty($settings['enable_' . $key]);
            $active_class = $checked ? 'active' : '';
            ?>
            <div class="ewog-platform-card <?php echo $active_class; ?>" data-platform="<?php echo esc_attr($key); ?>">
                <input type="checkbox" 
                       name="ewog_settings[enable_<?php echo esc_attr($key); ?>]" 
                       value="1" 
                       <?php checked($checked); ?> 
                       id="platform_<?php echo esc_attr($key); ?>" 
                       style="display: none;">
                <label for="platform_<?php echo esc_attr($key); ?>" class="ewog-platform-label">
                    <span class="ewog-platform-icon"><?php echo $platform['icon']; ?></span>
                    <strong><?php echo esc_html($platform['name']); ?></strong>
                    <small><?php echo esc_html($platform['desc']); ?></small>
                </label>
            </div>
            <?php
        }
        echo '</div>';
    }
    
    public function advanced_options_field($args) {
        $settings = get_option('ewog_settings', array());
        ?>
        <div class="ewog-advanced-options">
            <details class="ewog-collapsible">
                <summary class="ewog-collapsible-header">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                    <?php _e('Show Advanced Options', EWOG_TEXT_DOMAIN); ?>
                </summary>
                <div class="ewog-collapsible-content">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Image Size', EWOG_TEXT_DOMAIN); ?></th>
                            <td>
                                <select name="ewog_settings[image_size]">
                                    <option value="medium" <?php selected($settings['image_size'] ?? '', 'medium'); ?>>
                                        <?php _e('Medium (recommended)', EWOG_TEXT_DOMAIN); ?>
                                    </option>
                                    <option value="large" <?php selected($settings['image_size'] ?? '', 'large'); ?>>
                                        <?php _e('Large', EWOG_TEXT_DOMAIN); ?>
                                    </option>
                                    <option value="full" <?php selected($settings['image_size'] ?? '', 'full'); ?>>
                                        <?php _e('Full Size', EWOG_TEXT_DOMAIN); ?>
                                    </option>
                                </select>
                                <p class="description"><?php _e('Size of images used for social sharing', EWOG_TEXT_DOMAIN); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Performance', EWOG_TEXT_DOMAIN); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="ewog_settings[cache_meta_tags]" 
                                           value="1" 
                                           <?php checked(!empty($settings['cache_meta_tags'])); ?>>
                                    <?php _e('Enable caching for better performance', EWOG_TEXT_DOMAIN); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Debug', EWOG_TEXT_DOMAIN); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="ewog_settings[debug_mode]" 
                                           value="1" 
                                           <?php checked(!empty($settings['debug_mode'])); ?>>
                                    <?php _e('Enable debug mode (for troubleshooting)', EWOG_TEXT_DOMAIN); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </details>
        </div>
        <?php
    }
    
    /**
     * Helper methods for dashboard
     */
    private function get_plugin_status() {
        $settings = get_option('ewog_settings', array());
        return array(
            'schema' => !empty($settings['enable_schema']),
            'opengraph' => !empty($settings['enable_facebook']) || !empty($settings['enable_twitter']),
            'social_share' => !empty($settings['enable_social_share']),
            'sitemap' => !empty($settings['enable_product_sitemap'])
        );
    }
    
    private function render_status_item($label, $status) {
        $icon = $status ? 'yes-alt' : 'dismiss';
        $class = $status ? 'active' : 'inactive';
        $badge_text = $status ? __('Active', EWOG_TEXT_DOMAIN) : __('Inactive', EWOG_TEXT_DOMAIN);
        ?>
        <div class="ewog-status-item <?php echo $class; ?>">
            <span class="dashicons dashicons-<?php echo $icon; ?>"></span>
            <span class="ewog-status-label"><?php echo esc_html($label); ?></span>
            <span class="ewog-status-badge"><?php echo esc_html($badge_text); ?></span>
        </div>
        <?php
    }
    
    private function render_active_platforms() {
        $settings = get_option('ewog_settings', array());
        $platforms = array('facebook', 'twitter', 'linkedin', 'pinterest', 'whatsapp');
        $active = array();
        
        foreach ($platforms as $platform) {
            if (!empty($settings['enable_' . $platform])) {
                $active[] = ucfirst($platform);
            }
        }
        
        if (empty($active)) {
            echo '<p class="ewog-no-platforms">' . __('No social platforms enabled.', EWOG_TEXT_DOMAIN) . ' ';
            echo '<a href="?page=enhanced-woo-open-graph&tab=settings">' . __('Enable platforms', EWOG_TEXT_DOMAIN) . '</a></p>';
        } else {
            echo '<div class="ewog-platform-badges">';
            foreach ($active as $platform) {
                echo '<span class="ewog-platform-badge">' . esc_html($platform) . '</span>';
            }
            echo '</div>';
        }
    }
    
    private function render_sitemap_tools() {
        $settings = get_option('ewog_settings', array());
        
        if (empty($settings['enable_product_sitemap'])) {
            echo '<p>' . __('Sitemaps are currently disabled.', EWOG_TEXT_DOMAIN) . ' ';
            echo '<a href="?page=enhanced-woo-open-graph&tab=settings">' . __('Enable them in Settings', EWOG_TEXT_DOMAIN) . '</a></p>';
            return;
        }
        
        $last_generated = get_option('ewog_sitemap_last_generated', 0);
        ?>
        <div class="ewog-sitemap-status">
            <?php if ($last_generated): ?>
                <p><strong><?php _e('Last Generated:', EWOG_TEXT_DOMAIN); ?></strong><br>
                   <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_generated); ?></p>
            <?php else: ?>
                <p><?php _e('Sitemaps have not been generated yet.', EWOG_TEXT_DOMAIN); ?></p>
            <?php endif; ?>
            
            <div class="ewog-sitemap-actions">
                <button type="button" class="button button-primary ewog-generate-sitemap">
                    <?php _e('Generate Now', EWOG_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button ewog-test-sitemap">
                    <?php _e('Test Sitemap', EWOG_TEXT_DOMAIN); ?>
                </button>
            </div>
            
            <div class="ewog-sitemap-links">
                <p><strong><?php _e('Sitemap URLs:', EWOG_TEXT_DOMAIN); ?></strong></p>
                <ul>
                    <li><code><?php echo home_url('/ewog-sitemap.xml'); ?></code></li>
                    <li><code><?php echo home_url('/product-sitemap.xml'); ?></code></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    private function display_plugin_status() {
        $settings = get_option('ewog_settings', array());
        
        echo '<div class="ewog-status-grid">';
        
        // Schema Status
        $schema_enabled = !empty($settings['enable_schema']);
        echo '<div class="ewog-status-item">';
        echo '<span class="ewog-status-indicator ' . ($schema_enabled ? 'enabled' : 'disabled') . '"></span>';
        echo '<span>' . __('Schema Markup', EWOG_TEXT_DOMAIN) . '</span>';
        echo '</div>';
        
        // Open Graph Status
        $og_enabled = !empty($settings['enable_facebook']) || !empty($settings['enable_twitter']);
        echo '<div class="ewog-status-item">';
        echo '<span class="ewog-status-indicator ' . ($og_enabled ? 'enabled' : 'disabled') . '"></span>';
        echo '<span>' . __('Open Graph', EWOG_TEXT_DOMAIN) . '</span>';
        echo '</div>';
        
        // Sitemap Status
        $sitemap_enabled = !empty($settings['enable_product_sitemap']);
        echo '<div class="ewog-status-item">';
        echo '<span class="ewog-status-indicator ' . ($sitemap_enabled ? 'enabled' : 'disabled') . '"></span>';
        echo '<span>' . __('Sitemaps', EWOG_TEXT_DOMAIN) . '</span>';
        echo '</div>';
        
        // Social Sharing Status
        $sharing_enabled = !empty($settings['enable_social_share']);
        echo '<div class="ewog-status-item">';
        echo '<span class="ewog-status-indicator ' . ($sharing_enabled ? 'enabled' : 'disabled') . '"></span>';
        echo '<span>' . __('Social Sharing', EWOG_TEXT_DOMAIN) . '</span>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * AJAX Handlers
     */
    public function ajax_quick_test() {
        check_ajax_referer('ewog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Unauthorized', EWOG_TEXT_DOMAIN));
        }
        
        // Get sample product
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1
        ));
        
        if (empty($products)) {
            wp_send_json_error(array(
                'message' => __('No published products found for testing', EWOG_TEXT_DOMAIN)
            ));
        }
        
        $product_url = get_permalink($products[0]->ID);
        
        wp_send_json_success(array(
            'message' => __('Test completed successfully!', EWOG_TEXT_DOMAIN),
            'product_url' => $product_url,
            'facebook_test' => 'https://developers.facebook.com/tools/debug/?q=' . urlencode($product_url),
            'twitter_test' => 'https://cards-dev.twitter.com/validator?url=' . urlencode($product_url),
            'schema_test' => 'https://search.google.com/test/rich-results?url=' . urlencode($product_url)
        ));
    }
    
    public function ajax_clear_cache() {
        check_ajax_referer('ewog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Unauthorized', EWOG_TEXT_DOMAIN));
        }
        
        // Clear plugin caches
        global $wpdb;
        
        // Clear transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_ewog_%' 
             OR option_name LIKE '_transient_timeout_ewog_%'"
        );
        
        // Clear meta cache table if it exists
        $table_name = $wpdb->prefix . 'ewog_meta_cache';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $wpdb->query("TRUNCATE TABLE $table_name");
        }
        
        // Clear WordPress object cache
        wp_cache_flush();
        
        wp_send_json_success(array(
            'message' => __('Cache cleared successfully!', EWOG_TEXT_DOMAIN)
        ));
    }
    
    public function ajax_test_sitemap() {
        check_ajax_referer('ewog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Unauthorized', EWOG_TEXT_DOMAIN));
        }
        
        $sitemap_url = home_url('/ewog-sitemap.xml');
        $response = wp_remote_get($sitemap_url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => __('Failed to fetch sitemap: ', EWOG_TEXT_DOMAIN) . $response->get_error_message()
            ));
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
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
            'message' => __('Sitemap is working correctly!', EWOG_TEXT_DOMAIN),
            'url' => $sitemap_url
        ));
    }
    
    public function ajax_generate_sitemap() {
        check_ajax_referer('ewog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Unauthorized', EWOG_TEXT_DOMAIN));
        }
        
        // Use existing sitemap functionality if available
        if (class_exists('EWOG_Sitemap')) {
            $sitemap = EWOG_Sitemap::get_instance();
            $sitemap->generate_all_sitemaps();
        }
        
        wp_send_json_success(array(
            'message' => __('Sitemaps generated successfully!', EWOG_TEXT_DOMAIN),
            'timestamp' => current_time('mysql')
        ));
    }
    
    public function ajax_validate_schema() {
        check_ajax_referer('ewog_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Unauthorized', EWOG_TEXT_DOMAIN));
        }
        
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1
        ));
        
        if (empty($products)) {
            wp_send_json_error(array(
                'message' => __('No published products found for testing', EWOG_TEXT_DOMAIN)
            ));
        }
        
        $product_url = get_permalink($products[0]->ID);
        $test_url = 'https://search.google.com/test/rich-results?url=' . urlencode($product_url);
        
        wp_send_json_success(array(
            'message' => __('Click the link below to test your Schema markup:', EWOG_TEXT_DOMAIN),
            'test_url' => $test_url,
            'product_url' => $product_url
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('woocommerce_page_enhanced-woo-open-graph' !== $hook) {
            return;
        }
        
        wp_enqueue_media();
        
        // Only enqueue if files exist
        $js_file = EWOG_PLUGIN_URL . 'assets/js/admin.js';
        $css_file = EWOG_PLUGIN_URL . 'assets/css/admin.css';
        
        if (file_exists(EWOG_PLUGIN_DIR . 'assets/js/admin.js')) {
            wp_enqueue_script(
                'ewog-admin',
                $js_file,
                array('jquery', 'wp-media-utils'),
                EWOG_VERSION,
                true
            );
            
            wp_localize_script('ewog-admin', 'ewogAdminVars', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ewog_admin_nonce'),
                'testing' => __('Testing...', EWOG_TEXT_DOMAIN),
                'generating' => __('Generating...', EWOG_TEXT_DOMAIN),
                'clearing' => __('Clearing...', EWOG_TEXT_DOMAIN),
                'saving' => __('Saving...', EWOG_TEXT_DOMAIN),
                'chooseImage' => __('Choose Image', EWOG_TEXT_DOMAIN),
                'useImage' => __('Use this Image', EWOG_TEXT_DOMAIN),
                'validating' => __('Validating...', EWOG_TEXT_DOMAIN)
            ));
        }
        
        if (file_exists(EWOG_PLUGIN_DIR . 'assets/css/admin.css')) {
            wp_enqueue_style(
                'ewog-admin',
                $css_file,
                array(),
                EWOG_VERSION
            );
        }
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
        
        // Boolean settings
        $booleans = array(
            'disable_title_description', 'enable_facebook', 'enable_twitter', 
            'enable_linkedin', 'enable_pinterest', 'enable_whatsapp', 
            'enable_schema', 'enable_enhanced_schema', 'enable_breadcrumb_schema',
            'enable_organization_schema', 'enable_product_sitemap', 'enable_social_share',
            'cache_meta_tags', 'debug_mode'
        );
        
        foreach ($booleans as $key) {
            $sanitized[$key] = !empty($input[$key]);
        }
        
        // Text fields
        $sanitized['facebook_app_id'] = sanitize_text_field($input['facebook_app_id'] ?? '');
        $sanitized['twitter_username'] = sanitize_text_field($input['twitter_username'] ?? '');
        
        // Number fields
        $sanitized['sitemap_products_per_page'] = intval($input['sitemap_products_per_page'] ?? 500);
        if ($sanitized['sitemap_products_per_page'] < 100) {
            $sanitized['sitemap_products_per_page'] = 100;
        }
        if ($sanitized['sitemap_products_per_page'] > 1000) {
            $sanitized['sitemap_products_per_page'] = 1000;
        }
        
        // Select/text settings
        $valid_image_sizes = array('thumbnail', 'medium', 'large', 'full');
        $sanitized['image_size'] = in_array($input['image_size'] ?? '', $valid_image_sizes) ? 
            $input['image_size'] : 'medium';
            
        $valid_button_styles = array('modern', 'classic', 'minimal');
        $sanitized['share_button_style'] = in_array($input['share_button_style'] ?? '', $valid_button_styles) ? 
            $input['share_button_style'] : 'modern';
            
        $valid_button_positions = array('after_add_to_cart', 'before_add_to_cart', 'after_summary', 'after_tabs');
        $sanitized['share_button_position'] = in_array($input['share_button_position'] ?? '', $valid_button_positions) ? 
            $input['share_button_position'] : 'after_add_to_cart';
        
        // URL fields
        $sanitized['fallback_image'] = esc_url_raw($input['fallback_image'] ?? '');
        
        // Keep other existing settings that might not be in the form
        $existing = get_option('ewog_settings', array());
        $sanitized = array_merge($existing, $sanitized);
        
        return $sanitized;
    }
}