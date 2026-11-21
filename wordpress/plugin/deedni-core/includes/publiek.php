<?php
/**
 * Openbaar kandidatenoverzicht: bezoekers zonder account zien een paar anonieme kaarten.
 *
 * Wat er WEL op een kaart staat: vakgebied, functie (uit de vaste lijst, nooit de vrije functietitel),
 * provincie of landsdeel, ervaring in een band (bijvoorbeeld "5 tot 10 jaar"), maximaal drie
 * vaardigheden die veel kandidaten delen, en beschikbaarheid.
 *
 * Wat er NOOIT op staat of in de HTML zit: naam, initialen, profielcode, ID, link of URL per kandidaat,
 * werkgevers, salaris, vrije tekst (samenvatting, projecten, woonplaats) of exacte getallen.
 * Er is geen detailpagina, geen REST- of AJAX-uitvoer, en de lijst staat niet in de REST API van de pagina.
 *
 * Herleidbaarheid:
 * - Alleen kandidaten die er zelf voor kiezen (Mijn profiel > Openbaar overzicht). Standaard uit.
 * - k-anonimiteit: elke combinatie van (functie of vakgebied) + (provincie of landsdeel) + ervaringsband
 *   die op een kaart staat, geldt voor minstens k kandidaten in het overzicht (Instellingen > deedni,
 *   standaard 3). Is een combinatie zeldzamer, dan tonen we hem globaler; lukt dat niet, dan niet.
 * - Vaardigheden alleen als minstens k kandidaten in het overzicht ze hebben.
 *
 * Tegen scrapen: 12 per pagina, maximaal een paar pagina's, een volgorde die per dag wisselt (niet op
 * aanmelddatum), en een teller per IP-adres (als hash, zie beveiliging.php).
 */

defined( 'ABSPATH' ) || exit;

/** Staat het openbare overzicht aan? (Instellingen > deedni) */
function deedni_publiek_aan() {
	return (bool) get_option( 'deedni_publiek_aan', 1 );
}

/** Minimale groepsgrootte (k). */
function deedni_publiek_k() {
	return max( 2, min( 20, (int) get_option( 'deedni_publiek_k', 3 ) ) );
}

function deedni_publiek_per_pagina() {
	return 12;
}

/** Maximaal aantal pagina's dat een bezoeker kan doorbladeren. */
function deedni_publiek_max_paginas() {
	return max( 1, min( 10, (int) get_option( 'deedni_publiek_paginas', 3 ) ) );
}

/** Provincie => landsdeel. */
function deedni_landsdeel( $provincie_slug ) {
	$map = array(
		'groningen'     => 'Noord-Nederland',
		'friesland'     => 'Noord-Nederland',
		'drenthe'       => 'Noord-Nederland',
		'overijssel'    => 'Oost-Nederland',
		'gelderland'    => 'Oost-Nederland',
		'flevoland'     => 'Oost-Nederland',
		'noord-holland' => 'West-Nederland',
		'zuid-holland'  => 'West-Nederland',
		'utrecht'       => 'West-Nederland',
		'noord-brabant' => 'Zuid-Nederland',
		'limburg'       => 'Zuid-Nederland',
		'zeeland'       => 'Zuid-Nederland',
	);
	return isset( $map[ $provincie_slug ] ) ? $map[ $provincie_slug ] : 'Nederland';
}

/** Ervaring als band, nooit het exacte aantal jaren. */
function deedni_ervaring_band( $jaren ) {
	$jaren = (int) $jaren;
	if ( $jaren < 2 ) {
		return 'Tot 2 jaar ervaring';
	}
	if ( $jaren < 5 ) {
		return '2 tot 5 jaar ervaring';
	}
	if ( $jaren < 10 ) {
		return '5 tot 10 jaar ervaring';
	}
	return 'Meer dan 10 jaar ervaring';
}

function deedni_publiek_beschikbaar( $waarde ) {
	$map = array(
		'direct'      => 'Binnen een maand beschikbaar',
		'1_maand'     => 'Binnen een maand beschikbaar',
		'2_3_maanden' => 'Binnen drie maanden beschikbaar',
		'in_overleg'  => 'Open voor een goed aanbod',
	);
	return isset( $map[ $waarde ] ) ? $map[ $waarde ] : '';
}

/** Kandidaten die meedoen: online, toestemming gegeven, niet verborgen door beheer, actueel. */
function deedni_publiek_ids() {
	$meta = array(
		'relation' => 'AND',
		array( 'key' => 'profiel_zichtbaar', 'value' => '1' ),
		array( 'key' => 'publiek_overzicht', 'value' => '1' ),
		array( 'key' => 'beheer_verborgen', 'compare' => 'NOT EXISTS' ),
	);
	$dagen = (int) get_option( 'deedni_actueel_dagen', 0 );
	if ( $dagen > 0 ) {
		$meta[] = array( 'key' => 'bevestigd_op', 'value' => time() - $dagen * DAY_IN_SECONDS, 'compare' => '>=', 'type' => 'NUMERIC' );
	}
	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => 'kandidaat',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'  => true,
			)
		)
	);
}

/** De ruwe kenmerken van één kandidaat (alleen gegevens uit vaste lijsten). */
function deedni_publiek_kenmerken( $id ) {
	$functie = 0;
	foreach ( deedni_term_ids( $id, 'specialisme' ) as $tid ) {
		if ( deedni_vakgebied_van( $tid ) !== (int) $tid ) {
			$functie = (int) $tid;
			break;
		}
	}
	if ( ! $functie ) {
		return null;
	}
	$vak  = deedni_vakgebied_van( $functie );
	$fterm = get_term( $functie, 'specialisme' );
	$vterm = get_term( $vak, 'specialisme' );
	if ( ! $fterm || is_wp_error( $fterm ) || ! $vterm || is_wp_error( $vterm ) ) {
		return null;
	}
	$prov = get_the_terms( $id, 'provincie' );
	$prov = ( $prov && ! is_wp_error( $prov ) ) ? $prov[0] : null;
	$tools = get_the_terms( $id, 'vaardigheid' );
	return array(
		'id'       => (int) $id,
		'functie'  => $fterm->name,
		'vak'      => $vterm->name,
		'vak_slug' => $vterm->slug,
		'prov'     => $prov ? $prov->name : '',
		'deel'     => $prov ? deedni_landsdeel( $prov->slug ) : '',
		'band'     => deedni_ervaring_band( get_post_meta( $id, 'ervaring_jaren', true ) ),
		'besch'    => deedni_publiek_beschikbaar( (string) get_post_meta( $id, 'beschikbaarheid', true ) ),
		'tools'    => ( $tools && ! is_wp_error( $tools ) ) ? wp_list_pluck( $tools, 'name' ) : array(),
	);
}

/**
 * Alle kaarten, al veralgemeend. Bewaard in een transient (5 minuten, en geleegd zodra een profiel
 * verandert). Bevat intern het ID (voor de blokkeerlijst), dat nooit in de HTML komt.
 */
function deedni_publiek_kaarten() {
	$cache = get_transient( 'deedni_publiek_kaarten' );
	if ( is_array( $cache ) ) {
		return $cache;
	}
	$k     = deedni_publiek_k();
	$ruw   = array_values( array_filter( array_map( 'deedni_publiek_kenmerken', deedni_publiek_ids() ) ) );
	$tel   = array();
	$tools = array();
	$niveaus = deedni_publiek_niveaus();
	foreach ( $ruw as $r ) {
		foreach ( $niveaus as $n => $fn ) {
			$sleutel = $n . '|' . implode( '|', $fn( $r ) );
			$tel[ $sleutel ] = ( isset( $tel[ $sleutel ] ) ? $tel[ $sleutel ] : 0 ) + 1;
		}
		foreach ( array_unique( $r['tools'] ) as $t ) {
			$tools[ $t ] = ( isset( $tools[ $t ] ) ? $tools[ $t ] : 0 ) + 1;
		}
	}
	// Eerste keuze: het preciezeste niveau waarop de groep groot genoeg is.
	$niveau = array();
	foreach ( $ruw as $i => $r ) {
		$niveau[ $i ] = 5; // 5 = niet tonen
		foreach ( $niveaus as $n => $fn ) {
			if ( $tel[ $n . '|' . implode( '|', $fn( $r ) ) ] >= $k ) {
				$niveau[ $i ] = $n;
				break;
			}
		}
	}
	// Daarna: elke kaart die je echt ziet, moet minstens k keer precies zo voorkomen. Anders kun je een
	// globale kaart "terugrekenen" door de preciezere kaarten ernaast af te trekken. Te kleine groepen gaan
	// een niveau omhoog, tot alles klopt (of de kaart verdwijnt).
	do {
		$gewijzigd = false;
		$label     = array();
		foreach ( $ruw as $i => $r ) {
			if ( $niveau[ $i ] <= 4 ) {
				$sleutel           = implode( '|', $niveaus[ $niveau[ $i ] ]( $r ) );
				$label[ $sleutel ] = ( isset( $label[ $sleutel ] ) ? $label[ $sleutel ] : 0 ) + 1;
			}
		}
		foreach ( $ruw as $i => $r ) {
			if ( $niveau[ $i ] <= 4 && $label[ implode( '|', $niveaus[ $niveau[ $i ] ]( $r ) ) ] < $k ) {
				++$niveau[ $i ];
				$gewijzigd = true;
			}
		}
	} while ( $gewijzigd );

	$kaarten = array();
	foreach ( $ruw as $i => $r ) {
		if ( $niveau[ $i ] > 4 ) {
			continue;
		}
		$waarden = $niveaus[ $niveau[ $i ] ]( $r );
		// Vaardigheden: alleen die veel voorkomen, de meest gedeelde eerst, maximaal drie.
		$mijn = array_values( array_filter( array_unique( $r['tools'] ), function ( $t ) use ( $tools, $k ) {
			return $tools[ $t ] >= $k;
		} ) );
		usort( $mijn, function ( $a, $b ) use ( $tools ) {
			return $tools[ $b ] - $tools[ $a ] ?: strcmp( $a, $b );
		} );
		$kaarten[] = array(
			'id'       => $r['id'],
			'titel'    => $waarden[0],
			'vak'      => $r['vak'],
			'vak_slug' => $r['vak_slug'],
			'regio'    => $waarden[1],
			'band'     => $waarden[2],
			'besch'    => $r['besch'],
			'tools'    => array_slice( $mijn, 0, 3 ),
			'niveau'   => $niveau[ $i ],
		);
	}
	set_transient( 'deedni_publiek_kaarten', $kaarten, 5 * MINUTE_IN_SECONDS );
	return $kaarten;
}

/** Van precies naar globaal: functie + provincie, functie + landsdeel, vakgebied + landsdeel, vakgebied + Nederland. */
function deedni_publiek_niveaus() {
	return array(
		1 => function ( $r ) {
			return array( $r['functie'], $r['prov'] ? $r['prov'] : 'Nederland', $r['band'] );
		},
		2 => function ( $r ) {
			return array( $r['functie'], $r['deel'] ? $r['deel'] : 'Nederland', $r['band'] );
		},
		3 => function ( $r ) {
			return array( $r['vak'], $r['deel'] ? $r['deel'] : 'Nederland', $r['band'] );
		},
		4 => function ( $r ) {
			return array( $r['vak'], 'Nederland', $r['band'] );
		},
	);
}

/** Cache legen zodra een kandidaatprofiel verandert. */
function deedni_publiek_cache_weg( $object_id = 0 ) {
	if ( ! $object_id || 'kandidaat' === get_post_type( $object_id ) ) {
		delete_transient( 'deedni_publiek_kaarten' );
	}
}
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $deedni_hook ) {
	add_action(
		$deedni_hook,
		function ( $meta_id, $object_id ) {
			deedni_publiek_cache_weg( $object_id );
		},
		10,
		2
	);
}
unset( $deedni_hook );
add_action( 'set_object_terms', 'deedni_publiek_cache_weg' );
add_action( 'deleted_post', 'deedni_publiek_cache_weg' );
add_action(
	'transition_post_status',
	function ( $nieuw, $oud, $post ) {
		deedni_publiek_cache_weg( $post->ID );
	},
	10,
	3
);
add_action( 'update_option_deedni_publiek_k', 'deedni_publiek_cache_weg' );
add_action( 'update_option_deedni_actueel_dagen', 'deedni_publiek_cache_weg' );

/** Hoe staat deze kandidaat in het overzicht? Voor het voorbeeld in Mijn profiel. */
function deedni_publiek_kaart_van( $kandidaat_id ) {
	foreach ( deedni_publiek_kaarten() as $kaart ) {
		if ( (int) $kaart['id'] === (int) $kandidaat_id ) {
			return $kaart;
		}
	}
	return null;
}

/** Volgorde die per dag wisselt en niets verraadt over aanmelddatum of ID. */
function deedni_publiek_volgorde( $kaarten ) {
	$zout = gmdate( 'Y-m-d' ) . wp_salt( 'nonce' );
	usort(
		$kaarten,
		function ( $a, $b ) use ( $zout ) {
			return strcmp( hash_hmac( 'sha256', (string) $a['id'], $zout ), hash_hmac( 'sha256', (string) $b['id'], $zout ) );
		}
	);
	return $kaarten;
}

/**
 * Mag het overzicht op dit verzoek getoond worden? Alleen bij een gewone paginaweergave: niet in de REST API
 * (daar zou /wp-json/wp/v2/pages/<id> de kaarten zonder teller tonen), niet in feeds, embeds of AJAX.
 */
function deedni_publiek_mag_renderen() {
	if ( deedni_in_editor() ) {
		return true;
	}
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() || is_feed() || is_embed() || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return false;
	}
	return ! is_admin();
}

/** Teller per IP: hoe vaak zag dit adres het overzicht in de afgelopen 10 minuten? Eén keer per verzoek. */
function deedni_publiek_te_vaak() {
	static $uitkomst = null;
	if ( null !== $uitkomst ) {
		return $uitkomst;
	}
	if ( deedni_in_editor() ) {
		$uitkomst = false;
		return $uitkomst;
	}
	$grens    = deedni_grens( 'publiek' );
	$uitkomst = deedni_tel( 'publiek', deedni_ip(), $grens[1] ) > $grens[0];
	return $uitkomst;
}

/** De gefilterde, geordende kaarten voor de huidige bezoeker. */
function deedni_publiek_voor_bezoeker( $vak = '' ) {
	$weg     = is_user_logged_in() ? deedni_blocked_kandidaat_ids() : array();
	$kaarten = array_values(
		array_filter(
			deedni_publiek_kaarten(),
			function ( $c ) use ( $weg ) {
				return ! in_array( (int) $c['id'], $weg, true );
			}
		)
	);
	// Vakgebieden waarop je kunt filteren: alleen met minstens k kaarten.
	$vakken = array();
	foreach ( $kaarten as $c ) {
		$vakken[ $c['vak_slug'] ] = array( $c['vak'], ( isset( $vakken[ $c['vak_slug'] ] ) ? $vakken[ $c['vak_slug'] ][1] : 0 ) + 1 );
	}
	$k      = deedni_publiek_k();
	$vakken = array_filter(
		$vakken,
		function ( $v ) use ( $k ) {
			return $v[1] >= $k;
		}
	);
	uasort(
		$vakken,
		function ( $a, $b ) {
			return strcmp( $a[0], $b[0] );
		}
	);
	if ( $vak && isset( $vakken[ $vak ] ) ) {
		$kaarten = array_values(
			array_filter(
				$kaarten,
				function ( $c ) use ( $vak ) {
					return $c['vak_slug'] === $vak;
				}
			)
		);
	} else {
		$vak = '';
	}
	$kaarten = array_slice( deedni_publiek_volgorde( $kaarten ), 0, deedni_publiek_per_pagina() * deedni_publiek_max_paginas() );
	return array( 'kaarten' => $kaarten, 'vakken' => $vakken, 'vak' => $vak );
}

/** Eén kaart: alleen tekst uit vaste lijsten, geen ID, code of link naar een kandidaat. */
function deedni_publiek_kaart_html( $c, $knop = true ) {
	$inner  = '<span class="pub-ico" aria-hidden="true">' . deedni_icon( 'user', 24 ) . '</span>';
	if ( $c['vak'] !== $c['titel'] ) {
		$inner .= '<span class="pub-vak">' . esc_html( $c['vak'] ) . '</span>';
	}
	$inner .= '<span class="pub-titel">' . esc_html( $c['titel'] ) . '</span>';
	$inner .= '<span class="pub-meta">' . esc_html( $c['regio'] ) . '<span aria-hidden="true"> · </span><span class="visually-hidden">, </span>' . esc_html( $c['band'] ) . '</span>';
	if ( $c['tools'] ) {
		$inner .= '<span class="pub-tools">';
		foreach ( $c['tools'] as $t ) {
			$inner .= '<span class="tag">' . esc_html( $t ) . '</span>';
		}
		$inner .= '</span>';
	}
	if ( $c['besch'] ) {
		$inner .= '<span class="pub-besch">' . esc_html( $c['besch'] ) . '</span>';
	}
	if ( ! $knop ) {
		return '<div class="pub-kaart">' . $inner . '</div>';
	}
	$inner .= '<span class="pub-meer">Bekijk profiel ' . deedni_icon( 'arrow', 16 ) . '</span>';
	return '<button type="button" class="pub-kaart" data-open="publiek-aanmelden">' . $inner . '</button>';
}

/** Het overzicht zelf. Gebruikt door het blok "Kandidaten voor bezoekers" en door de pagina Kandidaten zoeken. */
function deedni_publiek_overzicht_html( $a = array() ) {
	$a = wp_parse_args(
		$a,
		array(
			'kop'   => 'Wie staat er *klaar?*',
			'lead'  => 'Een greep uit de vakmensen die openstaan voor een aanbod. Zonder naam en zonder details: die ziet alleen een werkgever met een account.',
			'tag'   => 'h2',
			'leeg'  => 'Er staan nu nog geen profielen in het openbare overzicht. Werkgevers met een account zien alle profielen.',
		)
	);
	if ( ! deedni_publiek_aan() || ! deedni_publiek_mag_renderen() ) {
		return '';
	}
	deedni_popup_nodig( 'publiek-aanmelden' );
	wp_enqueue_style( 'deedni-paginas' );

	$id   = deedni_uid( 'h-pub' );
	$h    = '<div class="pub" data-publiek>';
	$h   .= '<div class="pub-head"><' . $a['tag'] . ' id="' . esc_attr( $id ) . '" class="pub-kop">' . deedni_tekst( $a['kop'] ) . '</' . $a['tag'] . '>';
	if ( $a['lead'] ) {
		$h .= '<p class="lead">' . deedni_tekst( $a['lead'] ) . '</p>';
	}
	$h .= '</div>';

	if ( deedni_publiek_te_vaak() ) {
		return $h . '<div class="card stack-s pub-dicht" role="status"><p><strong>Je hebt in korte tijd veel profielen bekeken.</strong></p><p class="fine">Probeer het over een kwartier opnieuw, of maak een account als werkgever om alle profielen met filters te bekijken.</p>' . deedni_publiek_knoppen() . '</div></div>';
	}

	$get    = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
	$vak    = isset( $get['vak'] ) && is_string( $get['vak'] ) ? sanitize_title( $get['vak'] ) : '';
	$pagina = isset( $get['pagina'] ) && is_string( $get['pagina'] ) ? max( 1, absint( $get['pagina'] ) ) : 1;
	$res    = deedni_publiek_voor_bezoeker( $vak );
	$basis  = remove_query_arg( array( 'vak', 'pagina' ) );

	if ( count( $res['vakken'] ) > 1 ) {
		$h .= '<nav class="pub-filter chips" aria-label="Filter op vakgebied">';
		$h .= '<a class="chip" href="' . esc_url( $basis ) . '"' . ( '' === $res['vak'] ? ' aria-current="true"' : '' ) . '>Alles</a>';
		foreach ( $res['vakken'] as $slug => $v ) {
			$h .= '<a class="chip" href="' . esc_url( add_query_arg( 'vak', $slug, $basis ) ) . '"' . ( $slug === $res['vak'] ? ' aria-current="true"' : '' ) . '>' . esc_html( $v[0] ) . '</a>';
		}
		$h .= '</nav>';
	}

	if ( ! $res['kaarten'] ) {
		return $h . '<div class="card stack-s"><p class="fine">' . esc_html( $a['leeg'] ) . '</p>' . deedni_publiek_knoppen() . '</div></div>';
	}

	$per     = deedni_publiek_per_pagina();
	$paginas = (int) ceil( count( $res['kaarten'] ) / $per );
	$pagina  = min( $pagina, $paginas );
	$h      .= '<ul class="pub-grid" aria-labelledby="' . esc_attr( $id ) . '">';
	foreach ( array_slice( $res['kaarten'], ( $pagina - 1 ) * $per, $per ) as $c ) {
		$h .= '<li>' . deedni_publiek_kaart_html( $c ) . '</li>';
	}
	$h .= '</ul>';

	if ( $paginas > 1 ) {
		$args = $res['vak'] ? array( 'vak' => $res['vak'] ) : array();
		$h   .= '<nav class="pub-paginas" aria-label="Pagina\'s">';
		$h   .= $pagina > 1 ? '<a class="btn btn--ghost btn--small" rel="prev" href="' . esc_url( add_query_arg( $args + ( $pagina > 2 ? array( 'pagina' => $pagina - 1 ) : array() ), $basis ) ) . '">Vorige</a>' : '<span></span>';
		$h   .= '<span class="fine">Pagina ' . (int) $pagina . ' van ' . (int) $paginas . '</span>';
		$h   .= $pagina < $paginas ? '<a class="btn btn--ghost btn--small" rel="next" href="' . esc_url( add_query_arg( $args + array( 'pagina' => $pagina + 1 ), $basis ) ) . '">Volgende</a>' : '<span></span>';
		$h   .= '</nav>';
	}
	$h .= '<div class="pub-slot"><p class="fine">Je ziet hier een selectie van kandidaten die daar zelf voor kozen, zonder naam, werkgevers of salaris. Met een account als werkgever zie je alle profielen, met filters en matchpercentage.</p>' . deedni_publiek_knoppen() . '</div>';
	return $h . '</div>';
}

function deedni_publiek_knoppen() {
	if ( is_user_logged_in() ) {
		return '';
	}
	return '<div class="row">' . deedni_cta( 'Account maken als werkgever', add_query_arg( 'rol', 'werkgever', deedni_page_url( 'registreren' ) ), 'user' ) . '<a class="btn btn--ghost" href="' . esc_url( deedni_page_url( 'inloggen' ) ) . '">Inloggen</a></div>';
}

/** De pop-up bij een klik op een kaart. */
function deedni_popup_publiek_aanmelden() {
	$h  = '<dialog class="modal modal--smal" id="publiek-aanmelden" aria-labelledby="pa-kop">';
	$h .= '<div class="modal-head"><div class="stack-s"><h2 id="pa-kop">Bekijk het hele profiel</h2></div><button type="button" class="modal-close" data-close aria-label="Sluiten">×</button></div>';
	$h .= '<div class="modal-body"><p>Ervaring, projecten, resultaten en salarisindicatie ziet alleen een werkgever met een account. We controleren elk bedrijf voordat het kan zoeken, zodat kandidaten weten wie er meekijkt.</p>';
	if ( is_user_logged_in() ) {
		$h .= '<p class="fine">Je bent ingelogd zonder toegang tot zoeken. Werkgevers kunnen zoeken zodra hun bedrijf is goedgekeurd.</p></div>';
	} else {
		$h .= '<div class="row">' . deedni_cta( 'Account maken als werkgever', add_query_arg( 'rol', 'werkgever', deedni_page_url( 'registreren' ) ), 'user' ) . '<a class="btn btn--ghost" href="' . esc_url( deedni_page_url( 'inloggen' ) ) . '">Inloggen</a></div>';
		$h .= '<p class="fine">Zoek je zelf werk? <a href="' . esc_url( deedni_page_url( 'registreren' ) ) . '">Maak gratis je profiel</a> en laat bedrijven jou vinden.</p></div>';
	}
	return $h . '</dialog>';
}

/** Zoekmachines: de pagina's met het overzicht niet indexeren, en ook niet volgen (paginering, filters). */
add_action(
	'wp_head',
	function () {
		$id = get_queried_object_id();
		if ( $id && ! deedni_is_page( 'zoeken' ) && has_block( 'deedni/kandidaten-publiek', $id ) ) {
			echo '<meta name="robots" content="noindex, follow">' . "\n";
		}
	},
	1
);

/* ---------- Het blok ---------- */

deedni_blok(
	'kandidaten-publiek',
	array(
		'titel'        => 'Kandidaten voor bezoekers',
		'beschrijving' => 'Een kort, anoniem overzicht van kandidaten voor bezoekers zonder account. Alleen kandidaten die daar toestemming voor gaven, zonder naam, code of detailpagina.',
		'icoon'        => 'groups',
		'stijl'        => 'deedni-paginas',
		'achtergrond'  => 'standaard',
		'tonen'        => 'bezoeker',
		'uitleg'       => 'Het aantal per pagina (12), het aantal pagina\'s en de minimale groepsgrootte stel je in bij Instellingen > deedni.',
		'velden'       => array(
			'kop'  => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Wie staat er *klaar?*' ),
			'lead' => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Een greep uit de vakmensen die openstaan voor een aanbod. Zonder naam en zonder details: die ziet alleen een werkgever met een account.' ),
			'leeg' => array( 'type' => 'text', 'label' => 'Tekst als er nog niemand in staat', 'standaard' => 'Er staan nu nog geen profielen in het openbare overzicht. Werkgevers met een account zien alle profielen.' ),
		),
		'render'       => function ( $a ) {
			$html = deedni_publiek_overzicht_html( $a );
			if ( '' === $html ) {
				return '';
			}
			return '<section class="sec pg-sec' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . '><div class="wrap">' . $html . '</div></section>';
		},
	)
);
