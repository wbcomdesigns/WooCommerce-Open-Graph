<?php
/**
 * Social Share Handler Class - COMPLETE FIXED VERSION
 * Manages social sharing buttons and functionality
 * 
 * @package Woo_Open_Graph
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WOG_Social_Share {
    
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
     * Initialize the class
     */
    private function __construct() {
        $this->settings = get_option('wog_settings', array());
        $this->init_hooks();
    }
    
    /**
     * Set up WordPress hooks
     */
    private function init_hooks() {
        if (!empty($this->settings['enable_social_share'])) {
            $this->add_share_buttons();
        }
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wog_social_share', array($this, 'social_share_shortcode'));
        
        add_action('wp_ajax_wog_track_share', array($this, 'ajax_track_share'));
        add_action('wp_ajax_nopriv_wog_track_share', array($this, 'ajax_track_share'));
        
        // Debug shortcode for testing
        add_shortcode('wog_debug_urls', array($this, 'debug_urls_shortcode'));
    }
    
    /**
     * Add share buttons based on position setting
     */
    private function add_share_buttons() {
        $position = !empty($this->settings['share_button_position']) ? $this->settings['share_button_position'] : 'after_add_to_cart';
        
        switch ($position) {
            case 'before_add_to_cart':
                add_action('woocommerce_before_add_to_cart_button', array($this, 'display_share_buttons'));
                break;
            case 'after_summary':
                add_action('woocommerce_after_single_product_summary', array($this, 'display_share_buttons'), 15);
                break;
            case 'after_tabs':
                add_action('woocommerce_after_single_product_summary', array($this, 'display_share_buttons'), 25);
                break;
            default:
                add_action('woocommerce_after_add_to_cart_button', array($this, 'display_share_buttons'));
                break;
        }
    }
    
    /**
     * Display share buttons on product pages
     */
    public function display_share_buttons() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $this->render_share_buttons($product);
    }
    
    /**
     * Render share buttons HTML - FIXED VERSION
     */
    public function render_share_buttons($product) {
        $style = !empty($this->settings['share_button_style']) ? $this->settings['share_button_style'] : 'modern';
        
        // Get RAW data (no encoding/escaping at this stage)
        $raw_title = get_the_title($product->get_id());
        $raw_url = get_permalink($product->get_id());
        $raw_description = $this->get_clean_product_description($product);
        $raw_image = $this->get_product_image_url($product);
        
        $share_data = array(
            'url' => $raw_url,
            'title' => $raw_title,
            'description' => $raw_description,
            'image' => $raw_image
        );
        
        ?>
        <div class="wog-social-share wog-style-<?php echo esc_attr($style); ?>" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <?php 
            // Debug output if WP_DEBUG is enabled
            if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
                $this->debug_share_urls($product, $share_data);
            }
            ?>
            <div class="wog-share-label">
                <?php esc_html_e('Share this product:', 'woo-open-graph'); ?>
            </div>
            <div class="wog-share-buttons">
                <?php 
                $enabled_platforms = $this->get_enabled_platforms();
                
                foreach ($enabled_platforms as $platform) {
                    $this->render_share_button($platform, $share_data);
                }
                
                $this->render_copy_link_button($share_data);
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get list of enabled social platforms
     */
    private function get_enabled_platforms() {
        $platforms = array();
        
        $available_platforms = array('facebook', 'twitter', 'linkedin', 'pinterest', 'whatsapp', 'email');
        
        foreach ($available_platforms as $platform) {
            if (!empty($this->settings['enable_' . $platform])) {
                $platforms[] = $platform;
            }
        }
        
        return $platforms;
    }
    
    /**
     * Render individual share button - FIXED VERSION
     */
    private function render_share_button($platform, $share_data) {
        $platforms = $this->get_platform_config();
        
        if (!isset($platforms[$platform])) {
            return;
        }
        
        $config = $platforms[$platform];
        $url = $this->build_share_url($platform, $share_data);
        
        // Debug logging if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("WOG Share URL for {$platform}: " . $url);
        }
        
        ?>
        <a href="<?php echo esc_url($url); ?>" 
           class="wog-share-btn wog-share-<?php echo esc_attr($platform); ?>"
           data-platform="<?php echo esc_attr($platform); ?>"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="<?php echo esc_attr(sprintf(__('Share on %s', 'woo-open-graph'), $config['name'])); ?>">
            <?php echo $config['icon']; ?>
            <span class="wog-share-text"><?php echo esc_html($config['name']); ?></span>
        </a>
        <?php
    }
    
    /**
     * Render copy link button
     */
    private function render_copy_link_button($share_data) {
        ?>
        <button type="button" 
                class="wog-share-btn wog-share-copy" 
                data-url="<?php echo esc_attr($share_data['url']); ?>"
                data-platform="copy"
                aria-label="<?php esc_attresc_html_e('Copy product link', 'woo-open-graph'); ?>"
                title="<?php esc_attresc_html_e('Copy link to clipboard', 'woo-open-graph'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
            </svg>
            <span class="wog-share-text"><?php esc_html_e('Copy', 'woo-open-graph'); ?></span>
        </button>
        <?php
    }
    
    /**
     * Build share URL for each platform - COMPLETELY FIXED VERSION
     */
    private function build_share_url($platform, $share_data) {
        // Start with RAW data (no esc_url or sanitize yet to avoid double encoding)
        $raw_url = $share_data['url'];
        $raw_title = $share_data['title'];
        $raw_description = $share_data['description'];
        $raw_image = $share_data['image'];
        
        // Clean up the text data (but don't URL encode yet)
        $clean_title = $this->clean_text_for_sharing($raw_title);
        $clean_description = $this->clean_text_for_sharing($raw_description);
        
        switch ($platform) {
            case 'facebook':
                // Facebook only needs the URL - it will fetch title/description from meta tags
                return 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($raw_url);
                
            case 'twitter':
                $text = $clean_title;
                if (!empty($clean_description) && strlen($clean_title) < 100) {
                    $text .= ' - ' . $clean_description;
                }
                // Limit to Twitter's character limit (minus URL length ~23 chars)
                $text = $this->truncate_text($text, 250);
                return 'https://twitter.com/intent/tweet?url=' . rawurlencode($raw_url) . '&text=' . rawurlencode($text);
                
            case 'linkedin':
                // LinkedIn also fetches from meta tags, just needs URL
                return 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($raw_url);
                
            case 'pinterest':
                $pin_description = !empty($clean_description) ? $clean_description : $clean_title;
                $pin_description = $this->truncate_text($pin_description, 500); // Pinterest limit
                
                $pinterest_url = 'https://pinterest.com/pin/create/button/?url=' . rawurlencode($raw_url);
                if (!empty($raw_image)) {
                    $pinterest_url .= '&media=' . rawurlencode($raw_image);
                }
                $pinterest_url .= '&description=' . rawurlencode($pin_description);
                
                return $pinterest_url;
                
            case 'whatsapp':
                // WhatsApp mobile sharing - keep it simple
                $whatsapp_text = $clean_title . ' ' . $raw_url;
                return 'https://wa.me/?text=' . rawurlencode($whatsapp_text);
                
            case 'email':
                $subject = sprintf(__('Check out: %s', 'woo-open-graph'), $clean_title);
                $body_text = $clean_title;
                
                if (!empty($clean_description)) {
                    $body_text .= "\n\n" . $clean_description;
                }
                
                $body_text .= "\n\n" . __('View product:', 'woo-open-graph') . ' ' . $raw_url;
                
                return 'mailto:?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body_text);
                
            default:
                return esc_url($raw_url);
        }
    }
    
    /**
     * Clean text for social sharing (no URL encoding)
     */
    private function clean_text_for_sharing($text) {
        if (empty($text)) {
            return '';
        }
        
        // Decode any existing HTML entities first
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Strip all HTML tags
        $text = wp_strip_all_tags($text);
        
        // Remove extra whitespace and normalize
        $text = trim(preg_replace('/\s+/', ' ', $text));
        
        // Remove any existing URL encoding (in case it was pre-encoded)
        $text = urldecode($text);
        
        return $text;
    }
    
    /**
     * Helper method to truncate text properly
     */
    private function truncate_text($text, $max_length) {
        if (strlen($text) <= $max_length) {
            return $text;
        }
        
        $truncated = substr($text, 0, $max_length);
        
        // Try to break at word boundary
        $last_space = strrpos($truncated, ' ');
        if ($last_space !== false && $last_space > ($max_length * 0.75)) {
            $truncated = substr($truncated, 0, $last_space);
        }
        
        return $truncated . '...';
    }
    
    /**
     * Get clean product description (no encoding)
     */
    private function get_clean_product_description($product) {
        $description = '';
        
        // Try short description first
        $short_desc = $product->get_short_description();
        if (!empty($short_desc)) {
            $description = $short_desc;
        } else {
            // Fall back to excerpt from long description
            $long_desc = $product->get_description();
            if (!empty($long_desc)) {
                $description = $long_desc;
            }
        }
        
        // Clean up but don't encode
        $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
        $description = wp_strip_all_tags($description);
        $description = trim(preg_replace('/\s+/', ' ', $description));
        
        // Add product price if available
        if ($product->get_price()) {
            $price = $product->get_price();
            $currency = get_woocommerce_currency_symbol();
            $description = trim($description . ' - ' . $currency . $price);
        }
        
        // Limit length for social sharing
        return $this->truncate_text($description, 200);
    }
    
    /**
     * Get product image URL
     */
    private function get_product_image_url($product) {
        if ($product->get_image_id()) {
            $image_data = wp_get_attachment_image_src($product->get_image_id(), 'large');
            return $image_data ? $image_data[0] : '';
        }
        
        return '';
    }
    
    /**
     * Get platform configuration with icons
     */
    private function get_platform_config() {
        return array(
            'facebook' => array(
                'name' => 'Facebook',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'
            ),
            'twitter' => array(
                'name' => 'Twitter',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>'
            ),
            'linkedin' => array(
                'name' => 'LinkedIn',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>'
            ),
            'pinterest' => array(
                'name' => 'Pinterest',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.748-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24c6.624 0 11.99-5.367 11.99-12.013C24.007 5.367 18.641.001.001 12.017z"/></svg>'
            ),
            'whatsapp' => array(
                'name' => 'WhatsApp',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.402 0 .002 5.4.002 12.05c0 2.134.555 4.135 1.527 5.877L0 24l6.186-1.622a11.81 11.81 0 005.864 1.518C18.598 23.896 24 18.496 24 11.85 24 5.4 18.6.001 12.05.001"/></svg>'
            ),
            'email' => array(
                'name' => 'Email',
                'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>'
            )
        );
    }
    
    /**
     * Enqueue scripts and styles for social sharing
     */
    public function enqueue_scripts() {
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_style(
            'wog-social-share',
            WOG_PLUGIN_URL . 'assets/css/social-share.css',
            array(),
            WOG_VERSION
        );
        
        wp_enqueue_script(
            'wog-social-share',
            WOG_PLUGIN_URL . 'assets/js/social-share.js',
            array('jquery'),
            WOG_VERSION,
            true
        );
        
        wp_localize_script('wog-social-share', 'wogShare', array(
            'copied' => __('Link copied!', 'woo-open-graph'),
            'copyFailed' => __('Failed to copy link', 'woo-open-graph'),
            'copyLink' => __('Copy link', 'woo-open-graph'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wog_share_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ));
    }
    
    /**
     * Social share shortcode handler
     */
    public function social_share_shortcode($atts) {
        global $product;
        
        if (!$product) {
            global $post;
            if ($post && $post->post_type === 'product') {
                $product = wc_get_product($post->ID);
            }
        }
        
        if (!$product) {
            return '';
        }
        
        ob_start();
        $this->render_share_buttons($product);
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for tracking share events
     */
    public function ajax_track_share() {
        check_ajax_referer('wog_share_nonce', 'nonce');

        $platform = sanitize_text_field(wp_unslash($_POST['platform'] ?? ''));
        $product_id = intval($_POST['product_id'] ?? 0);
        $url = esc_url_raw(wp_unslash($_POST['url'] ?? ''));
        
        if ($platform && $product_id) {
            do_action('wog_social_share_tracked', $platform, $product_id, $url);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("WOG: Share tracked - Platform: {$platform}, Product: {$product_id}, URL: {$url}");
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Share tracked successfully', 'woo-open-graph')
        ));
    }
    
    /**
     * Debug URLs shortcode for testing
     */
    public function debug_urls_shortcode($atts) {
        if (!current_user_can('manage_options')) {
            return '<p style="color: red;">Access denied - admin only</p>';
        }
        
        global $product;
        if (!$product) {
            global $post;
            if ($post && $post->post_type === 'product') {
                $product = wc_get_product($post->ID);
            }
        }
        
        if (!$product) {
            return '<p style="color: red;">No product found on this page</p>';
        }
        
        // Test data
        $test_data = array(
            'url' => get_permalink($product->get_id()),
            'title' => get_the_title($product->get_id()),
            'description' => $this->get_clean_product_description($product),
            'image' => $this->get_product_image_url($product)
        );
        
        $output = '<div style="background: #f9f9f9; padding: 20px; margin: 20px 0; border: 1px solid #ddd; font-family: monospace;">';
        $output .= '<h3>WOG Share URL Debug</h3>';
        $output .= '<p><strong>Product:</strong> ' . esc_html($test_data['title']) . '</p>';
        $output .= '<p><strong>Raw URL:</strong> ' . esc_html($test_data['url']) . '</p>';
        $output .= '<p><strong>Clean Title:</strong> ' . esc_html($this->clean_text_for_sharing($test_data['title'])) . '</p>';
        $output .= '<p><strong>Clean Description:</strong> ' . esc_html($test_data['description']) . '</p>';
        
        $platforms = array('facebook', 'twitter', 'linkedin', 'pinterest', 'whatsapp', 'email');
        
        foreach ($platforms as $platform) {
            $share_url = $this->build_share_url($platform, $test_data);
            $output .= '<p><strong>' . ucfirst($platform) . ':</strong><br>';
            $output .= '<code style="word-break: break-all; background: #fff; padding: 5px; display: block; margin: 5px 0;">' . esc_html($share_url) . '</code></p>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Debug method for share URL generation
     */
    private function debug_share_urls($product, $share_data) {
        if (!defined('WP_DEBUG') || !WP_DEBUG || !current_user_can('manage_options')) {
            return;
        }
        
        echo "<!-- WOG DEBUG: Share URL Generation -->\n";
        echo "<!-- Raw Title: " . esc_html($share_data['title']) . " -->\n";
        echo "<!-- Clean Title: " . esc_html($this->clean_text_for_sharing($share_data['title'])) . " -->\n";
        echo "<!-- Raw URL: " . esc_html($share_data['url']) . " -->\n";
        echo "<!-- Description: " . esc_html($share_data['description']) . " -->\n";
        
        $platforms = array('facebook', 'twitter', 'linkedin', 'pinterest', 'whatsapp');
        
        foreach ($platforms as $platform) {
            $url = $this->build_share_url($platform, $share_data);
            echo "<!-- {$platform} URL: " . esc_html($url) . " -->\n";
        }
        echo "<!-- END WOG DEBUG -->\n";
    }
}