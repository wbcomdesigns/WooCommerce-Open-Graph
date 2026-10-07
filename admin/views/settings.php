<?php
/**
 * Settings tab partial: one card per settings section, one form per tab.
 *
 * Rendered inside shell.php by WOG_Admin::admin_page(), so `$this` is the
 * WOG_Admin instance and `$active` is the tab slug.
 *
 * @package Woo_Open_Graph
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

global $wp_settings_sections;

$wog_page         = WOG_Admin::settings_page_id( $active );
$wog_sections     = isset( $wp_settings_sections[ $wog_page ] ) ? $wp_settings_sections[ $wog_page ] : array();
$wog_section_desc = $this->get_section_descriptions( $active );
$wog_last_section = array_key_last( $wog_sections );
?>
<form method="post" action="options.php" class="wog-settings-form">
	<?php settings_fields( WOG_Admin::OPTION_GROUP ); ?>
	<input type="hidden" name="wog_settings[<?php echo esc_attr( WOG_Admin::TAB_FIELD ); ?>]" value="<?php echo esc_attr( $active ); ?>" />

	<?php foreach ( $wog_sections as $wog_section_id => $wog_section ) : ?>
		<div class="wog-card">
			<div class="wog-card__head">
				<h2 class="wog-card__title"><?php echo esc_html( $wog_section['title'] ); ?></h2>
				<?php if ( ! empty( $wog_section_desc[ $wog_section_id ] ) ) : ?>
					<p class="wog-card__desc"><?php echo esc_html( $wog_section_desc[ $wog_section_id ] ); ?></p>
				<?php endif; ?>
			</div>
			<table class="form-table" role="presentation">
				<?php do_settings_fields( $wog_page, $wog_section_id ); ?>
			</table>
			<?php
			if ( $wog_section_id === $wog_last_section ) {
				submit_button( __( 'Save Settings', 'woo-open-graph' ) );
			}
			?>
		</div>
	<?php endforeach; ?>
</form>
