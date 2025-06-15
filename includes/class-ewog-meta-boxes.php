<?php
/**
 * Enhanced WooCommerce Open Graph Meta Boxes
 * 
 * Provides admin interface for per-product Open Graph control
 */

if (!defined('ABSPATH')) {
    exit;
}

class EWOG_Meta_Boxes {
    
    private static $instance = null;
    private $settings;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->settings = get_option('ewog_settings', array());
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Add meta boxes to product edit screen
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        
        // Save meta box data
        add_action('save_post', array($this, 'save_product_meta_boxes'));
        
        // Add bulk edit support
        add_action('woocommerce_product_bulk_edit_end', array($this, 'bulk_edit_fields'));
        add_action('woocommerce_product_bulk_edit_save', array($this, 'bulk_edit_save'));
        
        // Add quick edit support
        add_action('woocommerce_product_quick_edit_end', array($this, 'quick_edit_fields'));
        add_action('woocommerce_product_quick_edit_save', array($this, 'quick_edit_save'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add admin notices for validation
        add_action('admin_notices', array($this, 'show_validation_notices'));
        
        // AJAX handlers for dynamic features
        add_action('wp_ajax_ewog_generate_og_preview', array($this, 'ajax_generate_og_preview'));
        add_action('wp_ajax_ewog_validate_og_image', array($this, 'ajax_validate_og_image'));
        add_action('wp_ajax_ewog_test_social_share', array($this, 'ajax_test_social_share'));
    }
    
    /**
     * Add meta boxes to product edit screen
     */
    public function add_product_meta_boxes() {
        add_meta_box(
            'ewog_product_settings',
            __('Enhanced Open Graph Settings', EWOG_TEXT_DOMAIN),
            array($this, 'render_product_meta_box'),
            'product',
            'normal',
            'high'
        );
        
        add_meta_box(
            'ewog_social_preview',
            __('Social Media Preview', EWOG_TEXT_DOMAIN),
            array($this, 'render_social_preview_meta_box'),
            'product',
            'side',
            'default'
        );
        
        add_meta_box(
            'ewog_advanced_settings',
            __('Advanced Open Graph', EWOG_TEXT_DOMAIN),
            array($this, 'render_advanced_meta_box'),
            'product',
            'normal',
            'low'
        );
    }
    
    /**
     * Render main Open Graph settings meta box
     */
    public function render_product_meta_box($post) {
        wp_nonce_field('ewog_product_meta_box', 'ewog_meta_box_nonce');
        
        // Get current values
        $og_title = get_post_meta($post->ID, '_ewog_og_title', true);
        $og_description = get_post_meta($post->ID, '_ewog_og_description', true);
        $og_image = get_post_meta($post->ID, '_ewog_og_image', true);
        $og_type = get_post_meta($post->ID, '_ewog_og_type', true) ?: 'product';
        $disable_og = get_post_meta($post->ID, '_ewog_disable_og', true);
        $custom_tags = get_post_meta($post->ID, '_ewog_custom_tags', true) ?: array();
        
        ?>
        <div class="ewog-meta-box-container">
            <table class="form-table ewog-meta-table">
                <tr>
                    <td colspan="2">
                        <label class="ewog-toggle">
                            <input type="checkbox" name="ewog_disable_og" value="1" <?php checked($disable_og, '1'); ?> />
                            <span class="ewog-toggle-text"><?php _e('Disable Open Graph for this product', EWOG_TEXT_DOMAIN); ?></span>
                        </label>
                        <p class="description"><?php _e('Check this to completely disable Open Graph meta tags for this specific product.', EWOG_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ewog_og_title"><?php _e('Open Graph Title', EWOG_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="ewog_og_title" 
                               name="ewog_og_title" 
                               value="<?php echo esc_attr($og_title); ?>" 
                               class="large-text ewog-og-title" 
                               maxlength="60"
                               placeholder="<?php esc_attr_e('Leave empty to auto-generate from product name', EWOG_TEXT_DOMAIN); ?>" />
                        <p class="description">
                            <?php _e('Custom title for social sharing. Recommended: 40-60 characters.', EWOG_TEXT_DOMAIN); ?>
                            <span class="ewog-char-count" data-target="ewog_og_title">0/60</span>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ewog_og_description"><?php _e('Open Graph Description', EWOG_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <textarea id="ewog_og_description" 
                                  name="ewog_og_description" 
                                  rows="3" 
                                  class="large-text ewog-og-description" 
                                  maxlength="155"
                                  placeholder="<?php esc_attr_e('Leave empty to auto-generate from product description', EWOG_TEXT_DOMAIN); ?>"><?php echo esc_textarea($og_description); ?></textarea>
                        <p class="description">
                            <?php _e('Custom description for social sharing. Recommended: 120-155 characters.', EWOG_TEXT_DOMAIN); ?>
                            <span class="ewog-char-count" data-target="ewog_og_description">0/155</span>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ewog_og_image"><?php _e('Open Graph Image', EWOG_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <div class="ewog-image-field">
                            <input type="hidden" 
                                   id="ewog_og_image" 
                                   name="ewog_og_image" 
                                   value="<?php echo esc_attr($og_image); ?>" />
                            
                            <div class="ewog-image-preview">
                                <?php if ($og_image): ?>
                                    <img src="<?php echo esc_url($og_image); ?>" style="max-width: 300px; height: auto;" />
                                <?php endif; ?>
                            </div>
                            
                            <p class="ewog-image-controls">
                                <button type="button" class="button ewog-upload-image">
                                    <?php _e('Choose Image', EWOG_TEXT_DOMAIN); ?>
                                </button>
                                <button type="button" class="button ewog-remove-image" <?php echo !$og_image ? 'style="display:none;"' : ''; ?>>
                                    <?php _e('Remove Image', EWOG_TEXT_DOMAIN); ?>
                                </button>
                                <button type="button" class="button ewog-validate-image" <?php echo !$og_image ? 'style="display:none;"' : ''; ?>>
                                    <?php _e('Validate Image', EWOG_TEXT_DOMAIN); ?>
                                </button>
                            </p>
                            
                            <p class="description">
                                <?php _e('Custom image for social sharing. Recommended: 1200x630px. Leave empty to use product featured image.', EWOG_TEXT_DOMAIN); ?>
                            </p>
                            
                            <div class="ewog-image-validation-result"></div>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ewog_og_type"><?php _e('Open Graph Type', EWOG_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <select id="ewog_og_type" name="ewog_og_type" class="regular-text">
                            <option value="product" <?php selected($og_type, 'product'); ?>><?php _e('Product', EWOG_TEXT_DOMAIN); ?></option>
                            <option value="article" <?php selected($og_type, 'article'); ?>><?php _e('Article', EWOG_TEXT_DOMAIN); ?></option>
                            <option value="website" <?php selected($og_type, 'website'); ?>><?php _e('Website', EWOG_TEXT_DOMAIN); ?></option>
                        </select>
                        <p class="description"><?php _e('The type of content. "Product" is recommended for WooCommerce products.', EWOG_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            </table>
            
            <div class="ewog-custom-tags-section">
                <h4><?php _e('Custom Meta Tags', EWOG_TEXT_DOMAIN); ?></h4>
                <div class="ewog-custom-tags-container">
                    <?php $this->render_custom_tags_fields($custom_tags); ?>
                </div>
                <button type="button" class="button ewog-add-custom-tag"><?php _e('Add Custom Tag', EWOG_TEXT_DOMAIN); ?></button>
            </div>
        </div>
        
        <style>
        .ewog-meta-box-container { padding: 10px 0; }
        .ewog-meta-table th { width: 200px; vertical-align: top; padding-top: 15px; }
        .ewog-meta-table td { padding: 10px 0; }
        .ewog-toggle { display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .ewog-char-count { font-weight: bold; color: #666; float: right; }
        .ewog-char-count.warning { color: #d63384; }
        .ewog-image-field { max-width: 400px; }
        .ewog-image-preview img { border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; }
        .ewog-image-controls { margin: 10px 0; }
        .ewog-custom-tags-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
        .ewog-custom-tag-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .ewog-custom-tag-row input[type="text"] { flex: 1; }
        .ewog-custom-tag-row .button { flex-shrink: 0; }
        </style>
        <?php
    }
    
    /**
     * Render custom tags fields
     */
    private function render_custom_tags_fields($custom_tags) {
        if (empty($custom_tags)) {
            echo '<p class="ewog-no-custom-tags">' . __('No custom tags added yet.', EWOG_TEXT_DOMAIN) . '</p>';
            return;
        }
        
        foreach ($custom_tags as $index => $tag) {
            ?>
            <div class="ewog-custom-tag-row">
                <input type="text" 
                       name="ewog_custom_tags[<?php echo $index; ?>][property]" 
                       value="<?php echo esc_attr($tag['property'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Property (e.g., og:brand)', EWOG_TEXT_DOMAIN); ?>" />
                <input type="text" 
                       name="ewog_custom_tags[<?php echo $index; ?>][content]" 
                       value="<?php echo esc_attr($tag['content'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Content', EWOG_TEXT_DOMAIN); ?>" />
                <button type="button" class="button ewog-remove-custom-tag"><?php _e('Remove', EWOG_TEXT_DOMAIN); ?></button>
            </div>
            <?php
        }
    }
    
    /**
     * Render social media preview meta box
     */
    public function render_social_preview_meta_box($post) {
        ?>
        <div class="ewog-social-preview-container">
            <div class="ewog-preview-tabs">
                <button type="button" class="ewog-preview-tab active" data-platform="facebook">
                    <?php _e('Facebook', EWOG_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="ewog-preview-tab" data-platform="twitter">
                    <?php _e('Twitter', EWOG_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="ewog-preview-tab" data-platform="linkedin">
                    <?php _e('LinkedIn', EWOG_TEXT_DOMAIN); ?>
                </button>
            </div>
            
            <div class="ewog-preview-content">
                <div class="ewog-preview-loading" style="display: none;">
                    <p><?php _e('Generating preview...', EWOG_TEXT_DOMAIN); ?></p>
                </div>
                
                <div class="ewog-preview-result" id="ewog-preview-facebook">
                    <!-- Facebook preview will be loaded here -->
                </div>
                
                <div class="ewog-preview-result" id="ewog-preview-twitter" style="display: none;">
                    <!-- Twitter preview will be loaded here -->
                </div>
                
                <div class="ewog-preview-result" id="ewog-preview-linkedin" style="display: none;">
                    <!-- LinkedIn preview will be loaded here -->
                </div>
            </div>
            
            <div class="ewog-preview-actions">
                <button type="button" class="button button-primary ewog-refresh-preview">
                    <?php _e('Refresh Preview', EWOG_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button ewog-test-sharing">
                    <?php _e('Test Sharing', EWOG_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
        
        <style>
        .ewog-social-preview-container { padding: 10px; }
        .ewog-preview-tabs { display: flex; margin-bottom: 15px; border-bottom: 1px solid #ddd; }
        .ewog-preview-tab { 
            padding: 8px 15px; 
            border: none; 
            background: none; 
            cursor: pointer; 
            border-bottom: 2px solid transparent;
        }
        .ewog-preview-tab.active { 
            border-bottom-color: #0073aa; 
            font-weight: 600; 
        }
        .ewog-preview-content { min-height: 200px; margin-bottom: 15px; }
        .ewog-preview-actions { text-align: center; }
        .ewog-preview-actions .button { margin: 0 5px; }
        </style>
        <?php
    }
    
    /**
     * Render advanced settings meta box
     */
    public function render_advanced_meta_box($post) {
        // Get advanced settings
        $twitter_card = get_post_meta($post->ID, '_ewog_twitter_card', true) ?: 'summary_large_image';
        $fb_app_id = get_post_meta($post->ID, '_ewog_fb_app_id', true);
        $article_author = get_post_meta($post->ID, '_ewog_article_author', true);
        $article_section = get_post_meta($post->ID, '_ewog_article_section', true);
        $product_availability = get_post_meta($post->ID, '_ewog_product_availability', true);
        $product_condition = get_post_meta($post->ID, '_ewog_product_condition', true) ?: 'new';
        $product_brand = get_post_meta($post->ID, '_ewog_product_brand', true);
        
        ?>
        <div class="ewog-advanced-container">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ewog_twitter_card"><?php _e('Twitter Card Type', EWOG_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <select id="ewog_twitter_card" name="ewog_twitter_card">
                            <option value="summary" <?php selected($twitter_card, 'summary'); ?>><?php _e('Summary', EWOG_TEXT_DOMAIN); ?></option>
                            <option value="summary_large_image" <?php selected($twitter_card, 'summary_large_image'); ?>><?php _e('Summary Large Image', EWOG_TEXT_DOMAIN); ?></option>
                            <option value="product" <?php selected($twitter_card, 'product'); ?>><?php _e('Product', EWOG_TEXT_DOMAIN); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ewog_fb_app_id"><?php _e('Facebook App ID', EWOG_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="ewog_fb_app_id" 
                               name="ewog_fb_app_id" 
                               value="<?php echo esc_attr($fb_app_id); ?>" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e('Override global Facebook App ID', EWOG_TEXT_DOMAIN); ?>" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ewog_product_brand"><?php _e('Product Brand', EWOG_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="ewog_product_brand" 
                               name="ewog_product_brand" 
                               value="<?php echo esc_attr($product_brand); ?>" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e('Override auto-detected brand', EWOG_TEXT_DOMAIN); ?>" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ewog_product_condition"><?php _e('Product Condition', EWOG_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <select id="ewog_product_condition" name="ewog_product_condition">
                            <option value="new" <?php selected($product_condition, 'new'); ?>><?php _e('New', EWOG_TEXT_DOMAIN); ?></option>
                            <option value="refurbished" <?php selected($product_condition, 'refurbished'); ?>><?php _e('Refurbished', EWOG_TEXT_DOMAIN); ?></option>
                            <option value="used" <?php selected($product_condition, 'used'); ?>><?php _e('Used', EWOG_TEXT_DOMAIN); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ewog_product_availability"><?php _e('Availability Override', EWOG_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <select id="ewog_product_availability" name="ewog_product_availability">
                            <option value=""><?php _e('Auto-detect from stock status', EWOG_TEXT_DOMAIN); ?></option>
                            <option value="in stock" <?php selected($product_availability, 'in stock'); ?>><?php _e('In Stock', EWOG_TEXT_DOMAIN); ?></option>
                            <option value="out of stock" <?php selected($product_availability, 'out of stock'); ?>><?php _e('Out of Stock', EWOG_TEXT_DOMAIN); ?></option>
                            <option value="preorder" <?php selected($product_availability, 'preorder'); ?>><?php _e('Pre-order', EWOG_TEXT_DOMAIN); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_product_meta_boxes($post_id) {
        // Security checks
        if (!isset($_POST['ewog_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['ewog_meta_box_nonce'], 'ewog_product_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Save basic Open Graph fields
        $fields = array(
            'ewog_disable_og' => '_ewog_disable_og',
            'ewog_og_title' => '_ewog_og_title',
            'ewog_og_description' => '_ewog_og_description',
            'ewog_og_image' => '_ewog_og_image',
            'ewog_og_type' => '_ewog_og_type',
            'ewog_twitter_card' => '_ewog_twitter_card',
            'ewog_fb_app_id' => '_ewog_fb_app_id',
            'ewog_product_brand' => '_ewog_product_brand',
            'ewog_product_condition' => '_ewog_product_condition',
            'ewog_product_availability' => '_ewog_product_availability'
        );
        
        foreach ($fields as $field => $meta_key) {
            $value = sanitize_text_field($_POST[$field] ?? '');
            
            if ($field === 'ewog_og_description') {
                $value = sanitize_textarea_field($_POST[$field] ?? '');
            }
            
            if ($field === 'ewog_og_image') {
                $value = esc_url_raw($_POST[$field] ?? '');
            }
            
            if (empty($value)) {
                delete_post_meta($post_id, $meta_key);
            } else {
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        // Save custom tags
        $custom_tags = array();
        if (isset($_POST['ewog_custom_tags']) && is_array($_POST['ewog_custom_tags'])) {
            foreach ($_POST['ewog_custom_tags'] as $tag) {
                if (!empty($tag['property']) && !empty($tag['content'])) {
                    $custom_tags[] = array(
                        'property' => sanitize_text_field($tag['property']),
                        'content' => sanitize_text_field($tag['content'])
                    );
                }
            }
        }
        
        if (empty($custom_tags)) {
            delete_post_meta($post_id, '_ewog_custom_tags');
        } else {
            update_post_meta($post_id, '_ewog_custom_tags', $custom_tags);
        }
        
        // Clear any cached meta tags for this product
        wp_cache_delete("ewog_product_meta_{$post_id}", 'ewog');
        
        do_action('ewog_product_meta_saved', $post_id, $_POST);
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type !== 'product' || !in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_script(
            'ewog-meta-boxes',
            EWOG_PLUGIN_URL . 'assets/js/meta-boxes.js',
            array('jquery', 'wp-media-utils'),
            EWOG_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ewog-meta-boxes',
            EWOG_PLUGIN_URL . 'assets/css/meta-boxes.css',
            array(),
            EWOG_VERSION
        );
        
        wp_localize_script('ewog-meta-boxes', 'ewogMetaBoxes', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ewog_meta_boxes_nonce'),
            'strings' => array(
                'chooseImage' => __('Choose Image', EWOG_TEXT_DOMAIN),
                'useImage' => __('Use this Image', EWOG_TEXT_DOMAIN),
                'removeImage' => __('Remove Image', EWOG_TEXT_DOMAIN),
                'validating' => __('Validating...', EWOG_TEXT_DOMAIN),
                'validImage' => __('✓ Valid Open Graph image', EWOG_TEXT_DOMAIN),
                'invalidImage' => __('⚠ Image may not be optimal for social sharing', EWOG_TEXT_DOMAIN),
                'refreshing' => __('Refreshing preview...', EWOG_TEXT_DOMAIN),
                'testing' => __('Testing...', EWOG_TEXT_DOMAIN)
            )
        ));
    }
    
    /**
     * AJAX: Generate Open Graph preview
     */
    public function ajax_generate_og_preview() {
        check_ajax_referer('ewog_meta_boxes_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $platform = sanitize_text_field($_POST['platform'] ?? 'facebook');
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Unauthorized');
        }
        
        // Get current form data
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $image = esc_url_raw($_POST['image'] ?? '');
        
        // Generate preview HTML
        $preview_html = $this->generate_preview_html($platform, array(
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'url' => get_permalink($post_id)
        ));
        
        wp_send_json_success(array(
            'html' => $preview_html,
            'platform' => $platform
        ));
    }
    
    /**
     * AJAX: Validate Open Graph image
     */
    public function ajax_validate_og_image() {
        check_ajax_referer('ewog_meta_boxes_nonce', 'nonce');
        
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        
        if (!$image_url) {
            wp_send_json_error('No image URL provided');
        }
        
        $validation_result = $this->validate_og_image($image_url);
        
        wp_send_json_success($validation_result);
    }
    
    /**
     * Generate preview HTML for different platforms
     */
    private function generate_preview_html($platform, $data) {
        $title = $data['title'] ?: 'Product Title';
        $description = $data['description'] ?: 'Product description...';
        $image = $data['image'] ?: wc_placeholder_img_src();
        $url = $data['url'];
        $domain = parse_url($url, PHP_URL_HOST);
        
        switch ($platform) {
            case 'facebook':
                return $this->generate_facebook_preview($title, $description, $image, $domain);
            case 'twitter':
                return $this->generate_twitter_preview($title, $description, $image, $domain);
            case 'linkedin':
                return $this->generate_linkedin_preview($title, $description, $image, $domain);
            default:
                return '<p>Preview not available for this platform.</p>';
        }
    }
    
    /**
     * Generate Facebook preview HTML
     */
    private function generate_facebook_preview($title, $description, $image, $domain) {
        return sprintf('
            <div class="ewog-facebook-preview">
                <div class="ewog-preview-header">
                    <strong>%s</strong>
                    <span class="ewog-preview-domain">%s</span>
                </div>
                <div class="ewog-preview-image">
                    <img src="%s" alt="" />
                </div>
                <div class="ewog-preview-content">
                    <h4>%s</h4>
                    <p>%s</p>
                </div>
            </div>',
            esc_html($domain),
            esc_html($domain),
            esc_url($image),
            esc_html($title),
            esc_html(wp_trim_words($description, 20))
        );
    }
    
    /**
     * Generate Twitter preview HTML
     */
    private function generate_twitter_preview($title, $description, $image, $domain) {
        return sprintf('
            <div class="ewog-twitter-preview">
                <div class="ewog-preview-image">
                    <img src="%s" alt="" />
                </div>
                <div class="ewog-preview-content">
                    <h4>%s</h4>
                    <p>%s</p>
                    <span class="ewog-preview-domain">%s</span>
                </div>
            </div>',
            esc_url($image),
            esc_html($title),
            esc_html(wp_trim_words($description, 15)),
            esc_html($domain)
        );
    }
    
    /**
     * Generate LinkedIn preview HTML
     */
    private function generate_linkedin_preview($title, $description, $image, $domain) {
        return sprintf('
            <div class="ewog-linkedin-preview">
                <div class="ewog-preview-image">
                    <img src="%s" alt="" />
                </div>
                <div class="ewog-preview-content">
                    <h4>%s</h4>
                    <p>%s</p>
                    <span class="ewog-preview-domain">%s</span>
                </div>
            </div>',
            esc_url($image),
            esc_html($title),
            esc_html(wp_trim_words($description, 25)),
            esc_html($domain)
        );
    }
    
    /**
     * Validate Open Graph image
     */
    private function validate_og_image($image_url) {
        $response = wp_remote_head($image_url);
        
        if (is_wp_error($response)) {
            return array(
                'valid' => false,
                'message' => 'Could not access image URL',
                'details' => array()
            );
        }
        
        $headers = wp_remote_retrieve_headers($response);
        $content_type = $headers['content-type'] ?? '';
        $content_length = intval($headers['content-length'] ?? 0);
        
        $issues = array();
        $warnings = array();
        
        // Check content type
        if (!str_starts_with($content_type, 'image/')) {
            $issues[] = 'URL does not point to an image';
        }
        
        // Check file size (recommend < 5MB for social platforms)
        if ($content_length > 5 * 1024 * 1024) {
            $warnings[] = 'Image is very large (>5MB), may load slowly on social platforms';
        }
        
        // Try to get image dimensions
        $image_info = @getimagesize($image_url);
        if ($image_info) {
            $width = $image_info[0];
            $height = $image_info[1];
            
            // Check recommended dimensions for Open Graph (1200x630)
            $ratio = $width / $height;
            $recommended_ratio = 1200 / 630; // ~1.91
            
            if (abs($ratio - $recommended_ratio) > 0.2) {
                $warnings[] = sprintf('Aspect ratio (%.2f:1) differs from recommended 1.91:1', $ratio);
            }
            
            if ($width < 600 || $height < 315) {
                $warnings[] = 'Image is smaller than recommended minimum (600x315)';
            }
            
            if ($width < 200 || $height < 200) {
                $issues[] = 'Image is too small for social platforms';
            }
        }
        
        return array(
            'valid' => empty($issues),
            'message' => empty($issues) ? 
                (empty($warnings) ? 'Image looks good!' : 'Image is valid with minor recommendations') :
                'Image has issues that should be addressed',
            'issues' => $issues,
            'warnings' => $warnings,
            'details' => array(
                'content_type' => $content_type,
                'file_size' => size_format($content_length),
                'dimensions' => $image_info ? "{$image_info[0]}x{$image_info[1]}" : 'Unknown'
            )
        );
    }
    
    /**
     * Show validation notices
     */
    public function show_validation_notices() {
        global $post_type, $pagenow;
        
        if ($post_type !== 'product' || !in_array($pagenow, array('post.php', 'post-new.php'))) {
            return;
        }
        
        // Check for validation issues
        if (isset($_GET['ewog_validation']) && $_GET['ewog_validation'] === 'error') {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Enhanced Open Graph:</strong> <?php echo esc_html($_GET['ewog_message'] ?? 'Validation error occurred'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Bulk edit fields
     */
    public function bulk_edit_fields() {
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php _e('Open Graph', EWOG_TEXT_DOMAIN); ?></span>
                    <select name="ewog_bulk_action">
                        <option value=""><?php _e('— No Change —', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="enable"><?php _e('Enable Open Graph', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="disable"><?php _e('Disable Open Graph', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="reset"><?php _e('Reset to Default', EWOG_TEXT_DOMAIN); ?></option>
                    </select>
                </label>
            </div>
        </fieldset>
        <?php
    }
    
    /**
     * Save bulk edit
     */
    public function bulk_edit_save($product) {
        $action = sanitize_text_field($_REQUEST['ewog_bulk_action'] ?? '');
        
        if (empty($action) || !$product instanceof WC_Product) {
            return;
        }
        
        switch ($action) {
            case 'enable':
                delete_post_meta($product->get_id(), '_ewog_disable_og');
                break;
            case 'disable':
                update_post_meta($product->get_id(), '_ewog_disable_og', '1');
                break;
            case 'reset':
                $meta_keys = array(
                    '_ewog_disable_og', '_ewog_og_title', '_ewog_og_description',
                    '_ewog_og_image', '_ewog_og_type', '_ewog_custom_tags'
                );
                foreach ($meta_keys as $key) {
                    delete_post_meta($product->get_id(), $key);
                }
                break;
        }
        
        wp_cache_delete("ewog_product_meta_{$product->get_id()}", 'ewog');
    }
    
    /**
     * Quick edit fields
     */
    public function quick_edit_fields() {
        ?>
        <fieldset class="inline-edit-col-left">
            <div class="inline-edit-col">
                <label class="alignleft">
                    <input type="checkbox" name="ewog_disable_og" value="1" />
                    <span class="checkbox-title"><?php _e('Disable Open Graph', EWOG_TEXT_DOMAIN); ?></span>
                </label>
            </div>
        </fieldset>
        <?php
    }
    
    /**
     * Save quick edit
     */
    public function quick_edit_save($product) {
        if (!$product instanceof WC_Product) {
            return;
        }
        
        $disable_og = isset($_REQUEST['ewog_disable_og']) ? '1' : '';
        
        if ($disable_og) {
            update_post_meta($product->get_id(), '_ewog_disable_og', '1');
        } else {
            delete_post_meta($product->get_id(), '_ewog_disable_og');
        }
        
        wp_cache_delete("ewog_product_meta_{$product->get_id()}", 'ewog');
    }
}