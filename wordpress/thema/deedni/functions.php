<?php
/**
 * deedni thema.
 */

defined( 'ABSPATH' ) || exit;

define( 'DEEDNI_THEME_VERSION', '1.2.0' );

require_once __DIR__ . '/inc/acf.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/seed.php';
require_once __DIR__ . '/inc/style.php';

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'responsive-embeds' );
		// Laat de plugin weten dat dit thema de profielpagina zelf opmaakt (single-kandidaat.php).
		add_theme_support( 'deedni' );

		register_nav_menus(
			array(
				'primary' => 'Hoofdmenu',
				'footer'  => 'Footer',
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'deedni-fonts', 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap', array(), null );
		wp_enqueue_style( 'deedni', get_stylesheet_uri(), array( 'deedni-fonts' ), DEEDNI_THEME_VERSION );
		wp_enqueue_script( 'deedni', get_theme_file_uri( 'assets/js/main.js' ), array(), DEEDNI_THEME_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
);

add_filter(
	'wp_resource_hints',
	function ( $urls, $type ) {
		if ( 'preconnect' === $type ) {
			$urls[] = 'https://fonts.googleapis.com';
			$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
		}
		return $urls;
	},
	10,
	2
);

/** Link naar een deedni-pagina, of een veilige terugval als de plugin uit staat. */
function deedni_theme_url( $key, $args = array() ) {
	if ( function_exists( 'deedni_page_url' ) && deedni_page_id( $key ) ) {
		return deedni_page_url( $key, $args );
	}
	return home_url( '/#aanmelden' );
}

/** Ankerlink naar een sectie op de homepage, ook vanaf andere pagina's. */
function deedni_theme_anchor( $id ) {
	return is_front_page() ? '#' . $id : home_url( '/#' . $id );
}

/** Menu-items als losse links, zoals in het ontwerp (geen lijst). */
function deedni_theme_menu_links( $location, $fallback ) {
	$locations = get_nav_menu_locations();
	$items     = ! empty( $locations[ $location ] ) ? wp_get_nav_menu_items( $locations[ $location ] ) : array();
	$links     = array();
	if ( $items ) {
		foreach ( $items as $item ) {
			if ( ! $item->menu_item_parent ) {
				$links[ $item->url ] = $item->title;
			}
		}
	} else {
		foreach ( $fallback as $id => $title ) {
			$links[ deedni_theme_anchor( $id ) ] = $title;
		}
	}
	$current = home_url( add_query_arg( array() ) );
	foreach ( $links as $url => $title ) {
		$is_current = untrailingslashit( strtok( $url, '?#' ) ) === untrailingslashit( strtok( $current, '?#' ) ) && false === strpos( $url, '#' );
		printf( '<a href="%s"%s>%s</a>', esc_url( $url ), $is_current ? ' aria-current="page"' : '', esc_html( $title ) );
	}
}

/** Korte intro onder de titel van de deedni-pagina's. */
function deedni_theme_page_lead() {
	if ( ! function_exists( 'deedni_is_page' ) ) {
		return '';
	}
	if ( deedni_is_page( 'registreren' ) ) {
		return 'Marketeers maken gratis een anoniem profiel. Werkgevers zoeken gratis en betalen pas bij resultaat.';
	}
	if ( deedni_is_page( 'zoeken' ) ) {
		return 'Anonieme profielen van online marketeers. Stuur een aanbod met salaris. Zegt iemand ja, dan zie je naam, cv en contactgegevens.';
	}
	if ( deedni_is_page( 'profiel' ) ) {
		return deedni_is_werkgever()
			? 'Kandidaten zien je bedrijfsnaam, plaats en omschrijving bij elk aanbod dat je stuurt.'
			: 'Bedrijven zien alleen je anonieme profiel. Je naam, contactgegevens en cv zien ze pas als jij ja zegt.';
	}
	return '';
}

/** Zoeken en het overzicht gebruiken de volle breedte. */
function deedni_theme_is_wide() {
	return function_exists( 'deedni_is_page' ) && ( deedni_is_page( 'zoeken' ) || deedni_is_page( 'dashboard' ) );
}

/** De titel van het overzicht persoonlijk maken. */
add_filter(
	'the_title',
	function ( $title, $post_id = 0 ) {
		if ( function_exists( 'deedni_page_id' ) && (int) $post_id === deedni_page_id( 'dashboard' ) && in_the_loop() && is_user_logged_in() ) {
			$user = wp_get_current_user();
			$name = $user->first_name ? $user->first_name : $user->display_name;
			return 'Hoi ' . $name;
		}
		return $title;
	},
	10,
	2
);
