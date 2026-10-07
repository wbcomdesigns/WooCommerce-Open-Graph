<?php
/**
 * Admin Interface Class
 *
 * Card-panel settings screen under the shared WB Plugins hub, the same shell
 * every Wbcom plugin uses (reference: BuddyPress Member Reviews).
 *
 * Data contract is unchanged: one `wog_settings` option in the
 * `wog_settings_group` group, validated by WOG_Settings::validate_settings(),
 * and the `page=woo-open-graph` slug, so stored settings and links survive.
 *
 * @package Woo_Open_Graph
 * @version 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOG_Admin
 *
 * Handles the WordPress admin interface for plugin settings.
 */
class WOG_Admin {

	/**
	 * Menu slug. Kept from the old WooCommerce submenu so bookmarks and the
	 * plugin action link still resolve.
	 */
	const MENU_SLUG = 'woo-open-graph';

	/**
	 * Settings group and option name (unchanged).
	 */
	const OPTION_GROUP = 'wog_settings_group';
	const OPTION_NAME  = 'wog_settings';

	/**
	 * Hidden field that tells the sanitizer which tab posted.
	 */
	const TAB_FIELD = '_wog_tab';

	/**
	 * Singleton instance.
	 *
	 * @var WOG_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize admin interface
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Set up WordPress admin hooks
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		// Priority 999 reclaims the shared hub landing render when a legacy
		// wbcom-wrapper plugin registered wbcomplugins first.
		add_action( 'admin_menu', array( $this, 'takeover_hub_landing' ), 999 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		// options.php defaults to manage_options; the screen is open to manage_woocommerce
		// (Shop Managers), who were shown a form whose Save was refused.
		add_filter( 'option_page_capability_' . self::OPTION_GROUP, array( $this, 'settings_capability' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'in_admin_header', array( $this, 'suppress_foreign_notices' ), 1 );
		add_filter( 'plugin_action_links_' . plugin_basename( WOG_PLUGIN_FILE ), array( $this, 'add_action_links' ) );

		add_action( 'wp_ajax_wog_generate_sitemap', array( $this, 'ajax_generate_sitemap' ) );
		add_action( 'wp_ajax_wog_test_sitemap', array( $this, 'ajax_test_sitemap' ) );
	}

	/**
	 * Sidebar tabs, keyed by slug.
	 *
	 * @return array<string, array{label:string, icon:string, group:string}>
	 */
	public static function get_tabs() {
		$tabs = array(
			'overview'        => array(
				'label' => __( 'Overview', 'woo-open-graph' ),
				'icon'  => 'dashicons-chart-bar',
				'group' => 'main',
			),
			'general'         => array(
				'label' => __( 'General', 'woo-open-graph' ),
				'icon'  => 'dashicons-admin-generic',
				'group' => 'settings',
			),
			'sharing'         => array(
				'label' => __( 'Share Buttons', 'woo-open-graph' ),
				'icon'  => 'dashicons-share',
				'group' => 'settings',
			),
			'structured-data' => array(
				'label' => __( 'Structured Data', 'woo-open-graph' ),
				'icon'  => 'dashicons-editor-code',
				'group' => 'settings',
			),
			'sitemaps'        => array(
				'label' => __( 'Sitemaps', 'woo-open-graph' ),
				'icon'  => 'dashicons-networking',
				'group' => 'settings',
			),
			'advanced'        => array(
				'label' => __( 'Advanced', 'woo-open-graph' ),
				'icon'  => 'dashicons-admin-tools',
				'group' => 'settings',
			),
			'discover'        => array(
				'label' => __( 'Discover', 'woo-open-graph' ),
				'icon'  => 'dashicons-products',
				'group' => 'discover',
			),
		);

		/**
		 * Filter the settings screen tabs.
		 *
		 * @since 2.1.0
		 *
		 * @param array $tabs Tab descriptors keyed by slug.
		 */
		return apply_filters( 'wog_admin_tabs', $tabs );
	}

	/**
	 * Settings sections per tab: the one registry that drives registration,
	 * rendering and the per-tab save merge.
	 *
	 * @return array<string, array<string, array{title:string, desc:string, fields:array}>>
	 */
	private function get_sections() {
		$checkbox = static function ( $title, $description ) {
			return array(
				'type'        => 'checkbox',
				'title'       => $title,
				'description' => $description,
			);
		};

		return array(
			'general'         => array(
				'wog_core_section'     => array(
					'title'  => __( 'Social Platforms', 'woo-open-graph' ),
					'desc'   => __( 'Choose which networks get product-aware tags when a product link is shared.', 'woo-open-graph' ),
					'fields' => array(
						'enable_facebook'  => $checkbox( __( 'Facebook', 'woo-open-graph' ), __( 'Generate Facebook Open Graph meta tags for better sharing', 'woo-open-graph' ) ),
						'enable_twitter'   => $checkbox( __( 'Twitter', 'woo-open-graph' ), __( 'Generate Twitter Card meta tags for better sharing', 'woo-open-graph' ) ),
						'enable_linkedin'  => $checkbox( __( 'LinkedIn', 'woo-open-graph' ), __( 'Optimize sharing for LinkedIn professional network', 'woo-open-graph' ) ),
						'enable_pinterest' => $checkbox( __( 'Pinterest', 'woo-open-graph' ), __( 'Enable Pinterest Rich Pins with product data', 'woo-open-graph' ) ),
						'enable_whatsapp'  => $checkbox( __( 'WhatsApp', 'woo-open-graph' ), __( 'Optimize sharing for WhatsApp mobile messaging', 'woo-open-graph' ) ),
					),
				),
				'wog_platform_section' => array(
					'title'  => __( 'Platform Accounts', 'woo-open-graph' ),
					'desc'   => __( 'Optional platform-specific settings for enhanced integration.', 'woo-open-graph' ),
					'fields' => array(
						'facebook_app_id'  => array(
							'type'        => 'text',
							'title'       => __( 'Facebook App ID', 'woo-open-graph' ),
							'placeholder' => '123456789012345',
							'description' => __( 'Optional: Your Facebook App ID for better analytics and insights', 'woo-open-graph' ),
						),
						'twitter_username' => array(
							'type'        => 'text',
							'title'       => __( 'Twitter Username', 'woo-open-graph' ),
							'placeholder' => 'yourstore',
							'description' => __( 'Optional: Your Twitter username (without @) for attribution', 'woo-open-graph' ),
						),
					),
				),
				'wog_content_section'  => array(
					'title'  => __( 'Image & Content Settings', 'woo-open-graph' ),
					'desc'   => __( 'Configure how images and content appear when shared on social media.', 'woo-open-graph' ),
					'fields' => array(
						'fallback_image' => array(
							'type'        => 'image',
							'title'       => __( 'Default Social Image', 'woo-open-graph' ),
							'description' => __( 'Default image when products don\'t have featured images (recommended: 1200x630px)', 'woo-open-graph' ),
						),
						'image_size'     => array(
							'type'        => 'select',
							'title'       => __( 'Social Image Size', 'woo-open-graph' ),
							'options'     => array_intersect_key(
								array(
									'medium' => __( 'Medium (300x300)', 'woo-open-graph' ),
									'large'  => __( 'Large (1024x1024)', 'woo-open-graph' ),
									'full'   => __( 'Full Size', 'woo-open-graph' ),
								),
								array_flip( WOG_Settings::IMAGE_SIZES )
							),
							'description' => __( 'Size of product images used for social sharing', 'woo-open-graph' ),
						),
					),
				),
			),
			'sharing'         => array(
				'wog_sharing_section' => array(
					'title'  => __( 'Social Sharing Buttons', 'woo-open-graph' ),
					'desc'   => __( 'Configure social sharing buttons that appear on your product pages.', 'woo-open-graph' ),
					'fields' => array(
						'enable_social_share'   => $checkbox( __( 'Enable Share Buttons', 'woo-open-graph' ), __( 'Add social share buttons to product pages', 'woo-open-graph' ) ),
						'enable_email'          => $checkbox( __( 'Email Button', 'woo-open-graph' ), __( 'Show an Email share button (opens the shopper\'s email app with the product link)', 'woo-open-graph' ) ),
						'share_button_style'    => array(
							'type'        => 'select',
							'title'       => __( 'Button Style', 'woo-open-graph' ),
							'options'     => array(
								'modern'  => __( 'Modern', 'woo-open-graph' ),
								'classic' => __( 'Classic', 'woo-open-graph' ),
								'minimal' => __( 'Minimal', 'woo-open-graph' ),
							),
							'description' => __( 'Visual style for share buttons', 'woo-open-graph' ),
						),
						'share_button_position' => array(
							'type'        => 'select',
							'title'       => __( 'Button Position', 'woo-open-graph' ),
							'options'     => array(
								'after_add_to_cart'  => __( 'After Add to Cart Button', 'woo-open-graph' ),
								'before_add_to_cart' => __( 'Before Add to Cart Button', 'woo-open-graph' ),
								'after_summary'      => __( 'After Product Summary', 'woo-open-graph' ),
								'after_tabs'         => __( 'After Product Tabs', 'woo-open-graph' ),
							),
							'description' => __( 'Where to display social share buttons on product pages', 'woo-open-graph' ),
						),
					),
				),
			),
			'structured-data' => array(
				'wog_schema_section'       => array(
					'title'  => __( 'Product Schema', 'woo-open-graph' ),
					'desc'   => __( 'WooCommerce already outputs Product structured data. These options only add what it leaves out.', 'woo-open-graph' ),
					'fields' => array(
						// Master switch: the schema class registers all of its output only when this is on.
						'enable_schema'            => $checkbox( __( 'Structured Data', 'woo-open-graph' ), __( 'Add this plugin\'s structured data: extra Product fields (brand, GTIN, MPN), breadcrumbs and your organization. Off by default because WooCommerce already outputs Product structured data. Breadcrumb and Organization schema below only work while this is on.', 'woo-open-graph' ) ),
						'enable_enhanced_schema'   => $checkbox( __( 'Enhanced Schema', 'woo-open-graph' ), __( 'Include advanced product properties (GTIN, MPN, brand, specifications)', 'woo-open-graph' ) ),
						'enable_breadcrumb_schema' => $checkbox( __( 'Breadcrumb Schema', 'woo-open-graph' ), __( 'Add breadcrumb navigation schema markup (needs Structured Data on)', 'woo-open-graph' ) ),
					),
				),
				'wog_organization_section' => array(
					'title'  => __( 'Organization', 'woo-open-graph' ),
					'desc'   => __( 'Tell search engines who runs the store.', 'woo-open-graph' ),
					'fields' => array(
						'enable_organization_schema' => $checkbox( __( 'Organization Schema', 'woo-open-graph' ), __( 'Add organization and store information schema (needs Structured Data on)', 'woo-open-graph' ) ),
						'organization_logo'          => array(
							'type'        => 'image',
							'title'       => __( 'Organization Logo', 'woo-open-graph' ),
							'description' => __( 'Logo published in the Organization structured data. Leave empty to use your theme logo, then your Site Icon.', 'woo-open-graph' ),
						),
						'social_profiles'            => array(
							'type'        => 'textarea',
							'title'       => __( 'Social Profiles', 'woo-open-graph' ),
							'placeholder' => "https://www.facebook.com/yourstore\nhttps://www.instagram.com/yourstore",
							'description' => __( 'One profile URL per line. Published as sameAs in the Organization structured data so search engines can link your store to its profiles.', 'woo-open-graph' ),
						),
					),
				),
			),
			'sitemaps'        => array(
				'wog_sitemap_section' => array(
					'title'  => __( 'XML Sitemaps', 'woo-open-graph' ),
					'desc'   => __( 'Generate XML sitemaps to help search engines discover your products and categories.', 'woo-open-graph' ),
					'fields' => array(
						'enable_product_sitemap'    => array(
							'type'        => 'sitemap',
							'title'       => __( 'Enable Product Sitemaps', 'woo-open-graph' ),
							'description' => __( 'Generate XML sitemaps for products and categories', 'woo-open-graph' ),
						),
						'sitemap_products_per_page' => array(
							'type'        => 'number',
							'title'       => __( 'Products Per Sitemap', 'woo-open-graph' ),
							'min'         => 100,
							'max'         => 1000,
							'default'     => 500,
							'description' => __( 'Number of products per sitemap file (recommended: 500)', 'woo-open-graph' ),
						),
					),
				),
			),
			'advanced'        => array(
				'wog_advanced_section' => array(
					'title'  => __( 'Advanced Settings', 'woo-open-graph' ),
					'desc'   => __( 'Advanced options for power users and specific use cases.', 'woo-open-graph' ),
					'fields' => array(
						'disable_title_description' => $checkbox( __( 'Override SEO Titles', 'woo-open-graph' ), __( 'Override titles and descriptions from other SEO plugins (use with caution)', 'woo-open-graph' ) ),
						'delete_data_on_uninstall'  => $checkbox( __( 'Delete Data On Uninstall', 'woo-open-graph' ), __( 'Delete all plugin data (settings and per-product social titles/descriptions) when the plugin is deleted', 'woo-open-graph' ) ),
					),
				),
			),
		);
	}

	/**
	 * Capability options.php checks before saving our settings group.
	 *
	 * @return string
	 */
	public function settings_capability() {
		return 'manage_woocommerce';
	}

	/**
	 * Settings page id for one tab (the do_settings_sections() page).
	 *
	 * @param string $tab Tab slug.
	 * @return string
	 */
	public static function settings_page_id( $tab ) {
		return 'wog_settings_' . $tab;
	}

	/**
	 * Attach as a submenu under the shared WB Plugins hub.
	 */
	public function add_admin_menu() {
		// First Wbcom plugin to load creates the shared WB Plugins hub.
		if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
			add_menu_page(
				esc_html__( 'WB Plugins', 'woo-open-graph' ),
				esc_html__( 'WB Plugins', 'woo-open-graph' ),
				'manage_options',
				'wbcomplugins',
				array( $this, 'render_hub' ),
				'dashicons-lightbulb',
				59
			);
		}

		add_submenu_page(
			'wbcomplugins',
			'Open Graph for WooCommerce',
			esc_html__( 'Open Graph', 'woo-open-graph' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Render the shared hub landing with the card-panel dashboard, whichever
	 * Wbcom plugin registered the hub first.
	 */
	public function takeover_hub_landing() {
		global $admin_page_hooks;
		if ( empty( $admin_page_hooks['wbcomplugins'] ) ) {
			return;
		}
		remove_all_actions( 'toplevel_page_wbcomplugins' );
		add_action( 'toplevel_page_wbcomplugins', array( $this, 'render_hub' ) );
	}

	/**
	 * Register the setting plus one settings page per tab.
	 */
	public function register_settings() {
		register_setting( self::OPTION_GROUP, self::OPTION_NAME, array( $this, 'sanitize_settings' ) );

		foreach ( $this->get_sections() as $tab => $sections ) {
			$page = self::settings_page_id( $tab );
			foreach ( $sections as $section_id => $section ) {
				add_settings_section( $section_id, $section['title'], '__return_false', $page );
				foreach ( $section['fields'] as $id => $field ) {
					add_settings_field(
						$id,
						$field['title'],
						array( $this, $field['type'] . '_field' ),
						$page,
						$section_id,
						array_merge( $field, array( 'id' => $id ) )
					);
				}
			}
		}
	}

	/**
	 * Section descriptions for one tab, keyed by section id.
	 *
	 * @param string $tab Tab slug.
	 * @return array<string, string>
	 */
	public function get_section_descriptions( $tab ) {
		$sections = $this->get_sections();
		return isset( $sections[ $tab ] ) ? wp_list_pluck( $sections[ $tab ], 'desc' ) : array();
	}

	/**
	 * Render the shared WB Plugins hub landing page.
	 */
	public function render_hub() {
		include WOG_PLUGIN_DIR . 'admin/views/hub.php';
	}

	/**
	 * Render the settings screen: shell + active tab.
	 */
	public function admin_page() {
		$wog_tabs  = self::get_tabs();
		$tab_slugs = array_keys( $wog_tabs );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab routing.
		$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $tab_slugs[0];
		if ( ! isset( $wog_tabs[ $active ] ) ) {
			$active = $tab_slugs[0];
		}

		$page_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );

		if ( in_array( $active, array( 'overview', 'discover' ), true ) ) {
			$view_path = WOG_PLUGIN_DIR . 'admin/views/' . $active . '.php';
		} else {
			$view_path = WOG_PLUGIN_DIR . 'admin/views/settings.php';
		}

		include WOG_PLUGIN_DIR . 'admin/views/shell.php';
	}

	/**
	 * Render a checkbox settings field as a switch.
	 *
	 * @param array $args The field arguments.
	 */
	public function checkbox_field( $args ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings[ $args['id'] ] ) ? $settings[ $args['id'] ] : false;

		echo '<div class="wog-switch-row">';
		echo '<label class="wog-switch">';
		echo '<input type="checkbox" id="wog_' . esc_attr( $args['id'] ) . '" name="wog_settings[' . esc_attr( $args['id'] ) . ']" value="1" ' . checked( 1, $value, false ) . ' />';
		echo '<span class="wog-slider" aria-hidden="true"></span>';
		echo '</label>';
		echo '<label for="wog_' . esc_attr( $args['id'] ) . '" class="description">' . esc_html( $args['description'] ) . '</label>';
		echo '</div>';
	}

	/**
	 * Render a text settings field.
	 *
	 * @param array $args The field arguments.
	 */
	public function text_field( $args ) {
		$settings    = get_option( self::OPTION_NAME, array() );
		$value       = isset( $settings[ $args['id'] ] ) ? $settings[ $args['id'] ] : '';
		$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';

		echo '<input type="text" ';
		echo 'name="wog_settings[' . esc_attr( $args['id'] ) . ']" ';
		echo 'value="' . esc_attr( $value ) . '" ';
		echo 'placeholder="' . esc_attr( $placeholder ) . '" ';
		echo 'class="regular-text" />';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render a textarea settings field (array values shown one per line).
	 *
	 * @param array $args The field arguments.
	 */
	public function textarea_field( $args ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings[ $args['id'] ] ) ? $settings[ $args['id'] ] : '';
		$value    = is_array( $value ) ? implode( "\n", $value ) : (string) $value;

		echo '<textarea name="wog_settings[' . esc_attr( $args['id'] ) . ']" rows="4" class="large-text" ';
		echo 'placeholder="' . esc_attr( isset( $args['placeholder'] ) ? $args['placeholder'] : '' ) . '">';
		echo esc_textarea( $value );
		echo '</textarea>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render a select settings field.
	 *
	 * @param array $args The field arguments.
	 */
	public function select_field( $args ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings[ $args['id'] ] ) ? $settings[ $args['id'] ] : '';

		echo '<select name="wog_settings[' . esc_attr( $args['id'] ) . ']">';
		foreach ( $args['options'] as $option_value => $option_label ) {
			echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>';
			echo esc_html( $option_label );
			echo '</option>';
		}
		echo '</select>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render a number settings field.
	 *
	 * @param array $args The field arguments.
	 */
	public function number_field( $args ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings[ $args['id'] ] ) ? $settings[ $args['id'] ] : ( $args['default'] ?? '' );
		$min_attr = isset( $args['min'] ) ? ' min="' . esc_attr( $args['min'] ) . '"' : '';
		$max_attr = isset( $args['max'] ) ? ' max="' . esc_attr( $args['max'] ) . '"' : '';

		echo '<input type="number" ';
		echo 'name="wog_settings[' . esc_attr( $args['id'] ) . ']" ';
		echo 'value="' . esc_attr( $value ) . '" ';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are pre-escaped with esc_attr above.
		echo $min_attr . $max_attr . ' ';
		echo 'class="small-text" />';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render an image upload settings field.
	 *
	 * @param array $args The field arguments.
	 */
	public function image_field( $args ) {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings[ $args['id'] ] ) ? $settings[ $args['id'] ] : '';

		echo '<div class="wog-image-field">';
		echo '<input type="url" ';
		echo 'name="wog_settings[' . esc_attr( $args['id'] ) . ']" ';
		echo 'value="' . esc_attr( $value ) . '" ';
		echo 'placeholder="https://example.com/image.jpg" ';
		echo 'class="regular-text" />';

		echo '<button type="button" class="button" data-wog-select-image>' . esc_html__( 'Choose Image', 'woo-open-graph' ) . '</button>';

		echo '<img src="' . esc_url( $value ) . '" class="wog-image-preview" alt=""' . ( $value ? '' : ' hidden' ) . ' />';
		echo '</div>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render the sitemap settings field.
	 *
	 * @param array $args The field arguments.
	 */
	public function sitemap_field( $args ) {
		$settings       = get_option( self::OPTION_NAME, array() );
		$value          = ! empty( $settings[ $args['id'] ] );
		$last_generated = get_option( 'wog_sitemap_last_generated', 0 );

		$this->checkbox_field( $args );

		if ( ! $value ) {
			return;
		}

		echo '<div class="wog-sitemap-box">';

		if ( $last_generated ) {
			echo '<p><strong>' . esc_html__( 'Last Generated:', 'woo-open-graph' ) . '</strong> ' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_generated ) ) . '</p>';
		}

		echo '<p><strong>' . esc_html__( 'Sitemap URLs:', 'woo-open-graph' ) . '</strong></p>';
		echo '<ul>';
		echo '<li><a href="' . esc_url( home_url( '/wog-sitemap.xml' ) ) . '" target="_blank">' . esc_url( home_url( '/wog-sitemap.xml' ) ) . '</a> (Main Index)</li>';
		echo '<li><a href="' . esc_url( home_url( '/product-sitemap-1.xml' ) ) . '" target="_blank">' . esc_url( home_url( '/product-sitemap-1.xml' ) ) . '</a> (Products)</li>';
		echo '<li><a href="' . esc_url( home_url( '/product-category-sitemap.xml' ) ) . '" target="_blank">' . esc_url( home_url( '/product-category-sitemap.xml' ) ) . '</a> (Categories)</li>';
		echo '</ul>';

		echo '<p class="wog-action-row">';
		echo '<button type="button" class="button" data-wog-sitemap="generate">' . esc_html__( 'Generate Now', 'woo-open-graph' ) . '</button> ';
		echo '<button type="button" class="button" data-wog-sitemap="test">' . esc_html__( 'Test Sitemaps', 'woo-open-graph' ) . '</button>';
		echo '</p>';

		echo '<div id="wog-sitemap-results" aria-live="polite"></div>';
		echo '</div>';
	}

	/**
	 * Add settings link to plugin actions.
	 *
	 * @param array $links The existing plugin action links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) . '">' . esc_html__( 'Settings', 'woo-open-graph' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * True on our settings screen or the shared hub landing (we own its render).
	 *
	 * @return bool
	 */
	private function is_our_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || empty( $screen->id ) ) {
			return false;
		}
		return (bool) preg_match( '/_page_' . preg_quote( self::MENU_SLUG, '/' ) . '$/', $screen->id )
			|| 'toplevel_page_wbcomplugins' === $screen->id;
	}

	/**
	 * Suppress third-party admin notices on our screen only.
	 */
	public function suppress_foreign_notices() {
		if ( ! $this->is_our_screen() ) {
			return;
		}
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function enqueue_admin_scripts() {
		if ( ! $this->is_our_screen() ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style( 'wog-admin', WOG_PLUGIN_URL . 'assets/css/admin.css', array(), WOG_VERSION );
		wp_enqueue_script( 'wog-admin', WOG_PLUGIN_URL . 'assets/js/admin.js', array(), WOG_VERSION, true );
		wp_localize_script(
			'wog-admin',
			'wogAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wog_admin_nonce' ),
				'i18n'    => array(
					'generating'  => __( 'Generating...', 'woo-open-graph' ),
					'generate'    => __( 'Generate Now', 'woo-open-graph' ),
					'testing'     => __( 'Testing...', 'woo-open-graph' ),
					'test'        => __( 'Test Sitemaps', 'woo-open-graph' ),
					'failed'      => __( 'Request failed. Please try again.', 'woo-open-graph' ),
					'chooseImage' => __( 'Choose Image', 'woo-open-graph' ),
					'useImage'    => __( 'Use This Image', 'woo-open-graph' ),
				),
			)
		);
	}

	/**
	 * AJAX handler for sitemap generation
	 */
	public function ajax_generate_sitemap() {
		check_ajax_referer( 'wog_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'woo-open-graph' ) ) );
		}

		if ( class_exists( 'WOG_Sitemap' ) ) {
			$sitemap = WOG_Sitemap::get_instance();
			$sitemap->generate_all_sitemaps_background();

			// The work is scheduled, not done: say so. "Last Generated" is stamped
			// by the background job when a sitemap is actually written.
			wp_send_json_success(
				array(
					'message' => __( 'Sitemap generation queued. Files are rebuilt in the background over the next few minutes.', 'woo-open-graph' ),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Sitemap generation not available', 'woo-open-graph' ),
				)
			);
		}
	}

	/**
	 * AJAX handler for sitemap testing
	 */
	public function ajax_test_sitemap() {
		check_ajax_referer( 'wog_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'woo-open-graph' ) ) );
		}

		$sitemap_url = home_url( '/wog-sitemap.xml' );
		$response    = wp_remote_get( $sitemap_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to fetch sitemap: ', 'woo-open-graph' ) . $response->get_error_message(),
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			wp_send_json_error(
				array(
					/* translators: %d: HTTP response code. */
					'message' => sprintf( __( 'Sitemap returned HTTP %d', 'woo-open-graph' ), $code ),
				)
			);
		}

		if ( false === strpos( $body, '<sitemapindex' ) && false === strpos( $body, '<urlset' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid sitemap format', 'woo-open-graph' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Sitemaps are working correctly!', 'woo-open-graph' ),
			)
		);
	}

	/**
	 * Sanitize and validate settings input.
	 *
	 * Each tab posts only its own fields, and validate_settings() rebuilds every
	 * key from its input (a missing checkbox becomes false, a missing text ''),
	 * so a tab save is merged over the stored option first. Without the merge,
	 * saving one tab would reset every other tab to its defaults.
	 *
	 * @param array $input The raw settings input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$input = (array) $input;
		$tab   = isset( $input[ self::TAB_FIELD ] ) ? sanitize_key( $input[ self::TAB_FIELD ] ) : '';
		unset( $input[ self::TAB_FIELD ] );

		$sections = $this->get_sections();
		if ( isset( $sections[ $tab ] ) ) {
			$merged = (array) get_option( self::OPTION_NAME, array() );
			foreach ( $sections[ $tab ] as $section ) {
				foreach ( array_keys( $section['fields'] ) as $field_id ) {
					// Absent = unchecked checkbox; validate_settings() turns null into its "off" value.
					$merged[ $field_id ] = isset( $input[ $field_id ] ) ? $input[ $field_id ] : null;
				}
			}
			$input = $merged;
		}

		// One sanitizer for every write path (settings screen, import), so the
		// accepted values cannot drift between them.
		return WOG_Settings::get_instance()->validate_settings( $input );
	}
}
