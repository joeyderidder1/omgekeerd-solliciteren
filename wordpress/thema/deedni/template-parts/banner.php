<?php
/**
 * Banner bovenaan een pagina (tab Banner van "Template: Default pagina").
 */

defined( 'ABSPATH' ) || exit;

$has_acf = function_exists( 'get_field' );
// Alleen verbergen als "Toon banner" bewust is uitgezet; een pagina zonder opgeslagen velden toont hem wel.
if ( '0' === (string) get_post_meta( get_the_ID(), 'banner_tonen', true ) ) {
	return;
}

$eyebrow = $has_acf ? get_field( 'banner_boventitel' ) : '';
$title   = $has_acf && get_field( 'banner_titel' ) ? get_field( 'banner_titel' ) : get_the_title();
$text    = $has_acf && get_field( 'banner_tekst' ) ? get_field( 'banner_tekst' ) : deedni_theme_page_lead();
$image   = $has_acf ? (int) get_field( 'banner_afbeelding' ) : 0;
$buttons = $has_acf ? dn_link( get_field( 'banner_button' ) ) . dn_link( get_field( 'banner_button_2' ), 'btn btn--ghost' ) : '';
$usps    = $has_acf && get_field( 'banner_usps' ) ? dn_repeater_texts( get_field( 'usps', 'option' ) ) : array();
?>
<div class="page-banner<?php echo $image ? ' page-banner--image' : ''; ?>">
	<div class="wrap page-banner__inner">
		<div class="page-banner__copy">
			<?php if ( $eyebrow ) : ?>
				<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1><?php echo dn_accent( $title ); // phpcs:ignore ?></h1>
			<?php if ( $text ) : ?>
				<p class="lead"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
			<?php if ( $buttons ) : ?>
				<div class="cta-row"><?php echo $buttons; // phpcs:ignore ?></div>
			<?php endif; ?>
		</div>
		<?php if ( $image ) : ?>
			<div class="page-banner__image"><?php echo wp_get_attachment_image( $image, 'large', false, array( 'fetchpriority' => 'high' ) ); ?></div>
		<?php endif; ?>
	</div>
	<?php if ( $usps ) : ?>
		<div class="wrap page-banner__usps"><?php echo dn_checks( $usps ); // phpcs:ignore ?></div>
	<?php endif; ?>
</div>
