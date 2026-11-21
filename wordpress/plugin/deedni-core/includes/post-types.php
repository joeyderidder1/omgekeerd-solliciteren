<?php
/**
 * Custom post types en taxonomieën.
 *
 * kandidaat  Anoniem profiel van een werkzoekende (elk beroep). Eén per gebruiker, de gebruiker is de auteur.
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
		'meta_box_cb'       => false, // Kandidaten kiezen dit zelf in hun profiel.
	);

	// Vakgebied en functie: bovenste laag is het vakgebied (bijvoorbeeld "Groen en buitenruimte"),
	// daaronder de functies (bijvoorbeeld "Hovenier"). De naam 'specialisme' blijft voor bestaande gegevens.
	register_taxonomy(
		'specialisme',
		'kandidaat',
		$common + array(
			'labels'       => deedni_taxonomy_labels( 'Vakgebied of functie', 'Vakgebieden en functies' ),
			'hierarchical' => true,
			'description'  => 'Bovenste laag: vakgebieden. Daaronder: de functies binnen dat vakgebied. Voeg zelf vakgebieden en functies toe of hernoem ze.',
		)
	);
	register_taxonomy(
		'vaardigheid',
		'kandidaat',
		$common + array(
			'labels'       => deedni_taxonomy_labels( 'Vaardigheid of tool', 'Vaardigheden en tools' ),
			'hierarchical' => false,
			'description'  => 'Vaardigheden, tools, certificaten en rijbewijzen. Koppel ze aan een of meer vakgebieden, dan zien kandidaten in dat vakgebied ze als eerste.',
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

/**
 * De standaardlijsten. Alles is daarna aan te passen in wp-admin bij Kandidaten > Vakgebieden en functies
 * en Kandidaten > Vaardigheden en tools. Termen die al bestaan worden niet aangeraakt.
 * Filter 'deedni_standaard_vakgebieden' en 'deedni_standaard_vaardigheden' om de lijsten te veranderen.
 */
function deedni_standaard_vakgebieden() {
	return apply_filters(
		'deedni_standaard_vakgebieden',
		array(
			'Marketing en communicatie'   => array( 'SEA en performance', 'SEO en content', 'Data en analytics', 'Marketing automation en CRM', 'CRO en UX', 'Social advertising', 'E-mailmarketing', 'Communicatie en pr' ),
			'ICT en webdesign'            => array( 'Webdesigner', 'Front-end developer', 'Back-end developer', 'UX- en UI-designer', 'Systeembeheerder', 'Data-analist' ),
			'Financiën en administratie'  => array( 'Accountant', 'Boekhouder', 'Controller', 'Salarisadministrateur', 'Administratief medewerker' ),
			'Groen en buitenruimte'       => array( 'Hovenier', 'Tuinontwerper', 'Boomverzorger', 'Medewerker groenvoorziening' ),
			'Bouw en techniek'            => array( 'Timmerman', 'Elektricien', 'Installatiemonteur', 'Loodgieter', 'Werkvoorbereider' ),
			'Zorg en welzijn'             => array( 'Verpleegkundige', 'Verzorgende IG', 'Begeleider', 'Doktersassistent' ),
			'Onderwijs en kinderopvang'   => array( 'Leerkracht', 'Docent', 'Pedagogisch medewerker' ),
			'Horeca en toerisme'          => array( 'Kok', 'Gastheer of gastvrouw', 'Bedrijfsleider horeca' ),
			'Logistiek en transport'      => array( 'Chauffeur', 'Magazijnmedewerker', 'Planner' ),
			'Sales en klantenservice'     => array( 'Accountmanager', 'Binnendienstmedewerker', 'Klantenservicemedewerker' ),
			'HR en recruitment'           => array( 'HR-adviseur', 'Recruiter' ),
			'Creatief en design'          => array( 'Grafisch ontwerper', 'Fotograaf', 'Videomaker' ),
			'Detailhandel'                => array( 'Verkoopmedewerker', 'Filiaalmanager' ),
		)
	);
}

/** Vaardigheid => vakgebieden waar hij bij hoort (leeg = voor iedereen). */
function deedni_standaard_vaardigheden() {
	$m  = 'Marketing en communicatie';
	$i  = 'ICT en webdesign';
	$f  = 'Financiën en administratie';
	$g  = 'Groen en buitenruimte';
	$bt = 'Bouw en techniek';
	$z  = 'Zorg en welzijn';
	$o  = 'Onderwijs en kinderopvang';
	$h  = 'Horeca en toerisme';
	$l  = 'Logistiek en transport';
	$s  = 'Sales en klantenservice';
	$hr = 'HR en recruitment';
	$c  = 'Creatief en design';
	$d  = 'Detailhandel';
	return apply_filters(
		'deedni_standaard_vaardigheden',
		array(
			// Voor iedereen.
			'Rijbewijs B'                => array(),
			'Microsoft Office'           => array(),
			'Excel'                      => array( $f, $m, $s, $hr ),
			'Engels'                     => array(),
			'Duits'                      => array(),
			'Leidinggeven'               => array(),
			'Projectmanagement'          => array(),
			'BHV'                        => array(),
			// Marketing.
			'Google Ads'                 => array( $m ),
			'Meta Ads'                   => array( $m ),
			'LinkedIn Ads'               => array( $m ),
			'TikTok Ads'                 => array( $m ),
			'GA4'                        => array( $m, $i ),
			'Google Tag Manager'         => array( $m, $i ),
			'Looker Studio'              => array( $m, $i ),
			'Merchant Center'            => array( $m ),
			'HubSpot'                    => array( $m, $s ),
			'Salesforce'                 => array( $m, $s ),
			'ActiveCampaign'             => array( $m ),
			'Klaviyo'                    => array( $m ),
			'Semrush'                    => array( $m ),
			'Ahrefs'                     => array( $m ),
			'Screaming Frog'             => array( $m ),
			'Hotjar'                     => array( $m, $i ),
			// ICT en web.
			'Figma'                      => array( $i, $c, $m ),
			'HTML en CSS'                => array( $i ),
			'JavaScript'                 => array( $i ),
			'PHP'                        => array( $i ),
			'React'                      => array( $i ),
			'SQL'                        => array( $i, $m, $f ),
			'Python'                     => array( $i, $m ),
			'WordPress'                  => array( $i, $m ),
			'Shopify'                    => array( $i, $m ),
			'Microsoft 365-beheer'       => array( $i ),
			// Financiën.
			'Exact Online'               => array( $f ),
			'AFAS'                       => array( $f, $hr ),
			'Twinfield'                  => array( $f ),
			'SAP'                        => array( $f, $l ),
			'Moneybird'                  => array( $f ),
			'Jaarrekeningen'             => array( $f ),
			'Btw-aangiftes'              => array( $f ),
			'RA of AA'                   => array( $f ),
			// Groen.
			'Snoeien'                    => array( $g ),
			'Bestrating'                 => array( $g, $bt ),
			'Beplantingsplannen'         => array( $g ),
			'Minigraver'                 => array( $g, $bt ),
			'Rijbewijs BE'               => array( $g, $bt, $l ),
			'Rijbewijs T'                => array( $g ),
			'European Tree Worker'       => array( $g ),
			// Bouw en techniek.
			'VCA'                        => array( $bt, $g, $l ),
			'NEN 3140'                   => array( $bt ),
			'AutoCAD'                    => array( $bt, $c ),
			'Revit'                      => array( $bt ),
			'Tekening lezen'             => array( $bt ),
			// Zorg.
			'BIG-registratie'            => array( $z ),
			'Voorbehouden handelingen'   => array( $z ),
			'Ouderenzorg'                => array( $z ),
			'Gehandicaptenzorg'          => array( $z ),
			// Onderwijs.
			'Lesbevoegdheid'             => array( $o ),
			'VVE-certificaat'            => array( $o ),
			'Kinder-EHBO'                => array( $o, $z ),
			// Horeca.
			'Sociale Hygiëne'            => array( $h ),
			'HACCP'                      => array( $h ),
			'À la carte koken'           => array( $h ),
			// Logistiek.
			'Rijbewijs C'                => array( $l ),
			'Rijbewijs CE'               => array( $l ),
			'Code 95'                    => array( $l ),
			'Heftruckcertificaat'        => array( $l, $d ),
			'Reachtruck'                 => array( $l ),
			// Sales en klantenservice.
			'Acquisitie'                 => array( $s ),
			'Offertes maken'             => array( $s, $bt ),
			'Telefonische klantcontacten' => array( $s, $d ),
			// HR.
			'Werving en selectie'        => array( $hr ),
			'Arbeidsrecht'               => array( $hr ),
			// Creatief.
			'Adobe Photoshop'            => array( $c, $m ),
			'Adobe Illustrator'          => array( $c ),
			'Adobe InDesign'             => array( $c ),
			'Premiere Pro'               => array( $c, $m ),
			// Detailhandel.
			'Kassawerk'                  => array( $d ),
			'Visual merchandising'       => array( $d, $c ),
		)
	);
}

function deedni_insert_default_terms() {
	// Vakgebieden met hun functies.
	foreach ( deedni_standaard_vakgebieden() as $vak => $functies ) {
		$v = term_exists( $vak, 'specialisme', 0 );
		if ( ! $v ) {
			$v = wp_insert_term( $vak, 'specialisme' );
		}
		if ( is_wp_error( $v ) || ! $v ) {
			continue;
		}
		$vak_id = (int) ( is_array( $v ) ? $v['term_id'] : $v );
		foreach ( $functies as $functie ) {
			$t = term_exists( $functie, 'specialisme' );
			if ( ! $t ) {
				wp_insert_term( $functie, 'specialisme', array( 'parent' => $vak_id ) );
				continue;
			}
			// Bestaande functie zonder vakgebied (zoals de marketingspecialismen van versie 2) onder het vakgebied hangen.
			$t_id = (int) ( is_array( $t ) ? $t['term_id'] : $t );
			$term = get_term( $t_id, 'specialisme' );
			if ( $term && ! is_wp_error( $term ) && 0 === (int) $term->parent && $t_id !== $vak_id ) {
				wp_update_term( $t_id, 'specialisme', array( 'parent' => $vak_id ) );
			}
		}
	}

	// Vaardigheden, gekoppeld aan vakgebieden.
	foreach ( deedni_standaard_vaardigheden() as $naam => $vakken ) {
		$t = term_exists( $naam, 'vaardigheid' );
		$nieuw = ! $t;
		if ( $nieuw ) {
			$t = wp_insert_term( $naam, 'vaardigheid' );
		}
		if ( is_wp_error( $t ) || ! $t ) {
			continue;
		}
		$t_id = (int) ( is_array( $t ) ? $t['term_id'] : $t );
		// Alleen koppelen als er nog niets is ingesteld, zodat eigen keuzes van de beheerder blijven staan.
		if ( $nieuw || '' === (string) get_term_meta( $t_id, 'deedni_vakgebieden', true ) ) {
			$ids = array();
			foreach ( $vakken as $vak ) {
				$v = term_exists( $vak, 'specialisme', 0 );
				if ( $v ) {
					$ids[] = (int) ( is_array( $v ) ? $v['term_id'] : $v );
				}
			}
			update_term_meta( $t_id, 'deedni_vakgebieden', $ids ? implode( ',', $ids ) : 'alle' );
		}
	}

	foreach ( array( 'Drenthe', 'Flevoland', 'Friesland', 'Gelderland', 'Groningen', 'Limburg', 'Noord-Brabant', 'Noord-Holland', 'Overijssel', 'Utrecht', 'Zeeland', 'Zuid-Holland' ) as $name ) {
		if ( ! term_exists( $name, 'provincie' ) ) {
			wp_insert_term( $name, 'provincie' );
		}
	}
}

/* ---------- Vakgebieden en functies ---------- */

/** Het vakgebied (bovenste term) van een term uit 'specialisme'. Een vakgebied is zijn eigen vakgebied. */
function deedni_vakgebied_van( $term_id ) {
	static $cache = array();
	$term_id = (int) $term_id;
	if ( isset( $cache[ $term_id ] ) ) {
		return $cache[ $term_id ];
	}
	$ouders = get_ancestors( $term_id, 'specialisme', 'taxonomy' );
	$cache[ $term_id ] = $ouders ? (int) end( $ouders ) : $term_id;
	return $cache[ $term_id ];
}

/** Vakgebieden (bovenste termen) bij een lijst termen. */
function deedni_vakgebieden_van( $term_ids ) {
	return array_values( array_unique( array_map( 'deedni_vakgebied_van', (array) $term_ids ) ) );
}

/** Alle vakgebieden met hun functies: array( vakgebied-term, array( functie-termen ) ). */
function deedni_vakgebieden_boom() {
	$terms = get_terms( array( 'taxonomy' => 'specialisme', 'hide_empty' => false, 'orderby' => 'name' ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	$boom = array();
	foreach ( $terms as $t ) {
		if ( 0 === (int) $t->parent ) {
			$boom[ $t->term_id ] = array( $t, array() );
		}
	}
	foreach ( $terms as $t ) {
		if ( 0 !== (int) $t->parent ) {
			$vak = deedni_vakgebied_van( $t->term_id );
			if ( isset( $boom[ $vak ] ) ) {
				$boom[ $vak ][1][] = $t;
			}
		}
	}
	return $boom;
}

/** Bij welke vakgebieden hoort een vaardigheid? Leeg = bij alle. */
function deedni_vaardigheid_vakgebieden( $term_id ) {
	$v = (string) get_term_meta( (int) $term_id, 'deedni_vakgebieden', true );
	if ( '' === $v || 'alle' === $v ) {
		return array();
	}
	return array_values( array_filter( array_map( 'intval', explode( ',', $v ) ) ) );
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
