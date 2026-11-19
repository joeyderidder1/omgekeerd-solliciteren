<?php
/**
 * Block: Content.
 */

defined( 'ABSPATH' ) || exit;

$center = 'midden' === get_sub_field( 'uitlijning' );
?>
<section <?php echo dn_section_attrs( 'content-block' . ( $center ? ' content-block--center' : '' ) ); // phpcs:ignore ?>>
	<div class="wrap">
		<?php dn_section_head( false ); ?>
		<?php if ( get_sub_field( 'tekst' ) ) : ?>
			<div class="entry"><?php echo wp_kses_post( get_sub_field( 'tekst' ) ); ?></div>
		<?php endif; ?>
		<?php $buttons = dn_link( get_sub_field( 'button' ) ) . dn_link( get_sub_field( 'button_2' ), 'btn btn--ghost' ); ?>
		<?php if ( $buttons ) : ?>
			<div class="cta-row"><?php echo $buttons; // phpcs:ignore ?></div>
		<?php endif; ?>
	</div>
</section>
