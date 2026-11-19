<?php
/**
 * Block: Afbeelding - Content.
 */

defined( 'ABSPATH' ) || exit;

$image = (int) get_sub_field( 'afbeelding' );
?>
<section <?php echo dn_section_attrs( 'media-block' . ( 'rechts' === get_sub_field( 'positie' ) ? ' media-block--reverse' : '' ) ); // phpcs:ignore ?>>
	<div class="wrap media-block__inner">
		<?php if ( $image ) : ?>
			<div class="media-block__image"><?php echo wp_get_attachment_image( $image, 'large', false, array( 'loading' => 'lazy' ) ); ?></div>
		<?php endif; ?>
		<div class="media-block__text">
			<?php dn_section_head( false ); ?>
			<?php if ( get_sub_field( 'tekst' ) ) : ?>
				<div class="entry"><?php echo wp_kses_post( get_sub_field( 'tekst' ) ); ?></div>
			<?php endif; ?>
			<?php $buttons = dn_link( get_sub_field( 'button' ) ) . dn_link( get_sub_field( 'button_2' ), 'btn btn--ghost' ); ?>
			<?php if ( $buttons ) : ?>
				<div class="cta-row"><?php echo $buttons; // phpcs:ignore ?></div>
			<?php endif; ?>
		</div>
	</div>
</section>
