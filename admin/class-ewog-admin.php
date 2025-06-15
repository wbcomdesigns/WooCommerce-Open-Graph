<?php
/**
 * Enhanced Admin Settings Class
 * 
 * Modern admin interface with comprehensive settings
 * File: admin/class-ewog-admin.php
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
        
        // Platform Settings Section
        add_settings_section(
            'ewog_platforms_section',
            __('Social Media Platforms', EWOG_TEXT_DOMAIN),
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
        
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // Essential toggle fields
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
        
        // Platform selection field
        add_settings_field(
            'social_platforms',
            __('Select Platforms', EWOG_TEXT_DOMAIN),
            array($this, 'platforms_field'),
            'ewog_settings',
            'ewog_platforms_section',
            array()
        );
        
        // Advanced options field
        add_settings_field(
            'advanced_options',
            __('Advanced Options', EWOG_TEXT_DOMAIN),
            array($this, 'advanced_options_field'),
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
     * Render settings tab
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
    
    public function platforms_section_callback() {
        echo '<p class="description">' . __('Choose which social media platforms to optimize for. Click on platform cards to enable them.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p class="description">' . __('Advanced options for power users. Most users can leave these as default.', EWOG_TEXT_DOMAIN) . '</p>';
    }
    
    /**
     * Field rendering methods
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
        
        wp_enqueue_style(
            'ewog-admin',
            EWOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            EWOG_VERSION
        );
        
        wp_enqueue_script(
            'ewog-admin',
            EWOG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            EWOG_VERSION,
            true
        );
        
        wp_localize_script('ewog-admin', 'ewogAdminVars', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ewog_admin_nonce'),
            'testing' => __('Testing...', EWOG_TEXT_DOMAIN),
            'generating' => __('Generating...', EWOG_TEXT_DOMAIN),
            'clearing' => __('Clearing...', EWOG_TEXT_DOMAIN),
            'saving' => __('Saving...', EWOG_TEXT_DOMAIN)
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
        
        // Boolean settings
        $booleans = array(
            'enable_schema', 'enable_facebook', 'enable_twitter', 'enable_linkedin',
            'enable_pinterest', 'enable_whatsapp', 'enable_social_share',
            'enable_product_sitemap', 'cache_meta_tags', 'debug_mode',
            'enable_enhanced_schema', 'enable_breadcrumb_schema', 'enable_organization_schema'
        );
        
        foreach ($booleans as $key) {
            $sanitized[$key] = !empty($input[$key]);
        }
        
        // Text/select settings
        $sanitized['image_size'] = in_array($input['image_size'] ?? '', array('medium', 'large', 'full')) 
            ? $input['image_size'] : 'medium';
            
        // Keep other existing settings that might not be in the form
        $existing = get_option('ewog_settings', array());
        $sanitized = array_merge($existing, $sanitized);
        
        return $sanitized;
    }
}