<?php
/**
 * Menu's per rol. Je past ze aan onder Weergave > Menu's. Elke rol heeft een eigen menu, en ingelogde
 * rollen ook een accountmenu (onder de initialen rechtsboven).
 */

defined( 'ABSPATH' ) || exit;

function deedni_menu_locaties() {
	return array(
		'deedni-bezoeker'          => 'Menu bezoeker (niet ingelogd)',
		'deedni-kandidaat'         => 'Menu marketeer',
		'deedni-werkgever'         => 'Menu werkgever',
		'deedni-account-kandidaat' => 'Accountmenu marketeer (onder de initialen)',
		'deedni-account-werkgever' => 'Accountmenu werkgever (onder de initialen)',
		'deedni-voet'              => 'Links onderaan (footer)',
	);
}

add_action(
	'after_setup_theme',
	function () {
		register_nav_menus( deedni_menu_locaties() );
	},
	20
);

/** Standaardinhoud van elk menu: tekst => link (zie deedni_link). */
function deedni_standaard_menus() {
	return array(
		'deedni-bezoeker'          => array(
			array( 'Zo werkt het', '#zo-werkt-het' ),
			array( 'Privacy', '#privacy' ),
			array( 'Prijzen', '#prijzen' ),
			array( 'Vragen', '#vragen' ),
			array( 'Inloggen', 'pagina:inloggen' ),
		),
		'deedni-kandidaat'         => array(
			array( 'Home', 'pagina:home' ),
			array( 'Aanbiedingen', 'pagina:dashboard' ),
			array( 'Vragen', '#vragen' ),
		),
		'deedni-werkgever'         => array(
			array( 'Home', 'pagina:home' ),
			array( 'Overzicht', 'pagina:dashboard' ),
			array( 'Kandidaten zoeken', 'pagina:zoeken' ),
			array( 'Vragen', '#vragen' ),
		),
		'deedni-account-kandidaat' => array(
			array( 'Mijn profiel', 'pagina:profiel' ),
			array( 'Blokkeerlijst', 'pagina:profiel#blokkeerlijst' ),
		),
		'deedni-account-werkgever' => array(
			array( 'Bedrijfsprofiel', 'pagina:profiel' ),
			array( 'Bewaarde kandidaten', 'pagina:dashboard#bewaard' ),
		),
		'deedni-voet'              => array(),
	);
}

/** Maakt de menu's aan en koppelt ze aan de locaties, alleen als die locatie nog leeg is. */
function deedni_installeer_menus() {
	$locaties = get_theme_mod( 'nav_menu_locations', array() );
	$namen    = deedni_menu_locaties();
	$changed  = false;
	foreach ( deedni_standaard_menus() as $loc => $items ) {
		if ( ! empty( $locaties[ $loc ] ) && wp_get_nav_menu_object( $locaties[ $loc ] ) ) {
			continue;
		}
		$naam = 'deedni: ' . preg_replace( '/\s*\(.*\)$/', '', $namen[ $loc ] );
		$menu = wp_get_nav_menu_object( $naam );
		$id   = $menu ? $menu->term_id : wp_create_nav_menu( $naam );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		if ( ! $menu ) {
			foreach ( $items as $i => $item ) {
				deedni_menu_item_toevoegen( $id, $item[0], $item[1], $i + 1 );
			}
		}
		$locaties[ $loc ] = $id;
		$changed          = true;
	}
	if ( $changed ) {
		set_theme_mod( 'nav_menu_locations', $locaties );
	}
}

function deedni_menu_item_toevoegen( $menu_id, $tekst, $link, $positie ) {
	$args = array(
		'menu-item-title'    => $tekst,
		'menu-item-status'   => 'publish',
		'menu-item-position' => $positie,
	);
	$pagina = 0 === strpos( $link, 'pagina:' ) ? explode( '#', substr( $link, 7 ), 2 ) : null;
	if ( $pagina && 'home' !== $pagina[0] && ! isset( $pagina[1] ) && deedni_page_id( $pagina[0] ) ) {
		$args['menu-item-type']      = 'post_type';
		$args['menu-item-object']    = 'page';
		$args['menu-item-object-id'] = deedni_page_id( $pagina[0] );
	} else {
		$url = 0 === strpos( $link, '#' ) ? home_url( '/' ) . $link : deedni_link( $link );
		$args['menu-item-type'] = 'custom';
		$args['menu-item-url']  = $url;
	}
	wp_update_nav_menu_item( $menu_id, 0, $args );
}

/**
 * De links in een menu, klaar om te tonen.
 * @return array van array( 'tekst', 'url', 'huidig' (bool), 'teller' (int) )
 */
function deedni_menu_items( $locatie ) {
	$locaties = get_nav_menu_locations();
	$items    = array();
	if ( ! empty( $locaties[ $locatie ] ) ) {
		$menu_items = wp_get_nav_menu_items( $locaties[ $locatie ] );
		foreach ( (array) $menu_items as $mi ) {
			if ( (int) $mi->menu_item_parent ) {
				continue; // Submenu's horen niet in dit ontwerp.
			}
			$items[] = array(
				'tekst'  => $mi->title,
				'url'    => $mi->url,
				'object' => 'post_type' === $mi->type ? (int) $mi->object_id : 0,
			);
		}
	} else {
		$standaard = deedni_standaard_menus();
		foreach ( isset( $standaard[ $locatie ] ) ? $standaard[ $locatie ] : array() as $item ) {
			$items[] = array( 'tekst' => $item[0], 'url' => deedni_link( $item[1] ), 'object' => 0 );
		}
	}

	$huidig_id  = is_singular() ? get_queried_object_id() : 0;
	$huidig_url = untrailingslashit( strtok( home_url( add_query_arg( array() ) ), '?#' ) );
	$dashboard  = untrailingslashit( strtok( deedni_page_url( 'dashboard' ), '?#' ) );
	$home       = untrailingslashit( home_url( '/' ) );
	$rol        = deedni_rol();

	foreach ( $items as &$item ) {
		$url            = $item['url'];
		$pad            = untrailingslashit( strtok( $url, '?#' ) );
		$heeft_anker    = false !== strpos( $url, '#' );
		$item['huidig'] = ! $heeft_anker && ( ( $item['object'] && $item['object'] === $huidig_id ) || ( $pad && $pad === $huidig_url && ( $pad !== $home || is_front_page() ) ) );
		// Kandidaatprofielen horen bij "Kandidaten zoeken".
		if ( is_singular( 'kandidaat' ) && ! $heeft_anker && $pad === untrailingslashit( strtok( deedni_page_url( 'zoeken' ), '?#' ) ) ) {
			$item['huidig'] = true;
		}
		$item['teller'] = 0;
		if ( $pad === $dashboard && ! $heeft_anker ) {
			$item['teller'] = 'kandidaat' === $rol ? deedni_aantal_nieuw() : ( 'werkgever' === $rol ? deedni_aantal_tegenvoorstellen() : 0 );
		}
	}
	return $items;
}
