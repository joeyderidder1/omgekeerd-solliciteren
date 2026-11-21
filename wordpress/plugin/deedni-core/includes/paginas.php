<?php
/**
 * Blokken voor de extra pagina's uit het ontwerp: Contact, Over ons, Blog, Nieuws, blogartikel en een
 * SEO-landingspagina. Alle teksten pas je aan in de editor; de pagina's worden bij de update aangemaakt
 * (zie deedni_pagina_lijst en deedni_create_pages). De landingspagina komt als concept.
 */

defined( 'ABSPATH' ) || exit;

/** Leestijd in minuten (200 woorden per minuut). */
function deedni_leestijd( $post_id ) {
	$woorden = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
	return max( 1, (int) round( $woorden / 200 ) );
}

/** De categorie "Nieuws" (aangemaakt bij de update). */
function deedni_nieuws_cat() {
	$t = get_term_by( 'slug', 'nieuws', 'category' );
	return $t ? (int) $t->term_id : 0;
}

/** Label boven een bericht: de eerste categorie die niet Nieuws of Geen categorie is, of de eerste tag bij nieuws. */
function deedni_bericht_label( $post_id ) {
	$cats = get_the_category( $post_id );
	foreach ( $cats as $c ) {
		if ( ! in_array( $c->slug, array( 'nieuws', 'uncategorized', 'geen-categorie' ), true ) ) {
			return $c->name;
		}
	}
	$tags = get_the_tags( $post_id );
	if ( $tags && ! is_wp_error( $tags ) ) {
		return $tags[0]->name;
	}
	return in_array( deedni_nieuws_cat(), wp_list_pluck( $cats, 'term_id' ), true ) ? 'Nieuws' : '';
}

/** Uitgelichte afbeelding, of een foto uit de plugin zodat de kaart niet leeg is. */
function deedni_bericht_beeld( $post_id, $class = '' ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail( $post_id, 'large', array( 'class' => $class, 'alt' => '', 'loading' => 'lazy' ) );
	}
	$fotos = array( 'werken-laptop.jpg', 'overleg.jpg', 'marketing-analyse.jpg', 'telefoon-laptop.jpg' );
	return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( DEEDNI_URL . 'assets/img/' . $fotos[ $post_id % 4 ] ) . '" alt="" loading="lazy">';
}

function deedni_bericht_datum( $post_id ) {
	return date_i18n( 'j M Y', get_post_time( 'U', false, $post_id ) );
}

/** Berichten voor een bron: 'blog' (alles behalve nieuws), 'nieuws' of 'alles'. */
function deedni_berichten_args( $bron, $extra = array() ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
	);
	$nieuws = deedni_nieuws_cat();
	if ( 'nieuws' === $bron ) {
		$args['cat'] = $nieuws ? $nieuws : -1;
	} elseif ( 'blog' === $bron && $nieuws ) {
		$args['category__not_in'] = array( $nieuws );
	}
	return array_merge( $args, $extra );
}

/** Een kaart met een bericht (blog). */
function deedni_bericht_kaart( $post_id, $groot = false ) {
	$label = deedni_bericht_label( $post_id );
	$h     = '<a class="pg-post' . ( $groot ? ' pg-post--groot' : '' ) . '" href="' . esc_url( get_permalink( $post_id ) ) . '">';
	$h    .= '<span class="pg-post-beeld">' . deedni_bericht_beeld( $post_id ) . '</span><span class="pg-post-tekst">';
	if ( $label ) {
		$h .= '<span class="pg-label">' . esc_html( $label ) . '</span>';
	}
	$h .= '<' . ( $groot ? 'h2' : 'h3' ) . ' class="pg-post-titel">' . esc_html( get_the_title( $post_id ) ) . '</' . ( $groot ? 'h2' : 'h3' ) . '>';
	$excerpt = get_the_excerpt( $post_id );
	if ( $excerpt ) {
		$h .= '<span class="pg-post-intro">' . esc_html( wp_trim_words( $excerpt, $groot ? 40 : 24 ) ) . '</span>';
	}
	$h .= '<span class="fine">' . esc_html( deedni_bericht_datum( $post_id ) ) . ' · ' . (int) deedni_leestijd( $post_id ) . ' min lezen</span>';
	return $h . '</span></a>';
}

/* =========================================================================
 * Paginakop
 * ========================================================================= */

deedni_blok(
	'paginakop',
	array(
		'titel'        => 'Paginakop',
		'beschrijving' => 'Grote titel met een korte inleiding, op een zachte kleurverloop. Bovenaan Contact, Over ons, Blog en Nieuws.',
		'icoon'        => 'heading',
		'stijl'        => 'deedni-paginas',
		'velden'       => array(
			'kop'  => array( 'type' => 'text', 'label' => 'Titel', 'standaard' => 'Titel van de pagina' ),
			'lead' => array( 'type' => 'textarea', 'label' => 'Inleiding', 'standaard' => '' ),
		),
		'render'       => function ( $a ) {
			$h = '<section class="pg-kop"' . deedni_anker_attr( $a ) . '><div class="wrap"><h1>' . deedni_tekst( $a['kop'] ) . '</h1>';
			if ( '' !== trim( (string) $a['lead'] ) ) {
				$h .= '<p class="lead">' . deedni_tekst( $a['lead'] ) . '</p>';
			}
			return $h . '</div></section>';
		},
	)
);

/* =========================================================================
 * Contact
 * ========================================================================= */

deedni_blok(
	'contact',
	array(
		'titel'        => 'Contactformulier',
		'beschrijving' => 'Formulier (naar Instellingen > deedni > Contact) met ernaast een foto en contactadressen.',
		'icoon'        => 'email',
		'stijl'        => 'deedni-paginas',
		'velden'       => array(
			'kop'         => array( 'type' => 'text', 'label' => 'Kop boven het formulier', 'standaard' => 'Stuur ons een bericht' ),
			'rollen'      => array( 'type' => 'text', 'label' => 'Keuzes bij "Ik ben" (met komma\'s)', 'standaard' => 'Werkzoekende, Werkgever, Anders' ),
			'toestemming' => array( 'type' => 'text', 'label' => 'Tekst bij het vinkje', 'standaard' => 'Ik ga akkoord dat deedni mijn gegevens gebruikt om te antwoorden. Lees de [privacyverklaring].', 'help' => '[privacyverklaring] wordt een link naar je privacypagina.' ),
			'knop'        => array( 'type' => 'text', 'label' => 'Knop', 'standaard' => 'Verstuur bericht' ),
			'bedankt_kop' => array( 'type' => 'text', 'label' => 'Kop na versturen', 'standaard' => 'Bericht verstuurd', 'groep' => 'Na versturen' ),
			'bedankt'     => array( 'type' => 'text', 'label' => 'Tekst na versturen', 'standaard' => 'Bedankt. We reageren binnen twee werkdagen op het e-mailadres dat je hebt ingevuld.', 'groep' => 'Na versturen' ),
			'foto'        => array( 'type' => 'image', 'label' => 'Foto naast het formulier', 'standaard' => deedni_img( 'overleg.jpg' ), 'groep' => 'Zijkant' ),
			'adressen'    => array(
				'type'      => 'lijst',
				'label'     => 'Contactadressen',
				'groep'     => 'Zijkant',
				'velden'    => array(
					'titel' => array( 'type' => 'text', 'label' => 'Voor wie' ),
					'tekst' => array( 'type' => 'text', 'label' => 'Waarvoor' ),
					'email' => array( 'type' => 'text', 'label' => 'E-mailadres' ),
				),
				'standaard' => array(
					array( 'titel' => 'Werkzoekenden', 'tekst' => 'Hulp bij je profiel of een aanbod.', 'email' => '' ),
					array( 'titel' => 'Werkgevers', 'tekst' => 'Vragen over zoeken, aanbiedingen en prijzen.', 'email' => '' ),
					array( 'titel' => 'Pers en samenwerking', 'tekst' => 'Voor artikelen, events en partners.', 'email' => '' ),
				),
			),
			'voetnoot'    => array( 'type' => 'text', 'label' => 'Kleine tekst onder de adressen', 'standaard' => 'We reageren binnen twee werkdagen.', 'groep' => 'Zijkant' ),
		),
		'render'       => 'deedni_render_contact',
	)
);

function deedni_render_contact( $a ) {
	$flash = deedni_flash( 'contact' );
	$err   = $flash && isset( $flash['errors'] ) ? $flash['errors'] : array();
	$data  = $flash && isset( $flash['data'] ) ? $flash['data'] : array( 'rol' => '', 'naam' => '', 'email' => '', 'onderwerp' => '', 'bericht' => '', 'akkoord' => false );
	$klaar = isset( $_GET['verstuurd'] ) && ! $err; // phpcs:ignore WordPress.Security.NonceVerification
	$rollen = array_values( array_filter( array_map( 'trim', explode( ',', (string) $a['rollen'] ) ) ) );
	$fout   = function ( $key ) use ( $err ) {
		return isset( $err[ $key ] ) ? '<span class="field-error" id="ct-fout-' . esc_attr( $key ) . '">' . esc_html( $err[ $key ] ) . '</span>' : '';
	};
	$invalid = function ( $key ) use ( $err ) {
		return isset( $err[ $key ] ) ? ' aria-invalid="true" aria-describedby="ct-fout-' . esc_attr( $key ) . '"' : '';
	};

	$h  = '<section class="pg-sec pg-contact"' . deedni_anker_attr( $a ) . ' id="contact-formulier"><div class="wrap pg-twee">';
	$h .= '<div class="card pg-formulier">';
	if ( $klaar ) {
		$h .= '<div class="modal-done" role="status" tabindex="-1" data-focus><span class="ok-dot" aria-hidden="true">' . deedni_icon( 'check', 28 ) . '</span><h2>' . esc_html( $a['bedankt_kop'] ) . '</h2><p>' . esc_html( $a['bedankt'] ) . '</p><a class="btn btn--ghost" href="' . esc_url( remove_query_arg( 'verstuurd' ) . '#contact-formulier' ) . '">Nog een bericht sturen</a></div>';
	} else {
		$h .= '<form class="stack" method="post" action="' . esc_url( remove_query_arg( array( 'verstuurd', 'deedni_melding' ) ) . '#contact-formulier' ) . '" novalidate' . ( $err ? ' data-focus' : '' ) . '>';
		$h .= '<input type="hidden" name="deedni_action" value="contact">' . deedni_nonce_field( 'deedni_contact' );
		$h .= '<h2>' . esc_html( $a['kop'] ) . '</h2>';
		if ( isset( $err['form'] ) ) {
			$h .= deedni_notice( esc_html( $err['form'] ), 'error' );
		} elseif ( $err ) {
			$h .= deedni_notice( 'Er ontbreekt nog iets. Kijk de velden met een rode tekst na.', 'error' );
		}
		if ( $rollen ) {
			$h .= '<fieldset class="stack-s"><legend>Ik ben</legend><div class="chips">';
			foreach ( $rollen as $i => $r ) {
				$h .= '<label class="chip chip--radio"><input type="radio" name="rol" value="' . esc_attr( $r ) . '"' . checked( $data['rol'] ? $data['rol'] === $r : 0 === $i, true, false ) . '>' . esc_html( $r ) . '</label>';
			}
			$h .= '</div></fieldset>';
		}
		$h .= '<div class="fields"><div class="field"><label for="ct-naam">Naam</label><input id="ct-naam" name="naam" autocomplete="name" maxlength="190" required value="' . esc_attr( $data['naam'] ) . '"' . $invalid( 'naam' ) . '>' . $fout( 'naam' ) . '</div>';
		$h .= '<div class="field"><label for="ct-mail">E-mailadres</label><input id="ct-mail" type="email" name="email" autocomplete="email" maxlength="190" required value="' . esc_attr( $data['email'] ) . '"' . $invalid( 'email' ) . '>' . $fout( 'email' ) . '</div></div>';
		$h .= '<div class="field"><label for="ct-onderwerp">Onderwerp</label><input id="ct-onderwerp" name="onderwerp" maxlength="190" value="' . esc_attr( $data['onderwerp'] ) . '"></div>';
		$h .= '<div class="field"><label for="ct-bericht">Bericht</label><textarea id="ct-bericht" name="bericht" rows="6" maxlength="5000" required' . $invalid( 'bericht' ) . '>' . esc_textarea( $data['bericht'] ) . '</textarea>' . $fout( 'bericht' ) . '</div>';
		$h .= '<label class="check"><input type="checkbox" name="akkoord" value="1" required' . checked( ! empty( $data['akkoord'] ), true, false ) . $invalid( 'akkoord' ) . '><span>' . deedni_contact_toestemming( $a['toestemming'] ) . '</span></label>' . $fout( 'akkoord' );
		$h .= '<p class="hp" aria-hidden="true"><label>Website <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></p>';
		$h .= '<div><button class="btn btn--primary" type="submit">' . esc_html( $a['knop'] ) . '</button></div></form>';
	}
	$h .= '</div><aside class="pg-contact-zij">';
	if ( ! empty( $a['foto']['url'] ) ) {
		$h .= '<div class="pg-foto">' . deedni_beeld( $a['foto'], array( 'alt' => '' ) ) . '</div>';
	}
	$adressen = array_filter( (array) $a['adressen'], function ( $r ) {
		return ! empty( $r['titel'] );
	} );
	if ( $adressen ) {
		$h .= '<ul class="pg-adressen">';
		foreach ( $adressen as $r ) {
			$r  = wp_parse_args( $r, array( 'titel' => '', 'tekst' => '', 'email' => '' ) );
			$h .= '<li><strong>' . esc_html( $r['titel'] ) . '</strong>' . ( $r['tekst'] ? '<span class="fine">' . esc_html( $r['tekst'] ) . '</span>' : '' );
			if ( is_email( $r['email'] ) ) {
				$h .= '<a href="' . esc_url( 'mailto:' . $r['email'] ) . '">' . esc_html( $r['email'] ) . '</a>';
			}
			$h .= '</li>';
		}
		$h .= '</ul>';
	}
	if ( $a['voetnoot'] ) {
		$h .= '<p class="fine">' . esc_html( $a['voetnoot'] ) . '</p>';
	}
	$h .= '</aside></div>' . deedni_contact_script() . '</section>';
	return $h;
}

/** "[privacyverklaring]" wordt een link naar de privacypagina (als die er is). */
function deedni_contact_toestemming( $tekst ) {
	$url  = get_privacy_policy_url();
	$link = $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">privacyverklaring</a>' : 'privacyverklaring';
	return str_replace( '[privacyverklaring]', $link, esc_html( $tekst ) );
}

function deedni_contact_script() {
	return '<script>(function(){var s=document.currentScript.parentNode,d=s.querySelector("[data-focus]");if(d){var e=d.querySelector("[aria-invalid]")||d;setTimeout(function(){e.focus({preventScroll:true});d.scrollIntoView({block:"center"});},50);}})();</script>';
}

/** Verwerken (via deedni_handle_forms). Stuurt een mail; bewaart niets in de database. */
function deedni_handle_contact() {
	if ( ! deedni_nonce_ok( 'deedni_contact' ) ) {
		deedni_fout( 'contact', array( 'form' => 'Je sessie is verlopen. Probeer het opnieuw.' ) );
		return;
	}
	$terug = deedni_terug_url( home_url( '/' ) );
	if ( deedni_post( 'website_url' ) ) {
		wp_safe_redirect( add_query_arg( 'verstuurd', 1, $terug ) . '#contact-formulier' );
		exit;
	}
	$data = array(
		'rol'       => mb_substr( deedni_post( 'rol' ), 0, 60 ),
		'naam'      => mb_substr( deedni_post( 'naam' ), 0, 190 ),
		'email'     => mb_substr( deedni_post( 'email', 'email' ), 0, 190 ),
		'onderwerp' => mb_substr( deedni_post( 'onderwerp' ), 0, 190 ),
		'bericht'   => mb_substr( deedni_post( 'bericht', 'textarea' ), 0, 5000 ),
		'akkoord'   => ! empty( $_POST['akkoord'] ), // phpcs:ignore WordPress.Security.NonceVerification
	);
	$errors = array();
	if ( '' === $data['naam'] ) {
		$errors['naam'] = 'Vul je naam in.';
	}
	if ( ! is_email( $data['email'] ) ) {
		$errors['email'] = 'Dit e-mailadres klopt niet. Bijvoorbeeld: naam@voorbeeld.nl';
	}
	if ( mb_strlen( trim( $data['bericht'] ) ) < 5 ) {
		$errors['bericht'] = 'Schrijf je vraag of bericht.';
	}
	if ( ! $data['akkoord'] ) {
		$errors['akkoord'] = 'Zet een vinkje, anders kunnen we je niet antwoorden.';
	}
	if ( deedni_grens_bereikt( 'contact', deedni_ip() ) ) {
		$errors['form'] = 'Er zijn te veel berichten vanaf dit adres verstuurd. Probeer het over een uur opnieuw.';
	}
	if ( $errors ) {
		deedni_fout( 'contact', $errors, $data );
		return;
	}
	deedni_tel( 'contact', deedni_ip(), deedni_grens( 'contact' )[1] );
	$naar = get_option( 'deedni_contact_email', '' );
	$naar = is_email( $naar ) ? $naar : get_option( 'admin_email' );
	$onderwerp = 'Contactformulier: ' . ( $data['onderwerp'] ? $data['onderwerp'] : 'nieuw bericht' );
	$alineas   = array(
		'<strong>' . esc_html( $data['naam'] ) . '</strong> (' . esc_html( $data['email'] ) . ')' . ( $data['rol'] ? ', ' . esc_html( mb_strtolower( $data['rol'] ) ) : '' ) . ', stuurde een bericht via het contactformulier.',
		nl2br( esc_html( $data['bericht'] ) ),
		'Antwoord gewoon op deze mail.',
	);
	add_filter( 'wp_mail', $reply = function ( $m ) use ( $data ) {
		$m['headers']   = (array) $m['headers'];
		$m['headers'][] = 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>' ), '', $data['naam'] ) . ' <' . $data['email'] . '>';
		return $m;
	} );
	deedni_mail( $naar, $onderwerp, $alineas );
	remove_filter( 'wp_mail', $reply );
	wp_safe_redirect( add_query_arg( 'verstuurd', 1, $terug ) . '#contact-formulier' );
	exit;
}

/* =========================================================================
 * Tekst met foto, punten (genummerd, stappen of kaarten)
 * ========================================================================= */

deedni_blok(
	'tekst-beeld',
	array(
		'titel'        => 'Tekst met foto',
		'beschrijving' => 'Een foto met ernaast een kop en een paar alinea\'s. Bijvoorbeeld "Waarom deedni bestaat" op Over ons.',
		'icoon'        => 'align-pull-left',
		'stijl'        => 'deedni-paginas',
		'achtergrond'  => 'standaard',
		'velden'       => array(
			'foto'         => array( 'type' => 'image', 'label' => 'Foto', 'standaard' => deedni_img( 'werken-laptop.jpg' ) ),
			'foto_rechts'  => array( 'type' => 'toggle', 'label' => 'Foto rechts', 'standaard' => false ),
			'kop'          => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Waarom deedni bestaat' ),
			'tekst'        => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => "Op een vacaturesite solliciteer je en hoop je op antwoord. Via een bureau weet je zelden wat er over je wordt gezegd. Bij deedni maak je een anoniem profiel en reageren bedrijven op jou, met functie en salaris.\n\nPas als jij ja zegt, delen we je naam, cv en contactgegevens. Zo blijft je huidige werkgever buiten beeld en blijft het gesprek professioneel.", 'help' => 'Een lege regel begint een nieuwe alinea.' ),
		),
		'render'       => function ( $a ) {
			$id = deedni_uid( 'h-tb' );
			$h  = '<section class="pg-sec' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap pg-tb' . ( $a['foto_rechts'] ? ' pg-tb--rechts' : '' ) . '">';
			$h .= ! empty( $a['foto']['url'] ) ? '<div class="pg-foto pg-foto--groot">' . deedni_beeld( $a['foto'], array( 'alt' => '' ) ) . '</div>' : '';
			$h .= '<div class="pg-tb-tekst"><h2 id="' . $id . '" class="pg-h2">' . deedni_tekst( $a['kop'] ) . '</h2>' . deedni_alineas( $a['tekst'], 'pg-groot' ) . '</div>';
			return $h . '</div></section>';
		},
	)
);

deedni_blok(
	'punten',
	array(
		'titel'        => 'Punten (genummerd, stappen of kaarten)',
		'beschrijving' => 'Een kop met drie of meer korte punten. Als genummerde lijst (01, 02), als stappen (1, 2, 3) of als kaarten met een vinkje.',
		'icoon'        => 'editor-ol',
		'stijl'        => 'deedni-paginas',
		'achtergrond'  => 'standaard',
		'velden'       => array(
			'kop'   => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Waar we voor staan' ),
			'lead'  => array( 'type' => 'textarea', 'label' => 'Inleiding (optioneel)', 'standaard' => '' ),
			'stijl' => array(
				'type'      => 'keuze',
				'label'     => 'Weergave',
				'opties'    => array(
					'kaarten'    => 'Kaarten met een vinkje',
					'genummerd'  => 'Genummerd (01, 02, 03)',
					'stappen'    => 'Stappen (1, 2, 3 in een rondje)',
				),
				'standaard' => 'kaarten',
			),
			'items' => array(
				'type'      => 'lijst',
				'label'     => 'Punten',
				'velden'    => array(
					'titel' => array( 'type' => 'text', 'label' => 'Titel' ),
					'tekst' => array( 'type' => 'textarea', 'label' => 'Tekst' ),
				),
				'standaard' => array(
					array( 'titel' => 'Anoniem tot jouw ja', 'tekst' => 'Bedrijven zien wat je kunt. Je naam, werkgevers en projectnamen blijven verborgen tot jij ja zegt.' ),
					array( 'titel' => 'Salaris altijd vooraf', 'tekst' => 'Een aanbod zonder salaris kan niet worden verstuurd. Geen onderhandeling in het donker.' ),
					array( 'titel' => 'Professioneel, niet vrijblijvend', 'tekst' => 'Geen swipen en geen matches. Een serieus platform voor mensen die hun vak serieus nemen.' ),
				),
			),
		),
		'render'       => function ( $a ) {
			$id    = deedni_uid( 'h-pt' );
			$stijl = in_array( $a['stijl'], array( 'kaarten', 'genummerd', 'stappen' ), true ) ? $a['stijl'] : 'kaarten';
			$h     = '<section class="pg-sec' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap">';
			$h    .= '<div class="pg-head"><h2 id="' . $id . '" class="pg-h2">' . deedni_tekst( $a['kop'] ) . '</h2>' . ( '' !== trim( (string) $a['lead'] ) ? '<p class="lead">' . deedni_tekst( $a['lead'] ) . '</p>' : '' ) . '</div>';
			$h    .= '<ol class="pg-punten pg-punten--' . $stijl . '">';
			$n     = 0;
			foreach ( (array) $a['items'] as $v ) {
				$v = wp_parse_args( $v, array( 'titel' => '', 'tekst' => '' ) );
				if ( '' === $v['titel'] ) {
					continue;
				}
				++$n;
				$merk = 'kaarten' === $stijl ? deedni_icon( 'check', 20 ) : ( 'genummerd' === $stijl ? sprintf( '%02d', $n ) : (string) $n );
				$h   .= '<li><span class="pg-merk" aria-hidden="true">' . $merk . '</span><strong>' . esc_html( $v['titel'] ) . '</strong>' . deedni_alineas( $v['tekst'], 'fine' ) . '</li>';
			}
			return $h . '</ol></div></section>';
		},
	)
);

/* =========================================================================
 * Blog en nieuws
 * ========================================================================= */

deedni_blok(
	'berichten',
	array(
		'titel'        => 'Berichten (blog of nieuws)',
		'beschrijving' => 'Berichten uit WordPress (Berichten > Nieuw). Blog: als raster met een uitgelicht bericht en filters per categorie. Nieuws: als lijst met datum. Nieuwsberichten zet je in de categorie Nieuws.',
		'icoon'        => 'admin-post',
		'stijl'        => 'deedni-paginas',
		'velden'       => array(
			'bron'       => array(
				'type'      => 'keuze',
				'label'     => 'Welke berichten?',
				'opties'    => array(
					'blog'   => 'Blog (alles behalve de categorie Nieuws)',
					'nieuws' => 'Nieuws (alleen de categorie Nieuws)',
					'alles'  => 'Alle berichten',
				),
				'standaard' => 'blog',
			),
			'weergave'   => array(
				'type'      => 'keuze',
				'label'     => 'Weergave',
				'opties'    => array(
					'raster' => 'Raster met foto\'s',
					'lijst'  => 'Lijst met datum (nieuws)',
				),
				'standaard' => 'raster',
			),
			'uitgelicht' => array( 'type' => 'toggle', 'label' => 'Nieuwste bericht groot bovenaan (raster)', 'standaard' => true ),
			'filters'    => array( 'type' => 'toggle', 'label' => 'Filters per categorie tonen (raster)', 'standaard' => true ),
			'aantal'     => array( 'type' => 'number', 'label' => 'Berichten per pagina', 'standaard' => 9 ),
			'leeg'       => array( 'type' => 'text', 'label' => 'Tekst als er nog niets is', 'standaard' => 'Hier verschijnen binnenkort de eerste berichten.' ),
		),
		'render'       => 'deedni_render_berichten',
	)
);

function deedni_render_berichten( $a ) {
	$raster = 'lijst' !== $a['weergave'];
	$get    = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
	$pagina = isset( $get['pg'] ) && is_string( $get['pg'] ) ? max( 1, absint( $get['pg'] ) ) : 1;
	$cat    = $raster && $a['filters'] && isset( $get['categorie'] ) && is_string( $get['categorie'] ) ? sanitize_title( $get['categorie'] ) : '';
	$per    = max( 1, min( 30, (int) $a['aantal'] ) );
	$extra  = array( 'posts_per_page' => $per, 'paged' => $pagina );
	$cat_t  = $cat ? get_term_by( 'slug', $cat, 'category' ) : null;
	if ( $cat_t ) {
		$extra['category__in'] = array( (int) $cat_t->term_id );
	}
	$q = new WP_Query( deedni_berichten_args( $a['bron'], $extra ) );

	$h = '<section class="pg-sec pg-berichten"' . deedni_anker_attr( $a ) . '><div class="wrap">';
	if ( $raster && $a['filters'] ) {
		$cats = get_categories( array( 'hide_empty' => true, 'exclude' => array_filter( array( deedni_nieuws_cat(), (int) get_option( 'default_category' ) ) ) ) );
		if ( count( $cats ) > 1 ) {
			$basis = remove_query_arg( array( 'categorie', 'pg' ) );
			$h    .= '<nav class="chips pg-filter" aria-label="Filter op onderwerp"><a class="chip" href="' . esc_url( $basis ) . '"' . ( $cat_t ? '' : ' aria-current="true"' ) . '>Alles</a>';
			foreach ( $cats as $c ) {
				$h .= '<a class="chip" href="' . esc_url( add_query_arg( 'categorie', $c->slug, $basis ) ) . '"' . ( $cat_t && $cat_t->term_id === $c->term_id ? ' aria-current="true"' : '' ) . '>' . esc_html( $c->name ) . '</a>';
			}
			$h .= '</nav>';
		}
	}
	if ( ! $q->have_posts() ) {
		return $h . '<p class="fine">' . esc_html( $a['leeg'] ) . '</p></div></section>';
	}
	$ids = wp_list_pluck( $q->posts, 'ID' );
	if ( $raster ) {
		if ( $a['uitgelicht'] && 1 === $pagina && ! $cat_t ) {
			$h .= deedni_bericht_kaart( array_shift( $ids ), true );
		}
		if ( $ids ) {
			$h .= '<div class="pg-raster">';
			foreach ( $ids as $id ) {
				$h .= deedni_bericht_kaart( $id );
			}
			$h .= '</div>';
		}
	} else {
		$h .= '<ol class="pg-nieuws">';
		foreach ( $ids as $id ) {
			$label = deedni_bericht_label( $id );
			$h    .= '<li><div class="pg-nieuws-wanneer"><time datetime="' . esc_attr( get_post_time( 'c', true, $id ) ) . '">' . esc_html( deedni_bericht_datum( $id ) ) . '</time>' . ( $label && 'Nieuws' !== $label ? '<span class="tag tag--accent">' . esc_html( $label ) . '</span>' : '' ) . '</div>';
			$h    .= '<a href="' . esc_url( get_permalink( $id ) ) . '"><h2 class="pg-nieuws-titel">' . esc_html( get_the_title( $id ) ) . '</h2><span class="fine">' . esc_html( wp_trim_words( get_the_excerpt( $id ), 40 ) ) . '</span><span class="pg-verder">Lees verder ' . deedni_icon( 'arrow', 16 ) . '</span></a></li>';
		}
		$h .= '</ol>';
	}
	if ( $q->max_num_pages > 1 ) {
		$args = $cat_t ? array( 'categorie' => $cat_t->slug ) : array();
		$basis = remove_query_arg( array( 'pg', 'categorie' ) );
		$h   .= '<nav class="pub-paginas" aria-label="Pagina\'s">';
		$h   .= $pagina > 1 ? '<a class="btn btn--ghost btn--small" rel="prev" href="' . esc_url( add_query_arg( $args + ( $pagina > 2 ? array( 'pg' => $pagina - 1 ) : array() ), $basis ) ) . '">Nieuwer</a>' : '<span></span>';
		$h   .= '<span class="fine">Pagina ' . (int) $pagina . ' van ' . (int) $q->max_num_pages . '</span>';
		$h   .= $pagina < $q->max_num_pages ? '<a class="btn btn--ghost btn--small" rel="next" href="' . esc_url( add_query_arg( $args + array( 'pg' => $pagina + 1 ), $basis ) ) . '">Ouder</a>' : '<span></span>';
		$h   .= '</nav>';
	}
	wp_reset_postdata();
	return $h . '</div></section>';
}

deedni_blok(
	'meer-lezen',
	array(
		'titel'        => 'Meer lezen',
		'beschrijving' => 'De nieuwste drie berichten als kaarten. Onder een artikel of op een landingspagina.',
		'icoon'        => 'excerpt-view',
		'stijl'        => 'deedni-paginas',
		'achtergrond'  => 'zacht',
		'velden'       => array(
			'kop'    => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Meer lezen' ),
			'bron'   => array(
				'type'      => 'keuze',
				'label'     => 'Welke berichten?',
				'opties'    => array( 'blog' => 'Blog', 'nieuws' => 'Nieuws', 'alles' => 'Alle berichten' ),
				'standaard' => 'blog',
			),
			'aantal' => array( 'type' => 'number', 'label' => 'Aantal', 'standaard' => 3 ),
		),
		'render'       => function ( $a ) {
			return deedni_meer_lezen_html( $a, is_singular( 'post' ) ? get_the_ID() : 0 );
		},
	)
);

function deedni_meer_lezen_html( $a, $behalve = 0 ) {
	$a   = wp_parse_args( $a, array( 'kop' => 'Meer lezen', 'bron' => 'blog', 'aantal' => 3, 'achtergrond' => 'zacht' ) );
	$ids = get_posts(
		deedni_berichten_args(
			$a['bron'],
			array(
				'posts_per_page' => max( 1, min( 6, (int) $a['aantal'] ) ),
				'post__not_in'   => $behalve ? array( (int) $behalve ) : array(),
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		)
	);
	if ( ! $ids ) {
		return '';
	}
	$id = deedni_uid( 'h-meer' );
	$h  = '<section class="pg-sec' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap"><h2 id="' . $id . '" class="pg-h2 pg-head">' . esc_html( $a['kop'] ) . '</h2><div class="pg-raster pg-raster--klein">';
	foreach ( $ids as $pid ) {
		$h .= deedni_bericht_kaart( $pid );
	}
	return $h . '</div></div></section>';
}

/* =========================================================================
 * Blogartikel (in het sjabloon Enkel bericht van het thema)
 * ========================================================================= */

deedni_blok(
	'artikel',
	array(
		'titel'        => 'Artikel',
		'beschrijving' => 'Het hele bericht: terug naar de blog, categorie, titel, datum en leestijd, foto, de tekst, een blok met een knop en Meer lezen. Staat in het sjabloon Enkel bericht.',
		'icoon'        => 'media-document',
		'stijl'        => 'deedni-paginas',
		'multiple'     => false,
		'velden'       => array(
			'auteur'     => array( 'type' => 'text', 'label' => 'Auteur', 'standaard' => 'Redactie deedni', 'help' => 'Leeg laten = de naam van de schrijver in WordPress.' ),
			'cta_kop'    => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Maak je profiel in tien minuten', 'groep' => 'Blok onder de tekst' ),
			'cta_tekst'  => array( 'type' => 'text', 'label' => 'Tekst', 'standaard' => 'Gratis voor werkzoekenden. Je naam blijft verborgen tot jij ja zegt.', 'groep' => 'Blok onder de tekst' ),
			'cta_knop'   => array( 'type' => 'text', 'label' => 'Knop', 'standaard' => 'Maak gratis je profiel', 'groep' => 'Blok onder de tekst' ),
			'cta_link'   => array( 'type' => 'link', 'label' => 'Link van de knop', 'standaard' => 'pagina:registreren', 'groep' => 'Blok onder de tekst' ),
			'meer_kop'   => array( 'type' => 'text', 'label' => 'Kop Meer lezen', 'standaard' => 'Meer lezen', 'groep' => 'Meer lezen' ),
		),
		'render'       => 'deedni_render_artikel',
	)
);

function deedni_render_artikel( $a ) {
	$post = get_post();
	if ( ! $post || 'post' !== $post->post_type ) {
		return deedni_in_editor() ? '<div class="deedni-editor-leeg">Artikel: hier komt het bericht met titel, foto, tekst en Meer lezen.</div>' : '';
	}
	$id     = (int) $post->ID;
	$nieuws = in_array( deedni_nieuws_cat(), wp_list_pluck( get_the_category( $id ), 'term_id' ), true );
	$terug  = deedni_page_url( $nieuws ? 'nieuws' : 'blog' );
	$label  = deedni_bericht_label( $id );
	$auteur = '' !== trim( (string) $a['auteur'] ) ? $a['auteur'] : get_the_author_meta( 'display_name', $post->post_author );

	$h  = '<article class="pg-artikel"><div class="wrap pg-artikel-kop">';
	$h .= '<a class="pg-terug" href="' . esc_url( $terug ) . '">← Terug naar ' . ( $nieuws ? 'nieuws' : 'de blog' ) . '</a>';
	if ( $label ) {
		$h .= '<span class="pg-label">' . esc_html( $label ) . '</span>';
	}
	$h .= '<h1>' . esc_html( get_the_title( $id ) ) . '</h1>';
	$h .= '<p class="pg-artikel-meta fine"><span>' . esc_html( $auteur ) . '</span><time datetime="' . esc_attr( get_post_time( 'c', true, $id ) ) . '">' . esc_html( date_i18n( 'j F Y', get_post_time( 'U', false, $id ) ) ) . '</time><span>' . (int) deedni_leestijd( $id ) . ' min lezen</span></p></div>';
	if ( has_post_thumbnail( $id ) ) {
		$h .= '<div class="wrap"><figure class="pg-artikel-beeld">' . get_the_post_thumbnail( $id, 'large', array( 'alt' => '' ) ) . '</figure></div>';
	}
	// De tekst zelf. Filters van WordPress (blokken, embeds) werken gewoon; dit blok staat in het sjabloon, niet in de tekst.
	remove_filter( 'the_content', 'wpautop' );
	$inhoud = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	add_filter( 'the_content', 'wpautop' );
	if ( ! has_blocks( $post->post_content ) ) {
		$inhoud = wpautop( $inhoud );
	}
	$h .= '<div class="wrap"><div class="pg-artikel-tekst pg-prose">' . $inhoud;
	if ( 'bezoeker' === deedni_rol() && $a['cta_kop'] ) {
		$h .= '<aside class="pg-artikel-cta"><div><strong>' . esc_html( $a['cta_kop'] ) . '</strong>' . ( $a['cta_tekst'] ? '<span>' . esc_html( $a['cta_tekst'] ) . '</span>' : '' ) . '</div>';
		$h .= '<a class="pg-pil" href="' . esc_url( deedni_link( $a['cta_link'] ) ) . '">' . esc_html( $a['cta_knop'] ) . '<span class="pg-pil-ico" aria-hidden="true">' . deedni_icon( 'arrow', 18 ) . '</span></a></aside>';
	}
	$h .= '</div></div></article>';
	$h .= deedni_meer_lezen_html( array( 'kop' => $a['meer_kop'], 'bron' => $nieuws ? 'nieuws' : 'blog' ), $id );
	return $h;
}

/* =========================================================================
 * SEO-landingspagina
 * ========================================================================= */

deedni_blok(
	'landing-hero',
	array(
		'titel'        => 'Landingspagina: kop',
		'beschrijving' => 'Kruimelpad, grote titel met de zoekterm, inleiding, twee knoppen en een foto.',
		'icoon'        => 'cover-image',
		'stijl'        => 'deedni-paginas',
		'velden'       => array(
			'kruimel'    => array( 'type' => 'text', 'label' => 'Kruimelpad (met / ertussen)', 'standaard' => 'Voor werkzoekenden / Vacatures', 'help' => 'Home staat er altijd voor. Het laatste stuk is deze pagina.' ),
			'kop'        => array( 'type' => 'text', 'label' => 'Titel (H1, met de zoekterm)', 'standaard' => 'Vacatures waarbij het salaris al vaststaat' ),
			'lead'       => array( 'type' => 'textarea', 'label' => 'Inleiding', 'standaard' => 'Je zoekt geen baan meer. Bedrijven zoeken jou, anoniem, en sturen een aanbod met salaris. Jij beslist of ze je naam te zien krijgen.' ),
			'knop1'      => array( 'type' => 'text', 'label' => 'Knop 1', 'standaard' => 'Maak gratis je profiel', 'groep' => 'Knoppen' ),
			'knop1_link' => array( 'type' => 'link', 'label' => 'Link knop 1', 'standaard' => 'pagina:registreren', 'groep' => 'Knoppen' ),
			'knop2'      => array( 'type' => 'text', 'label' => 'Knop 2', 'standaard' => 'Zo werkt het', 'groep' => 'Knoppen' ),
			'knop2_link' => array( 'type' => 'link', 'label' => 'Link knop 2', 'standaard' => '#stappen', 'groep' => 'Knoppen' ),
			'foto'       => array( 'type' => 'image', 'label' => 'Foto', 'standaard' => deedni_img( 'telefoon-laptop.jpg' ) ),
			'meta'       => array( 'type' => 'textarea', 'label' => 'Meta-omschrijving (voor Google)', 'standaard' => 'Bedrijven zoeken jou, anoniem, en sturen een aanbod met salaris. Maak gratis een profiel en beslis zelf wie je naam ziet.', 'groep' => 'Zoekmachines', 'help' => 'Ongeveer 150 tekens. De tekst onder de titel in de zoekresultaten. Gebruik je Yoast of Rank Math, dan vul je hem daar in en wordt deze overgeslagen.' ),
		),
		'render'       => function ( $a ) {
			if ( '' !== trim( (string) $a['meta'] ) && ! deedni_in_editor() ) {
				$GLOBALS['deedni_meta_omschrijving'] = $a['meta'];
			}
			$delen = array_values( array_filter( array_map( 'trim', explode( '/', (string) $a['kruimel'] ) ) ) );
			$h     = '<section class="pg-kop pg-landing"' . deedni_anker_attr( $a ) . '><div class="wrap pg-tb pg-tb--rechts"><div class="pg-tb-tekst">';
			$h    .= '<nav class="pg-kruimel fine" aria-label="Kruimelpad"><ol><li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li>';
			foreach ( $delen as $i => $d ) {
				$h .= '<li' . ( count( $delen ) - 1 === $i ? ' aria-current="page"' : '' ) . '>' . esc_html( $d ) . '</li>';
			}
			$h .= '</ol></nav><h1>' . deedni_tekst( $a['kop'] ) . '</h1><p class="lead">' . deedni_tekst( $a['lead'] ) . '</p><div class="row">';
			if ( $a['knop1'] ) {
				$h .= '<a class="pg-pil" href="' . esc_url( deedni_link( $a['knop1_link'] ) ) . '">' . esc_html( $a['knop1'] ) . '<span class="pg-pil-ico" aria-hidden="true">' . deedni_icon( 'arrow', 18 ) . '</span></a>';
			}
			if ( $a['knop2'] ) {
				$link = 0 === strpos( (string) $a['knop2_link'], '#' ) ? $a['knop2_link'] : deedni_link( $a['knop2_link'] );
				$h   .= '<a class="btn btn--ghost" href="' . esc_url( $link ) . '">' . esc_html( $a['knop2'] ) . '</a>';
			}
			$h .= '</div></div>' . ( ! empty( $a['foto']['url'] ) ? '<div class="pg-foto pg-foto--groot">' . deedni_beeld( $a['foto'], array( 'alt' => '' ) ) . '</div>' : '' );
			// Kruimelpad ook voor zoekmachines.
			$lijst = array( array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ) );
			foreach ( $delen as $i => $d ) {
				$lijst[] = array_filter( array( '@type' => 'ListItem', 'position' => $i + 2, 'name' => $d, 'item' => count( $delen ) - 1 === $i ? get_permalink() : null ) );
			}
			$h .= '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $lijst ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>';
			return $h . '</div></section>';
		},
	)
);

deedni_blok(
	'inhoud-index',
	array(
		'titel'        => 'Lange tekst met inhoudsopgave',
		'beschrijving' => 'Tekst in hoofdstukken met links een vaste inhoudsopgave ("Op deze pagina"). Goed voor zoekmachines.',
		'icoon'        => 'list-view',
		'stijl'        => 'deedni-paginas',
		'velden'       => array(
			'index_kop'  => array( 'type' => 'text', 'label' => 'Kop van de inhoudsopgave', 'standaard' => 'Op deze pagina' ),
			'extra_link' => array( 'type' => 'text', 'label' => 'Extra link onderaan de inhoudsopgave', 'standaard' => 'Veelgestelde vragen', 'help' => 'Springt naar de vragen (#vragen). Leeg = geen extra link.' ),
			'delen'      => array(
				'type'      => 'lijst',
				'label'     => 'Hoofdstukken',
				'velden'    => array(
					'kop'   => array( 'type' => 'text', 'label' => 'Kop (H2)' ),
					'tekst' => array( 'type' => 'textarea', 'label' => 'Tekst', 'help' => 'Een lege regel begint een nieuwe alinea. Een regel die begint met ### wordt een tussenkop.' ),
				),
				'standaard' => array(
					array( 'kop' => 'Welke functies zijn er?', 'tekst' => "Bedrijven zoeken in elk vak: van hovenier en elektricien tot accountant en webdesigner. Op deedni geef je aan welke functie je zoekt, met welke vaardigheden je werkt en welk salaris je minimaal wilt.\n\n### Vakmensen met ervaring\nJe profiel toont wat je hebt gedaan en wat je kunt, zonder de naam van je werkgever.\n\n### Starters\nOok met weinig ervaring kun je laten zien wat je kunt: opleiding, stages en vaardigheden." ),
					array( 'kop' => 'Wat verdien je?', 'tekst' => 'Het salaris hangt af van functie, ervaring en sector. Op deedni zie je dat per aanbod vooraf, in plaats van pas in de laatste gespreksronde.' ),
					array( 'kop' => 'Waarom anoniem zoeken?', 'tekst' => 'Zoeken naar een nieuwe baan terwijl je nog werkt, vraagt discretie. Op deedni ziet een bedrijf je naam pas als jij ja zegt, en je huidige werkgever zet je op de blokkeerlijst.' ),
				),
			),
		),
		'render'       => function ( $a ) {
			$delen = array_values( array_filter( (array) $a['delen'], function ( $d ) {
				return ! empty( $d['kop'] );
			} ) );
			if ( ! $delen ) {
				return '';
			}
			$h  = '<section class="pg-sec"' . deedni_anker_attr( $a ) . '><div class="wrap pg-index"><aside class="pg-index-nav"><nav aria-label="' . esc_attr( $a['index_kop'] ) . '"><strong class="fine">' . esc_html( $a['index_kop'] ) . '</strong><ol>';
			$ids = array();
			foreach ( $delen as $d ) {
				$id     = deedni_uid( sanitize_title( $d['kop'] ) );
				$ids[]  = $id;
				$h     .= '<li><a href="#' . esc_attr( $id ) . '">' . esc_html( $d['kop'] ) . '</a></li>';
			}
			if ( '' !== trim( (string) $a['extra_link'] ) ) {
				$h .= '<li><a href="#vragen">' . esc_html( $a['extra_link'] ) . '</a></li>';
			}
			$h .= '</ol></nav></aside><div class="pg-index-tekst pg-prose">';
			foreach ( $delen as $i => $d ) {
				$h .= '<h2 id="' . esc_attr( $ids[ $i ] ) . '">' . esc_html( $d['kop'] ) . '</h2>';
				foreach ( preg_split( '/\n\s*\n/', trim( (string) ( isset( $d['tekst'] ) ? $d['tekst'] : '' ) ) ) as $blok ) {
					foreach ( preg_split( '/\n/', $blok ) as $j => $regel ) {
						if ( 0 === strpos( ltrim( $regel ), '###' ) ) {
							$h .= '<h3>' . esc_html( trim( substr( ltrim( $regel ), 3 ) ) ) . '</h3>';
						} elseif ( '' !== trim( $regel ) ) {
							$h .= '<p>' . esc_html( trim( $regel ) ) . '</p>';
						}
					}
				}
			}
			return $h . '</div></div></section>';
		},
	)
);

/**
 * Meta-omschrijving uit het blok Landingspagina: kop. Een blokthema rendert de blokken vóór wp_head,
 * dus de waarde staat er al. Niet als een SEO-plugin dat zelf doet.
 */
add_action(
	'wp_head',
	function () {
		if ( empty( $GLOBALS['deedni_meta_omschrijving'] ) || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) ) {
			return;
		}
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $GLOBALS['deedni_meta_omschrijving'] ) ) . '">' . "\n";
	},
	2
);

/* =========================================================================
 * Pagina's, categorie Nieuws en menu's bij de update naar 3.1
 * ========================================================================= */

/** De inhoud (blokken) van de nieuwe pagina's. */
function deedni_pagina_inhoud( $key ) {
	$b = 'deedni_blok_inhoud';
	switch ( $key ) {
		case 'contact':
			return $b( 'deedni/paginakop', array( 'kop' => 'Neem contact op', 'lead' => 'Een vraag over je profiel, een aanbod of je account? Stuur een bericht of kijk eerst bij de veelgestelde vragen.' ) ) . "\n\n" . $b( 'deedni/contact' );
		case 'over-ons':
			return $b( 'deedni/paginakop', array( 'kop' => 'We draaien het solliciteren om.', 'lead' => 'deedni is gebouwd voor vakmensen die liever gevonden worden dan zelf zoeken. Jij houdt de regie, bedrijven doen de eerste stap.' ) ) . "\n\n" . $b( 'deedni/tekst-beeld' ) . "\n\n" . $b( 'deedni/punten', array( 'achtergrond' => 'zacht' ) );
		case 'blog':
			return $b( 'deedni/paginakop', array( 'kop' => 'Blog', 'lead' => 'Praktische artikelen voor werkzoekenden en werkgevers: profielen, salaris, privacy en het eerste gesprek.' ) ) . "\n\n" . $b( 'deedni/berichten' );
		case 'nieuws':
			return $b( 'deedni/paginakop', array( 'kop' => 'Nieuws', 'lead' => 'Wat er nieuw is bij deedni: aankondigingen, nieuwe functies en wijzigingen in onze regels.' ) ) . "\n\n" . $b( 'deedni/berichten', array( 'bron' => 'nieuws', 'weergave' => 'lijst' ) );
		case 'landing':
			return implode(
				"\n\n",
				array(
					$b( 'deedni/landing-hero' ),
					$b( 'deedni/punten', array( 'kop' => 'Wat je krijgt', 'stijl' => 'genummerd', 'items' => array(
						array( 'titel' => 'Altijd zicht op salaris', 'tekst' => 'Elk aanbod noemt het salaris vooraf. Je verliest geen tijd aan gesprekken die op een te laag bedrag stranden.' ),
						array( 'titel' => 'Anoniem tot jij ja zegt', 'tekst' => 'Je naam, je huidige werkgever en je projectnamen blijven verborgen. Bedrijven zien je vaardigheden, resultaten en ervaring.' ),
						array( 'titel' => 'Reageren met ja, nee of ja-maar', 'tekst' => 'Past alles behalve het salaris? Zeg dat in één bericht, of vraag om iets anders.' ),
					) ) ),
					$b( 'deedni/punten', array( 'kop' => 'In drie stappen naar een passend aanbod', 'stijl' => 'stappen', 'achtergrond' => 'zacht', 'anker' => 'stappen', 'items' => array(
						array( 'titel' => 'Maak je profiel', 'tekst' => 'Vul je ervaring, vaardigheden en resultaten in. Zet je huidige werkgever op de blokkeerlijst.' ),
						array( 'titel' => 'Ontvang aanbiedingen', 'tekst' => 'Bedrijven die bij je passen sturen een aanbod met functie, salaris en een persoonlijk bericht.' ),
						array( 'titel' => 'Kies zelf', 'tekst' => 'Zeg ja, nee of ja-maar. Pas na jouw ja zien ze wie je bent.' ),
					) ) ),
					$b( 'deedni/inhoud-index' ),
					$b( 'deedni/vragen', array( 'schema' => true ) ),
					$b( 'deedni/meer-lezen' ),
				)
			);
	}
	return '';
}

/** Categorie Nieuws, nieuwe pagina's in het bezoekersmenu. Eén keer per site (optie deedni_paginas_31). */
function deedni_installeer_paginas_31() {
	if ( ! term_exists( 'nieuws', 'category' ) ) {
		wp_insert_term( 'Nieuws', 'category', array( 'slug' => 'nieuws', 'description' => 'Berichten in deze categorie staan op de pagina Nieuws, niet op de blog.' ) );
	}
	if ( get_option( 'deedni_paginas_31' ) ) {
		return;
	}
	update_option( 'deedni_paginas_31', 1, false );

	// Bezoekersmenu: Nieuws, Blog, Over ons en Contact erbij, vóór Inloggen. Bestaande links blijven staan.
	$locaties = get_nav_menu_locations();
	if ( empty( $locaties['deedni-bezoeker'] ) ) {
		return;
	}
	$menu  = (int) $locaties['deedni-bezoeker'];
	$items = (array) wp_get_nav_menu_items( $menu );
	$al    = array();
	foreach ( $items as $mi ) {
		$al[] = (int) $mi->object_id;
		$al[] = untrailingslashit( (string) $mi->url );
	}
	$nieuw = array();
	foreach ( array( 'nieuws' => 'Nieuws', 'blog' => 'Blog', 'over-ons' => 'Over ons', 'contact' => 'Contact' ) as $key => $tekst ) {
		$pid = deedni_page_id( $key );
		if ( ! $pid || 'publish' !== get_post_status( $pid ) || in_array( $pid, $al, true ) || in_array( untrailingslashit( get_permalink( $pid ) ), $al, true ) ) {
			continue;
		}
		$nieuw[] = wp_update_nav_menu_item(
			$menu,
			0,
			array(
				'menu-item-title'     => $tekst,
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $pid,
			)
		);
	}
	$nieuw = array_filter( $nieuw, 'is_int' );
	if ( ! $nieuw ) {
		return;
	}
	// Volgorde: alles behalve Inloggen, dan de nieuwe, dan Inloggen.
	$voor = array();
	$na   = array();
	foreach ( $items as $mi ) {
		if ( (int) $mi->menu_item_parent ) {
			continue;
		}
		if ( (int) $mi->object_id === deedni_page_id( 'inloggen' ) || 'inloggen' === sanitize_title( $mi->title ) ) {
			$na[] = (int) $mi->ID;
		} else {
			$voor[] = (int) $mi->ID;
		}
	}
	$volgorde = array_merge( $voor, $nieuw, $na );
	foreach ( $volgorde as $i => $mid ) {
		wp_update_post( array( 'ID' => $mid, 'menu_order' => $i + 1 ) );
	}
}
