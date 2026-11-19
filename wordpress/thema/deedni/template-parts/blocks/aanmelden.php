<?php
/**
 * Block: Aanmelden. Tekst links, het aanmeldformulier van de plugin rechts.
 */

defined( 'ABSPATH' ) || exit;

$title_id = wp_unique_id( 'aanmeld-' );
?>
<section <?php echo dn_section_attrs(); // phpcs:ignore ?> aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
	<div class="wrap">
		<div class="signup">
			<div class="signup-copy">
				<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo dn_accent( get_sub_field( 'titel' ) ); // phpcs:ignore ?></h2>
				<?php if ( get_sub_field( 'tekst_marketeer' ) ) : ?>
					<p data-for="kandidaat"><?php echo esc_html( get_sub_field( 'tekst_marketeer' ) ); ?></p>
				<?php endif; ?>
				<?php if ( get_sub_field( 'tekst_werkgever' ) ) : ?>
					<p data-for="werkgever" hidden><?php echo esc_html( get_sub_field( 'tekst_werkgever' ) ); ?></p>
				<?php endif; ?>
				<?php echo dn_checks( dn_repeater_texts( get_sub_field( 'usps' ) ) ); // phpcs:ignore ?>
			</div>

			<?php if ( is_user_logged_in() ) : ?>
				<div class="signup-form">
					<h3>Je bent ingelogd</h3>
					<p>Bekijk je aanbiedingen en je profiel in je overzicht.</p>
					<a class="btn btn--accent" href="<?php echo esc_url( deedni_theme_url( 'dashboard' ) ); ?>">Naar mijn deedni</a>
				</div>
			<?php elseif ( shortcode_exists( 'deedni_registreren' ) ) : ?>
				<?php echo do_shortcode( '[deedni_registreren]' ); ?>
			<?php else : ?>
				<div class="signup-form"><p>Activeer de plugin deedni Core om het aanmeldformulier te tonen.</p></div>
			<?php endif; ?>
		</div>
	</div>
</section>
