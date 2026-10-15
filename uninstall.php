<?php
/**
 * Uninstall Open Graph for WooCommerce.
 *
 * Always removes the scheduled sitemap jobs and cached sitemaps. Settings and
 * per-product overrides are removed only when the owner ticked
 * Advanced > "Delete all plugin data when the plugin is deleted", so a
 * delete-and-reinstall keeps their configuration by default.
 *
 * Runs outside the plugin bootstrap: no plugin classes are available here.
 *
 * @package Woo_Open_Graph
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

wp_unschedule_hook( 'wog_generate_sitemaps' );
wp_unschedule_hook( 'wog_generate_single_sitemap' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off cleanup of this plugin's own transients.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wog_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wog_' ) . '%'
	)
);

$wog_settings = get_option( 'wog_settings', array() );

if ( empty( $wog_settings['delete_data_on_uninstall'] ) ) {
	return;
}

foreach ( array( 'wog_settings', 'wog_version', 'wog_migration_completed', 'wog_sitemap_last_generated', 'wog_flush_rewrite_rules', 'wog_rewrite_rules_flushed_v2' ) as $wog_option ) {
	delete_option( $wog_option );
}

foreach ( array( '_wog_og_title', '_wog_og_description', '_wog_disable_og' ) as $wog_meta_key ) {
	delete_post_meta_by_key( $wog_meta_key );
}
