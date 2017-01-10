<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Woo_Open_Graph
 * @subpackage Woo_Open_Graph/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Open_Graph
 * @subpackage Woo_Open_Graph/public
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
class Woo_Open_Graph_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Woo_Open_Graph_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Woo_Open_Graph_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-open-graph-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Woo_Open_Graph_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Woo_Open_Graph_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-open-graph-public.js', array('jquery'), $this->version, false);
    }

    public function wog_doctype_opengraph($output) {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            if (is_woocommerce() && is_product()) {
                return $output . ' xmlns:og="http://ogp.me/ns#"
           xmlns:fb="https://www.facebook.com/2008/fbml"';
            }
        }
    }

    public function override_title($title) {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            if (in_array('wc-vendors/class-wc-vendors.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                $v_page = WCV_Vendors::is_vendor_page();
                if ($v_page) {
                    $vendor_shop = urldecode(get_query_var('vendor_shop'));
                    $vendor_id = WCV_Vendors::get_vendor_id($vendor_shop);
                    $vendor_name = get_user_meta($vendor_id, 'pv_shop_name', true);
                    $title = __($vendor_name, $this->plugin_name) . ' | ' . get_bloginfo(strip_tags('name'));
                }
            }
        }
        return $title;
    }

    public function wog_opengraph() {
        global $post;
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            if (is_woocommerce() && is_product() && $post->post_type == 'product') {
                $options = get_option('wog_settings');
                if (has_post_thumbnail($post->ID)) {
                    $img_src_arr = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
                    $img_src = $img_src_arr[0];
                } else {
                    $img_src = apply_filters('woocommerce_placeholder_img_src', WC()->plugin_url() . '/assets/images/placeholder.png');
                }
                ?>
                <!--/ Twitter Open Graph for Woo Product /-->
                <?php if (!isset($options['wog_checkbox_disabled_plugins_options'])): ?>
                    <meta property="twitter:title" content="<?php echo get_the_title(); ?>" />
                    <meta property="twitter:description" content="<?php echo get_the_excerpt(); ?>" />
                <?php endif; ?>
                <meta property="twitter:image" content="<?php echo esc_attr($img_src);
                ?>" />
                <meta property="twitter:card" content="summary" />
                <meta property="twitter:url" content="<?php echo get_permalink(); ?>" />
                <meta property="twitter:site" content="<?php echo '@' . get_bloginfo(strip_tags('name')); ?>" />
                <!--/ Facebook Open Graph for Woo Product /-->
                <?php if (!isset($options['wog_checkbox_disabled_plugins_options'])): ?>
                    <meta property="og:title" content="<?php echo esc_attr(get_the_title()); ?>" />
                    <meta property="og:description" content="<?php echo get_the_excerpt(); ?>" />
                <?php endif; ?>
                <meta property="og:image" content="<?php echo esc_attr($img_src);
                ?>" />
                <meta property="og:type" content="product" />
                <meta property="og:url" content="<?php echo get_permalink(); ?>" />
                <meta property="og:site_name" content="<?php echo get_bloginfo(strip_tags('name')); ?>" />
                <!-- Google Plus Open Graph for Woo Product /-->
                <?php if (!isset($options['wog_checkbox_disabled_plugins_options'])): ?>
                    <meta property="name" content="<?php echo get_the_title(); ?>" />
                    <meta property="description" content="<?php echo get_the_excerpt(); ?>" />
                <?php endif; ?>
                <meta property="image" content="<?php echo esc_attr($img_src); ?>" />
                <?php
            }else if (is_product_category()) {
                $category = get_queried_object();
                $category_link = get_category_link($category->term_id);
                if (is_woocommerce() && is_product_category($category->slug)) {
                    $options = get_option('wog_settings');
                    $thumbnail_id = get_woocommerce_term_meta($category->term_id, 'thumbnail_id', true);
                    $image = wp_get_attachment_url($thumbnail_id);

                    if (!empty($image)) {
                        $img_src = $image;
                    } else {
                        $img_src = apply_filters('woocommerce_placeholder_img_src', WC()->plugin_url() . '/assets/images/placeholder.png');
                    }
                    ?>
                    <!--/ Twitter Open Graph for Woo Product /-->
                    <?php if (!isset($options['wog_checkbox_disabled_plugins_options'])): ?>
                        <meta property="twitter:title" content="<?php echo $category->name; ?>" />
                        <meta property="twitter:description" content="<?php echo $category->description; ?>" />
                    <?php endif; ?>
                    <meta property="twitter:image" content="<?php echo esc_attr($img_src);
                    ?>" />
                    <meta property="twitter:card" content="summary" />
                    <meta property="twitter:url" content="<?php echo esc_url($category_link); ?>" />
                    <meta property="twitter:site" content="<?php echo '@' . get_bloginfo(strip_tags('name')); ?>" />
                    <!--/ Facebook Open Graph for Woo Product /-->
                    <?php if (!isset($options['wog_checkbox_disabled_plugins_options'])): ?>
                        <meta property="og:title" content="<?php echo $category->name; ?>" />
                        <meta property="og:description" content="<?php echo $category->description; ?>" />
                    <?php endif; ?>
                    <meta property="og:image" content="<?php echo esc_attr($img_src);
                    ?>" />
                    <meta property="og:type" content="product" />
                    <meta property="og:url" content="<?php echo esc_url($category_link); ?>" />
                    <meta property="og:site_name" content="<?php echo get_bloginfo(strip_tags('name')); ?>" />
                    <!-- Google Plus Open Graph for Woo Product /-->
                    <?php if (!isset($options['wog_checkbox_disabled_plugins_options'])): ?>
                        <meta property="name" content="<?php echo $category->name; ?>" />
                        <meta property="description" content="<?php echo $category->description; ?>" />
                    <?php endif; ?>
                    <meta property="image" content="<?php echo esc_attr($img_src); ?>" />
                    <?php
                }
            }
            if (in_array('wc-vendors/class-wc-vendors.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                $v_page = WCV_Vendors::is_vendor_page();
                if ($v_page) {

                    $vendor_shop = urldecode(get_query_var('vendor_shop'));
                    $vendor_id = WCV_Vendors::get_vendor_id($vendor_shop);
                    $vendor_name = get_user_meta($vendor_id, 'pv_shop_name', true);
                    $vendor_description = get_user_meta($vendor_id, 'pv_shop_description', true);
                    $vendor_url = WCV_Vendors::get_vendor_shop_page($vendor_id);
                    $image = wp_get_attachment_image_src(get_user_meta($vendor_id, '_wcv_store_banner_id', true), 'full');
                    if (!empty($image)) {
                        $img_src = $image[0];
                    } else {
                        $img_src = apply_filters('woocommerce_placeholder_img_src', WC()->plugin_url() . '/assets/images/placeholder.png');
                    }
                    ?>
                    <!--/ Twitter Open Graph for Woo Product /-->
                    <meta property="twitter:title" content="<?php echo $vendor_name; ?>" />
                    <meta property="twitter:description" content="<?php echo $vendor_description; ?>" />
                    <meta property="twitter:image" content="<?php echo esc_attr($img_src); ?>" />
                    <meta property="twitter:card" content="summary" />
                    <meta property="twitter:url" content="<?php echo esc_url($vendor_url); ?>" />
                    <meta property="twitter:site" content="<?php echo '@' . get_bloginfo(strip_tags('name')); ?>" />
                    <!--/ Facebook Open Graph for Woo Product /-->
                    <meta property="og:title" content="<?php echo $vendor_name; ?>" />
                    <meta property="og:description" content="<?php echo $vendor_description; ?>" />
                    <meta property="og:image" content="<?php echo esc_attr($img_src); ?>" />
                    <meta property="og:image:width" content="300" />
                    <meta property="og:image:height" content="200" />
                    <meta property="og:type" content="product" />
                    <meta property="og:url" content="<?php echo esc_url($vendor_url); ?>" />
                    <meta property="og:site_name" content="<?php echo get_bloginfo(strip_tags('name')); ?>" />
                    <!-- Google Plus Open Graph for Woo Product /-->
                    <meta property="name" content="<?php echo $vendor_name; ?>" />
                    <meta property="description" content="<?php echo $vendor_description; ?>" />
                    <meta property="image" content="<?php echo esc_attr($img_src); ?>" />
                    <?php
                }
            }
        }
    }
}
