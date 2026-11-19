<?php
/**
 * Block: Labels.
 */

defined( 'ABSPATH' ) || exit;

$labels = dn_repeater_texts( get_sub_field( 'labels' ) );
if ( ! $labels ) {
	return;
}
$anchor = sanitize_title( (string) get_sub_field( 'anker' ) );
$bg     = 'wit' === get_sub_field( 'achtergrond' ) ? ' band--surface' : '';
?>
<section class="chips-block<?php echo esc_attr( $bg ); ?>"<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?>>
	<div class="wrap spec">
		<?php if ( get_sub_field( 'label' ) ) : ?>
			<p class="spec-label"><?php echo esc_html( get_sub_field( 'label' ) ); ?></p>
		<?php endif; ?>
		<div class="chips">
			<?php foreach ( $labels as $label ) : ?>
				<span class="chip"><?php echo esc_html( $label ); ?></span>
			<?php endforeach; ?>
		</div>
	</div>
</section>
