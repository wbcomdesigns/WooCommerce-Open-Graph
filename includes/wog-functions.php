<?php
/**
 * Plugin helper functions.
 *
 * @package Woo_Open_Graph
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin instance.
 *
 * @return Woo_Open_Graph
 */
function wog() {
	return Woo_Open_Graph::get_instance();
}

/**
 * Get plugin settings.
 *
 * @return array
 */
function wog_get_settings() {
	return wog()->get_settings();
}

/**
 * Log debug message.
 *
 * @param string $message The debug message.
 * @param mixed  $data    Optional data to log.
 */
function wog_debug_log( $message, $data = null ) {
	wog()->debug_log( $message, $data );
}
