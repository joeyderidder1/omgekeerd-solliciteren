<?php
/**
 * Gebruikersrollen. Kandidaten (kandidaten) en werkgevers krijgen alleen 'read': alles wat ze doen
 * loopt via de pagina's op de site, zodat ze nooit in wp-admin hoeven te komen.
 */

defined( 'ABSPATH' ) || exit;

function deedni_add_roles() {
	add_role( 'deedni_kandidaat', 'Kandidaat (werkzoekende)', array( 'read' => true, 'upload_files' => false ) );
	// Bestaande sites: de oude naam "Marketeer (kandidaat)" bijwerken.
	$roles = wp_roles();
	if ( isset( $roles->roles['deedni_kandidaat'] ) && 'Kandidaat (werkzoekende)' !== $roles->roles['deedni_kandidaat']['name'] ) {
		$roles->roles['deedni_kandidaat']['name'] = 'Kandidaat (werkzoekende)';
		$roles->role_names['deedni_kandidaat']    = 'Kandidaat (werkzoekende)';
		update_option( $roles->role_key, $roles->roles );
	}
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
