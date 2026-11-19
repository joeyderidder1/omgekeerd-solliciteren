<?php
/**
 * Gebruikersrollen. Kandidaten en werkgevers krijgen alleen 'read': alles wat ze doen loopt via
 * de formulieren op de site, zodat ze nooit in wp-admin hoeven te komen.
 */

defined( 'ABSPATH' ) || exit;

function deedni_add_roles() {
	add_role( 'deedni_kandidaat', 'Kandidaat', array( 'read' => true ) );
	add_role( 'deedni_werkgever', 'Werkgever', array( 'read' => true ) );
}

// Rollen ook toevoegen als de plugin is bijgewerkt zonder opnieuw te activeren.
add_action(
	'init',
	function () {
		if ( get_option( 'deedni_db_version' ) !== DEEDNI_VERSION ) {
			deedni_add_roles();
			update_option( 'deedni_db_version', DEEDNI_VERSION );
		}
	}
);
