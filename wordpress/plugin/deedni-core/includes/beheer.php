<?php
/**
 * Beheer van kandidaatprofielen in wp-admin.
 *
 * Een beheerder kan een profiel aanpassen als er gevoelige of onjuiste informatie in staat,
 * het profiel verbergen voor werkgevers (Ontdekken, zoeken, de profielpagina en nieuwe aanbiedingen)
 * en bij elke wijziging een korte notitie achterlaten. Elke wijziging komt in het logboek van het profiel.
 *
 * Velden (post meta):
 *   beheer_verborgen  1 als de beheerder het profiel verborg
 *   beheer_reden      uitleg voor de marketeer (zichtbaar op Mijn profiel)
 *   beheer_log        lijst met wijzigingen: t, door, velden, notitie
 */

defined( 'ABSPATH' ) || exit;

/** Heeft de beheerder dit profiel verborgen? */
function deedni_beheer_verborgen( $kandidaat_id ) {
	return (bool) get_post_meta( (int) $kandidaat_id, 'beheer_verborgen', true );
}

/** Melding voor de marketeer op Mijn profiel en het overzicht. */
function deedni_beheer_melding_tekst( $k ) {
	$tekst = '<p>Je profiel is tijdelijk verborgen door ' . esc_html( get_bloginfo( 'name' ) ) . '. Bedrijven kunnen je nu niet vinden en je geen nieuw aanbod sturen.</p>';
	if ( ! empty( $k['beheer_reden'] ) ) {
		$tekst .= '<p>' . nl2br( esc_html( $k['beheer_reden'] ) ) . '</p>';
	}
	return $tekst;
}

/** Logboek van een profiel, nieuwste eerst. */
function deedni_beheer_log( $kandidaat_id ) {
	return array_reverse( deedni_list( $kandidaat_id, 'beheer_log' ) );
}

function deedni_beheer_log_toevoegen( $kandidaat_id, $velden, $notitie ) {
	$log   = deedni_list( $kandidaat_id, 'beheer_log' );
	$log[] = array(
		't'       => time(),
		'door'    => wp_get_current_user()->display_name,
		'velden'  => array_values( $velden ),
		'notitie' => $notitie,
	);
	update_post_meta( $kandidaat_id, 'beheer_log', array_slice( $log, -100 ) );
}

/* ---------- Het formulier in wp-admin ---------- */

add_action(
	'add_meta_boxes_kandidaat',
	function () {
		remove_meta_box( 'deedni-kandidaat', 'kandidaat', 'normal' );
		add_meta_box( 'deedni-beheer', 'Profiel aanpassen', 'deedni_metabox_beheer', 'kandidaat', 'normal', 'high' );
		add_meta_box( 'deedni-beheer-zichtbaar', 'Zichtbaarheid', 'deedni_metabox_beheer_zichtbaar', 'kandidaat', 'side', 'high' );
		add_meta_box( 'deedni-beheer-log', 'Logboek', 'deedni_metabox_beheer_log', 'kandidaat', 'side', 'default' );
	},
	20
);

/** Labels van de velden, ook gebruikt in het logboek. */
function deedni_beheer_labels() {
	return array(
		'functietitel'      => 'Functietitel',
		'specialisme'       => 'Specialisme',
		'ervaring_jaren'    => 'Jaren ervaring',
		'samenvatting'      => 'Over mij',
		'provincie'         => 'Provincie',
		'regio'             => 'Regio (eigen tekst)',
		'werkplekken'       => 'Werkplek',
		'uren'              => 'Uren per week',
		'salaris_min'       => 'Salaris vanaf (per maand)',
		'beschikbaarheid'   => 'Beschikbaar',
		'zoekstatus'        => 'Zoekstatus',
		'opleiding'         => 'Opleiding en certificaten',
		'tools'             => 'Tools',
		'werkervaring'      => 'Werkervaring',
		'projecten'         => 'Projecten',
		'voornaam'          => 'Voornaam',
		'achternaam'        => 'Achternaam',
		'telefoon'          => 'Telefoon',
		'linkedin'          => 'LinkedIn',
		'huidige_werkgever' => 'Huidige werkgever',
		'cv'                => 'Cv',
		'blokkeerlijst'     => 'Blokkeerlijst',
		'zichtbaar'         => 'Online (keuze van de marketeer)',
		'beheer_verborgen'  => 'Verborgen door beheer',
		'beheer_reden'      => 'Reden voor de marketeer',
	);
}

function deedni_beheer_veld( $naam, $label, $waarde, $type = 'text', $extra = '' ) {
	$id = 'deedni-b-' . str_replace( array( '[', ']' ), array( '-', '' ), $naam );
	$h  = '<p class="deedni-b-veld"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
	if ( 'textarea' === $type ) {
		$h .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $naam ) . '" rows="4" class="large-text"' . $extra . '>' . esc_textarea( $waarde ) . '</textarea>';
	} else {
		$h .= '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $naam ) . '" value="' . esc_attr( $waarde ) . '" class="widefat"' . $extra . '>';
	}
	return $h . '</p>';
}

function deedni_beheer_select( $naam, $label, $opties, $huidig ) {
	$id = 'deedni-b-' . $naam;
	$h  = '<p class="deedni-b-veld"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label><select id="' . esc_attr( $id ) . '" name="' . esc_attr( $naam ) . '" class="widefat"><option value="">–</option>';
	foreach ( $opties as $waarde => $tekst ) {
		$h .= '<option value="' . esc_attr( $waarde ) . '"' . selected( (string) $huidig, (string) $waarde, false ) . '>' . esc_html( $tekst ) . '</option>';
	}
	return $h . '</select></p>';
}

function deedni_beheer_termen( $taxonomy ) {
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
	return is_wp_error( $terms ) ? array() : wp_list_pluck( $terms, 'name', 'term_id' );
}

function deedni_metabox_beheer( $post ) {
	$k = deedni_kandidaat( $post->ID );
	wp_nonce_field( 'deedni_beheer_profiel', 'deedni_beheer_nonce' );
	$l = deedni_beheer_labels();

	echo '<p>Marketeers vullen hun profiel zelf in op de site. Staat er gevoelige of onjuiste informatie in, pas het dan hier aan. ';
	echo 'Laat onderaan een korte notitie achter: die komt in het logboek. <a href="' . esc_url( get_permalink( $post ) ) . '" target="_blank" rel="noopener">Bekijk het profiel op de site</a>.</p>';

	echo '<div class="deedni-b-raster">';
	echo '<fieldset class="deedni-b-groep"><legend>Wat bedrijven altijd zien</legend>';
	echo '<p class="description">Code ' . esc_html( $k['code'] ) . '. Deze gegevens zijn zichtbaar voor goedgekeurde werkgevers.</p>';
	echo deedni_beheer_veld( 'deedni_b[functietitel]', $l['functietitel'], $k['functietitel'] ); // phpcs:ignore
	echo deedni_beheer_select( 'deedni_b[specialisme]', $l['specialisme'], deedni_beheer_termen( 'specialisme' ), $k['specialisme_ids'] ? $k['specialisme_ids'][0] : '' ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[ervaring_jaren]', $l['ervaring_jaren'], $k['ervaring_jaren'] ? $k['ervaring_jaren'] : '', 'number', ' min="0" max="50"' ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[samenvatting]', $l['samenvatting'], $k['samenvatting'], 'textarea' ); // phpcs:ignore
	echo deedni_beheer_select( 'deedni_b[provincie]', $l['provincie'], deedni_beheer_termen( 'provincie' ), $k['provincie_ids'] ? $k['provincie_ids'][0] : '' ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[regio]', $l['regio'], $k['regio'] ); // phpcs:ignore
	echo '<p class="deedni-b-veld"><span class="deedni-b-label">' . esc_html( $l['werkplekken'] ) . '</span><input type="hidden" name="deedni_b[werkplekken][]" value="">';
	foreach ( deedni_choices( 'werkvorm' ) as $waarde => $tekst ) {
		echo '<label class="deedni-b-check"><input type="checkbox" name="deedni_b[werkplekken][]" value="' . esc_attr( $waarde ) . '"' . checked( in_array( $waarde, $k['werkplekken'], true ), true, false ) . '> ' . esc_html( $tekst ) . '</label>';
	}
	echo '</p>';
	echo deedni_beheer_veld( 'deedni_b[uren]', $l['uren'], $k['uren'] ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[salaris_min]', $l['salaris_min'], $k['salaris_min'] ? $k['salaris_min'] : '', 'number', ' min="0" step="50"' ); // phpcs:ignore
	echo deedni_beheer_select( 'deedni_b[beschikbaarheid]', $l['beschikbaarheid'], deedni_choices( 'beschikbaarheid' ), $k['beschikbaarheid'] ); // phpcs:ignore
	echo deedni_beheer_select( 'deedni_b[zoekstatus]', $l['zoekstatus'], deedni_choices( 'zoekstatus' ), $k['zoekstatus'] ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[opleiding]', $l['opleiding'], $k['opleiding'], 'textarea' ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[tools]', $l['tools'] . ' (met komma\'s)', implode( ', ', $k['tools'] ) ); // phpcs:ignore
	echo '</fieldset>';

	echo '<fieldset class="deedni-b-groep"><legend>Pas zichtbaar na een ja</legend>';
	echo '<p class="description">Persoonlijke gegevens. Werkgevers zien deze alleen nadat de marketeer ja zei op hun aanbod.</p>';
	echo deedni_beheer_veld( 'deedni_b[voornaam]', $l['voornaam'], $k['voornaam'] ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[achternaam]', $l['achternaam'], $k['achternaam'] ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[telefoon]', $l['telefoon'], $k['telefoon'] ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[linkedin]', $l['linkedin'], $k['linkedin'], 'url' ); // phpcs:ignore
	echo deedni_beheer_veld( 'deedni_b[huidige_werkgever]', $l['huidige_werkgever'], $k['huidige_werkgever'] ); // phpcs:ignore
	if ( $k['cv'] ) {
		$cv = get_post( $k['cv'] );
		echo '<p class="deedni-b-veld"><span class="deedni-b-label">Cv</span>' . esc_html( $cv ? basename( (string) get_attached_file( $cv->ID ) ) : 'bestand ontbreekt' ) . ' <label class="deedni-b-check"><input type="checkbox" name="deedni_b[cv_verwijderen]" value="1"> Cv verwijderen</label></p>';
	}
	echo deedni_beheer_veld( 'deedni_b[blokkeerlijst]', $l['blokkeerlijst'] . ' (één bedrijf per regel)', $k['blokkeerlijst'], 'textarea' ); // phpcs:ignore
	echo '<p class="description">E-mailadres van het account: ' . esc_html( $k['email'] ) . ( $k['user_id'] ? ' (<a href="' . esc_url( get_edit_user_link( $k['user_id'] ) ) . '">aanpassen bij Gebruikers</a>)' : '' ) . '</p>';
	echo '</fieldset>';
	echo '</div>';

	// Werkervaring.
	echo '<fieldset class="deedni-b-groep"><legend>' . esc_html( $l['werkervaring'] ) . '</legend>';
	echo '<input type="hidden" name="deedni_b[werkervaring_aanwezig]" value="1">';
	if ( ! $k['werkervaring'] ) {
		echo '<p class="description">Geen werkervaring ingevuld.</p>';
	}
	foreach ( $k['werkervaring'] as $i => $w ) {
		$w = wp_parse_args( $w, array( 'functie' => '', 'werkgever' => '', 'soort' => '', 'periode' => '', 'tekst' => '' ) );
		$n = 'deedni_b[werkervaring][' . $i . ']';
		echo '<div class="deedni-b-rij"><div class="deedni-b-kolommen">';
		echo deedni_beheer_veld( $n . '[functie]', 'Functie', $w['functie'] ); // phpcs:ignore
		echo deedni_beheer_veld( $n . '[werkgever]', 'Werkgever', $w['werkgever'] ); // phpcs:ignore
		echo deedni_beheer_veld( $n . '[soort]', 'Soort bedrijf', $w['soort'] ); // phpcs:ignore
		echo deedni_beheer_veld( $n . '[periode]', 'Periode', $w['periode'] ); // phpcs:ignore
		echo '</div>';
		echo deedni_beheer_veld( $n . '[tekst]', 'Wat deed je', $w['tekst'], 'textarea' ); // phpcs:ignore
		echo '<label class="deedni-b-check deedni-b-weg"><input type="checkbox" name="' . esc_attr( $n ) . '[verwijderen]" value="1"> Deze werkervaring verwijderen</label></div>';
	}
	echo '</fieldset>';

	// Projecten.
	echo '<fieldset class="deedni-b-groep"><legend>' . esc_html( $l['projecten'] ) . '</legend>';
	echo '<input type="hidden" name="deedni_b[projecten_aanwezig]" value="1">';
	if ( ! $k['projecten'] ) {
		echo '<p class="description">Geen projecten ingevuld.</p>';
	}
	foreach ( $k['projecten'] as $i => $p ) {
		$p = wp_parse_args( $p, array( 'titel' => '', 'klant' => '', 'rol' => '', 'branche' => '', 'tools' => '', 'aanpak' => '', 'link' => '', 'resultaten' => array() ) );
		$n = 'deedni_b[projecten][' . $i . ']';
		echo '<div class="deedni-b-rij"><div class="deedni-b-kolommen">';
		echo deedni_beheer_veld( $n . '[titel]', 'Titel', $p['titel'] ); // phpcs:ignore
		echo deedni_beheer_veld( $n . '[klant]', 'Klant (omschrijving)', $p['klant'] ); // phpcs:ignore
		echo deedni_beheer_veld( $n . '[rol]', 'Rol', $p['rol'] ); // phpcs:ignore
		echo deedni_beheer_veld( $n . '[branche]', 'Branche', $p['branche'] ); // phpcs:ignore
		echo deedni_beheer_veld( $n . '[tools]', 'Tools', $p['tools'] ); // phpcs:ignore
		echo deedni_beheer_veld( $n . '[link]', 'Link', $p['link'], 'url' ); // phpcs:ignore
		echo '</div>';
		echo deedni_beheer_veld( $n . '[aanpak]', 'Aanpak', $p['aanpak'], 'textarea' ); // phpcs:ignore
		$resultaten = array_values( (array) $p['resultaten'] );
		$resultaten[] = array( 'cijfer' => '', 'tekst' => '' );
		echo '<div class="deedni-b-resultaten"><span class="deedni-b-label">Resultaten (laat leeg om weg te halen)</span>';
		foreach ( $resultaten as $j => $r ) {
			echo '<div class="deedni-b-kolommen deedni-b-kolommen--smal">';
			echo deedni_beheer_veld( $n . '[resultaten][' . $j . '][cijfer]', 'Cijfer', isset( $r['cijfer'] ) ? $r['cijfer'] : '' ); // phpcs:ignore
			echo deedni_beheer_veld( $n . '[resultaten][' . $j . '][tekst]', 'Uitleg', isset( $r['tekst'] ) ? $r['tekst'] : '' ); // phpcs:ignore
			echo '</div>';
		}
		echo '</div><label class="deedni-b-check deedni-b-weg"><input type="checkbox" name="' . esc_attr( $n ) . '[verwijderen]" value="1"> Dit project verwijderen</label></div>';
	}
	echo '</fieldset>';

	// Notitie.
	echo '<fieldset class="deedni-b-groep deedni-b-notitie"><legend>Notitie bij deze wijziging</legend>';
	echo deedni_beheer_veld( 'deedni_b[notitie]', 'Wat pas je aan en waarom? Alleen beheerders zien dit, in het logboek.', '', 'textarea', ' rows="2" placeholder="Bijvoorbeeld: telefoonnummer uit Over mij gehaald"' ); // phpcs:ignore
	echo '<label class="deedni-b-check"><input type="checkbox" name="deedni_b[mail]" value="1"> Stuur de marketeer een mail dat het profiel is aangepast</label>';
	echo '</fieldset>';
	echo '<p>Klik daarna rechtsboven op <strong>Bijwerken</strong>.</p>';
}

function deedni_metabox_beheer_zichtbaar( $post ) {
	$k        = deedni_kandidaat( $post->ID );
	$verborgen = deedni_beheer_verborgen( $post->ID );
	if ( $verborgen ) {
		echo '<p class="deedni-b-status is-verborgen"><strong>Verborgen door beheer.</strong> Werkgevers kunnen dit profiel niet vinden of openen.</p>';
	} elseif ( $k['zichtbaar'] ) {
		echo '<p class="deedni-b-status is-online"><strong>Online.</strong> Werkgevers kunnen dit profiel vinden in Ontdekken en bij Kandidaten zoeken.</p>';
	} else {
		echo '<p class="deedni-b-status"><strong>Offline.</strong> De marketeer heeft het profiel nog niet gepubliceerd of zelf offline gehaald.</p>';
	}
	echo '<p><label class="deedni-b-check"><input type="checkbox" name="deedni_b[beheer_verborgen]" value="1"' . checked( $verborgen, true, false ) . '> Verberg dit profiel voor werkgevers</label></p>';
	echo '<p class="description">Het profiel verdwijnt uit Ontdekken, zoeken en de profielpagina, en er kunnen geen nieuwe aanbiedingen meer komen. Lopende aanbiedingen blijven bestaan. De marketeer ziet een melding op Mijn profiel.</p>';
	echo deedni_beheer_veld( 'deedni_b[beheer_reden]', 'Reden voor de marketeer (optioneel)', (string) get_post_meta( $post->ID, 'beheer_reden', true ), 'textarea', ' rows="3" placeholder="Bijvoorbeeld: je profiel bevat de naam van je huidige werkgever. Pas dat aan, dan zetten we het weer online."' ); // phpcs:ignore
}

function deedni_metabox_beheer_log( $post ) {
	$log = deedni_beheer_log( $post->ID );
	if ( ! $log ) {
		echo '<p class="description">Nog geen wijzigingen door beheer.</p>';
		return;
	}
	echo '<ol class="deedni-b-log">';
	foreach ( $log as $regel ) {
		echo '<li><span class="deedni-b-log-kop">' . esc_html( wp_date( 'j M Y H:i', (int) $regel['t'] ) . ' · ' . $regel['door'] ) . '</span>';
		if ( ! empty( $regel['velden'] ) ) {
			echo '<span class="deedni-b-log-velden">' . esc_html( implode( ', ', (array) $regel['velden'] ) ) . '</span>';
		}
		if ( ! empty( $regel['notitie'] ) ) {
			echo '<span class="deedni-b-log-notitie">' . nl2br( esc_html( $regel['notitie'] ) ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol>';
}

/* ---------- Opslaan ---------- */

add_action( 'save_post_kandidaat', 'deedni_beheer_opslaan', 10, 2 );

function deedni_beheer_opslaan( $post_id, $post ) {
	if ( empty( $_POST['deedni_beheer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['deedni_beheer_nonce'] ) ), 'deedni_beheer_profiel' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! deedni_is_beheerder() ) {
		return;
	}
	$b = isset( $_POST['deedni_b'] ) && is_array( $_POST['deedni_b'] ) ? wp_unslash( $_POST['deedni_b'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	$tekst = function ( $key ) use ( $b ) {
		return isset( $b[ $key ] ) && is_string( $b[ $key ] ) ? sanitize_text_field( $b[ $key ] ) : '';
	};
	$lang = function ( $key ) use ( $b ) {
		return isset( $b[ $key ] ) && is_string( $b[ $key ] ) ? sanitize_textarea_field( $b[ $key ] ) : '';
	};

	$voor        = deedni_kandidaat( $post_id );
	$voor_verb   = deedni_beheer_verborgen( $post_id );
	$voor_reden  = (string) get_post_meta( $post_id, 'beheer_reden', true );

	foreach ( array( 'functietitel', 'regio', 'uren', 'voornaam', 'achternaam', 'telefoon', 'huidige_werkgever' ) as $key ) {
		deedni_set_field( $post_id, $key, $tekst( $key ) );
	}
	foreach ( array( 'samenvatting', 'opleiding' ) as $key ) {
		deedni_set_field( $post_id, $key, $lang( $key ) );
	}
	deedni_set_field( $post_id, 'linkedin', isset( $b['linkedin'] ) ? esc_url_raw( trim( (string) $b['linkedin'] ) ) : '' );
	deedni_set_field( $post_id, 'ervaring_jaren', min( 50, deedni_parse_int( $tekst( 'ervaring_jaren' ) ) ) );
	deedni_set_field( $post_id, 'salaris_min', deedni_parse_int( $tekst( 'salaris_min' ) ) );
	$v = $tekst( 'beschikbaarheid' );
	deedni_set_field( $post_id, 'beschikbaarheid', array_key_exists( $v, deedni_choices( 'beschikbaarheid' ) ) ? $v : '' );
	$v = $tekst( 'zoekstatus' );
	deedni_set_field( $post_id, 'zoekstatus', array_key_exists( $v, deedni_choices( 'zoekstatus' ) ) ? $v : 'open' );
	$plekken = isset( $b['werkplekken'] ) ? array_map( 'sanitize_key', (array) $b['werkplekken'] ) : array();
	deedni_set_field( $post_id, 'werkplekken', array_values( array_intersect( $plekken, array_keys( deedni_choices( 'werkvorm' ) ) ) ) );

	foreach ( array( 'specialisme', 'provincie' ) as $tax ) {
		$term = absint( $tekst( $tax ) );
		wp_set_object_terms( $post_id, $term ? array( $term ) : array(), $tax );
	}
	$tools = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', $tekst( 'tools' ) ) ) ) as $naam ) {
		$t = term_exists( $naam, 'vaardigheid' );
		if ( ! $t ) {
			$t = wp_insert_term( mb_substr( $naam, 0, 40 ), 'vaardigheid' );
		}
		if ( $t && ! is_wp_error( $t ) ) {
			$tools[] = (int) ( is_array( $t ) ? $t['term_id'] : $t );
		}
	}
	wp_set_object_terms( $post_id, array_values( array_unique( $tools ) ), 'vaardigheid' );

	if ( ! empty( $b['werkervaring_aanwezig'] ) ) {
		$rijen = array();
		foreach ( isset( $b['werkervaring'] ) ? (array) $b['werkervaring'] : array() as $rij ) {
			$rij = (array) $rij;
			if ( ! empty( $rij['verwijderen'] ) ) {
				continue;
			}
			$rij = array_map( 'sanitize_textarea_field', array_map( 'strval', array_intersect_key( $rij, array_flip( array( 'functie', 'werkgever', 'soort', 'periode', 'tekst' ) ) ) ) );
			if ( ! empty( $rij['functie'] ) ) {
				$rijen[] = $rij + array( 'functie' => '', 'werkgever' => '', 'soort' => '', 'periode' => '', 'tekst' => '' );
			}
		}
		deedni_set_field( $post_id, 'werkervaring', $rijen );
	}

	if ( ! empty( $b['projecten_aanwezig'] ) ) {
		$rijen = array();
		foreach ( isset( $b['projecten'] ) ? (array) $b['projecten'] : array() as $rij ) {
			$rij = (array) $rij;
			if ( ! empty( $rij['verwijderen'] ) ) {
				continue;
			}
			$p = array();
			foreach ( array( 'titel', 'klant', 'rol', 'branche', 'tools' ) as $key ) {
				$p[ $key ] = isset( $rij[ $key ] ) ? sanitize_text_field( (string) $rij[ $key ] ) : '';
			}
			$p['aanpak']     = isset( $rij['aanpak'] ) ? sanitize_textarea_field( (string) $rij['aanpak'] ) : '';
			$p['link']       = isset( $rij['link'] ) ? esc_url_raw( trim( (string) $rij['link'] ) ) : '';
			$p['resultaten'] = array();
			foreach ( isset( $rij['resultaten'] ) ? (array) $rij['resultaten'] : array() as $r ) {
				$r = array(
					'cijfer' => isset( $r['cijfer'] ) ? sanitize_text_field( (string) $r['cijfer'] ) : '',
					'tekst'  => isset( $r['tekst'] ) ? sanitize_text_field( (string) $r['tekst'] ) : '',
				);
				if ( $r['cijfer'] || $r['tekst'] ) {
					$p['resultaten'][] = $r;
				}
			}
			if ( $p['titel'] ) {
				$rijen[] = $p;
			}
		}
		deedni_set_field( $post_id, 'projecten', $rijen );
	}

	if ( isset( $b['blokkeerlijst'] ) ) {
		deedni_save_blokkeerlijst( $post_id, $lang( 'blokkeerlijst' ) );
	}
	if ( ! empty( $b['cv_verwijderen'] ) ) {
		delete_post_meta( $post_id, 'cv' );
	}

	$verborgen = ! empty( $b['beheer_verborgen'] );
	$reden     = $lang( 'beheer_reden' );
	if ( $verborgen ) {
		update_post_meta( $post_id, 'beheer_verborgen', 1 );
	} else {
		delete_post_meta( $post_id, 'beheer_verborgen' );
	}
	deedni_set_field( $post_id, 'beheer_reden', $reden );

	// Wat is er veranderd? Voor het logboek.
	clean_post_cache( $post_id );
	wp_cache_delete( $post_id, 'post_meta' );
	$na      = deedni_kandidaat( $post_id );
	$labels  = deedni_beheer_labels();
	$anders  = array();
	foreach ( $labels as $key => $label ) {
		if ( ! in_array( $key, array( 'beheer_verborgen', 'beheer_reden' ), true ) && array_key_exists( $key, $voor ) && wp_json_encode( $voor[ $key ] ) !== wp_json_encode( $na[ $key ] ) ) {
			$anders[] = $label;
		}
	}
	if ( $voor_verb !== $verborgen ) {
		$anders[] = $verborgen ? 'Profiel verborgen voor werkgevers' : 'Profiel weer zichtbaar voor werkgevers';
	}
	if ( $voor_reden !== $reden ) {
		$anders[] = $labels['beheer_reden'];
	}
	$notitie = $lang( 'notitie' );
	if ( $anders || '' !== $notitie ) {
		deedni_beheer_log_toevoegen( $post_id, $anders, $notitie );
	}

	if ( ! empty( $b['mail'] ) && $na['email'] ) {
		$alineas = array(
			'Hoi' . ( $na['voornaam'] ? ' ' . esc_html( $na['voornaam'] ) : '' ) . ',',
			'We hebben je profiel ' . esc_html( $na['code'] ) . ' op ' . esc_html( get_bloginfo( 'name' ) ) . ' aangepast' . ( $anders ? ': ' . esc_html( strtolower( implode( ', ', $anders ) ) ) : '' ) . '.',
		);
		if ( $verborgen ) {
			$alineas[] = 'Je profiel is op dit moment niet zichtbaar voor bedrijven.' . ( $reden ? ' ' . nl2br( esc_html( $reden ) ) : '' );
		}
		$alineas[] = 'Kijk je profiel even na. Klopt er iets niet, antwoord dan op deze mail.';
		deedni_mail( $na['email'], 'Je profiel is aangepast', $alineas, 'Bekijk je profiel', deedni_page_url( 'profiel' ) );
	}
}

/** Melding na opslaan. */
add_filter(
	'post_updated_messages',
	function ( $berichten ) {
		$berichten['kandidaat'] = array_fill( 0, 11, '' );
		$berichten['kandidaat'][1] = 'Profiel bijgewerkt. De wijziging staat in het logboek.';
		$berichten['kandidaat'][4] = 'Profiel bijgewerkt. De wijziging staat in het logboek.';
		$berichten['kandidaat'][6] = 'Profiel opgeslagen.';
		return $berichten;
	}
);

/* ---------- Snelle actie in de lijst met kandidaten ---------- */

add_filter(
	'post_row_actions',
	function ( $acties, $post ) {
		if ( 'kandidaat' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $acties;
		}
		$verborgen = deedni_beheer_verborgen( $post->ID );
		$url       = wp_nonce_url( admin_url( 'admin-post.php?action=deedni_beheer_verberg&kandidaat=' . $post->ID . '&aan=' . ( $verborgen ? 0 : 1 ) ), 'deedni_beheer_verberg_' . $post->ID );
		$acties['deedni_verberg'] = '<a href="' . esc_url( $url ) . '">' . ( $verborgen ? 'Weer tonen aan werkgevers' : 'Verbergen voor werkgevers' ) . '</a>';
		$acties['edit']           = '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">Profiel aanpassen</a>';
		return $acties;
	},
	10,
	2
);

add_action(
	'admin_post_deedni_beheer_verberg',
	function () {
		$id = isset( $_GET['kandidaat'] ) ? absint( $_GET['kandidaat'] ) : 0;
		check_admin_referer( 'deedni_beheer_verberg_' . $id );
		if ( ! $id || 'kandidaat' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) || ! deedni_is_beheerder() ) {
			wp_die( 'Geen toegang.' );
		}
		$aan = ! empty( $_GET['aan'] );
		if ( $aan ) {
			update_post_meta( $id, 'beheer_verborgen', 1 );
		} else {
			delete_post_meta( $id, 'beheer_verborgen' );
		}
		deedni_beheer_log_toevoegen( $id, array( $aan ? 'Profiel verborgen voor werkgevers' : 'Profiel weer zichtbaar voor werkgevers' ), '' );
		wp_safe_redirect( admin_url( 'edit.php?post_type=kandidaat&deedni_verborgen=' . ( $aan ? 1 : 0 ) ) );
		exit;
	}
);

add_action(
	'admin_notices',
	function () {
		$screen = get_current_screen();
		if ( $screen && 'edit-kandidaat' === $screen->id && isset( $_GET['deedni_verborgen'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$aan = '1' === $_GET['deedni_verborgen']; // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $aan ? 'Profiel verborgen voor werkgevers. Dit staat in het logboek.' : 'Profiel is weer zichtbaar voor werkgevers (als de marketeer het zelf online heeft staan).' ) . '</p></div>';
		}
	}
);

/* ---------- Opmaak van het formulier ---------- */

add_action(
	'admin_head-post.php',
	'deedni_beheer_stijl'
);

function deedni_beheer_stijl() {
	$screen = get_current_screen();
	if ( ! $screen || 'kandidaat' !== $screen->post_type ) {
		return;
	}
	?>
	<style>
		#deedni-beheer .deedni-b-raster { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 0 24px; }
		#deedni-beheer .deedni-b-groep, #deedni-beheer-zichtbaar fieldset { border: 1px solid #dcdcde; border-radius: 8px; padding: 4px 16px 12px; margin: 0 0 16px; min-width: 0; }
		#deedni-beheer legend { font-weight: 600; font-size: 14px; padding: 0 6px; }
		.deedni-b-veld { margin: 10px 0; }
		.deedni-b-veld > label, .deedni-b-label { display: block; font-weight: 600; margin-bottom: 4px; }
		.deedni-b-check { display: inline-flex; gap: 6px; align-items: center; margin-right: 14px; font-weight: 400 !important; }
		.deedni-b-rij { border-top: 1px solid #f0f0f1; padding-top: 6px; margin-top: 6px; }
		.deedni-b-rij:first-of-type { border-top: 0; }
		.deedni-b-kolommen { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0 16px; }
		.deedni-b-kolommen--smal { grid-template-columns: 140px 1fr; }
		.deedni-b-kolommen--smal .deedni-b-veld { margin: 4px 0; }
		.deedni-b-resultaten { background: #f6f7f7; border-radius: 6px; padding: 8px 12px; }
		.deedni-b-weg { color: #b32d2e; margin-top: 6px; }
		.deedni-b-notitie { background: #fcf9e8; border-color: #f0c33c !important; }
		.deedni-b-status { padding: 8px 10px; border-radius: 6px; background: #f0f0f1; }
		.deedni-b-status.is-online { background: #edfaef; }
		.deedni-b-status.is-verborgen { background: #fcf0f1; color: #8a2424; }
		.deedni-b-log { margin: 0; padding-left: 18px; }
		.deedni-b-log li { margin-bottom: 10px; }
		.deedni-b-log-kop { display: block; color: #646970; font-size: 12px; }
		.deedni-b-log-velden { display: block; font-weight: 600; }
		.deedni-b-log-notitie { display: block; font-style: italic; }
		.post-type-kandidaat #titlediv { margin-bottom: 12px; }
	</style>
	<?php
}
