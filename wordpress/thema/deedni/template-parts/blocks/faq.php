<?php
/**
 * Block: FAQ.
 */

defined( 'ABSPATH' ) || exit;
?>
<section <?php echo dn_section_attrs( '', 'wit' ); // phpcs:ignore ?>>
	<div class="wrap">
		<?php dn_section_head( false ); ?>
		<?php if ( have_rows( 'vragen' ) ) : ?>
			<div class="faq">
				<?php while ( have_rows( 'vragen' ) ) : the_row(); ?>
					<details><summary><?php echo esc_html( get_sub_field( 'vraag' ) ); ?></summary><?php echo wpautop( esc_html( get_sub_field( 'antwoord' ) ) ); // phpcs:ignore ?></details>
				<?php endwhile; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
