<?php
/**
 * Plugin Name:       deedni Core
 * Description:       Omgekeerd vacatureplatform: anonieme kandidaatprofielen, bedrijfsprofielen, aanbiedingen, rollen en zoeken voor werkgevers. Werkt samen met Advanced Custom Fields.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Author:            deedni
 * License:           GPL-2.0-or-later
 * Text Domain:       deedni
 */

defined( 'ABSPATH' ) || exit;

define( 'DEEDNI_VERSION', '1.0.0' );
define( 'DEEDNI_FILE', __FILE__ );
define( 'DEEDNI_DIR', plugin_dir_path( __FILE__ ) );
define( 'DEEDNI_URL', plugin_dir_url( __FILE__ ) );

require_once DEEDNI_DIR . 'includes/helpers.php';
require_once DEEDNI_DIR . 'includes/roles.php';
require_once DEEDNI_DIR . 'includes/post-types.php';
require_once DEEDNI_DIR . 'includes/acf.php';
require_once DEEDNI_DIR . 'includes/access.php';
require_once DEEDNI_DIR . 'includes/emails.php';
require_once DEEDNI_DIR . 'includes/forms.php';
require_once DEEDNI_DIR . 'includes/search.php';
require_once DEEDNI_DIR . 'includes/shortcodes.php';
require_once DEEDNI_DIR . 'includes/admin.php';

register_activation_hook( __FILE__, 'deedni_activate' );
register_deactivation_hook( __FILE__, 'deedni_deactivate' );

/**
 * Bij activeren: rollen, standaardtermen en de vijf pagina's met shortcodes aanmaken.
 */
function deedni_activate() {
	deedni_add_roles();
	deedni_register_post_types();
	deedni_register_taxonomies();
	deedni_insert_default_terms();
	deedni_create_pages();
	flush_rewrite_rules();
}

function deedni_deactivate() {
	flush_rewrite_rules();
}

/**
 * Maakt de pagina's aan die nog niet bestaan. Bestaande koppelingen blijven staan.
 */
function deedni_create_pages() {
	$pages   = get_option( 'deedni_pages', array() );
	$wanted  = array(
		'registreren' => array( 'Aanmelden', 'aanmelden', '[deedni_registreren]' ),
		'inloggen'    => array( 'Inloggen', 'inloggen', '[deedni_inloggen]' ),
		'dashboard'   => array( 'Mijn deedni', 'mijn-deedni', '[deedni_dashboard]' ),
		'profiel'     => array( 'Mijn profiel', 'mijn-profiel', '[deedni_profiel]' ),
		'zoeken'      => array( 'Kandidaten zoeken', 'kandidaten', '[deedni_zoeken]' ),
	);

	foreach ( $wanted as $key => $page ) {
		if ( ! empty( $pages[ $key ] ) && get_post_status( $pages[ $key ] ) ) {
			continue;
		}
		$pages[ $key ] = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page[0],
				'post_name'    => $page[1],
				'post_content' => '<!-- wp:shortcode -->' . $page[2] . '<!-- /wp:shortcode -->',
			)
		);
	}

	update_option( 'deedni_pages', $pages );
}
