<?php
/**
 * Block: Kolommen met lijst.
 */

defined( 'ABSPATH' ) || exit;

$icons = array(
	'oog'   => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle>',
	'plus'  => '<path d="M12 5v14M5 12h14"></path>',
	'slot'  => '<rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path>',
	'check' => '<path d="M20 6L9 17l-5-5"></path>',
);
?>
<section <?php echo dn_section_attrs(); // phpcs:ignore ?>>
	<div class="wrap">
		<?php dn_section_head(); ?>
		<?php if ( have_rows( 'kolommen' ) ) : ?>
			<div class="layers">
				<?php
				while ( have_rows( 'kolommen' ) ) :
					the_row();
					$icon = get_sub_field( 'icoon' );
					?>
					<div class="layer<?php echo 'donker' === get_sub_field( 'stijl' ) ? ' layer--locked' : ''; ?>">
						<div class="layer-head">
							<?php if ( isset( $icons[ $icon ] ) ) : ?>
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $icons[ $icon ]; // phpcs:ignore ?></svg>
							<?php endif; ?>
							<?php echo esc_html( get_sub_field( 'titel' ) ); ?>
						</div>
						<?php $items = dn_lines( get_sub_field( 'items' ) ); ?>
						<?php if ( $items ) : ?>
							<ul>
								<?php foreach ( $items as $item ) : ?>
									<li><?php echo esc_html( $item ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endwhile; ?>
			</div>
		<?php endif; ?>
		<?php if ( get_sub_field( 'notitie' ) ) : ?>
			<p class="note"><?php echo esc_html( get_sub_field( 'notitie' ) ); ?></p>
		<?php endif; ?>
	</div>
</section>
