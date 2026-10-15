<?php
/**
 * WB Plugins hub — landing dashboard at ?page=wbcomplugins.
 *
 * Lists every Wbcom plugin that has registered a submenu under the
 * shared wbcomplugins parent. Legacy wbcom-wrapper plugins register
 * boilerplate helper pages (Our Plugins, Our Themes, Support, License)
 * under this hub; those are filtered out via wbcom_hub_wrapper_helper_slugs.
 *
 * @package Woo_Open_Graph
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

$wog_submenu_entries = isset( $GLOBALS['submenu']['wbcomplugins'] ) && is_array( $GLOBALS['submenu']['wbcomplugins'] )
	? $GLOBALS['submenu']['wbcomplugins']
	: array();

// Legacy wbcom-wrapper boilerplate helper pages — not real plugins.
$wog_wrapper_helper_slugs = apply_filters(
	'wbcom_hub_wrapper_helper_slugs',
	array(
		'wbcom-plugins-page',
		'wbcom-themes-page',
		'wbcom-support-page',
		'wbcom-license-page',
	)
);

$wog_plugins = array();
foreach ( $wog_submenu_entries as $wog_entry ) {
	$wog_slug = isset( $wog_entry[2] ) ? (string) $wog_entry[2] : '';
	if ( '' === $wog_slug || 'wbcomplugins' === $wog_slug ) {
		continue;
	}
	if ( in_array( $wog_slug, $wog_wrapper_helper_slugs, true ) ) {
		continue;
	}
	$wog_plugins[] = array(
		'slug'       => $wog_slug,
		'menu_title' => isset( $wog_entry[0] ) ? wp_strip_all_tags( (string) $wog_entry[0] ) : $wog_slug,
		'page_title' => isset( $wog_entry[3] ) ? wp_strip_all_tags( (string) $wog_entry[3] ) : '',
		'url'        => admin_url( 'admin.php?page=' . rawurlencode( $wog_slug ) ),
	);
}

$wog_plugin_count = count( $wog_plugins );
?>
<div class="wrap wog-admin">
	<header class="wog-page-header">
		<div class="wog-page-header__title">
			<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
			<div>
				<h1><?php esc_html_e( 'WB Plugins', 'woo-open-graph' ); ?></h1>
				<p class="wog-page-header__subtitle">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: active Wbcom plugin count */
							_n(
								'%d Wbcom plugin active on this site.',
								'%d Wbcom plugins active on this site.',
								$wog_plugin_count,
								'woo-open-graph'
							),
							$wog_plugin_count
						)
					);
					?>
				</p>
			</div>
		</div>
	</header>

	<?php if ( 0 === $wog_plugin_count ) : ?>
		<div class="wog-empty-state">
			<span class="wog-empty-state__icon" aria-hidden="true">
				<span class="dashicons dashicons-lightbulb"></span>
			</span>
			<p class="wog-empty-state__title"><?php esc_html_e( 'No Wbcom plugins attached to this hub yet', 'woo-open-graph' ); ?></p>
			<p class="wog-empty-state__desc">
				<?php esc_html_e( 'Activate one or more Wbcom plugins and they will appear here automatically.', 'woo-open-graph' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="wog-hub-grid">
			<?php foreach ( $wog_plugins as $wog_p ) : ?>
				<a href="<?php echo esc_url( $wog_p['url'] ); ?>" class="wog-hub-card">
					<span class="wog-hub-card__icon" aria-hidden="true">
						<span class="dashicons dashicons-admin-plugins"></span>
					</span>
					<span class="wog-hub-card__title"><?php echo esc_html( $wog_p['menu_title'] ); ?></span>
					<?php if ( ! empty( $wog_p['page_title'] ) && $wog_p['page_title'] !== $wog_p['menu_title'] ) : ?>
						<span class="wog-hub-card__subtitle"><?php echo esc_html( $wog_p['page_title'] ); ?></span>
					<?php endif; ?>
					<span class="wog-hub-card__cta">
						<?php esc_html_e( 'Open settings', 'woo-open-graph' ); ?>
						<span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="wog-card" style="margin-top: 20px;">
		<div class="wog-card__head">
			<p class="wog-card__title"><?php esc_html_e( 'About WB Plugins', 'woo-open-graph' ); ?></p>
		</div>
		<div class="wog-card__body">
			<p style="margin: 0 0 8px;">
				<?php esc_html_e( 'This hub is the single entry point for every Wbcom Designs plugin installed on your site. Each plugin lives on its own page under this menu and keeps its own settings and data.', 'woo-open-graph' ); ?>
			</p>
			<p style="margin: 0;">
				<a href="https://wbcomdesigns.com/" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Visit wbcomdesigns.com for more plugins and themes', 'woo-open-graph' ); ?>
				</a>
			</p>
		</div>
	</div>
</div>
