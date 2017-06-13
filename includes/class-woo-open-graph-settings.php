<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Woo_Open_Graph
 * @subpackage Woo_Open_Graph/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_Open_Graph
 * @subpackage Woo_Open_Graph/includes
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
class Woo_Open_Graph_Settings {

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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    // register settings and sanitization callback
    public function wog_init() {
        register_setting('wog_settings', 'wog_settings');
        add_settings_section(
                'wog_settings_section', __('Woo Open Graph Settings', $this->plugin_name), array($this, 'wog_settings_section_callback'), 'wog_settings'
        );

        add_settings_field(
                'wog_checkbox_disabled_plugins_options', __('Disable Product Title and Description for open graph', $this->plugin_name), array($this, 'wog_checkbox_disabled_plugins_options_render'), 'wog_settings', 'wog_settings_section'
        );
		add_settings_field(
                'wog_checkbox_disable_enable_twitter_option', __('Enable Twitter for open graph', $this->plugin_name), array($this, 'wog_checkbox_disable_enable_twitter_option_render'), 'wog_settings', 'wog_settings_section'
        );
		add_settings_field(
                'wog_checkbox_disable_enable_facebook_option', __('Enable Facebook for open graph', $this->plugin_name), array($this, 'wog_checkbox_disable_enable_facebook_option_render'), 'wog_settings', 'wog_settings_section'
        );		
		add_settings_field(
                'wog_checkbox_disable_enable_google_option', __('Enable Google+ for open graph', $this->plugin_name), array($this, 'wog_checkbox_disable_enable_google_option_render'), 'wog_settings', 'wog_settings_section'
        );
		add_settings_field(
                'wog_checkbox_disable_enable_linkedin_option', __('Enable LinkedIn for open graph', $this->plugin_name), array($this, 'wog_checkbox_disable_enable_linkedin_option_render'), 'wog_settings', 'wog_settings_section'
        );
		add_settings_field(
                'wog_checkbox_disable_enable_pinterest_option', __('Enable Pinterest for open graph', $this->plugin_name), array($this, 'wog_checkbox_disable_enable_pinterest_option_render'), 'wog_settings', 'wog_settings_section'
        );
    }

    public function wog_checkbox_disabled_plugins_options_render() {

        $options = get_option('wog_settings');
        ?>
        <input type='checkbox' name='wog_settings[wog_checkbox_disabled_plugins_options]' <?php checked(isset($options['wog_checkbox_disabled_plugins_options']), 1); ?> value='1'>        
        <?php
    }
	
	public function wog_checkbox_disable_enable_twitter_option_render() {

        $options = get_option('wog_settings');
        ?>        
        <input type='checkbox' name='wog_settings[wog_checkbox_disable_enable_twitter_option]' <?php checked(isset($options['wog_checkbox_disable_enable_twitter_option']), 1); ?> value='1'>
        
        <?php
    }
	
	public function wog_checkbox_disable_enable_facebook_option_render() {

        $options = get_option('wog_settings');
        ?>        
        <input type='checkbox' name='wog_settings[wog_checkbox_disable_enable_facebook_option]' <?php checked(isset($options['wog_checkbox_disable_enable_facebook_option']), 1); ?> value='1'>
        
        <?php
    }
	
	public function wog_checkbox_disable_enable_google_option_render() {

        $options = get_option('wog_settings');
        ?>        
        <input type='checkbox' name='wog_settings[wog_checkbox_disable_enable_google_option]' <?php checked(isset($options['wog_checkbox_disable_enable_google_option']), 1); ?> value='1'>
        
        <?php
    }
	
	public function wog_checkbox_disable_enable_linkedin_option_render() {

        $options = get_option('wog_settings');
        ?>        
        <input type='checkbox' name='wog_settings[wog_checkbox_disable_enable_linkedin_option]' <?php checked(isset($options['wog_checkbox_disable_enable_linkedin_option']), 1); ?> value='1'>
        
        <?php
    }
	
	public function wog_checkbox_disable_enable_pinterest_option_render() {

        $options = get_option('wog_settings');
        ?>        
        <input type='checkbox' name='wog_settings[wog_checkbox_disable_enable_pinterest_option]' <?php checked(isset($options['wog_checkbox_disable_enable_pinterest_option']), 1); ?> value='1'>
        
        <?php
    }
	
// add admin page to menu
    public function wog_plugin_menu() {

        add_submenu_page('options-general.php', __('Woo Open Graph', $this->plugin_name), __('Woo Open Graph', $this->plugin_name), 'manage_options', 'woo-open-graph', array($this, 'wog_plugin_options'));
    }

    public function wog_settings_section_callback() {
		
        echo __('If already have any Open Graph Plugin like: WP Facebook Open Graph protocol, Yoast plugin etc.. and need to override title and description please check the checkbox below. <br/>You can also enable social media like Facebook, Twitter, LinkedIn and Pinterest to add meta tags to your &lt;HEAD&gt;...&lt;/HEAD&gt; section for open graph. <br/>Please save this setting after enable or disable any checkbox.', $this->plugin_name);
    }

    public function wog_plugin_options() {
        ?>
        <form action='options.php' method='post'>
            <?php
            settings_fields('wog_settings');
            do_settings_sections('wog_settings');
            submit_button();
            ?>
        </form>
        <?php
    }
}