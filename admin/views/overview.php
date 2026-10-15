<?php
/**
 * Overview partial: what the plugin is doing on this store right now.
 *
 * Rendered inside shell.php.
 *
 * @package Woo_Open_Graph
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$wog_settings = WOG_Settings::get_instance();
$wog_on       = static function ( $key ) use ( $wog_settings ) {
	return (bool) $wog_settings->get( $key, false );
};

$wog_products = wp_count_posts( 'product' );
$wog_products = isset( $wog_products->publish ) ? (int) $wog_products->publish : 0;

// One indexed pass over postmeta for both per-product counts.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin-only aggregate.
$wog_meta_counts = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT
			COUNT( DISTINCT CASE WHEN pm.meta_key IN ( %s, %s ) THEN pm.post_id END ) AS custom_text,
			COUNT( DISTINCT CASE WHEN pm.meta_key = %s THEN pm.post_id END ) AS social_off
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key IN ( %s, %s, %s )
		 AND pm.meta_value <> ''
		 AND p.post_type = 'product'
		 AND p.post_status = 'publish'",
		'_wog_og_title',
		'_wog_og_description',
		'_wog_disable_og',
		'_wog_og_title',
		'_wog_og_description',
		'_wog_disable_og'
	)
);
$wog_custom_text = $wog_meta_counts ? (int) $wog_meta_counts->custom_text : 0;
$wog_social_off  = $wog_meta_counts ? (int) $wog_meta_counts->social_off : 0;

$wog_sitemap_on   = $wog_on( 'enable_product_sitemap' );
$wog_sitemap_last = (int) get_option( 'wog_sitemap_last_generated', 0 );

$wog_tab_url = static function ( $tab ) use ( $page_url ) {
	return $page_url . '&tab=' . $tab;
};

$wog_positions = array(
	'after_add_to_cart'  => __( 'after the Add to Cart button', 'woo-open-graph' ),
	'before_add_to_cart' => __( 'before the Add to Cart button', 'woo-open-graph' ),
	'after_summary'      => __( 'after the product summary', 'woo-open-graph' ),
	'after_tabs'         => __( 'after the product tabs', 'woo-open-graph' ),
);
$wog_position  = $wog_settings->get( 'share_button_position', 'after_add_to_cart' );
$wog_position  = isset( $wog_positions[ $wog_position ] ) ? $wog_positions[ $wog_position ] : $wog_positions['after_add_to_cart'];
/* translators: %s: where the buttons appear, e.g. "after the Add to Cart button". */
$wog_share_on = sprintf( __( 'On: shown on product pages %s', 'woo-open-graph' ), $wog_position );

/*
 * Configuration rows, written as consequences rather than stored values.
 * Each: whether it is on, row label, "on" sentence, tab that changes it.
 * Organization schema is only published while the Structured Data master switch is on.
 */
$wog_rows = array(
	array( $wog_on( 'enable_facebook' ), __( 'Facebook', 'woo-open-graph' ), __( 'On: shared product links show the product title, image and price', 'woo-open-graph' ), 'general' ),
	array( $wog_on( 'enable_twitter' ), __( 'X (Twitter)', 'woo-open-graph' ), __( 'On: product links show a large image card on X', 'woo-open-graph' ), 'general' ),
	array( $wog_on( 'enable_pinterest' ), __( 'Pinterest', 'woo-open-graph' ), __( 'On: pins carry Rich Pin product data', 'woo-open-graph' ), 'general' ),
	array( $wog_on( 'enable_social_share' ), __( 'Share buttons', 'woo-open-graph' ), $wog_share_on, 'sharing' ),
	array( $wog_on( 'enable_schema' ), __( 'Structured data', 'woo-open-graph' ), __( 'On: extra fields are added to WooCommerce\'s Product schema', 'woo-open-graph' ), 'structured-data' ),
	array( $wog_on( 'enable_schema' ) && $wog_on( 'enable_organization_schema' ), __( 'Organization schema', 'woo-open-graph' ), __( 'On: search engines are told who runs the store', 'woo-open-graph' ), 'structured-data' ),
	array( $wog_on( 'enable_product_sitemap' ), __( 'XML sitemaps', 'woo-open-graph' ), __( 'On: products and categories are listed for search engines', 'woo-open-graph' ), 'sitemaps' ),
);
?>

<div class="wog-stats-grid">
	<div class="wog-stat">
		<p class="wog-stat__label"><?php esc_html_e( 'Published Products', 'woo-open-graph' ); ?></p>
		<p class="wog-stat__value"><?php echo esc_html( number_format_i18n( $wog_products ) ); ?></p>
		<p class="wog-stat__trend"><?php esc_html_e( 'Products that get social tags when shared', 'woo-open-graph' ); ?></p>
	</div>
	<div class="wog-stat">
		<p class="wog-stat__label"><?php esc_html_e( 'Custom Social Text', 'woo-open-graph' ); ?></p>
		<p class="wog-stat__value"><?php echo esc_html( number_format_i18n( $wog_custom_text ) ); ?></p>
		<p class="wog-stat__trend"><?php esc_html_e( 'Products with their own social title or description', 'woo-open-graph' ); ?></p>
	</div>
	<div class="wog-stat">
		<p class="wog-stat__label"><?php esc_html_e( 'Social Output Off', 'woo-open-graph' ); ?></p>
		<p class="wog-stat__value"><?php echo esc_html( number_format_i18n( $wog_social_off ) ); ?></p>
		<p class="wog-stat__trend"><?php esc_html_e( 'Products switched off in their Social Media box', 'woo-open-graph' ); ?></p>
	</div>
	<div class="wog-stat">
		<p class="wog-stat__label"><?php esc_html_e( 'Sitemap', 'woo-open-graph' ); ?></p>
		<p class="wog-stat__value">
			<?php
			if ( ! $wog_sitemap_on ) {
				esc_html_e( 'Off', 'woo-open-graph' );
			} elseif ( $wog_sitemap_last ) {
				echo esc_html( date_i18n( get_option( 'date_format' ), $wog_sitemap_last ) );
			} else {
				esc_html_e( 'Not yet', 'woo-open-graph' );
			}
			?>
		</p>
		<p class="wog-stat__trend"><?php esc_html_e( 'Last time the sitemap files were written', 'woo-open-graph' ); ?></p>
	</div>
</div>

<div class="wog-card">
	<div class="wog-card__head">
		<h2 class="wog-card__title"><?php esc_html_e( 'Current Configuration', 'woo-open-graph' ); ?></h2>
		<p class="wog-card__desc"><?php esc_html_e( 'What happens right now when someone shares one of your products.', 'woo-open-graph' ); ?></p>
	</div>
	<table class="form-table" role="presentation">
		<?php foreach ( $wog_rows as list( $wog_is_on, $wog_label, $wog_on_text, $wog_tab ) ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $wog_label ); ?></th>
				<td>
					<?php if ( $wog_is_on ) : ?>
						<span class="wog-status-on"><?php echo esc_html( $wog_on_text ); ?></span>
					<?php else : ?>
						<span class="wog-status-off"><?php esc_html_e( 'Off', 'woo-open-graph' ); ?></span>
						<a href="<?php echo esc_url( $wog_tab_url( $wog_tab ) ); ?>" class="wog-inline-hint"><?php esc_html_e( 'Turn on', 'woo-open-graph' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
</div>

<div class="wog-card">
	<div class="wog-card__head">
		<h2 class="wog-card__title"><?php esc_html_e( 'Testing Tools', 'woo-open-graph' ); ?></h2>
		<p class="wog-card__desc"><?php esc_html_e( 'Paste a product URL into these tools to see exactly what each network reads.', 'woo-open-graph' ); ?></p>
	</div>
	<div class="wog-card__body">
		<div class="wog-action-row">
			<?php
			$wog_tools = array(
				'https://developers.facebook.com/tools/debug/' => __( 'Facebook Debugger', 'woo-open-graph' ),
				'https://search.google.com/test/rich-results' => __( 'Google Rich Results', 'woo-open-graph' ),
				'https://www.linkedin.com/post-inspector/' => __( 'LinkedIn Inspector', 'woo-open-graph' ),
			);
			foreach ( $wog_tools as $wog_tool_url => $wog_tool_label ) :
				?>
				<a href="<?php echo esc_url( $wog_tool_url ); ?>" class="wog-btn wog-btn-secondary" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $wog_tool_label ); ?>
					<span class="dashicons dashicons-external" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'woo-open-graph' ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</div>
