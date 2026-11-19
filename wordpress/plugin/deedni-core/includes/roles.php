<?php
/**
 * Gebruikersrollen. Marketeers (kandidaten) en werkgevers krijgen alleen 'read': alles wat ze doen
 * loopt via de pagina's op de site, zodat ze nooit in wp-admin hoeven te komen.
 */

defined( 'ABSPATH' ) || exit;

function deedni_add_roles() {
	add_role( 'deedni_kandidaat', 'Marketeer (kandidaat)', array( 'read' => true, 'upload_files' => false ) );
	add_role( 'deedni_werkgever', 'Werkgever', array( 'read' => true, 'upload_files' => false ) );
}

// Na een update van de plugin: rollen, termen, pagina's en oude inhoud bijwerken zonder opnieuw te activeren.
add_action(
	'init',
	function () {
		if ( get_option( 'deedni_db_version' ) === DEEDNI_VERSION ) {
			return;
		}
		deedni_add_roles();
		deedni_insert_default_terms();
		deedni_create_pages();
		deedni_migreer_v1();
		deedni_installeer_menus();
		deedni_installeer_homepage();
		update_option( 'deedni_db_version', DEEDNI_VERSION );
		flush_rewrite_rules( false );
	},
	20
);
