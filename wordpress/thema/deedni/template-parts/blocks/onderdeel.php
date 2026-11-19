<?php
/**
 * Block: deedni-onderdeel. Toont een onderdeel van de plugin (formulier, zoeken, overzicht).
 */

defined( 'ABSPATH' ) || exit;

$part    = get_sub_field( 'onderdeel' );
$allowed = array( 'registreren', 'inloggen', 'dashboard', 'profiel', 'zoeken' );
if ( ! in_array( $part, $allowed, true ) || ! shortcode_exists( 'deedni_' . $part ) ) {
	return;
}
$wide = in_array( $part, array( 'dashboard', 'zoeken' ), true );
?>
<section <?php echo dn_section_attrs( 'onderdeel-block' ); // phpcs:ignore ?>>
	<div class="wrap">
		<div class="entry<?php echo $wide ? ' entry--wide' : ''; ?>">
			<?php echo do_shortcode( '[deedni_' . $part . ']' ); ?>
		</div>
	</div>
</section>
