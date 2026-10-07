<?php
/**
 * Admin page shell: page header, sidebar nav, body slot.
 *
 * Receives from WOG_Admin::admin_page():
 *
 * @var array  $wog_tabs           Tab registry keyed by slug.
 * @var string $active              Active tab slug.
 * @var string $page_url            Base URL (admin.php?page=woo-open-graph).
 * @var string $view                View slug (e.g. 'overview', 'settings-general').
 * @var string $view_path           Absolute path to the partial.
 *
 * @package Woo_Open_Graph
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

$wog_version = defined( 'WOG_VERSION' ) ? WOG_VERSION : '';
?>
<div class="wrap wog-admin">

	<header class="wog-page-header">
		<div class="wog-page-header__title">
			<span class="dashicons dashicons-share" aria-hidden="true"></span>
			<div>
				<h1><?php echo 'Open Graph for WooCommerce'; ?></h1>
				<p class="wog-page-header__subtitle"><?php esc_html_e( 'Configure how your WooCommerce products appear when shared on social media platforms. This plugin works alongside your existing SEO plugin to fill any gaps.', 'woo-open-graph' ); ?></p>
			</div>
		</div>
		<div class="wog-page-header__actions">
			<?php if ( $wog_version ) : ?>
				<span class="wog-version-pill">v<?php echo esc_html( $wog_version ); ?></span>
			<?php endif; ?>
		</div>
	</header>

	<?php
	/*
	 * Without this marker, core's common.js re-parents every .notice to sit
	 * right after the first <h1> it finds, which slots the "Settings saved"
	 * banner between our title and its subtitle instead of below the header.
	 */
	?>
	<hr class="wp-header-end">

	<div class="wog-settings-layout">

		<aside class="wog-settings-sidebar">
			<div class="wog-settings-sidebar-brand">
				<span class="wog-settings-brand-icon" aria-hidden="true">
					<span class="dashicons dashicons-share"></span>
				</span>
				<div class="wog-settings-brand-text">
					<p class="wog-settings-brand-name"><?php esc_html_e( 'Open Graph', 'woo-open-graph' ); ?></p>
					<p class="wog-settings-brand-sub"><?php esc_html_e( 'Plugin', 'woo-open-graph' ); ?></p>
				</div>
			</div>
			<nav class="wog-settings-sidebar-nav" aria-label="<?php esc_attr_e( 'Open Graph navigation', 'woo-open-graph' ); ?>">
				<?php
				$wog_printed_groups = array();
				$wog_group_labels   = array(
					'settings' => esc_html__( 'Settings', 'woo-open-graph' ),
					'discover' => esc_html__( 'More Tools', 'woo-open-graph' ),
				);
				foreach ( $wog_tabs as $wog_slug => $wog_tab ) {
					$wog_group = isset( $wog_tab['group'] ) ? $wog_tab['group'] : 'main';
					if ( 'main' !== $wog_group && ! in_array( $wog_group, $wog_printed_groups, true ) ) {
						echo '<div class="wog-snav-divider" role="separator"></div>';
						if ( isset( $wog_group_labels[ $wog_group ] ) ) {
							echo '<p class="wog-snav-section-label">' . esc_html( $wog_group_labels[ $wog_group ] ) . '</p>';
						}
						$wog_printed_groups[] = $wog_group;
					}
					$wog_classes  = 'wog-snav-link';
					$wog_classes .= $active === $wog_slug ? ' wog-snav-link--active' : '';
					echo '<a href="' . esc_url( $page_url . '&tab=' . $wog_slug ) . '" class="' . esc_attr( $wog_classes ) . '">';
					echo '<span class="dashicons ' . esc_attr( $wog_tab['icon'] ) . '" aria-hidden="true"></span>';
					echo esc_html( $wog_tab['label'] );
					echo '</a>';
				}
				?>

				<div class="wog-snav-divider" role="separator"></div>
				<p class="wog-snav-section-label"><?php esc_html_e( 'Resources', 'woo-open-graph' ); ?></p>
				<a href="https://wordpress.org/plugins/woo-open-graph/" class="wog-snav-link" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-book" aria-hidden="true"></span>
					<?php esc_html_e( 'Plugin page', 'woo-open-graph' ); ?>
					<span class="dashicons dashicons-external wog-snav-link__ext" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'woo-open-graph' ); ?></span>
				</a>
			</nav>
		</aside>

		<div class="wog-settings-main">
			<?php
			// Render settings notices inside the content column so the banner
			// aligns with the panel chrome instead of spanning full width.
			settings_errors();

			/*
			 * Settings tabs render their own <form> (admin/views/settings.php);
			 * the shell only provides the chrome + body slot.
			 */
			if ( file_exists( $view_path ) ) {
				include $view_path;
			}
			?>
		</div>

	</div>
</div>
