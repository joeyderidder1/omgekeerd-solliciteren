<?php
/**
 * Kleine UI-details die niet in CSS of JavaScript kunnen:
 * - Pre-rendering: WordPress 6.8+ laadt een volgende pagina alvast als je met de muis op een link blijft
 *   staan (Speculation Rules). Voor bezoekers maken we daar een volledige voorweergave van.
 * - Een voorweergave of prefetch telt nooit als "bekeken" (zie access.php).
 */

defined( 'ABSPATH' ) || exit;

/** Is dit verzoek een prefetch of prerender van de browser (nog niet echt geopend)? */
function deedni_is_voorladen() {
	$doel = isset( $_SERVER['HTTP_SEC_PURPOSE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_SEC_PURPOSE'] ) ) : '';
	if ( ! $doel && isset( $_SERVER['HTTP_PURPOSE'] ) ) {
		$doel = sanitize_text_field( wp_unslash( $_SERVER['HTTP_PURPOSE'] ) );
	}
	return false !== stripos( $doel, 'prefetch' ) || false !== stripos( $doel, 'prerender' );
}

/**
 * Bezoekers: voorweergave (prerender) bij hover. Ingelogd: de standaard van WordPress (alleen prefetch bij
 * klikken), want pagina's als het overzicht zijn persoonlijk en veranderen vaak.
 */
add_filter(
	'wp_speculation_rules_configuration',
	function ( $config ) {
		if ( ! is_array( $config ) || is_user_logged_in() || ! apply_filters( 'deedni_prerender', true ) ) {
			return $config;
		}
		return array(
			'mode'      => 'prerender',
			'eagerness' => 'moderate',
		);
	}
);

/**
 * Nooit alvast laden: kandidaatprofielen (die tellen als bekeken). Links met een vraagteken (cv-downloads,
 * uitloggen, filters) slaat WordPress zelf al over.
 */
add_filter(
	'wp_speculation_rules_href_exclude_paths',
	function ( $paths ) {
		$paths[] = '/kandidaat/*';
		return $paths;
	}
);

/**
 * Curly quotes: rechte aanhalingstekens en apostroffen worden typografische (“zo” en ‘zo’, en ’s avonds).
 * Voor tekst die mensen zelf typen, zoals quotes en berichten bij een aanbod. Geeft platte tekst terug;
 * escapen gebeurt daarna nog steeds met esc_html().
 */
function deedni_krul( $tekst ) {
	$tekst = (string) $tekst;
	if ( false === strpbrk( $tekst, "\"'" ) ) {
		return $tekst;
	}
	$tekst = preg_replace( "/(^|[\\s(\\[{\\x{2014}\\x{2013}-])\"/u", '$1“', $tekst );
	$tekst = str_replace( '"', '”', $tekst );
	$tekst = preg_replace( "/(^|[\\s(\\[{\\x{2014}\\x{2013}-])'(?=\\p{L}{2})/u", '$1‘', $tekst ); // ‘zo’ (maar niet ’s of ’t)
	$tekst = str_replace( "'", '’', $tekst );
	return $tekst;
}
