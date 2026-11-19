<?php
/**
 * Homepage: alleen ACF-blokken (veldgroep "Template: Homepage").
 */

defined( 'ABSPATH' ) || exit;

get_header();

$front_id = (int) get_option( 'page_on_front' );

if ( ! $front_id || ! deedni_render_blocks( $front_id ) ) :
	?>
	<div class="wrap page-banner">
		<h1>deedni</h1>
		<?php if ( current_user_can( 'edit_pages' ) ) : ?>
			<p class="lead">De homepage heeft nog geen blokken.</p>
			<?php echo deedni_seed_button(); // phpcs:ignore ?>
		<?php endif; ?>
	</div>
	<?php
endif;

get_footer();
