<?php

/**
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://wbcomdesigns.com
 * @since             1.0.0
 * @package           Woo_Open_Graph
 *
 * @wordpress-plugin
 * Plugin Name:       Woo Open Graph
 * Plugin URI:        http://wbcomdesigns.com
 * Description:       This plugin will add an extended feature to the big name “ WooCommerce ” that will adds well executed and accurate Open Graph Meta Tags to your site with title,description and WooCommerce featured image.
 * Version:           1.0.0
 * Author:            Wbcom Designs <admin@wbcomdesigns.com>
 * Author URI:        http://wbcomdesigns.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-open-graph
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-open-graph-activator.php
 */
function activate_woo_open_graph() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-open-graph-activator.php';
    Woo_Open_Graph_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-open-graph-deactivator.php
 */
function deactivate_woo_open_graph() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-open-graph-deactivator.php';
    Woo_Open_Graph_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woo_open_graph' );
register_deactivation_hook( __FILE__, 'deactivate_woo_open_graph' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woo-open-graph.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_open_graph() {

    $plugin = new Woo_Open_Graph();
    $plugin->run();

}
run_woo_open_graph();
