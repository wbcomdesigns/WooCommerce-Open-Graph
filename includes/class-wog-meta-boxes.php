<?php
/**
 * Clean WordPress-Native Meta Boxes.
 *
 * Simple meta boxes that follow WordPress standards.
 * Just helps fill gaps that main SEO plugins might miss.
 *
 * @package Woo_Open_Graph
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOG_Meta_Boxes
 *
 * Handles product meta boxes for social media settings.
 */
class WOG_Meta_Boxes {

	/**
	 * Singleton instance.
	 *
	 * @var WOG_Meta_Boxes|null
	 */
	private static $instance = null;

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Get singleton instance.
	 *
	 * @return WOG_Meta_Boxes
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
		$this->settings = get_option( 'wog_settings', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_product_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_product_meta_boxes' ) );
		// After WooCommerce (priority 10) rebuilds the product columns; before that
		// there is no 'name' column to insert after, so the column never appeared.
		add_filter( 'manage_product_posts_columns', array( $this, 'add_product_columns' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( $this, 'populate_product_columns' ), 10, 2 );
	}

	/**
	 * Add meta boxes.
	 */
	public function add_product_meta_boxes() {
		add_meta_box(
			'wog_social_settings',
			__( 'Social Media Settings', 'woo-open-graph' ),
			array( $this, 'render_social_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Render clean WordPress-native meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_social_meta_box( $post ) {
		wp_nonce_field( 'wog_product_meta_box', 'wog_meta_box_nonce' );

		// Get current values.
		$og_title       = get_post_meta( $post->ID, '_wog_og_title', true );
		$og_description = get_post_meta( $post->ID, '_wog_og_description', true );
		$disable_og     = get_post_meta( $post->ID, '_wog_disable_og', true );

		// Placeholders show exactly what will be published: the same builders the
		// front end uses, not a second implementation that can drift.
		$product             = wc_get_product( $post->ID );
		$default_title       = get_the_title( $post->ID );
		$default_description = '';

		if ( $product && class_exists( 'WOG_Meta_Tags' ) ) {
			$default_title       = WOG_Meta_Tags::get_instance()->get_optimized_title( $product );
			$default_description = WOG_Meta_Tags::get_instance()->get_optimized_description( $product );
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
							<?php checked( ! $disable_og, true ); ?> />
					<strong><?php esc_html_e( 'Enable social sharing output for this product', 'woo-open-graph' ); ?></strong>
				</label>
				<br>
				<span class="description">
					<?php esc_html_e( 'Social preview tags and share buttons for this product. Search structured data (Google product results) is not affected.', 'woo-open-graph' ); ?>
				</span>
			</p>

			<div id="wog-fields" <?php echo $disable_og ? 'style="display:none;"' : ''; ?>>
				<!-- Title Field -->
				<p>
					<label for="wog_og_title">
						<strong><?php esc_html_e( 'Social Media Title', 'woo-open-graph' ); ?></strong>
					</label>
					<input type="text"
							id="wog_og_title"
							name="wog_og_title"
							value="<?php echo esc_attr( $og_title ); ?>"
							class="widefat"
							placeholder="<?php echo esc_attr( $default_title ); ?>"
							maxlength="60" />
					<span class="description">
						<?php esc_html_e( 'Custom title for social sharing. Leave empty to use product name.', 'woo-open-graph' ); ?>
						<span class="wog-counter" data-current="<?php echo esc_attr( mb_strlen( $og_title ) ); ?>" data-max="60">
							(<?php echo esc_html( mb_strlen( $og_title ) ); ?>/60)
						</span>
					</span>
				</p>

				<!-- Description Field -->
				<p>
					<label for="wog_og_description">
						<strong><?php esc_html_e( 'Social Media Description', 'woo-open-graph' ); ?></strong>
					</label>
					<textarea id="wog_og_description"
								name="wog_og_description"
								class="widefat"
								rows="3"
								placeholder="<?php echo esc_attr( $default_description ); ?>"
								maxlength="155"><?php echo esc_textarea( $og_description ); ?></textarea>
					<span class="description">
						<?php esc_html_e( 'Custom description for social sharing. Leave empty to use product description.', 'woo-open-graph' ); ?>
						<span class="wog-counter" data-current="<?php echo esc_attr( mb_strlen( $og_description ) ); ?>" data-max="155">
							(<?php echo esc_html( mb_strlen( $og_description ) ); ?>/155)
						</span>
					</span>
				</p>

				<!-- Image Info -->
				<p class="wog-image-info">
					<strong><?php esc_html_e( 'Social Media Image:', 'woo-open-graph' ); ?></strong>
					<?php if ( $product && $product->get_image_id() ) : ?>
						<span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Featured image will be used', 'woo-open-graph' ); ?></span>
					<?php else : ?>
						<span style="color: #d63638;">&#9888; <?php esc_html_e( 'No featured image set', 'woo-open-graph' ); ?></span>
						<br><span class="description"><?php esc_html_e( 'Set a featured image to improve social media sharing.', 'woo-open-graph' ); ?></span>
					<?php endif; ?>
				</p>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Toggle fields when checkbox changes.
			$('#wog_enable_og').change(function() {
				$('#wog-fields').toggle(this.checked);
			});

			// Simple character counters.
			function updateCounter(field) {
				var $field = $(field);
				var $counter = $field.siblings('.description').find('.wog-counter');
				var current = $field.val().length;
				var max = parseInt($counter.data('max'));

				$counter.text('(' + current + '/' + max + ')');

				// Simple color coding.
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

			// Bind counter updates.
			$('#wog_og_title, #wog_og_description').on('input', function() {
				updateCounter(this);
			});

			// Initialize counters.
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
			border-inline-start: 4px solid #00a32a;
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
	 * Add product list column.
	 *
	 * @param array $columns The existing columns.
	 * @return array
	 */
	public function add_product_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'name' === $key ) {
				$new_columns['wog_status'] = __( 'Social', 'woo-open-graph' );
			}
		}

		return $new_columns;
	}

	/**
	 * Populate status column.
	 *
	 * @param string $column  The column name.
	 * @param int    $post_id The post ID.
	 */
	public function populate_product_columns( $column, $post_id ) {
		if ( 'wog_status' === $column ) {
			if ( ! wog_is_social_enabled_for_product( $post_id ) ) {
				$state = array( 'off', '#d63638', __( 'Off', 'woo-open-graph' ), __( 'Social output disabled for this product', 'woo-open-graph' ) );
			} elseif ( get_post_meta( $post_id, '_wog_og_title', true ) || get_post_meta( $post_id, '_wog_og_description', true ) ) {
				$state = array( 'custom', '#dba617', __( 'Custom', 'woo-open-graph' ), __( 'Custom social title or description', 'woo-open-graph' ) );
			} else {
				$state = array( 'auto', '#00a32a', __( 'Auto', 'woo-open-graph' ), __( 'Automatic social content', 'woo-open-graph' ) );
			}

			// Text carries the state; the colour only reinforces it.
			printf(
				'<span class="wog-status wog-status-%1$s" title="%4$s"><span aria-hidden="true" style="color:%2$s;">&#9679;</span> %3$s<span class="screen-reader-text">: %4$s</span></span>',
				esc_attr( $state[0] ),
				esc_attr( $state[1] ),
				esc_html( $state[2] ),
				esc_attr( $state[3] )
			);
		}
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_product_meta_boxes( $post_id ) {
		// Security checks.
		if ( ! isset( $_POST['wog_meta_box_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wog_meta_box_nonce'] ) ), 'wog_product_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		// Save fields.
		$fields = array(
			'wog_enable_og'      => '_wog_disable_og', // Inverted logic.
			'wog_og_title'       => '_wog_og_title',
			'wog_og_description' => '_wog_og_description',
		);

		foreach ( $fields as $field => $meta_key ) {
			if ( 'wog_enable_og' === $field ) {
				// Inverted checkbox: save as 1 if NOT checked (disabled), empty if checked (enabled).
				$value = empty( $_POST[ $field ] ) ? '1' : '';
			} elseif ( 'wog_og_description' === $field ) {
				$value = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ?? '' ) );
			} else {
				$value = sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) );
			}

			if ( empty( $value ) ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}

		do_action( 'wog_product_meta_saved', $post_id );
	}
}
