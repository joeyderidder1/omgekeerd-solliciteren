<?php
/**
 * Block: Call to action.
 */

defined( 'ABSPATH' ) || exit;
?>
<section <?php echo dn_section_attrs(); // phpcs:ignore ?>>
	<div class="wrap">
		<div class="signup cta-block">
			<div class="signup-copy">
				<h2><?php echo dn_accent( get_sub_field( 'titel' ) ); // phpcs:ignore ?></h2>
				<?php if ( get_sub_field( 'tekst' ) ) : ?>
					<p><?php echo esc_html( get_sub_field( 'tekst' ) ); ?></p>
				<?php endif; ?>
			</div>
			<?php $buttons = dn_link( get_sub_field( 'button' ) ) . dn_link( get_sub_field( 'button_2' ), 'btn btn--light' ); ?>
			<?php if ( $buttons ) : ?>
				<div class="cta-row"><?php echo $buttons; // phpcs:ignore ?></div>
			<?php endif; ?>
		</div>
	</div>
</section>
