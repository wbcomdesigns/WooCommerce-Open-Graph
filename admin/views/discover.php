<?php
/**
 * Discover partial: ecosystem cross-promotion (read-only display view).
 *
 * Rendered inside shell.php. This is a pure presentation view — product
 * cards with outbound links to other free Wbcom Designs tools. No forms,
 * no settings, no options, no AJAX.
 *
 * @package Woo_Open_Graph
 * @since   2.1.0
 */

defined( 'ABSPATH' ) || exit;

$wog_ecosystem_img = WOG_PLUGIN_URL . 'assets/images/ecosystem/';

/*
 * Each product: brand mark (shipped in assets/images/ecosystem/), name,
 * a short admin-specific blurb (worded differently from readme.txt), and
 * the wbcomdesigns.com download URL.
 */
$wog_ecosystem = array(
	array(
		'name' => 'BuddyX',
		'logo' => 'buddyx.png',
		'icon' => 'admin-appearance',
		'desc' => __( 'A free, fast community theme for BuddyPress, BuddyBoss and PeepSo with a modern layout and dark mode.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddyx-theme/',
	),
	array(
		'name' => 'BuddyNext',
		'logo' => 'buddynext.svg',
		'desc' => __( 'Launch a full social network on WordPress with feeds, member spaces, profiles, and private messaging.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddynext/',
	),
	array(
		'name' => 'Jetonomy',
		'logo' => 'jetonomy.svg',
		'desc' => __( 'Run discussion forums and Q&A boards that police themselves with trust levels and scale to huge topic counts.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/jetonomy/',
	),
	array(
		'name' => 'Mediaverse',
		'logo' => 'mediaverse.svg',
		'desc' => __( 'Give members a place to share photos and video, react, follow each other, and chat one to one.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/mediaverse/',
	),
	array(
		'name' => 'Eventonomy',
		'logo' => 'eventonomy.svg',
		'icon' => 'calendar-alt',
		'desc' => __( 'Run community events with RSVPs, calendars, and front-end submissions.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/eventonomy/',
	),
	array(
		'name' => 'WB Gamification',
		'logo' => 'wb-gamification.svg',
		'icon' => 'awards',
		'desc' => __( 'Reward members with points, badges, and leaderboards to keep engagement high.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wordpress-gamification-plugin/',
	),
	array(
		'name' => 'Listora',
		'logo' => 'listora.svg',
		'desc' => __( 'Build any kind of searchable directory with multiple listing types, ratings, maps, and self-serve submissions.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/listora/',
	),
	array(
		'name' => 'WP Career Board',
		'logo' => 'wp-career-board.svg',
		'icon' => 'businessman',
		'desc' => __( 'Add a job board with front-end listings, applications, and employer profiles.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wp-career-board/',
	),
	array(
		'name' => 'Learnomy',
		'logo' => 'learnomy.svg',
		'desc' => __( 'Publish and sell online courses, grade quizzes automatically, and hand learners a certificate at the end.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/learnomy/',
	),
	array(
		'name' => 'WP Sell Services',
		'logo' => 'wp-sell-services.svg',
		'icon' => 'cart',
		'desc' => __( 'A free service marketplace with vendor dashboards, an 11-status order flow, and Stripe or PayPal built in.', 'woo-open-graph' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wp-sell-services/',
	),
);
?>

<div class="wog-card">
	<div class="wog-card__head">
		<p class="wog-card__title"><?php esc_html_e( 'More Free Tools from Wbcom Designs', 'woo-open-graph' ); ?></p>
		<p class="wog-card__desc"><?php esc_html_e( 'Open Graph makes your products look right wherever they are shared. These free plugins from Wbcom Designs cover the rest of your site: a theme, a social network, forums, media, events, gamification, directories, jobs, courses, and services.', 'woo-open-graph' ); ?></p>
	</div>
	<div class="wog-card__body">
		<div class="wog-discover-grid">
			<?php foreach ( $wog_ecosystem as $wog_product ) : ?>
				<div class="wog-discover-card">
					<span class="wog-discover-card__logo" aria-hidden="true">
						<img src="<?php echo esc_url( $wog_ecosystem_img . $wog_product['logo'] ); ?>" alt="<?php echo esc_attr( $wog_product['name'] ); ?>" width="52" height="52" loading="lazy" />
					</span>
					<h3 class="wog-discover-card__title"><?php echo esc_html( $wog_product['name'] ); ?></h3>
					<p class="wog-discover-card__desc"><?php echo esc_html( $wog_product['desc'] ); ?></p>
					<a class="wog-btn wog-btn-secondary wog-discover-card__cta" href="<?php echo esc_url( $wog_product['url'] ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Get it free', 'woo-open-graph' ); ?>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
						<span class="screen-reader-text">
							<?php
							/* translators: %s: product name. */
							echo esc_html( sprintf( __( '%s (opens in a new tab)', 'woo-open-graph' ), $wog_product['name'] ) );
							?>
						</span>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
