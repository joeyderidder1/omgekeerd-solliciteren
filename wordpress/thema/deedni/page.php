<?php
/**
 * Pagina's: banner bovenaan en daarna de ACF-blokken.
 * Een pagina zonder blokken toont de gewone inhoud (zo blijven de pagina's van de plugin met hun shortcode werken).
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/banner' );

	if ( ! deedni_render_blocks() && trim( get_the_content() ) ) :
		?>
		<div class="wrap page-body">
			<div class="entry<?php echo deedni_theme_is_wide() ? ' entry--wide' : ''; ?>">
				<?php the_content(); ?>
			</div>
		</div>
		<?php
	endif;
endwhile;

get_footer();
