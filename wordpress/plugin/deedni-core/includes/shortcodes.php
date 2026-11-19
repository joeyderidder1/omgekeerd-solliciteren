<?php
/**
 * Oude shortcodes uit versie 1 blijven werken. Ze tonen nu het blok van versie 2.
 * Nieuwe pagina's gebruiken gewoon de blokken.
 */

defined( 'ABSPATH' ) || exit;

foreach (
	array(
		'deedni_inloggen'    => 'deedni/inloggen',
		'deedni_registreren' => 'deedni/inloggen',
		'deedni_dashboard'   => 'deedni/overzicht',
		'deedni_profiel'     => 'deedni/mijn-profiel',
		'deedni_zoeken'      => 'deedni/zoeken',
	) as $deedni_sc => $deedni_sc_blok
) {
	add_shortcode(
		$deedni_sc,
		function () use ( $deedni_sc_blok ) {
			return do_blocks( deedni_blok_inhoud( $deedni_sc_blok ) );
		}
	);
}
unset( $deedni_sc, $deedni_sc_blok );
