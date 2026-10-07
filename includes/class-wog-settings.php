<?php
/**
 * Enhanced Settings Class.
 *
 * Comprehensive settings management with caching and validation.
 *
 * @package Woo_Open_Graph
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOG_Settings
 *
 * Manages plugin settings with caching, validation, and migration.
 */
class WOG_Settings {

	/**
	 * Singleton instance.
	 *
	 * @var WOG_Settings|null
	 */
	private static $instance = null;

	/**
	 * Current settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Default settings values.
	 *
	 * @var array
	 */
	private $default_settings;

	/**
	 * Image sizes an og:image may use. The one list the sanitizer and the admin
	 * select both read, so they cannot drift. (No 'thumbnail': it is below
	 * Facebook's 200px minimum.)
	 */
	const IMAGE_SIZES = array( 'medium', 'large', 'full' );

	/**
	 * Cache key for settings.
	 *
	 * @var string
	 */
	private $cache_key = 'wog_settings_cache';

	/**
	 * Get singleton instance.
	 *
	 * @return WOG_Settings
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->set_default_settings();
		$this->load_settings();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Clear cache when settings are updated.
		add_action( 'update_option_wog_settings', array( $this, 'clear_settings_cache' ) );
	}

	/**
	 * Set default settings.
	 */
	private function set_default_settings() {
		$this->default_settings = array(
			// Schema settings. Off by default: WooCommerce core already emits Product
			// structured data. When enabled, this plugin gap-fills Woo's graph rather
			// than emitting a second, competing one.
			'enable_schema'              => false,
			'enable_enhanced_schema'     => true,
			'enable_breadcrumb_schema'   => true,
			'enable_organization_schema' => true,

			// Open Graph settings.
			'enable_facebook'            => true,
			'enable_twitter'             => true,
			'enable_linkedin'            => true,
			'enable_pinterest'           => true,
			'enable_whatsapp'            => true,
			'enable_email'               => false,
			'disable_title_description'  => false,
			'image_size'                 => 'large',
			'fallback_image'             => '',
			'facebook_app_id'            => '',
			'twitter_username'           => '',

			// Organization schema.
			'organization_logo'          => '',
			'social_profiles'            => array(),

			// Sitemap settings.
			'enable_product_sitemap'     => true,
			'sitemap_products_per_page'  => 500,

			// Social sharing settings.
			'enable_social_share'        => true,
			'share_button_style'         => 'modern',
			'share_button_position'      => 'after_add_to_cart',

			// Advanced settings.
			'debug_mode'                 => false,
			'delete_data_on_uninstall'   => false,
		);

		// Allow plugins to modify default settings.
		$this->default_settings = apply_filters( 'wog_default_settings', $this->default_settings );
	}

	/**
	 * Load settings from database with caching.
	 */
	private function load_settings() {
		// Try to get from cache first.
		$cached_settings = wp_cache_get( $this->cache_key, 'wog' );

		if ( false !== $cached_settings ) {
			$this->settings = $cached_settings;
			return;
		}

		// Load from database.
		$saved_settings = get_option( 'wog_settings', array() );
		$this->settings = wp_parse_args( $saved_settings, $this->default_settings );

		// Cache the settings.
		wp_cache_set( $this->cache_key, $this->settings, 'wog', HOUR_IN_SECONDS );
	}

	/**
	 * Clear settings cache.
	 */
	public function clear_settings_cache() {
		wp_cache_delete( $this->cache_key, 'wog' );

		// Reload settings.
		$this->load_settings();

		do_action( 'wog_settings_cache_cleared' );
	}

	/**
	 * Get a specific setting.
	 *
	 * @param string $key           The setting key.
	 * @param mixed  $default_value The default value if not set.
	 * @return mixed
	 */
	public function get( $key, $default_value = null ) {
		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		}

		return null !== $default_value ? $default_value : ( isset( $this->default_settings[ $key ] ) ? $this->default_settings[ $key ] : null );
	}

	/**
	 * Get all settings.
	 *
	 * @return array
	 */
	public function get_all_settings() {
		return $this->settings;
	}

	/**
	 * Update multiple settings.
	 *
	 * @param array $settings The settings to update.
	 * @return bool
	 */
	public function update_multiple( $settings ) {
		$this->settings = wp_parse_args( $settings, $this->settings );
		$result         = update_option( 'wog_settings', $this->settings );

		if ( $result ) {
			$this->clear_settings_cache();
			do_action( 'wog_settings_updated', $settings );
		}

		return $result;
	}

	/**
	 * The default settings: the one array activation and every read share.
	 *
	 * @since 2.1.0
	 * @return array
	 */
	public function get_default_settings() {
		return $this->default_settings;
	}

	/**
	 * Validate settings.
	 *
	 * @param array $settings The settings to validate.
	 * @return array
	 */
	public function validate_settings( $settings ) {
		$validated = array();

		// Boolean settings.
		$boolean_settings = array(
			'enable_schema',
			'enable_enhanced_schema',
			'enable_breadcrumb_schema',
			'enable_organization_schema',
			'enable_facebook',
			'enable_twitter',
			'enable_linkedin',
			'enable_pinterest',
			'enable_whatsapp',
			'enable_email',
			'disable_title_description',
			'enable_product_sitemap',
			'enable_social_share',
			'debug_mode',
			'delete_data_on_uninstall',
		);

		foreach ( $boolean_settings as $setting ) {
			$validated[ $setting ] = isset( $settings[ $setting ] ) ? (bool) $settings[ $setting ] : false;
		}

		// Text settings with sanitization.
		$validated['twitter_username'] = sanitize_text_field( $settings['twitter_username'] ?? '' );
		$validated['facebook_app_id']  = sanitize_text_field( $settings['facebook_app_id'] ?? '' );

		// Remove @ from Twitter username if present.
		if ( ! empty( $validated['twitter_username'] ) && '@' === $validated['twitter_username'][0] ) {
			$validated['twitter_username'] = substr( $validated['twitter_username'], 1 );
		}

		// Validate Twitter username format: an unresolvable handle is worse than none.
		if ( ! empty( $validated['twitter_username'] ) && ! preg_match( '/^[A-Za-z0-9_]{1,15}$/', $validated['twitter_username'] ) ) {
			if ( function_exists( 'add_settings_error' ) ) {
				add_settings_error(
					'wog_settings',
					'wog_twitter_username',
					/* translators: %s: rejected username. */
					sprintf( __( '"%s" is not a valid X (Twitter) username: use 1-15 letters, numbers or underscores. The username was not changed.', 'woo-open-graph' ), esc_html( $validated['twitter_username'] ) )
				);
			}
			$previous                      = get_option( 'wog_settings', array() );
			$validated['twitter_username'] = isset( $previous['twitter_username'] ) ? (string) $previous['twitter_username'] : '';
		}

		// Select settings with validation.
		$validated['image_size'] = in_array( $settings['image_size'] ?? '', self::IMAGE_SIZES, true ) ?
			$settings['image_size'] : 'large';

		$valid_button_styles             = array( 'modern', 'classic', 'minimal' );
		$validated['share_button_style'] = in_array( $settings['share_button_style'] ?? '', $valid_button_styles, true ) ?
			$settings['share_button_style'] : 'modern';

		$valid_button_positions             = array( 'after_add_to_cart', 'before_add_to_cart', 'after_summary', 'after_tabs' );
		$validated['share_button_position'] = in_array( $settings['share_button_position'] ?? '', $valid_button_positions, true ) ?
			$settings['share_button_position'] : 'after_add_to_cart';

		// Number settings with validation.
		$validated['sitemap_products_per_page'] = intval( $settings['sitemap_products_per_page'] ?? 500 );
		if ( $validated['sitemap_products_per_page'] < 100 ) {
			$validated['sitemap_products_per_page'] = 100;
		}
		if ( $validated['sitemap_products_per_page'] > 1000 ) {
			$validated['sitemap_products_per_page'] = 1000;
		}

		// URL settings.
		$validated['fallback_image']    = esc_url_raw( $settings['fallback_image'] ?? '' );
		$validated['organization_logo'] = esc_url_raw( $settings['organization_logo'] ?? '' );

		// Social profiles: one URL per line from the textarea, or an array on import.
		$profiles = $settings['social_profiles'] ?? array();
		if ( is_string( $profiles ) ) {
			$profiles = preg_split( '/[\r\n]+/', $profiles );
		}
		$validated['social_profiles'] = array();
		foreach ( array_filter( (array) $profiles, 'is_string' ) as $profile ) {
			$profile = trim( $profile );
			if ( filter_var( $profile, FILTER_VALIDATE_URL ) && in_array( wp_parse_url( $profile, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) {
				$validated['social_profiles'][] = esc_url_raw( $profile );
			}
		}

		return apply_filters( 'wog_validated_settings', $validated, $settings );
	}

	/**
	 * Migration helper for old settings.
	 */
	public function migrate_old_settings() {
		if ( get_option( 'wog_migration_completed' ) ) {
			return;
		}

		$old_settings = get_option( 'woo_open_graph_settings', false );

		if ( $old_settings && is_array( $old_settings ) ) {
			$migration_map = array(
				'enable_facebook'  => 'enable_facebook',
				'enable_twitter'   => 'enable_twitter',
				'facebook_app_id'  => 'facebook_app_id',
				'twitter_username' => 'twitter_username',
			);

			// Fill only keys still at their default, so a value set since 2.0 is never overwritten.
			$defaults          = $this->get_default_settings();
			$migrated_settings = array();
			foreach ( $migration_map as $old_key => $new_key ) {
				if ( isset( $old_settings[ $old_key ] ) && $this->get( $new_key ) === $defaults[ $new_key ] ) {
					$migrated_settings[ $new_key ] = $old_settings[ $old_key ];
				}
			}

			if ( ! empty( $migrated_settings ) ) {
				$this->update_multiple( $migrated_settings );
				do_action( 'wog_settings_migrated', $migrated_settings, $old_settings );
			}
		}

		update_option( 'wog_migration_completed', true );
	}
}
