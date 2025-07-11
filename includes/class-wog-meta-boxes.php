<?php
/**
 * Clean WordPress-Native Meta Boxes - Final Version
 * 
 * Simple meta boxes that follow WordPress standards
 * Just helps fill gaps that main SEO plugins might miss
 */

if (!defined('ABSPATH')) {
    exit;
}

class WOG_Meta_Boxes {
    
    private static $instance = null;
    private $settings;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->settings = get_option('wog_settings', array());
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post', array($this, 'save_product_meta_boxes'));
        add_filter('manage_product_posts_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_product_columns'), 10, 2);
    }
    
    /**
     * Add meta boxes
     */
    public function add_product_meta_boxes() {
        add_meta_box(
            'wog_social_settings',
            __('Social Media Settings', 'woo-open-graph'),
            array($this, 'render_social_meta_box'),
            'product',
            'normal',
            'default'
        );
    }
    
    /**
     * Render clean WordPress-native meta box
     */
    public function render_social_meta_box($post) {
        wp_nonce_field('wog_product_meta_box', 'wog_meta_box_nonce');
        
        // Get current values
        $og_title = get_post_meta($post->ID, '_wog_og_title', true);
        $og_description = get_post_meta($post->ID, '_wog_og_description', true);
        $disable_og = get_post_meta($post->ID, '_wog_disable_og', true);
        
        // Get smart defaults for placeholders
        $product = wc_get_product($post->ID);
        $default_title = $product ? $product->get_name() : get_the_title($post->ID);
        $default_description = '';
        
        if ($product) {
            $short_desc = $product->get_short_description();
            $long_desc = $product->get_description();
            $default_description = wp_trim_words(wp_strip_all_tags($short_desc ?: $long_desc), 20, '...');
        }
        
        ?>
        <div class="wog-meta-box">
            <!-- Enable/Disable Toggle -->
            <p class="wog-toggle-field">
                <label>
                    <input type="checkbox" 
                           id="wog_enable_og" 
                           name="wog_enable_og" 
                           value="1" 
                           <?php checked(!$disable_og, true); ?> />
                    <strong><?php _e('Enable social media optimization for this product', 'woo-open-graph'); ?></strong>
                </label>
                <br>
                <span class="description">
                    <?php _e('Generate optimized previews when this product is shared on Facebook, Twitter, LinkedIn, etc.', 'woo-open-graph'); ?>
                </span>
            </p>
            
            <div id="wog-fields" <?php echo $disable_og ? 'style="display:none;"' : ''; ?>>
                <!-- Title Field -->
                <p>
                    <label for="wog_og_title">
                        <strong><?php _e('Social Media Title', 'woo-open-graph'); ?></strong>
                    </label>
                    <input type="text" 
                           id="wog_og_title" 
                           name="wog_og_title" 
                           value="<?php echo esc_attr($og_title); ?>" 
                           class="widefat" 
                           placeholder="<?php echo esc_attr($default_title); ?>"
                           maxlength="60" />
                    <span class="description">
                        <?php _e('Custom title for social sharing. Leave empty to use product name.', 'woo-open-graph'); ?>
                        <span class="wog-counter" data-current="<?php echo strlen($og_title); ?>" data-max="60">
                            (<?php echo strlen($og_title); ?>/60)
                        </span>
                    </span>
                </p>
                
                <!-- Description Field -->
                <p>
                    <label for="wog_og_description">
                        <strong><?php _e('Social Media Description', 'woo-open-graph'); ?></strong>
                    </label>
                    <textarea id="wog_og_description" 
                              name="wog_og_description" 
                              class="widefat" 
                              rows="3"
                              placeholder="<?php echo esc_attr($default_description); ?>"
                              maxlength="155"><?php echo esc_textarea($og_description); ?></textarea>
                    <span class="description">
                        <?php _e('Custom description for social sharing. Leave empty to use product description.', 'woo-open-graph'); ?>
                        <span class="wog-counter" data-current="<?php echo strlen($og_description); ?>" data-max="155">
                            (<?php echo strlen($og_description); ?>/155)
                        </span>
                    </span>
                </p>
                
                <!-- Image Info -->
                <p class="wog-image-info">
                    <strong><?php _e('Social Media Image:', 'woo-open-graph'); ?></strong>
                    <?php if ($product && $product->get_image_id()): ?>
                        <span style="color: #00a32a;">✓ <?php _e('Featured image will be used', 'woo-open-graph'); ?></span>
                    <?php else: ?>
                        <span style="color: #d63638;">⚠ <?php _e('No featured image set', 'woo-open-graph'); ?></span>
                        <br><span class="description"><?php _e('Set a featured image to improve social media sharing.', 'woo-open-graph'); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Toggle fields when checkbox changes
            $('#wog_enable_og').change(function() {
                $('#wog-fields').toggle(this.checked);
            });
            
            // Simple character counters
            function updateCounter(field) {
                var $field = $(field);
                var $counter = $field.siblings('.description').find('.wog-counter');
                var current = $field.val().length;
                var max = parseInt($counter.data('max'));
                
                $counter.text('(' + current + '/' + max + ')');
                
                // Simple color coding
                if (current > max * 0.9) {
                    $counter.css('color', '#d63638');
                } else if (current > max * 0.8) {
                    $counter.css('color', '#dba617'); 
                } else if (current > 0) {
                    $counter.css('color', '#00a32a');
                } else {
                    $counter.css('color', '#666');
                }
            }
            
            // Bind counter updates
            $('#wog_og_title, #wog_og_description').on('input', function() {
                updateCounter(this);
            });
            
            // Initialize counters
            updateCounter('#wog_og_title');
            updateCounter('#wog_og_description');
        });
        </script>
        
        <style>
        .wog-meta-box p {
            margin: 1em 0;
        }
        .wog-meta-box .wog-toggle-field {
            padding: 10px;
            background: #f6f7f7;
            border-left: 4px solid #00a32a;
            margin-bottom: 15px;
        }
        .wog-meta-box .wog-counter {
            font-family: Consolas, Monaco, monospace;
            font-size: 11px;
            color: #666;
            font-weight: 600;
        }
        .wog-meta-box .wog-image-info {
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 3px;
        }
        </style>
        <?php
    }
    
    /**
     * Add product list column
     */
    public function add_product_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'name') {
                $new_columns['wog_status'] = __('Social', 'woo-open-graph');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate status column
     */
    public function populate_product_columns($column, $post_id) {
        if ($column === 'wog_status') {
            $disable_og = get_post_meta($post_id, '_wog_disable_og', true);
            $custom_title = get_post_meta($post_id, '_wog_og_title', true);
            $custom_description = get_post_meta($post_id, '_wog_og_description', true);
            
            if ($disable_og) {
                echo '<span style="color: #d63638;" title="' . esc_attr__('Social media optimization disabled', 'woo-open-graph') . '">●</span>';
            } elseif ($custom_title || $custom_description) {
                echo '<span style="color: #dba617;" title="' . esc_attr__('Custom social media content', 'woo-open-graph') . '">●</span>';
            } else {
                echo '<span style="color: #00a32a;" title="' . esc_attr__('Using automatic social media content', 'woo-open-graph') . '">●</span>';
            }
        }
    }
    
    /**
     * Save meta box data
     */
    public function save_product_meta_boxes($post_id) {
        // Security checks
        if (!isset($_POST['wog_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['wog_meta_box_nonce'], 'wog_product_meta_box')) {
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
        
        // Save fields
        $fields = array(
            'wog_enable_og' => '_wog_disable_og', // Note: inverted logic
            'wog_og_title' => '_wog_og_title',
            'wog_og_description' => '_wog_og_description'
        );
        
        foreach ($fields as $field => $meta_key) {
            if ($field === 'wog_enable_og') {
                // Inverted checkbox: save as 1 if NOT checked (disable_og), empty if checked (enabled)
                $value = empty($_POST[$field]) ? '1' : '';
            } elseif ($field === 'wog_og_description') {
                $value = sanitize_textarea_field($_POST[$field] ?? '');
            } else {
                $value = sanitize_text_field($_POST[$field] ?? '');
            }
            
            if (empty($value)) {
                delete_post_meta($post_id, $meta_key);
            } else {
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        // Clear any cached data
        wp_cache_delete("wog_product_meta_{$post_id}", 'wog');
        
        do_action('wog_product_meta_saved', $post_id);
    }
}