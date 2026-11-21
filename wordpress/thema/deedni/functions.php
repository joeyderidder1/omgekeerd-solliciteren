<?php
/**
 * deedni thema (versie 2): een blokthema. De inhoud en de opmaak komen uit de plugin deedni Core.
 */

defined( 'ABSPATH' ) || exit;

// Updates uit GitHub Releases, zichtbaar onder Dashboard > Updates (zie wordpress/UPDATES.md).
require_once __DIR__ . '/inc/updater.php';
Deedni_GitHub_Updater::thema( get_template(), wp_get_theme( get_template() )->get( 'Version' ) );

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'responsive-embeds' );
		// Klassieke menu's (Weergave > Menu's) voor het menu per rol.
		add_theme_support( 'menus' );
	}
);

/** Zonder de plugin werkt de site niet: zeg dat duidelijk in wp-admin. */
add_action(
	'admin_notices',
	function () {
		if ( function_exists( 'deedni_blok' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>deedni:</strong> activeer de plugin <strong>deedni Core</strong> (versie 2). Het thema gebruikt de blokken, menu\'s en opmaak uit die plugin.</p></div>';
	}
);

/** Weergave > Menu's tonen, ook al is dit een blokthema. */
add_action(
	'admin_menu',
	function () {
		foreach ( isset( $GLOBALS['submenu']['themes.php'] ) ? $GLOBALS['submenu']['themes.php'] : array() as $item ) {
			if ( isset( $item[2] ) && 'nav-menus.php' === $item[2] ) {
				return;
			}
		}
		add_submenu_page( 'themes.php', 'Menu\'s', 'Menu\'s', 'edit_theme_options', 'nav-menus.php' );
	},
	99
);

/** Een rustige pagina zonder plugin: dan in elk geval basisopmaak. */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'deedni-thema', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
	}
);

/**
 * viewport-fit=cover: op telefoons met een notch of afgeronde hoeken loopt de achtergrond door tot de rand.
 * De opmaak (deedni Core) houdt tekst en knoppen met env(safe-area-inset-*) buiten die randen.
 */
add_action(
	'wp_head',
	function () {
		// has_action geeft de prioriteit terug, en die is hier 0: daarom de vergelijking met false.
		if ( false !== has_action( 'wp_head', '_block_template_viewport_meta_tag' ) ) {
			remove_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );
			echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />' . "\n";
		}
	},
	-1
);
