<?php
/**
 * Installatie: pagina's met blokken aanmaken, de homepage vullen en gegevens van versie 1 omzetten.
 * Alles hier is veilig om vaker te draaien: bestaande pagina's en eigen teksten blijven staan.
 */

defined( 'ABSPATH' ) || exit;

/** Inhoud van een pagina met één blok. */
function deedni_blok_inhoud( $blok, $attrs = array() ) {
	return '<!-- wp:' . $blok . ( $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '' ) . ' /-->';
}

/** De vaste pagina's (inloggen, overzicht, profiel, zoeken). Bestaande pagina's worden niet overschreven. */
function deedni_create_pages() {
	$pages = (array) get_option( 'deedni_pages', array() );
	foreach ( deedni_pagina_lijst() as $key => $p ) {
		$id = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
		if ( $id && get_post_status( $id ) && 'trash' !== get_post_status( $id ) ) {
			continue;
		}
		$bestaand = get_page_by_path( $p[1] );
		if ( $bestaand && 'trash' !== $bestaand->post_status ) {
			$pages[ $key ] = (int) $bestaand->ID;
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => isset( $p[3] ) ? $p[3] : 'publish',
				'post_title'   => $p[0],
				'post_name'    => $p[1],
				'post_content' => 'inhoud' === $p[2] ? deedni_pagina_inhoud( $key ) : deedni_blok_inhoud( $p[2] ),
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			$pages[ $key ] = (int) $id;
			update_post_meta( $id, '_wp_page_template', 'volle-breedte' );
		}
	}
	update_option( 'deedni_pages', $pages );
}

/** De blokken van de homepage, in de volgorde van het ontwerp. */
function deedni_home_inhoud() {
	$blokken = array( 'welkom', 'hero', 'grote-foto', 'band', 'kandidaten-boog', 'profielen', 'zo-werkt-het', 'tijdlijn', 'snel-zoeken', 'cijfers', 'verschil', 'quotes', 'privacy', 'prijzen', 'vragen', 'slot' );
	$out     = array();
	foreach ( $blokken as $b ) {
		if ( isset( deedni_blok_defs()[ $b ] ) ) {
			$out[] = deedni_blok_inhoud( 'deedni/' . $b );
		}
	}
	return implode( "\n\n", $out );
}

/**
 * Homepage vullen. Alleen als er nog geen homepage is, of als de huidige homepage geen blokken heeft
 * (zoals bij versie 1, waar de homepage uit ACF-velden kwam). Eerdere inhoud blijft bewaard als revisie.
 */
function deedni_installeer_homepage( $forceer = false ) {
	if ( ! $forceer && 'deedni' !== get_stylesheet() && 'deedni' !== get_template() ) {
		return;
	}
	$front = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
	if ( $front && get_post_status( $front ) ) {
		$inhoud = (string) get_post_field( 'post_content', $front );
		if ( ! $forceer && has_blocks( $inhoud ) ) {
			return;
		}
		wp_update_post( array( 'ID' => $front, 'post_content' => deedni_home_inhoud() ) );
		update_post_meta( $front, '_wp_page_template', 'volle-breedte' );
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Home',
			'post_name'    => 'home',
			'post_content' => deedni_home_inhoud(),
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_wp_page_template', 'volle-breedte' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $id );
	}
}

/** Na het activeren van het thema: menu's en homepage klaarzetten. */
add_action(
	'after_switch_theme',
	function () {
		deedni_installeer_menus();
		deedni_installeer_homepage();
	}
);

/**
 * Van versie 1 naar 2:
 * - pagina's die alleen een oude shortcode bevatten krijgen het nieuwe blok (andere pagina's blijven zoals ze zijn);
 * - "werkvorm" (één keuze) wordt "werkplekken" (meerdere keuzes).
 */
function deedni_migreer_v1() {
	$map = array(
		'deedni_inloggen'    => 'deedni/inloggen',
		'deedni_registreren' => 'deedni/inloggen',
		'deedni_dashboard'   => 'deedni/overzicht',
		'deedni_profiel'     => 'deedni/mijn-profiel',
		'deedni_zoeken'      => 'deedni/zoeken',
	);
	foreach ( (array) get_option( 'deedni_pages', array() ) as $id ) {
		$inhoud = trim( (string) get_post_field( 'post_content', (int) $id ) );
		$kaal   = trim( preg_replace( '#<!-- /?wp:(shortcode|paragraph)[^>]*-->|</?p>#', '', $inhoud ) );
		if ( preg_match( '/^\[(deedni_[a-z]+)\]$/', $kaal, $m ) && isset( $map[ $m[1] ] ) ) {
			wp_update_post( array( 'ID' => (int) $id, 'post_content' => deedni_blok_inhoud( $map[ $m[1] ] ) ) );
			update_post_meta( (int) $id, '_wp_page_template', 'volle-breedte' );
		}
	}

	$ids = get_posts(
		array(
			'post_type'   => 'kandidaat',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_query'  => array( array( 'key' => 'werkvorm', 'compare' => 'EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $ids as $id ) {
		$oud = (string) get_post_meta( $id, 'werkvorm', true );
		if ( ! get_post_meta( $id, 'werkplekken', true ) && $oud ) {
			update_post_meta( $id, 'werkplekken', array( $oud ) );
		}
	}
}
