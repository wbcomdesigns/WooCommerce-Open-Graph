<?php
/**
 * Ultra Modern Enhanced WooCommerce Open Graph Meta Boxes - COMPLETE REPLACEMENT
 * 
 * This completely replaces the basic meta boxes with an ultra-modern interface featuring:
 * - Real-time preview updates with platform switching
 * - Smart default content generation with AI optimization
 * - Modern responsive design with smooth animations
 * - Character counters with visual feedback and validation
 * - Auto-suggestions and intelligent tag recommendations
 * - Advanced image handling with analysis and optimization
 * - Multiple platform previews (Facebook, Twitter, LinkedIn)
 * - Tabbed interface for better organization
 * - Accessibility and keyboard navigation support
 * 
 * @package Enhanced_Woo_Open_Graph
 * @version 2.0.0
 * @author Wbcom Designs
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
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        
        // Save meta box data
        add_action('save_post', array($this, 'save_product_meta_boxes'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_ewog_generate_og_preview', array($this, 'ajax_generate_og_preview'));
        add_action('wp_ajax_ewog_validate_og_image', array($this, 'ajax_validate_og_image'));
        add_action('wp_ajax_ewog_get_default_content', array($this, 'ajax_get_default_content'));
        add_action('wp_ajax_ewog_analyze_image', array($this, 'ajax_analyze_image'));
        add_action('wp_ajax_ewog_suggest_tags', array($this, 'ajax_suggest_tags'));
        
        // Add product list columns
        add_filter('manage_product_posts_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_product_columns'), 10, 2);
    }
    
    /**
     * Add meta boxes with modern layout
     */
    public function add_product_meta_boxes() {
        // Main Open Graph meta box - full width for better UX
        add_meta_box(
            'ewog_main_settings',
            '<span class="dashicons dashicons-share"></span> ' . __('Social Media Optimization', EWOG_TEXT_DOMAIN),
            array($this, 'render_main_meta_box'),
            'product',
            'normal',
            'high'
        );
        
        // Live preview sidebar
        add_meta_box(
            'ewog_live_preview',
            '<span class="dashicons dashicons-visibility"></span> ' . __('Live Preview', EWOG_TEXT_DOMAIN),
            array($this, 'render_preview_meta_box'),
            'product',
            'side',
            'high'
        );
        
        // Quick actions
        add_meta_box(
            'ewog_quick_actions',
            '<span class="dashicons dashicons-admin-tools"></span> ' . __('Quick Actions', EWOG_TEXT_DOMAIN),
            array($this, 'render_actions_meta_box'),
            'product',
            'side',
            'default'
        );
    }
    
    /**
     * Render ultra modern main meta box
     */
    public function render_main_meta_box($post) {
        wp_nonce_field('ewog_product_meta_box', 'ewog_meta_box_nonce');
        
        // Get current values with smart defaults
        $og_title = get_post_meta($post->ID, '_ewog_og_title', true);
        $og_description = get_post_meta($post->ID, '_ewog_og_description', true);
        $og_image = get_post_meta($post->ID, '_ewog_og_image', true);
        $og_type = get_post_meta($post->ID, '_ewog_og_type', true) ?: 'product';
        $disable_og = get_post_meta($post->ID, '_ewog_disable_og', true);
        $custom_tags = get_post_meta($post->ID, '_ewog_custom_tags', true) ?: array();
        
        // Get smart defaults
        $defaults = $this->get_smart_defaults($post);
        
        ?>
        <div class="ewog-modern-metabox">
            <!-- Header with toggle -->
            <div class="ewog-metabox-header">
                <div class="ewog-toggle-container">
                    <label class="ewog-modern-toggle">
                        <input type="checkbox" name="ewog_disable_og" value="1" <?php checked($disable_og, '1'); ?> />
                        <span class="ewog-toggle-slider"></span>
                        <span class="ewog-toggle-label">
                            <strong><?php _e('Enable Social Media Optimization', EWOG_TEXT_DOMAIN); ?></strong>
                            <small><?php _e('Automatically generate and optimize social media previews for this product', EWOG_TEXT_DOMAIN); ?></small>
                        </span>
                    </label>
                </div>
            </div>
            
            <div class="ewog-metabox-content" id="ewog-metabox-content">
                <!-- Tabbed Interface -->
                <div class="ewog-tabs-container">
                    <nav class="ewog-tabs-nav">
                        <button type="button" class="ewog-tab-btn active" data-tab="basic">
                            <span class="dashicons dashicons-format-aside"></span>
                            <?php _e('Basic Settings', EWOG_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" class="ewog-tab-btn" data-tab="advanced">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Advanced', EWOG_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" class="ewog-tab-btn" data-tab="custom">
                            <span class="dashicons dashicons-tag"></span>
                            <?php _e('Custom Tags', EWOG_TEXT_DOMAIN); ?>
                        </button>
                    </nav>
                    
                    <!-- Basic Settings Tab -->
                    <div class="ewog-tab-content active" id="ewog-tab-basic">
                        <div class="ewog-form-grid">
                            <!-- Title Field -->
                            <div class="ewog-field-group">
                                <label for="ewog_og_title" class="ewog-field-label">
                                    <?php _e('Social Media Title', EWOG_TEXT_DOMAIN); ?>
                                    <span class="ewog-field-required">*</span>
                                </label>
                                <div class="ewog-field-wrapper">
                                    <input type="text" 
                                           id="ewog_og_title" 
                                           name="ewog_og_title" 
                                           value="<?php echo esc_attr($og_title); ?>" 
                                           class="ewog-modern-input" 
                                           maxlength="60"
                                           placeholder="<?php echo esc_attr($defaults['title']); ?>"
                                           data-default="<?php echo esc_attr($defaults['title']); ?>" />
                                    <div class="ewog-field-actions">
                                        <button type="button" class="ewog-btn-default" data-field="title">
                                            <?php _e('Use Default', EWOG_TEXT_DOMAIN); ?>
                                        </button>
                                        <button type="button" class="ewog-btn-ai" data-field="title">
                                            <span class="dashicons dashicons-lightbulb"></span>
                                            <?php _e('Optimize', EWOG_TEXT_DOMAIN); ?>
                                        </button>
                                    </div>
                                    <div class="ewog-field-footer">
                                        <div class="ewog-char-counter">
                                            <span class="ewog-char-current">0</span>/<span class="ewog-char-max">60</span>
                                            <div class="ewog-char-indicator">
                                                <div class="ewog-char-bar"></div>
                                            </div>
                                        </div>
                                        <div class="ewog-field-status">
                                            <span class="ewog-status-icon"></span>
                                            <span class="ewog-status-text"><?php _e('Optimal length: 40-60 characters', EWOG_TEXT_DOMAIN); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description Field -->
                            <div class="ewog-field-group">
                                <label for="ewog_og_description" class="ewog-field-label">
                                    <?php _e('Social Media Description', EWOG_TEXT_DOMAIN); ?>
                                </label>
                                <div class="ewog-field-wrapper">
                                    <textarea id="ewog_og_description" 
                                              name="ewog_og_description" 
                                              rows="3" 
                                              class="ewog-modern-textarea" 
                                              maxlength="155"
                                              placeholder="<?php echo esc_attr($defaults['description']); ?>"
                                              data-default="<?php echo esc_attr($defaults['description']); ?>"><?php echo esc_textarea($og_description); ?></textarea>
                                    <div class="ewog-field-actions">
                                        <button type="button" class="ewog-btn-default" data-field="description">
                                            <?php _e('Use Default', EWOG_TEXT_DOMAIN); ?>
                                        </button>
                                        <button type="button" class="ewog-btn-ai" data-field="description">
                                            <span class="dashicons dashicons-lightbulb"></span>
                                            <?php _e('Optimize', EWOG_TEXT_DOMAIN); ?>
                                        </button>
                                    </div>
                                    <div class="ewog-field-footer">
                                        <div class="ewog-char-counter">
                                            <span class="ewog-char-current">0</span>/<span class="ewog-char-max">155</span>
                                            <div class="ewog-char-indicator">
                                                <div class="ewog-char-bar"></div>
                                            </div>
                                        </div>
                                        <div class="ewog-field-status">
                                            <span class="ewog-status-icon"></span>
                                            <span class="ewog-status-text"><?php _e('Optimal length: 120-155 characters', EWOG_TEXT_DOMAIN); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Image Field -->
                            <div class="ewog-field-group ewog-image-field-group">
                                <label class="ewog-field-label">
                                    <?php _e('Social Media Image', EWOG_TEXT_DOMAIN); ?>
                                </label>
                                <div class="ewog-image-field-modern">
                                    <input type="hidden" id="ewog_og_image" name="ewog_og_image" value="<?php echo esc_attr($og_image); ?>" />
                                    
                                    <div class="ewog-image-preview-container">
                                        <?php if ($og_image): ?>
                                            <div class="ewog-image-preview active">
                                                <img src="<?php echo esc_url($og_image); ?>" alt="Social media preview" />
                                                <div class="ewog-image-overlay">
                                                    <button type="button" class="ewog-btn-icon ewog-change-image" title="<?php esc_attr_e('Change Image', EWOG_TEXT_DOMAIN); ?>">
                                                        <span class="dashicons dashicons-edit"></span>
                                                    </button>
                                                    <button type="button" class="ewog-btn-icon ewog-remove-image" title="<?php esc_attr_e('Remove Image', EWOG_TEXT_DOMAIN); ?>">
                                                        <span class="dashicons dashicons-no-alt"></span>
                                                    </button>
                                                    <button type="button" class="ewog-btn-icon ewog-analyze-image" title="<?php esc_attr_e('Analyze Image', EWOG_TEXT_DOMAIN); ?>">
                                                        <span class="dashicons dashicons-search"></span>
                                                    </button>
                                                </div>
                                                <div class="ewog-image-specs">
                                                    <span class="ewog-image-dimensions"></span>
                                                    <span class="ewog-image-size"></span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="ewog-image-placeholder">
                                                <div class="ewog-placeholder-content">
                                                    <span class="dashicons dashicons-format-image"></span>
                                                    <h4><?php _e('Add Social Media Image', EWOG_TEXT_DOMAIN); ?></h4>
                                                    <p><?php _e('Recommended: 1200x630px (1.91:1 ratio)', EWOG_TEXT_DOMAIN); ?></p>
                                                    <button type="button" class="ewog-btn-primary ewog-upload-image">
                                                        <span class="dashicons dashicons-upload"></span>
                                                        <?php _e('Choose Image', EWOG_TEXT_DOMAIN); ?>
                                                    </button>
                                                    <button type="button" class="ewog-btn-default ewog-use-featured">
                                                        <?php _e('Use Featured Image', EWOG_TEXT_DOMAIN); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="ewog-image-analysis" style="display: none;">
                                        <div class="ewog-analysis-results"></div>
                                    </div>
                                    
                                    <div class="ewog-image-recommendations">
                                        <h5><?php _e('Image Guidelines', EWOG_TEXT_DOMAIN); ?></h5>
                                        <ul>
                                            <li><span class="ewog-check">✓</span> <?php _e('Minimum size: 600x315px', EWOG_TEXT_DOMAIN); ?></li>
                                            <li><span class="ewog-check">✓</span> <?php _e('Recommended: 1200x630px', EWOG_TEXT_DOMAIN); ?></li>
                                            <li><span class="ewog-check">✓</span> <?php _e('Aspect ratio: 1.91:1 (Facebook/Twitter)', EWOG_TEXT_DOMAIN); ?></li>
                                            <li><span class="ewog-check">✓</span> <?php _e('File size: Under 5MB', EWOG_TEXT_DOMAIN); ?></li>
                                            <li><span class="ewog-check">✓</span> <?php _e('Format: JPG, PNG, or WebP', EWOG_TEXT_DOMAIN); ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Tab -->
                    <div class="ewog-tab-content" id="ewog-tab-advanced">
                        <?php $this->render_advanced_settings($post); ?>
                    </div>
                    
                    <!-- Custom Tags Tab -->
                    <div class="ewog-tab-content" id="ewog-tab-custom">
                        <?php $this->render_custom_tags_settings($custom_tags); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        // Initialize modern meta box functionality
        jQuery(document).ready(function($) {
            window.ewogMetaBox = new EWOGModernMetaBox({
                postId: <?php echo $post->ID; ?>,
                defaults: <?php echo json_encode($defaults); ?>,
                ajaxUrl: ajaxurl,
                nonce: '<?php echo wp_create_nonce('ewog_meta_boxes_nonce'); ?>'
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render live preview meta box
     */
    public function render_preview_meta_box($post) {
        ?>
        <div class="ewog-live-preview-container">
            <div class="ewog-preview-controls">
                <div class="ewog-platform-selector">
                    <button type="button" class="ewog-platform-btn active" data-platform="facebook">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </button>
                    <button type="button" class="ewog-platform-btn" data-platform="twitter">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                        Twitter
                    </button>
                    <button type="button" class="ewog-platform-btn" data-platform="linkedin">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                        LinkedIn
                    </button>
                </div>
                
                <button type="button" class="ewog-refresh-preview" title="<?php esc_attr_e('Refresh Preview', EWOG_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
            
            <div class="ewog-preview-viewport">
                <div class="ewog-preview-loading">
                    <div class="ewog-spinner"></div>
                    <p><?php _e('Generating preview...', EWOG_TEXT_DOMAIN); ?></p>
                </div>
                
                <div class="ewog-preview-content" id="ewog-preview-facebook">
                    <!-- Facebook preview will be loaded here -->
                </div>
                
                <div class="ewog-preview-content" id="ewog-preview-twitter" style="display: none;">
                    <!-- Twitter preview will be loaded here -->
                </div>
                
                <div class="ewog-preview-content" id="ewog-preview-linkedin" style="display: none;">
                    <!-- LinkedIn preview will be loaded here -->
                </div>
            </div>
            
            <div class="ewog-preview-footer">
                <small class="ewog-preview-note">
                    <?php _e('Preview updates automatically as you type', EWOG_TEXT_DOMAIN); ?>
                </small>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render quick actions meta box
     */
    public function render_actions_meta_box($post) {
        ?>
        <div class="ewog-actions-container">
            <div class="ewog-action-group">
                <h4><?php _e('Validation', EWOG_TEXT_DOMAIN); ?></h4>
                <button type="button" class="ewog-action-btn ewog-test-facebook">
                    <span class="dashicons dashicons-facebook"></span>
                    <?php _e('Test Facebook', EWOG_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="ewog-action-btn ewog-test-twitter">
                    <span class="dashicons dashicons-twitter"></span>
                    <?php _e('Test Twitter', EWOG_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="ewog-action-btn ewog-test-schema">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Test Schema', EWOG_TEXT_DOMAIN); ?>
                </button>
            </div>
            
            <div class="ewog-action-group">
                <h4><?php _e('Content', EWOG_TEXT_DOMAIN); ?></h4>
                <button type="button" class="ewog-action-btn ewog-reset-defaults">
                    <span class="dashicons dashicons-undo"></span>
                    <?php _e('Reset to Defaults', EWOG_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="ewog-action-btn ewog-copy-settings">
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php _e('Copy Settings', EWOG_TEXT_DOMAIN); ?>
                </button>
            </div>
            
            <div class="ewog-action-group">
                <h4><?php _e('Tools', EWOG_TEXT_DOMAIN); ?></h4>
                <button type="button" class="ewog-action-btn ewog-export-settings">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export Settings', EWOG_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get smart defaults for the current product
     */
    private function get_smart_defaults($post) {
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return array(
                'title' => get_the_title($post->ID),
                'description' => get_the_excerpt($post->ID),
                'image' => wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'large')[0] ?? ''
            );
        }
        
        // Smart title generation
        $title = $product->get_name();
        $brand = $this->get_product_brand($product);
        if ($brand) {
            $title = $brand . ' - ' . $title;
        }
        
        // Add key selling points
        if ($product->is_on_sale()) {
            $title = '🔥 ' . $title . ' - Sale!';
        } elseif ($product->is_featured()) {
            $title = '⭐ ' . $title;
        }
        
        // Smart description generation
        $description = $product->get_short_description();
        if (empty($description)) {
            $description = wp_trim_words($product->get_description(), 25);
        }
        
        // Add price and availability
        if ($product->get_price()) {
            $description .= ' Starting at ' . wc_price($product->get_price()) . '.';
        }
        
        if ($product->is_in_stock()) {
            $description .= ' ✅ In Stock.';
        }
        
        // Add shipping info if available
        if ($product->needs_shipping()) {
            $description .= ' Free shipping available.';
        }
        
        // Smart image selection
        $image = '';
        if ($product->get_image_id()) {
            $image_data = wp_get_attachment_image_src($product->get_image_id(), 'large');
            $image = $image_data ? $image_data[0] : '';
        }
        
        return array(
            'title' => wp_trim_words($title, 10, ''),
            'description' => wp_trim_words($description, 25, '...'),
            'image' => $image
        );
    }
    
    /**
     * Get product brand
     */
    private function get_product_brand($product) {
        $brand_taxonomies = array('product_brand', 'pwb-brand', 'yith_product_brand', 'pa_brand');
        
        foreach ($brand_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $terms = get_the_terms($product->get_id(), $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    return $terms[0]->name;
                }
            }
        }
        
        return '';
    }
    
    /**
     * Render advanced settings
     */
    private function render_advanced_settings($post) {
        // Get advanced meta values
        $twitter_card = get_post_meta($post->ID, '_ewog_twitter_card', true) ?: 'summary_large_image';
        $product_condition = get_post_meta($post->ID, '_ewog_product_condition', true) ?: 'new';
        $product_brand = get_post_meta($post->ID, '_ewog_product_brand', true);
        $schema_type = get_post_meta($post->ID, '_ewog_schema_type', true) ?: 'Product';
        
        ?>
        <div class="ewog-advanced-settings">
            <div class="ewog-form-grid">
                <div class="ewog-field-group">
                    <label for="ewog_twitter_card" class="ewog-field-label">
                        <?php _e('Twitter Card Type', EWOG_TEXT_DOMAIN); ?>
                    </label>
                    <select id="ewog_twitter_card" name="ewog_twitter_card" class="ewog-modern-select">
                        <option value="summary" <?php selected($twitter_card, 'summary'); ?>><?php _e('Summary', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="summary_large_image" <?php selected($twitter_card, 'summary_large_image'); ?>><?php _e('Summary Large Image', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="product" <?php selected($twitter_card, 'product'); ?>><?php _e('Product Card', EWOG_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                
                <div class="ewog-field-group">
                    <label for="ewog_schema_type" class="ewog-field-label">
                        <?php _e('Schema Type', EWOG_TEXT_DOMAIN); ?>
                    </label>
                    <select id="ewog_schema_type" name="ewog_schema_type" class="ewog-modern-select">
                        <option value="Product" <?php selected($schema_type, 'Product'); ?>><?php _e('Product', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="Book" <?php selected($schema_type, 'Book'); ?>><?php _e('Book', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="SoftwareApplication" <?php selected($schema_type, 'SoftwareApplication'); ?>><?php _e('Software', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="Course" <?php selected($schema_type, 'Course'); ?>><?php _e('Course', EWOG_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                
                <div class="ewog-field-group">
                    <label for="ewog_product_condition" class="ewog-field-label">
                        <?php _e('Product Condition', EWOG_TEXT_DOMAIN); ?>
                    </label>
                    <select id="ewog_product_condition" name="ewog_product_condition" class="ewog-modern-select">
                        <option value="new" <?php selected($product_condition, 'new'); ?>><?php _e('New', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="refurbished" <?php selected($product_condition, 'refurbished'); ?>><?php _e('Refurbished', EWOG_TEXT_DOMAIN); ?></option>
                        <option value="used" <?php selected($product_condition, 'used'); ?>><?php _e('Used', EWOG_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                
                <div class="ewog-field-group">
                    <label for="ewog_product_brand" class="ewog-field-label">
                        <?php _e('Brand Override', EWOG_TEXT_DOMAIN); ?>
                    </label>
                    <input type="text" 
                           id="ewog_product_brand" 
                           name="ewog_product_brand" 
                           value="<?php echo esc_attr($product_brand); ?>" 
                           class="ewog-modern-input" 
                           placeholder="<?php esc_attr_e('Leave empty to auto-detect', EWOG_TEXT_DOMAIN); ?>" />
                    <p class="ewog-field-help"><?php _e('Override the automatically detected brand for this product', EWOG_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render custom tags settings
     */
    private function render_custom_tags_settings($custom_tags) {
        ?>
        <div class="ewog-custom-tags-container">
            <p class="ewog-section-description">
                <?php _e('Add custom Open Graph and Twitter meta tags for advanced optimization.', EWOG_TEXT_DOMAIN); ?>
            </p>
            
            <div class="ewog-custom-tags-list" id="ewog-custom-tags-list">
                <?php if (!empty($custom_tags)): ?>
                    <?php foreach ($custom_tags as $index => $tag): ?>
                        <?php $this->render_custom_tag_row($index, $tag); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ewog-no-tags-message">
                        <span class="dashicons dashicons-tag"></span>
                        <p><?php _e('No custom tags added yet.', EWOG_TEXT_DOMAIN); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="ewog-custom-tags-actions">
                <button type="button" class="ewog-btn-primary ewog-add-custom-tag">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Custom Tag', EWOG_TEXT_DOMAIN); ?>
                </button>
                
                <button type="button" class="ewog-btn-default ewog-suggest-tags">
                    <span class="dashicons dashicons-lightbulb"></span>
                    <?php _e('Suggest Tags', EWOG_TEXT_DOMAIN); ?>
                </button>
            </div>
            
            <div class="ewog-tag-suggestions" style="display: none;">
                <h5><?php _e('Suggested Tags', EWOG_TEXT_DOMAIN); ?></h5>
                <div class="ewog-suggestion-list"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render custom tag row
     */
    private function render_custom_tag_row($index, $tag) {
        ?>
        <div class="ewog-custom-tag-row">
            <div class="ewog-tag-fields">
                <input type="text" 
                       name="ewog_custom_tags[<?php echo $index; ?>][property]" 
                       value="<?php echo esc_attr($tag['property'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Property (e.g., og:brand)', EWOG_TEXT_DOMAIN); ?>" 
                       class="ewog-tag-property" />
                <input type="text" 
                       name="ewog_custom_tags[<?php echo $index; ?>][content]" 
                       value="<?php echo esc_attr($tag['content'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Content', EWOG_TEXT_DOMAIN); ?>" 
                       class="ewog-tag-content" />
            </div>
            <button type="button" class="ewog-btn-icon ewog-remove-tag" title="<?php esc_attr_e('Remove Tag', EWOG_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php
    }
    
    /**
     * Add product list columns
     */
    public function add_product_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'name') {
                $new_columns['ewog_status'] = '<span class="dashicons dashicons-share" title="' . esc_attr__('Social Media Status', EWOG_TEXT_DOMAIN) . '"></span>';
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate custom columns
     */
    public function populate_product_columns($column, $post_id) {
        if ($column === 'ewog_status') {
            $disable_og = get_post_meta($post_id, '_ewog_disable_og', true);
            $custom_title = get_post_meta($post_id, '_ewog_og_title', true);
            $custom_description = get_post_meta($post_id, '_ewog_og_description', true);
            $custom_image = get_post_meta($post_id, '_ewog_og_image', true);
            
            if ($disable_og) {
                echo '<span class="ewog-status-indicator disabled" title="' . esc_attr__('Social media optimization disabled', EWOG_TEXT_DOMAIN) . '">⚫</span>';
            } elseif ($custom_title || $custom_description || $custom_image) {
                echo '<span class="ewog-status-indicator custom" title="' . esc_attr__('Custom settings configured', EWOG_TEXT_DOMAIN) . '">🟡</span>';
            } else {
                echo '<span class="ewog-status-indicator auto" title="' . esc_attr__('Using automatic settings', EWOG_TEXT_DOMAIN) . '">🟢</span>';
            }
        }
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
        
        // Save all fields
        $fields = array(
            'ewog_disable_og' => '_ewog_disable_og',
            'ewog_og_title' => '_ewog_og_title',
            'ewog_og_description' => '_ewog_og_description',
            'ewog_og_image' => '_ewog_og_image',
            'ewog_twitter_card' => '_ewog_twitter_card',
            'ewog_schema_type' => '_ewog_schema_type',
            'ewog_product_condition' => '_ewog_product_condition',
            'ewog_product_brand' => '_ewog_product_brand'
        );
        
        foreach ($fields as $field => $meta_key) {
            $value = $_POST[$field] ?? '';
            
            if ($field === 'ewog_og_description') {
                $value = sanitize_textarea_field($value);
            } elseif ($field === 'ewog_og_image') {
                $value = esc_url_raw($value);
            } else {
                $value = sanitize_text_field($value);
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
        
        // Clear cache
        wp_cache_delete("ewog_product_meta_{$post_id}", 'ewog');
        
        do_action('ewog_product_meta_saved', $post_id, $_POST);
    }
    
    /**
     * Enqueue modern admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type, $post;
        
        if ($post_type !== 'product') {
            return;
        }
        
        if (!in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
            return;
        }
        
        wp_enqueue_media();
        
        // Enhanced CSS
        wp_enqueue_style(
            'ewog-modern-meta-boxes',
            EWOG_PLUGIN_URL . 'assets/css/meta-boxes.css',
            array(),
            EWOG_VERSION
        );
        
        // Enhanced JavaScript
        wp_enqueue_script(
            'ewog-modern-meta-boxes',
            EWOG_PLUGIN_URL . 'assets/js/meta-boxes.js',
            array('jquery', 'wp-media-utils'),
            EWOG_VERSION,
            true
        );
        
        // Localize script with enhanced data
        wp_localize_script('ewog-modern-meta-boxes', 'ewogModernMeta', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ewog_meta_boxes_nonce'),
            'postId' => $post ? $post->ID : 0,
            'strings' => array(
                'loading' => __('Loading...', EWOG_TEXT_DOMAIN),
                'error' => __('An error occurred', EWOG_TEXT_DOMAIN),
                'success' => __('Success!', EWOG_TEXT_DOMAIN),
                'chooseImage' => __('Choose Image', EWOG_TEXT_DOMAIN),
                'useImage' => __('Use this Image', EWOG_TEXT_DOMAIN),
                'analyzing' => __('Analyzing image...', EWOG_TEXT_DOMAIN),
                'validating' => __('Validating...', EWOG_TEXT_DOMAIN),
                'optimal' => __('Optimal', EWOG_TEXT_DOMAIN),
                'good' => __('Good', EWOG_TEXT_DOMAIN),
                'needs_improvement' => __('Needs Improvement', EWOG_TEXT_DOMAIN),
                'too_short' => __('Too short', EWOG_TEXT_DOMAIN),
                'too_long' => __('Too long', EWOG_TEXT_DOMAIN),
                'confirm_reset' => __('Are you sure you want to reset all settings to defaults?', EWOG_TEXT_DOMAIN)
            ),
            'limits' => array(
                'title_min' => 30,
                'title_max' => 60,
                'title_optimal' => 40,
                'desc_min' => 120,
                'desc_max' => 155,
                'desc_optimal' => 140
            ),
            'platforms' => array(
                'facebook' => array(
                    'name' => 'Facebook',
                    'debugUrl' => 'https://developers.facebook.com/tools/debug/'
                ),
                'twitter' => array(
                    'name' => 'Twitter',
                    'debugUrl' => 'https://cards-dev.twitter.com/validator'
                ),
                'linkedin' => array(
                    'name' => 'LinkedIn',
                    'debugUrl' => 'https://www.linkedin.com/post-inspector/'
                )
            )
        ));
    }
    
    /**
     * AJAX: Generate real-time preview
     */
    public function ajax_generate_og_preview() {
        check_ajax_referer('ewog_meta_boxes_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $platform = sanitize_text_field($_POST['platform'] ?? 'facebook');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $image = esc_url_raw($_POST['image'] ?? '');
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Unauthorized');
        }
        
        // Get defaults if fields are empty
        if (empty($title) || empty($description)) {
            $defaults = $this->get_smart_defaults(get_post($post_id));
            $title = $title ?: $defaults['title'];
            $description = $description ?: $defaults['description'];
            $image = $image ?: $defaults['image'];
        }
        
        $preview_data = array(
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'url' => get_permalink($post_id),
            'domain' => parse_url(home_url(), PHP_URL_HOST),
            'site_name' => get_bloginfo('name')
        );
        
        $html = $this->generate_platform_preview($platform, $preview_data);
        
        wp_send_json_success(array(
            'html' => $html,
            'platform' => $platform
        ));
    }
    
    /**
     * Generate platform-specific preview HTML
     */
    private function generate_platform_preview($platform, $data) {
        switch ($platform) {
            case 'facebook':
                return $this->generate_facebook_preview($data);
            case 'twitter':
                return $this->generate_twitter_preview($data);
            case 'linkedin':
                return $this->generate_linkedin_preview($data);
            default:
                return '<p>Preview not available</p>';
        }
    }
    
    /**
     * Generate Facebook preview
     */
    private function generate_facebook_preview($data) {
        return sprintf('
            <div class="ewog-preview-facebook">
                <div class="ewog-facebook-header">
                    <div class="ewog-facebook-user">
                        <div class="ewog-facebook-avatar"></div>
                        <div class="ewog-facebook-info">
                            <strong>%s</strong>
                            <span class="ewog-facebook-time">Just now</span>
                        </div>
                    </div>
                </div>
                <div class="ewog-facebook-content">
                    <p>Check out this amazing product!</p>
                </div>
                <div class="ewog-facebook-link-preview">
                    %s
                    <div class="ewog-facebook-link-info">
                        <div class="ewog-facebook-domain">%s</div>
                        <div class="ewog-facebook-title">%s</div>
                        <div class="ewog-facebook-description">%s</div>
                    </div>
                </div>
            </div>',
            esc_html($data['site_name']),
            $data['image'] ? '<img src="' . esc_url($data['image']) . '" alt="" class="ewog-facebook-image" />' : '<div class="ewog-facebook-no-image"></div>',
            esc_html(strtoupper($data['domain'])),
            esc_html($data['title']),
            esc_html($data['description'])
        );
    }
    
    /**
     * Generate Twitter preview
     */
    private function generate_twitter_preview($data) {
        return sprintf('
            <div class="ewog-preview-twitter">
                <div class="ewog-twitter-header">
                    <div class="ewog-twitter-avatar"></div>
                    <div class="ewog-twitter-info">
                        <strong>%s</strong>
                        <span class="ewog-twitter-handle">@%s</span>
                        <span class="ewog-twitter-time">• now</span>
                    </div>
                </div>
                <div class="ewog-twitter-content">
                    <p>Loving this product! 😍 %s</p>
                </div>
                <div class="ewog-twitter-card">
                    %s
                    <div class="ewog-twitter-card-info">
                        <div class="ewog-twitter-title">%s</div>
                        <div class="ewog-twitter-description">%s</div>
                        <div class="ewog-twitter-domain">%s</div>
                    </div>
                </div>
            </div>',
            esc_html($data['site_name']),
            esc_html(strtolower(str_replace(' ', '', $data['site_name']))),
            esc_url($data['url']),
            $data['image'] ? '<img src="' . esc_url($data['image']) . '" alt="" class="ewog-twitter-image" />' : '<div class="ewog-twitter-no-image"></div>',
            esc_html($data['title']),
            esc_html($data['description']),
            esc_html($data['domain'])
        );
    }
    
    /**
     * Generate LinkedIn preview
     */
    private function generate_linkedin_preview($data) {
        return sprintf('
            <div class="ewog-preview-linkedin">
                <div class="ewog-linkedin-header">
                    <div class="ewog-linkedin-avatar"></div>
                    <div class="ewog-linkedin-info">
                        <strong>%s</strong>
                        <div class="ewog-linkedin-subtitle">Company • 1st</div>
                        <div class="ewog-linkedin-time">Just now</div>
                    </div>
                </div>
                <div class="ewog-linkedin-content">
                    <p>Excited to share this incredible product with our network!</p>
                </div>
                <div class="ewog-linkedin-link-preview">
                    %s
                    <div class="ewog-linkedin-link-info">
                        <div class="ewog-linkedin-title">%s</div>
                        <div class="ewog-linkedin-description">%s</div>
                        <div class="ewog-linkedin-domain">%s</div>
                    </div>
                </div>
            </div>',
            esc_html($data['site_name']),
            $data['image'] ? '<img src="' . esc_url($data['image']) . '" alt="" class="ewog-linkedin-image" />' : '<div class="ewog-linkedin-no-image"></div>',
            esc_html($data['title']),
            esc_html($data['description']),
            esc_html($data['domain'])
        );
    }
    
    /**
     * AJAX: Get default content
     */
    public function ajax_get_default_content() {
        check_ajax_referer('ewog_meta_boxes_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Unauthorized');
        }
        
        $defaults = $this->get_smart_defaults(get_post($post_id));
        
        wp_send_json_success($defaults);
    }
    
    /**
     * AJAX: Validate and analyze image
     */
    public function ajax_analyze_image() {
        check_ajax_referer('ewog_meta_boxes_nonce', 'nonce');
        
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        
        if (!$image_url) {
            wp_send_json_error('No image URL provided');
        }
        
        $analysis = $this->analyze_image($image_url);
        
        wp_send_json_success($analysis);
    }
    
    /**
     * Analyze image for social media optimization
     */
    private function analyze_image($image_url) {
        $response = wp_remote_head($image_url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return array(
                'valid' => false,
                'score' => 0,
                'issues' => array('Could not access image'),
                'recommendations' => array()
            );
        }
        
        $headers = wp_remote_retrieve_headers($response);
        $content_type = $headers['content-type'] ?? '';
        $content_length = intval($headers['content-length'] ?? 0);
        
        $score = 100;
        $issues = array();
        $recommendations = array();
        
        // Check content type
        if (strpos($content_type, 'image/') !== 0) {
            $issues[] = 'Invalid image format';
            $score -= 50;
        }
        
        // Check file size
        if ($content_length > 5 * 1024 * 1024) { // 5MB
            $issues[] = 'File size too large (over 5MB)';
            $score -= 20;
        }
        
        // Get image dimensions
        $image_info = @getimagesize($image_url);
        if ($image_info) {
            $width = $image_info[0];
            $height = $image_info[1];
            $ratio = $width / $height;
            
            // Check minimum size
            if ($width < 600 || $height < 315) {
                $issues[] = 'Image too small (minimum 600x315)';
                $score -= 30;
            }
            
            // Check aspect ratio
            if (abs($ratio - 1.91) > 0.2) {
                $recommendations[] = 'Consider using 1.91:1 aspect ratio (1200x630) for optimal display';
                $score -= 10;
            }
            
            // Check if image is too large
            if ($width > 2000 || $height > 2000) {
                $recommendations[] = 'Image could be optimized for faster loading';
                $score -= 5;
            }
        } else {
            $issues[] = 'Could not determine image dimensions';
            $score -= 20;
        }
        
        // Determine overall rating
        $rating = 'excellent';
        if ($score < 90) $rating = 'good';
        if ($score < 70) $rating = 'fair';
        if ($score < 50) $rating = 'poor';
        
        return array(
            'valid' => empty($issues),
            'score' => max(0, $score),
            'rating' => $rating,
            'issues' => $issues,
            'recommendations' => $recommendations,
            'dimensions' => $image_info ? "{$width}x{$height}" : 'Unknown',
            'file_size' => $content_length ? size_format($content_length) : 'Unknown',
            'format' => $content_type
        );
    }
    
    /**
     * AJAX: Suggest custom tags
     */
    public function ajax_suggest_tags() {
        check_ajax_referer('ewog_meta_boxes_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Unauthorized');
        }
        
        $suggestions = $this->get_tag_suggestions($post_id);
        
        wp_send_json_success($suggestions);
    }
    
    /**
     * Get smart tag suggestions based on product
     */
    private function get_tag_suggestions($post_id) {
        $product = wc_get_product($post_id);
        $suggestions = array();
        
        if (!$product) {
            return $suggestions;
        }
        
        // Product-specific suggestions
        if ($product->get_price()) {
            $suggestions[] = array(
                'property' => 'product:price:amount',
                'content' => $product->get_price(),
                'description' => 'Product price for rich snippets'
            );
            
            $suggestions[] = array(
                'property' => 'product:price:currency',
                'content' => get_woocommerce_currency(),
                'description' => 'Price currency'
            );
        }
        
        // Brand suggestion
        $brand = $this->get_product_brand($product);
        if ($brand) {
            $suggestions[] = array(
                'property' => 'product:brand',
                'content' => $brand,
                'description' => 'Product brand'
            );
        }
        
        // Availability
        $suggestions[] = array(
            'property' => 'product:availability',
            'content' => $product->is_in_stock() ? 'in stock' : 'out of stock',
            'description' => 'Product availability status'
        );
        
        // Category
        $categories = wp_get_post_terms($post_id, 'product_cat');
        if (!empty($categories)) {
            $suggestions[] = array(
                'property' => 'product:category',
                'content' => $categories[0]->name,
                'description' => 'Primary product category'
            );
        }
        
        // Color attribute
        $color = $product->get_attribute('color') ?: $product->get_attribute('pa_color');
        if ($color) {
            $suggestions[] = array(
                'property' => 'product:color',
                'content' => $color,
                'description' => 'Product color'
            );
        }
        
        // Size attribute
        $size = $product->get_attribute('size') ?: $product->get_attribute('pa_size');
        if ($size) {
            $suggestions[] = array(
                'property' => 'product:size',
                'content' => $size,
                'description' => 'Product size'
            );
        }
        
        return $suggestions;
    }
}