<?php
/**
 * Custom post types en taxonomieën.
 *
 * kandidaat  Anoniem profiel van een marketeer. Eén per gebruiker, de gebruiker is de auteur.
 * bedrijf    Bedrijfsprofiel van een werkgever. Eén per gebruiker.
 * aanbod     Een aanbod van een werkgever (auteur) aan een kandidaat, met status.
 *
 * Niets hiervan staat in de REST API, de zoekfunctie of de sitemap: profielen zijn alleen te zien
 * via de pagina's van de plugin, waar de toegang wordt gecontroleerd.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'deedni_register_post_types' );
add_action( 'init', 'deedni_register_taxonomies' );

function deedni_register_post_types() {
	register_post_type(
		'kandidaat',
		array(
			'labels'              => deedni_post_type_labels( 'Kandidaat', 'Kandidaten' ),
			'public'              => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => array( 'slug' => 'kandidaat', 'with_front' => false ),
			'menu_icon'           => 'dashicons-id-alt',
			'menu_position'       => 25,
			'supports'            => array( 'title', 'author' ),
		)
	);

	register_post_type(
		'bedrijf',
		array(
			'labels'       => deedni_post_type_labels( 'Bedrijf', 'Bedrijven' ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => false,
			'menu_icon'    => 'dashicons-building',
			'menu_position' => 26,
			'supports'     => array( 'title', 'author' ),
		)
	);

	register_post_type(
		'aanbod',
		array(
			'labels'       => deedni_post_type_labels( 'Aanbod', 'Aanbiedingen' ),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => false,
			'menu_icon'    => 'dashicons-email-alt',
			'menu_position' => 27,
			'supports'     => array( 'title', 'author' ),
		)
	);
}

function deedni_register_taxonomies() {
	$common = array(
		'public'            => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_rest'      => false,
		'rewrite'           => false,
		'meta_box_cb'       => false, // De ACF-velden tonen deze termen al.
	);

	register_taxonomy(
		'specialisme',
		'kandidaat',
		$common + array(
			'labels'       => deedni_taxonomy_labels( 'Specialisme', 'Specialismen' ),
			'hierarchical' => true,
		)
	);
	register_taxonomy(
		'vaardigheid',
		'kandidaat',
		$common + array(
			'labels'       => deedni_taxonomy_labels( 'Tool of vaardigheid', 'Tools en vaardigheden' ),
			'hierarchical' => false,
		)
	);
	register_taxonomy(
		'provincie',
		'kandidaat',
		$common + array(
			'labels'       => deedni_taxonomy_labels( 'Provincie', 'Provincies' ),
			'hierarchical' => true,
		)
	);
}

function deedni_insert_default_terms() {
	$terms = array(
		'specialisme' => array( 'SEA en performance', 'SEO en content', 'Data en analytics', 'Marketing automation en CRM', 'CRO en UX', 'Social advertising', 'E-mailmarketing' ),
		'vaardigheid' => array( 'Google Ads', 'Meta Ads', 'LinkedIn Ads', 'TikTok Ads', 'GA4', 'Google Tag Manager', 'Looker Studio', 'Merchant Center', 'HubSpot', 'Salesforce', 'ActiveCampaign', 'Klaviyo', 'Semrush', 'Ahrefs', 'Screaming Frog', 'Hotjar', 'Figma', 'SQL', 'Python', 'WordPress', 'Shopify' ),
		'provincie'   => array( 'Drenthe', 'Flevoland', 'Friesland', 'Gelderland', 'Groningen', 'Limburg', 'Noord-Brabant', 'Noord-Holland', 'Overijssel', 'Utrecht', 'Zeeland', 'Zuid-Holland' ),
	);
	foreach ( $terms as $taxonomy => $names ) {
		foreach ( $names as $name ) {
			if ( ! term_exists( $name, $taxonomy ) ) {
				wp_insert_term( $name, $taxonomy );
			}
		}
	}
}

function deedni_post_type_labels( $singular, $plural ) {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'menu_name'          => $plural,
		'add_new'            => 'Nieuw',
		'add_new_item'       => $singular . ' toevoegen',
		'edit_item'          => $singular . ' bewerken',
		'new_item'           => 'Nieuw: ' . strtolower( $singular ),
		'view_item'          => $singular . ' bekijken',
		'search_items'       => $plural . ' zoeken',
		'not_found'          => 'Niets gevonden',
		'not_found_in_trash' => 'Niets gevonden in de prullenbak',
		'all_items'          => 'Alle ' . strtolower( $plural ),
	);
}

function deedni_taxonomy_labels( $singular, $plural ) {
	return array(
		'name'          => $plural,
		'singular_name' => $singular,
		'menu_name'     => $plural,
		'all_items'     => 'Alle ' . strtolower( $plural ),
		'edit_item'     => $singular . ' bewerken',
		'add_new_item'  => $singular . ' toevoegen',
		'search_items'  => $plural . ' zoeken',
		'not_found'     => 'Niets gevonden',
	);
}
