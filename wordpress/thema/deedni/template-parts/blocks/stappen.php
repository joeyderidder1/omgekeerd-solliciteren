<?php
/**
 * Block: Stappen.
 */

defined( 'ABSPATH' ) || exit;

$classes = array( 'licht' => '', 'donker' => 'is-employer', 'accent' => 'is-final' );
?>
<section <?php echo dn_section_attrs( '', 'wit' ); // phpcs:ignore ?>>
	<div class="wrap">
		<?php dn_section_head(); ?>
		<?php if ( have_rows( 'stappen' ) ) : ?>
			<ol class="flow">
				<?php
				while ( have_rows( 'stappen' ) ) :
					the_row();
					$class = isset( $classes[ get_sub_field( 'stijl' ) ] ) ? $classes[ get_sub_field( 'stijl' ) ] : '';
					?>
					<li<?php echo $class ? ' class="' . esc_attr( $class ) . '"' : ''; ?>>
						<span class="num" aria-hidden="true"></span>
						<?php if ( get_sub_field( 'wie' ) ) : ?>
							<span class="who"><?php echo esc_html( get_sub_field( 'wie' ) ); ?></span>
						<?php endif; ?>
						<strong><?php echo esc_html( get_sub_field( 'titel' ) ); ?></strong>
						<span class="desc"><?php echo esc_html( get_sub_field( 'tekst' ) ); ?></span>
					</li>
				<?php endwhile; ?>
			</ol>
		<?php endif; ?>
		<?php if ( get_sub_field( 'legenda_licht' ) || get_sub_field( 'legenda_donker' ) ) : ?>
			<div class="legend" aria-hidden="true">
				<?php if ( get_sub_field( 'legenda_licht' ) ) : ?>
					<span><i style="background: var(--bg);"></i><?php echo esc_html( get_sub_field( 'legenda_licht' ) ); ?></span>
				<?php endif; ?>
				<?php if ( get_sub_field( 'legenda_donker' ) ) : ?>
					<span><i style="background: var(--block);"></i><?php echo esc_html( get_sub_field( 'legenda_donker' ) ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
