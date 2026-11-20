<?php
/**
 * Plugin Name:       deedni Core
 * Description:       Het platform achter deedni: rollen (marketeer en werkgever), inloggen, anonieme profielen, zoeken met matchpercentage, aanbiedingen met "Ja, maar..." en alle deedni-blokken voor de pagina's.
 * Version:           2.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.0
 * Author:            deedni
 * License:           GPL-2.0-or-later
 * Text Domain:       deedni
 */

defined( 'ABSPATH' ) || exit;

define( 'DEEDNI_VERSION', '2.1.0' );
define( 'DEEDNI_FILE', __FILE__ );
define( 'DEEDNI_DIR', plugin_dir_path( __FILE__ ) );
define( 'DEEDNI_URL', plugin_dir_url( __FILE__ ) );

require_once DEEDNI_DIR . 'includes/helpers.php';
require_once DEEDNI_DIR . 'includes/roles.php';
require_once DEEDNI_DIR . 'includes/post-types.php';
require_once DEEDNI_DIR . 'includes/profiel.php';
require_once DEEDNI_DIR . 'includes/aanbod.php';
require_once DEEDNI_DIR . 'includes/match.php';
require_once DEEDNI_DIR . 'includes/access.php';
require_once DEEDNI_DIR . 'includes/emails.php';
require_once DEEDNI_DIR . 'includes/acties.php';
require_once DEEDNI_DIR . 'includes/zoeken.php';
require_once DEEDNI_DIR . 'includes/statistiek.php';
require_once DEEDNI_DIR . 'includes/menus.php';
require_once DEEDNI_DIR . 'includes/blokken.php';
require_once DEEDNI_DIR . 'includes/installatie.php';
require_once DEEDNI_DIR . 'includes/shortcodes.php';
require_once DEEDNI_DIR . 'includes/admin.php';

register_activation_hook( __FILE__, 'deedni_activate' );
register_deactivation_hook( __FILE__, 'deedni_deactivate' );

/**
 * Bij activeren: rollen, standaardtermen, pagina's, menu's en (als het deedni-thema actief is) de homepage.
 */
function deedni_activate() {
	deedni_add_roles();
	deedni_register_post_types();
	deedni_register_taxonomies();
	deedni_insert_default_terms();
	deedni_create_pages();
	deedni_installeer_menus();
	deedni_installeer_homepage();
	update_option( 'deedni_db_version', DEEDNI_VERSION );
	flush_rewrite_rules();
}

function deedni_deactivate() {
	flush_rewrite_rules();
}
