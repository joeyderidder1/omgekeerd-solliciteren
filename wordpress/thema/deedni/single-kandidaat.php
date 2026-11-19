<?php
/**
 * Een anoniem kandidaatprofiel. De plugin controleert al wie dit mag zien.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="wrap page-head">
		<?php if ( function_exists( 'deedni_can_search' ) && deedni_can_search() ) : ?>
			<a class="link-btn" href="<?php echo esc_url( wp_get_referer() && false !== strpos( wp_get_referer(), deedni_theme_url( 'zoeken' ) ) ? wp_get_referer() : deedni_theme_url( 'zoeken' ) ); ?>">← Terug naar de resultaten</a>
		<?php endif; ?>
	</div>
	<div class="wrap page-body">
		<?php
		if ( function_exists( 'deedni_render_kandidaat' ) ) {
			echo deedni_render_kandidaat( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		?>
	</div>
	<?php
endwhile;

get_footer();
