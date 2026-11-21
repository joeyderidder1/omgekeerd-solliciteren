<?php
/**
 * De deedni-blokken: elke sectie van het ontwerp is een blok met velden in de zijbalk van de editor.
 * Je klikt op een sectie en past rechts de teksten, afbeeldingen, links en kleuren aan.
 *
 * Een blok maak je met deedni_blok( 'naam', array(
 *     'titel'   => 'Naam in de editor',
 *     'velden'  => array( 'kop' => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => '...' ), ... ),
 *     'render'  => function ( $a, $blok ) { return '<section>...</section>'; },
 * ) );
 *
 * Veldtypes: text, textarea, number, toggle, keuze (met 'opties'), link, image, lijst (met 'velden').
 * Elk veld kan een 'groep' hebben: dat wordt een paneel in de zijbalk.
 */

defined( 'ABSPATH' ) || exit;

function deedni_blok( $naam, $def ) {
	$GLOBALS['deedni_blokken'][ $naam ] = $def;
}

function deedni_blok_defs() {
	return isset( $GLOBALS['deedni_blokken'] ) ? $GLOBALS['deedni_blokken'] : array();
}

foreach ( glob( DEEDNI_DIR . 'includes/blokken/*.php' ) as $deedni_blok_bestand ) {
	require_once $deedni_blok_bestand;
}
unset( $deedni_blok_bestand );

/** Een plaatje uit de plugin als standaardwaarde voor een afbeeldingsveld. */
function deedni_img( $file, $alt = '' ) {
	return array(
		'id'  => 0,
		'url' => DEEDNI_URL . 'assets/img/' . $file,
		'alt' => $alt,
	);
}

/** De velden die elk sectieblok krijgt (achtergrond, tonen aan, anker). */
function deedni_algemene_velden( $def ) {
	$velden = array();
	if ( ! empty( $def['achtergrond'] ) ) {
		$velden['achtergrond'] = array(
			'type'      => 'keuze',
			'label'     => 'Achtergrond',
			'groep'     => 'Opmaak',
			'opties'    => array(
				'standaard' => 'Standaard (lichte achtergrond)',
				'zacht'     => 'Zacht blok (lichte accentkleur)',
				'accent'    => 'Accentkleur',
				'donker'    => 'Donker blok met gloed',
				'geel'      => 'Geel blok (markeerkleur)',
				'licht'     => 'Licht blok met gloed',
			),
			'standaard' => $def['achtergrond'],
		);
	}
	$velden['tonen'] = array(
		'type'      => 'keuze',
		'label'     => 'Wie ziet deze sectie?',
		'groep'     => 'Opmaak',
		'opties'    => array(
			'iedereen'  => 'Iedereen',
			'bezoeker'  => 'Alleen bezoekers (niet ingelogd)',
			'ingelogd'  => 'Alleen ingelogde gebruikers',
			'kandidaat' => 'Alleen kandidaten',
			'werkgever' => 'Alleen werkgevers',
		),
		'standaard' => isset( $def['tonen'] ) ? $def['tonen'] : 'iedereen',
		'help'      => 'Handig als een sectie alleen voor een bepaalde rol bedoeld is.',
	);
	if ( ! empty( $def['anker'] ) ) {
		$velden['anker'] = array(
			'type'      => 'text',
			'label'     => 'Anker (voor links in het menu)',
			'groep'     => 'Opmaak',
			'standaard' => $def['anker'],
			'help'      => 'Een menu-link naar #' . $def['anker'] . ' springt naar deze sectie. Alleen kleine letters en streepjes.',
		);
	}
	$ruimte = array(
		''     => 'Standaard',
		'geen' => 'Geen',
		's'    => 'Klein',
		'm'    => 'Middel',
		'l'    => 'Groot',
		'xl'   => 'Extra groot',
	);
	$velden['ruimte_boven'] = array(
		'type'      => 'keuze',
		'label'     => 'Ruimte boven dit blok',
		'groep'     => 'Opmaak',
		'opties'    => $ruimte,
		'standaard' => '',
		'help'      => 'Extra witruimte tussen dit blok en wat erboven staat (bijvoorbeeld de kop van de site).',
	);
	$velden['ruimte_onder'] = array(
		'type'      => 'keuze',
		'label'     => 'Ruimte onder dit blok',
		'groep'     => 'Opmaak',
		'opties'    => $ruimte,
		'standaard' => '',
		'help'      => 'Extra witruimte tussen dit blok en het volgende blok.',
	);
	$velden['voorbeeld_rol'] = array(
		'type'      => 'keuze',
		'label'     => 'Voorbeeld in de editor bekijken als',
		'groep'     => 'Opmaak',
		'opties'    => array(
			''          => 'Mijzelf',
			'bezoeker'  => 'Bezoeker',
			'kandidaat' => 'Werkzoekende',
			'werkgever' => 'Werkgever',
		),
		'standaard' => '',
		'help'      => 'Alleen voor het voorbeeld hier in de editor. Bezoekers zien altijd hun eigen versie.',
	);
	return $velden;
}

/** Attribuuttype voor een veldtype. */
function deedni_attribuut( $veld ) {
	$types = array(
		'number' => 'number',
		'toggle' => 'boolean',
		'image'  => 'object',
		'lijst'  => 'array',
	);
	$attr = array( 'type' => isset( $types[ $veld['type'] ] ) ? $types[ $veld['type'] ] : 'string' );
	if ( array_key_exists( 'standaard', $veld ) ) {
		$attr['default'] = $veld['standaard'];
	} else {
		$defaults        = array( 'number' => 0, 'boolean' => false, 'object' => array(), 'array' => array(), 'string' => '' );
		$attr['default'] = $defaults[ $attr['type'] ];
	}
	return $attr;
}

/** Leeg item voor een lijst (bij "Toevoegen" in de editor). */
function deedni_leeg_item( $velden ) {
	$item = array();
	foreach ( $velden as $key => $v ) {
		$a            = deedni_attribuut( array( 'type' => $v['type'] ) );
		$item[ $key ] = isset( $v['nieuw'] ) ? $v['nieuw'] : $a['default'];
	}
	return $item;
}

/** De rol die de editor-preview gebruikt (zie deedni_rol). */
function deedni_rol_override( $rol = null ) {
	static $override = '';
	if ( null !== $rol ) {
		$override = $rol;
	}
	return $override;
}

/**
 * Wordt dit blok getoond als voorbeeld in de editor? Alleen voor beheerders: de REST API rendert blokken
 * ook voor bezoekers (bijvoorbeeld /wp-json/wp/v2/pages/123), en in de editor-weergave vallen de
 * toegangscontroles weg.
 */
function deedni_in_editor() {
	return defined( 'REST_REQUEST' ) && REST_REQUEST && is_user_logged_in() && deedni_is_beheerder();
}

/** Mag de huidige bezoeker een blok met deze instelling 'tonen' zien? */
function deedni_mag_zien( $tonen ) {
	$rol = deedni_rol();
	switch ( $tonen ) {
		case 'bezoeker':
			return 'bezoeker' === $rol;
		case 'ingelogd':
			return 'bezoeker' !== $rol;
		case 'kandidaat':
		case 'werkgever':
			return $tonen === $rol;
	}
	return true;
}

add_filter( 'block_categories_all', function ( $cats ) {
	array_unshift( $cats, array( 'slug' => 'deedni', 'title' => 'deedni', 'icon' => null ) );
	return $cats;
} );

add_action( 'init', 'deedni_registreer_blokken', 20 );

function deedni_registreer_blokken() {
	$v = DEEDNI_VERSION;
	wp_register_style( 'deedni', DEEDNI_URL . 'assets/css/deedni.css', array(), $v );
	wp_register_style( 'deedni-home', DEEDNI_URL . 'assets/css/home.css', array( 'deedni' ), $v );
	wp_register_style( 'deedni-app', DEEDNI_URL . 'assets/css/app.css', array( 'deedni' ), $v );
	wp_register_style( 'deedni-paginas', DEEDNI_URL . 'assets/css/paginas.css', array( 'deedni' ), $v );
	wp_register_script( 'deedni', DEEDNI_URL . 'assets/js/deedni.js', array(), $v, array( 'in_footer' => true ) );
	wp_register_script( 'deedni-home', DEEDNI_URL . 'assets/js/home.js', array( 'deedni' ), $v, array( 'in_footer' => true ) );
	wp_register_script( 'deedni-app', DEEDNI_URL . 'assets/js/app.js', array( 'deedni' ), $v, array( 'in_footer' => true ) );
	wp_register_script(
		'deedni-editor',
		DEEDNI_URL . 'assets/js/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data' ),
		$v,
		array( 'in_footer' => true )
	);

	$voor_js = array();
	foreach ( deedni_blok_defs() as $naam => $def ) {
		$velden = array_merge( isset( $def['velden'] ) ? $def['velden'] : array(), deedni_algemene_velden( $def ) );
		$attrs  = array();
		foreach ( $velden as $key => $veld ) {
			$attrs[ $key ] = deedni_attribuut( $veld );
			if ( 'lijst' === $veld['type'] ) {
				$velden[ $key ]['nieuw'] = deedni_leeg_item( $veld['velden'] );
			}
		}
		$stijl  = isset( $def['stijl'] ) ? $def['stijl'] : 'deedni';
		$script = isset( $def['script'] ) ? $def['script'] : 'deedni';
		register_block_type(
			'deedni/' . $naam,
			array(
				'api_version'           => 3,
				'title'                 => $def['titel'],
				'description'           => isset( $def['beschrijving'] ) ? $def['beschrijving'] : '',
				'category'              => 'deedni',
				'icon'                  => isset( $def['icoon'] ) ? $def['icoon'] : 'layout',
				'attributes'            => $attrs,
				'supports'              => array( 'html' => false, 'customClassName' => true, 'multiple' => ! isset( $def['multiple'] ) || $def['multiple'] ),
				'editor_script_handles' => array( 'deedni-editor' ),
				'style_handles'         => array_unique( array( 'deedni', $stijl ) ),
				'view_script_handles'   => array_unique( array( 'deedni', $script ) ),
				'render_callback'       => function ( $a, $content, $block ) use ( $naam, $def ) {
					return deedni_render_blok( $naam, $def, $a, $block );
				},
			)
		);
		$voor_js[] = array(
			'naam'         => $naam,
			'titel'        => $def['titel'],
			'beschrijving' => isset( $def['beschrijving'] ) ? $def['beschrijving'] : '',
			'icoon'        => isset( $def['icoon'] ) ? $def['icoon'] : 'layout',
			'velden'       => $velden,
			'uitleg'       => isset( $def['uitleg'] ) ? $def['uitleg'] : '',
		);
	}

	wp_add_inline_script(
		'deedni-editor',
		'window.deedniBlokken = ' . wp_json_encode(
			array(
				'blokken' => $voor_js,
				'links'   => deedni_link_opties(),
			)
		) . ';',
		'before'
	);
}

/** Keuzes voor een linkveld in de editor. */
function deedni_link_opties() {
	$opties = array(
		array( 'label' => 'Homepage', 'value' => 'pagina:home' ),
	);
	foreach ( deedni_pagina_lijst() as $key => $p ) {
		$opties[] = array( 'label' => 'Pagina: ' . $p[0], 'value' => 'pagina:' . $key );
	}
	foreach ( array( 'zo-werkt-het' => 'Zo werkt het', 'privacy' => 'Privacy', 'prijzen' => 'Prijzen', 'vragen' => 'Vragen' ) as $anker => $label ) {
		$opties[] = array( 'label' => 'Sectie op de homepage: ' . $label, 'value' => '#' . $anker );
	}
	return $opties;
}

function deedni_render_blok( $naam, $def, $a, $block ) {
	$in_editor = deedni_in_editor();
	if ( $in_editor && ! empty( $a['voorbeeld_rol'] ) ) {
		deedni_rol_override( $a['voorbeeld_rol'] );
	}
	if ( ! $in_editor && ! deedni_mag_zien( isset( $a['tonen'] ) ? $a['tonen'] : 'iedereen' ) ) {
		return '';
	}
	if ( $in_editor && isset( $a['tonen'] ) && 'iedereen' !== $a['tonen'] && ! deedni_mag_zien( $a['tonen'] ) ) {
		$html = '<div class="deedni-editor-leeg">' . esc_html( $def['titel'] ) . ': deze sectie is verborgen voor de rol in het voorbeeld. Kies rechts onder Opmaak een andere rol.</div>';
		deedni_rol_override( '' );
		return $html;
	}
	$html = call_user_func( $def['render'], $a, $block );
	deedni_rol_override( '' );
	if ( '' === trim( (string) $html ) && $in_editor ) {
		return '<div class="deedni-editor-leeg">' . esc_html( $def['titel'] ) . ': op dit moment is hier niets te tonen' . ( ! empty( $def['leeg'] ) ? ' (' . esc_html( $def['leeg'] ) . ')' : '' ) . '.</div>';
	}
	if ( $html ) {
		// alignfull: ook in een smalle paginalay-out gebruikt het blok de hele breedte (het heeft zelf een .wrap).
		$extra = trim( ( $in_editor ? '' : 'alignfull ' ) . deedni_ruimte_klassen( $a ) . ' ' . ( ! empty( $a['className'] ) ? $a['className'] : '' ) );
		if ( '' !== $extra ) {
			$html = preg_replace( '/class="/', 'class="' . esc_attr( $extra ) . ' ', $html, 1 );
		}
	}
	return $html;
}

/** Klassen voor de ruimte boven en onder een blok (veld "Ruimte boven/onder dit blok"). */
function deedni_ruimte_klassen( $a ) {
	$klassen = array();
	foreach ( array( 'boven', 'onder' ) as $kant ) {
		$waarde = isset( $a[ 'ruimte_' . $kant ] ) ? $a[ 'ruimte_' . $kant ] : '';
		if ( in_array( $waarde, array( 'geen', 's', 'm', 'l', 'xl' ), true ) ) {
			$klassen[] = 'ruimte-' . $kant . '-' . $waarde;
		}
	}
	return implode( ' ', $klassen );
}

/** Klassen voor de achtergrond van een sectie. */
function deedni_achtergrond_klasse( $a ) {
	$map = array(
		'zacht'  => ' blk blk--soft',
		'accent' => ' blk blk--accent',
		'donker' => ' blk blk--ink',
		'geel'   => ' blk blk--geel',
		'licht'  => ' blk blk--licht',
	);
	return isset( $a['achtergrond'], $map[ $a['achtergrond'] ] ) ? $map[ $a['achtergrond'] ] : '';
}

function deedni_anker_attr( $a ) {
	return ! empty( $a['anker'] ) ? ' id="' . esc_attr( sanitize_title( $a['anker'] ) ) . '"' : '';
}

/** Een afbeelding uit een afbeeldingsveld. */
function deedni_beeld( $img, $attrs = array() ) {
	if ( empty( $img['url'] ) ) {
		return '';
	}
	if ( ! empty( $img['id'] ) && wp_attachment_is_image( $img['id'] ) ) {
		$attrs = array_merge( array( 'alt' => isset( $img['alt'] ) ? $img['alt'] : '' ), $attrs );
		$html  = wp_get_attachment_image( $img['id'], 'large', false, $attrs );
		if ( $html ) {
			return $html;
		}
	}
	$extra = '';
	foreach ( $attrs as $k => $v ) {
		$extra .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	return '<img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( isset( $img['alt'] ) ? $img['alt'] : '' ) . '"' . $extra . '>';
}

/** Kop van een sectie: grote titel met *zachte* woorden en een inleiding. */
function deedni_sec_head( $id, $kop, $lead = '', $tag = 'h2', $reveal = true ) {
	$h = '<div class="sec-head' . ( $reveal ? ' reveal' : '' ) . '">';
	$h .= '<' . $tag . ' id="' . esc_attr( $id ) . '" class="big-h">' . deedni_tekst( $kop ) . '</' . $tag . '>';
	if ( '' !== trim( (string) $lead ) ) {
		$h .= '<p class="lead">' . deedni_tekst( $lead ) . '</p>';
	}
	return $h . '</div>';
}

/** Uniek id voor koppen binnen een pagina. */
function deedni_uid( $prefix ) {
	static $n = array();
	$n[ $prefix ] = isset( $n[ $prefix ] ) ? $n[ $prefix ] + 1 : 1;
	return $prefix . ( $n[ $prefix ] > 1 ? '-' . $n[ $prefix ] : '' );
}

/* ---------- Pop-ups die meerdere blokken gebruiken ---------- */

/** Pop-ups die op deze pagina nodig zijn, onderaan de pagina geplaatst. */
function deedni_popup_nodig( $naam ) {
	$GLOBALS['deedni_popups'][ $naam ] = true;
}

add_action(
	'wp_footer',
	function () {
		if ( empty( $GLOBALS['deedni_popups'] ) ) {
			return;
		}
		foreach ( array_keys( $GLOBALS['deedni_popups'] ) as $naam ) {
			$fn = 'deedni_popup_' . str_replace( '-', '_', $naam );
			if ( function_exists( $fn ) ) {
				echo call_user_func( $fn ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
	},
	5
);

/** Klasse .js op <html> zo vroeg mogelijk: animaties starten pas als JavaScript werkt. */
add_action(
	'wp_head',
	function () {
		echo "<script>document.documentElement.classList.add('js')</script>\n";
	},
	1
);

/** Basisstijl en script ook laden op pagina's zonder deedni-blok (de kop staat er altijd). */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'deedni' );
		wp_enqueue_script( 'deedni' );
		wp_localize_script(
			'deedni',
			'deedniData',
			array(
				'rol' => deedni_rol(),
			)
		);
	}
);

/** In de editor: de stijl van de site, zodat het voorbeeld klopt. */
add_action(
	'enqueue_block_assets',
	function () {
		if ( is_admin() ) {
			wp_enqueue_style( 'deedni' );
			wp_enqueue_style( 'deedni-home' );
			wp_enqueue_style( 'deedni-app' );
			wp_enqueue_style( 'deedni-paginas' );
			wp_enqueue_style( 'deedni-editor', DEEDNI_URL . 'assets/css/editor.css', array( 'deedni' ), DEEDNI_VERSION );
		}
	}
);
