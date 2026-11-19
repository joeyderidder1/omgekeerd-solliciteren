<?php
/**
 * Block: Prijzen, met een rekenvoorbeeld (schuif voor het salaris).
 */

defined( 'ABSPATH' ) || exit;

$uid = wp_unique_id( 'calc-' );
$min = (int) get_sub_field( 'reken_min' ) ? (int) get_sub_field( 'reken_min' ) : 35000;
$max = (int) get_sub_field( 'reken_max' ) ? (int) get_sub_field( 'reken_max' ) : 90000;
$val = min( $max, max( $min, (int) get_sub_field( 'reken_start' ) ? (int) get_sub_field( 'reken_start' ) : $min ) );
$pct = (float) get_sub_field( 'reken_procent' );
$bmn = (float) get_sub_field( 'reken_bureau_min' );
$bmx = (float) get_sub_field( 'reken_bureau_max' );
$eur = function ( $n ) {
	return '€' . number_format( $n, 0, ',', '.' );
};
?>
<section <?php echo dn_section_attrs(); // phpcs:ignore ?>>
	<div class="wrap">
		<?php dn_section_head(); ?>
		<?php if ( have_rows( 'pakketten' ) ) : ?>
			<div class="plans">
				<?php while ( have_rows( 'pakketten' ) ) : the_row(); ?>
					<div class="plan<?php echo get_sub_field( 'uitgelicht' ) ? ' plan--featured' : ''; ?>">
						<h3><?php echo esc_html( get_sub_field( 'titel' ) ); ?></h3>
						<?php if ( get_sub_field( 'voor_wie' ) ) : ?>
							<p class="plan-for"><?php echo esc_html( get_sub_field( 'voor_wie' ) ); ?></p>
						<?php endif; ?>
						<div class="price"><strong><?php echo esc_html( get_sub_field( 'prijs' ) ); ?></strong><span><?php echo esc_html( get_sub_field( 'toelichting' ) ); ?></span></div>
						<?php $items = dn_lines( get_sub_field( 'items' ) ); ?>
						<?php if ( $items ) : ?>
							<ul><?php foreach ( $items as $item ) { echo '<li>' . esc_html( $item ) . '</li>'; } ?></ul>
						<?php endif; ?>
					</div>
				<?php endwhile; ?>
			</div>
		<?php endif; ?>

		<?php if ( get_sub_field( 'reken_tonen' ) && $pct ) : ?>
			<div class="calc" data-calc data-pct="<?php echo esc_attr( $pct ); ?>" data-bureau-min="<?php echo esc_attr( $bmn ); ?>" data-bureau-max="<?php echo esc_attr( $bmx ); ?>">
				<div class="calc-input">
					<label for="<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( get_sub_field( 'reken_titel' ) ); ?></label>
					<span class="value" data-calc-out aria-hidden="true"><?php echo esc_html( $eur( $val ) ); ?></span>
					<input type="range" id="<?php echo esc_attr( $uid ); ?>" min="<?php echo (int) $min; ?>" max="<?php echo (int) $max; ?>" step="1000" value="<?php echo (int) $val; ?>" aria-describedby="<?php echo esc_attr( $uid ); ?>-hint">
					<div class="range-ends" aria-hidden="true"><span><?php echo esc_html( $eur( $min ) ); ?></span><span><?php echo esc_html( $eur( $max ) ); ?></span></div>
					<span class="fine" id="<?php echo esc_attr( $uid ); ?>-hint"><?php echo esc_html( get_sub_field( 'reken_hint' ) ); ?></span>
				</div>
				<div class="calc-out" aria-live="polite">
					<div class="calc-box calc-box--us"><small><?php echo esc_html( get_sub_field( 'reken_label_ons' ) ); ?></small><strong data-calc-us><?php echo esc_html( $eur( $val * $pct / 100 ) ); ?></strong></div>
					<?php if ( $bmn ) : ?>
						<div class="calc-box"><small><?php echo esc_html( get_sub_field( 'reken_label_bureau' ) ); ?></small><strong data-calc-bureau><?php echo esc_html( $eur( $val * $bmn / 100 ) . ( $bmx ? '–' . number_format( $val * $bmx / 100, 0, ',', '.' ) : '' ) ); ?></strong></div>
					<?php endif; ?>
					<?php if ( get_sub_field( 'reken_voetnoot' ) ) : ?>
						<p class="fine" style="grid-column: 1 / -1;"><?php echo esc_html( get_sub_field( 'reken_voetnoot' ) ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
