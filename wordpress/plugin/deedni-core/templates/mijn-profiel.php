<?php
/**
 * Mijn profiel van een kandidaat: vijf stappen in één formulier, met rechts een live voorbeeld.
 * Zonder JavaScript staan alle stappen onder elkaar. Met JavaScript zie je steeds één stap.
 *
 * Variabelen: $a (velden van het blok).
 * Overschrijven? Kopieer dit bestand naar je-thema/deedni/mijn-profiel.php.
 */

defined( 'ABSPATH' ) || exit;

if ( deedni_in_editor() && ! deedni_is_kandidaat() ) {
	$ids = get_posts( array( 'post_type' => 'kandidaat', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	$id  = $ids ? (int) $ids[0] : 0;
} else {
	$id = deedni_ensure_profile( get_current_user_id(), 'kandidaat' );
}
$k     = $id ? deedni_kandidaat( $id ) : null;
if ( ! $k ) {
	echo '<div class="wrap page"><p class="deedni-editor-leeg">Nog geen kandidaten. Maak een account als kandidaat om dit blok te zien.</p></div>';
	return;
}
$c     = deedni_compleet( $k );
$stap_open = array();
foreach ( $c['todo'] as $t ) {
	$stap_open[ $t[1] ] = true;
}

$ziet = function ( $wie ) {
	if ( 'ja' === $wie ) {
		return ' <span class="tag tag--ink who-sees">Na jouw ja</span>';
	}
	if ( 'jij' === $wie ) {
		return ' <span class="tag tag--soft who-sees">Alleen jij</span>';
	}
	return ' <span class="tag who-sees">Iedereen</span>';
};
$veld = function ( $name, $label, $value, $wie = 'iedereen', $extra = array() ) use ( $ziet ) {
	$e    = wp_parse_args( $extra, array( 'type' => 'text', 'hint' => '', 'live' => '', 'auto' => '', 'textarea' => false, 'ph' => '', 'euro' => false, 'id' => 'p-' . $name ) );
	$attr = ' id="' . esc_attr( $e['id'] ) . '" name="' . esc_attr( $name ) . '"' . ( $e['live'] ? ' data-live="' . esc_attr( $e['live'] ) . '"' : '' ) . ( $e['auto'] ? ' autocomplete="' . esc_attr( $e['auto'] ) . '"' : '' ) . ( $e['ph'] ? ' placeholder="' . esc_attr( $e['ph'] ) . '"' : '' ) . ( $e['hint'] ? ' aria-describedby="' . esc_attr( $e['id'] ) . '-hint"' : '' );
	$h    = '<div class="field"><label for="' . esc_attr( $e['id'] ) . '">' . esc_html( $label ) . $ziet( $wie ) . '</label>';
	if ( $e['textarea'] ) {
		$h .= '<textarea' . $attr . '>' . esc_textarea( $value ) . '</textarea>';
	} else {
		$input = '<input type="' . esc_attr( $e['type'] ) . '"' . $attr . ' value="' . esc_attr( $value ) . '"' . ( $e['euro'] ? ' inputmode="numeric"' : '' ) . '>';
		$h    .= $e['euro'] ? '<div class="euro">' . $input . '</div>' : $input;
	}
	if ( $e['hint'] ) {
		$h .= '<span class="hint" id="' . esc_attr( $e['id'] ) . '-hint">' . esc_html( $e['hint'] ) . '</span>';
	}
	return $h . '</div>';
};
$select = function ( $name, $label, $opties, $huidig, $leeg = '' ) {
	$h = '<div class="field"><label for="p-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label><select id="p-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';
	if ( $leeg ) {
		$h .= '<option value="">' . esc_html( $leeg ) . '</option>';
	}
	foreach ( $opties as $v => $l ) {
		$h .= '<option value="' . esc_attr( $v ) . '"' . selected( (string) $huidig, (string) $v, false ) . '>' . esc_html( $l ) . '</option>';
	}
	return $h . '</select></div>';
};
/** Keuzelijst met vakgebieden als groep en de functies daaronder. */
$functie_select = function ( $huidig ) {
	$h = '<div class="field"><label for="p-specialisme">Vakgebied en functie</label><select id="p-specialisme" name="specialisme" data-vak-keuze aria-describedby="p-specialisme-hint"><option value="">Kies je functie</option>';
	foreach ( deedni_vakgebieden_boom() as $rij ) {
		$vak = $rij[0];
		$h  .= '<optgroup label="' . esc_attr( $vak->name ) . '">';
		$h  .= '<option value="' . (int) $vak->term_id . '" data-vak="' . (int) $vak->term_id . '"' . selected( (int) $huidig, (int) $vak->term_id, false ) . '>' . esc_html( $vak->name ) . ' (algemeen)</option>';
		foreach ( $rij[1] as $t ) {
			$h .= '<option value="' . (int) $t->term_id . '" data-vak="' . (int) $vak->term_id . '"' . selected( (int) $huidig, (int) $t->term_id, false ) . '>' . esc_html( $t->name ) . '</option>';
		}
		$h .= '</optgroup>';
	}
	return $h . '</select><span class="hint" id="p-specialisme-hint">Staat je functie er niet bij? Kies je vakgebied (algemeen) en schrijf je functie bij Functietitel.</span></div>';
};
$termen = function ( $tax ) {
	$o = array();
	foreach ( deedni_filter_terms( $tax ) as $t ) {
		$o[ $t->term_id ] = $t->name;
	}
	return $o;
};

$stappen = array(
	'persoonlijk'  => 'Persoonlijk',
	'werkervaring' => 'Werkervaring',
	'projecten'    => 'Projecten',
	'wat-zoek-je'  => 'Wat zoek je',
	'controleren'  => 'Controleren',
);
$sleutels = array_keys( $stappen );

/** Eén werkervaring als in- en uitklapbaar blok. */
$werk_item = function ( $i, $w ) {
	$w    = wp_parse_args( $w, array( 'functie' => '', 'werkgever' => '', 'soort' => '', 'periode' => '', 'tekst' => '' ) );
	$n    = 'werkervaring[' . $i . ']';
	$p    = 'we-' . $i;
	$open = '' === $w['functie'];
	$h    = '<li class="item" data-item><details' . ( $open ? ' open' : '' ) . '><summary><span class="stack-s"><strong data-sam="functie">' . esc_html( $w['functie'] ? $w['functie'] : 'Nieuwe werkervaring' ) . '</strong>';
	$h   .= '<span class="fine">Werkgever ' . deedni_redact( 's' ) . ( $w['soort'] ? ' · ' . esc_html( $w['soort'] ) : '' ) . ( $w['periode'] ? ' · ' . esc_html( $w['periode'] ) : '' ) . '</span></span><span class="btn btn--ghost btn--small" aria-hidden="true">Wijzig</span></summary>';
	$h   .= '<div class="item-body stack">';
	$h   .= '<div class="fields"><div class="field"><label for="' . $p . '-f">Functie <span class="tag who-sees">Iedereen</span></label><input id="' . $p . '-f" name="' . $n . '[functie]" value="' . esc_attr( $w['functie'] ) . '" data-sam-bron="functie"></div>';
	$h   .= '<div class="field"><label for="' . $p . '-w">Werkgever <span class="tag tag--ink who-sees">Na jouw ja</span></label><input id="' . $p . '-w" name="' . $n . '[werkgever]" value="' . esc_attr( $w['werkgever'] ) . '"></div></div>';
	$h   .= '<div class="fields"><div class="field"><label for="' . $p . '-s">Soort bedrijf <span class="tag who-sees">Iedereen</span></label><input id="' . $p . '-s" name="' . $n . '[soort]" value="' . esc_attr( $w['soort'] ) . '" placeholder="Bijvoorbeeld: bureau voor webwinkels"></div>';
	$h   .= '<div class="field"><label for="' . $p . '-p">Periode</label><input id="' . $p . '-p" name="' . $n . '[periode]" value="' . esc_attr( $w['periode'] ) . '" placeholder="2022 tot nu"></div></div>';
	$h   .= '<div class="field"><label for="' . $p . '-t">Wat deed je? <span class="tag who-sees">Iedereen</span></label><textarea id="' . $p . '-t" name="' . $n . '[tekst]">' . esc_textarea( $w['tekst'] ) . '</textarea></div>';
	$h   .= '<div><button type="button" class="link-btn" data-weg>Deze werkervaring verwijderen</button><span class="fine no-js-only"> Verwijderen zonder JavaScript: maak de functie leeg en sla op.</span></div>';
	return $h . '</div></details></li>';
};

/** Eén resultaat binnen een project. */
$res_rij = function ( $i, $j, $r ) {
	$r = wp_parse_args( $r, array( 'cijfer' => '', 'tekst' => '' ) );
	$n = 'projecten[' . $i . '][resultaten][' . $j . ']';
	$p = 'pr-' . $i . '-' . $j;
	$h  = '<li class="result-row" data-res-rij>';
	$h .= '<div class="field"><label for="' . $p . '-c">Kerncijfer <span class="fine">(optioneel)</span></label><input id="' . $p . '-c" name="' . $n . '[cijfer]" data-res="cijfer" placeholder="9, −40%, 2 weken" value="' . esc_attr( $r['cijfer'] ) . '"></div>';
	$h .= '<div class="field"><label for="' . $p . '-t">Wat heb je bereikt?</label><input id="' . $p . '-t" name="' . $n . '[tekst]" data-res="tekst" placeholder="Bijvoorbeeld: klant gaf een 9, of oplevering twee weken eerder" value="' . esc_attr( $r['tekst'] ) . '"></div>';
	return $h . '<button type="button" class="weg" data-weg-res aria-label="Resultaat verwijderen">×</button></li>';
};

/** Eén project als in- en uitklapbaar blok. */
$project_item = function ( $i, $pr ) use ( $res_rij ) {
	$pr   = wp_parse_args( $pr, array( 'titel' => '', 'klant' => '', 'rol' => '', 'branche' => '', 'tools' => '', 'aanpak' => '', 'link' => '', 'resultaten' => array() ) );
	$n    = 'projecten[' . $i . ']';
	$p    = 'pr-' . $i;
	$open = '' === $pr['titel'];
	$eerste = $pr['resultaten'] ? $pr['resultaten'][0] : null;
	$h    = '<li class="item" data-item><details' . ( $open ? ' open' : '' ) . '><summary><span class="stack-s"><strong data-sam="titel">' . esc_html( $pr['titel'] ? $pr['titel'] : 'Nieuw project' ) . '</strong>';
	if ( $pr['rol'] || $pr['branche'] ) {
		$h .= '<span class="fine">' . esc_html( implode( ', ', array_filter( array( $pr['rol'], $pr['branche'] ) ) ) ) . '</span>';
	}
	if ( $eerste ) {
		$h .= '<span class="fine">' . esc_html( $eerste['tekst'] ) . ( $eerste['cijfer'] ? ' <span class="delta">' . esc_html( $eerste['cijfer'] ) . '</span>' : '' ) . '</span>';
	}
	$h .= deedni_tools_html( $pr['tools'] ) . '</span><span class="btn btn--ghost btn--small" aria-hidden="true">Wijzig</span></summary>';
	$h .= '<div class="item-body stack">';
	$h .= '<div class="field"><label for="' . $p . '-titel">Korte titel <span class="tag who-sees">Iedereen</span></label><input id="' . $p . '-titel" name="' . $n . '[titel]" value="' . esc_attr( $pr['titel'] ) . '" data-sam-bron="titel" aria-describedby="' . $p . '-titel-hint"><span class="hint" id="' . $p . '-titel-hint">Zonder klantnaam, bijvoorbeeld “Stadstuin opnieuw aangelegd” of “Jaarafsluiting voor 40 mkb-klanten”.</span></div>';
	$h .= '<div class="fields"><div class="field"><label for="' . $p . '-klant">Klant of projectnaam <span class="tag tag--ink who-sees">Na jouw ja</span></label><input id="' . $p . '-klant" name="' . $n . '[klant]" value="' . esc_attr( $pr['klant'] ) . '"></div>';
	$h .= '<div class="field"><label for="' . $p . '-rol">Jouw rol</label><input id="' . $p . '-rol" name="' . $n . '[rol]" value="' . esc_attr( $pr['rol'] ) . '" placeholder="Uitvoerder, ontwerper, eindverantwoordelijke"></div></div>';
	$h .= '<div class="fields"><div class="field"><label for="' . $p . '-br">Soort klant of branche</label><input id="' . $p . '-br" name="' . $n . '[branche]" value="' . esc_attr( $pr['branche'] ) . '" placeholder="Particulier, mkb, gemeente"></div>';
	$h .= '<div class="field"><label for="' . $p . '-tools">Vaardigheden of tools <span class="fine">(optioneel)</span></label><input id="' . $p . '-tools" name="' . $n . '[tools]" value="' . esc_attr( $pr['tools'] ) . '" placeholder="Bestrating, Figma, Exact Online"></div></div>';
	$h .= '<div class="field"><label for="' . $p . '-aanpak">Wat heb je gedaan?</label><textarea id="' . $p . '-aanpak" name="' . $n . '[aanpak]">' . esc_textarea( $pr['aanpak'] ) . '</textarea></div>';
	// Resultaten zijn optioneel: niet elk vak heeft cijfers. Zonder resultaten blijft dit blok dicht.
	$h .= '<fieldset class="stack-s"><legend>Resultaat <span class="fine">(optioneel)</span> <span class="tag who-sees">Iedereen</span></legend><p class="fine">Heeft dit project iets meetbaars opgeleverd, zoals een tevreden klant, een besparing of meer omzet? Zet het hier. Geen cijfers? Laat dit leeg, de beschrijving is genoeg.</p>';
	$h .= '<ul class="stack-s res-lijst" data-res-lijst data-i="' . (int) $i . '">';
	foreach ( array_values( (array) $pr['resultaten'] ) as $j => $r ) {
		$h .= $res_rij( $i, $j, $r );
	}
	$h .= '</ul>';
	if ( ! $pr['resultaten'] ) {
		$h .= '<noscript><ul class="stack-s res-lijst">' . $res_rij( $i, 0, array() ) . '</ul></noscript>';
	}
	$h .= '<div><button type="button" class="btn btn--ghost btn--small" data-res-plus>Resultaat toevoegen</button></div></fieldset>';
	$h .= '<div class="field"><label for="' . $p . '-link">Link naar het werk <span class="tag tag--ink who-sees">Na jouw ja</span></label><input id="' . $p . '-link" type="url" name="' . $n . '[link]" value="' . esc_attr( $pr['link'] ) . '" placeholder="https://"></div>';
	$h .= '<div><button type="button" class="link-btn" data-weg>Dit project verwijderen</button><span class="fine no-js-only"> Verwijderen zonder JavaScript: maak de titel leeg en sla op.</span></div>';
	return $h . '</div></details></li>';
};

$knoppen = function ( $stap ) use ( $sleutels, $stappen ) {
	$i = array_search( $stap, $sleutels, true );
	$h = '<div class="nav-steps">';
	$h .= $i > 0 ? '<button type="submit" class="btn btn--ghost" name="naar" value="' . esc_attr( $sleutels[ $i - 1 ] ) . '">Vorige</button>' : '<span></span>';
	if ( $i < count( $sleutels ) - 1 ) {
		$h .= '<button type="submit" class="btn btn--primary" name="naar" value="' . esc_attr( $sleutels[ $i + 1 ] ) . '">Opslaan, volgende: ' . esc_html( mb_strtolower( $stappen[ $sleutels[ $i + 1 ] ] ) ) . '</button>';
	}
	return $h . '</div>';
};

$geblokkeerd = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $k['blokkeerlijst'] ) ) ) );
?>
<div class="wrap page deedni-profiel">
	<?php echo deedni_melding_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php if ( $k['beheer_verborgen'] ) : ?>
		<?php echo deedni_notice( deedni_beheer_melding_tekst( $k ), 'warn' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php endif; ?>
	<div class="split">
		<form class="stack profiel-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( deedni_page_url( 'profiel' ) ); ?>" data-profiel>
			<input type="hidden" name="deedni_action" value="profiel">
			<?php echo deedni_nonce_field( 'deedni_profiel' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<input type="hidden" name="werkplekken_aanwezig" value="1">
			<input type="hidden" name="tools_aanwezig" value="1">
			<input type="hidden" name="werkervaring_aanwezig" value="1">
			<input type="hidden" name="projecten_aanwezig" value="1">

			<nav class="steps" aria-label="Stappen van je profiel">
				<?php
				$n = 0;
				foreach ( $stappen as $key => $label ) :
					++$n;
					$klaar = 'controleren' === $key ? $k['zichtbaar'] : empty( $stap_open[ $key ] ) && ( 'wat-zoek-je' !== $key || empty( $stap_open['blokkeerlijst'] ) );
					?>
					<a href="#<?php echo esc_attr( $key ); ?>" data-stap="<?php echo esc_attr( $key ); ?>"<?php echo $klaar ? ' class="is-done"' : ''; ?>><span class="n"><?php echo (int) $n; ?></span><span class="t breed" data-breed="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $label ); ?></span></a>
				<?php endforeach; ?>
			</nav>

			<?php echo deedni_fouten_html( 'profiel' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<section class="stap" id="stap-persoonlijk" aria-labelledby="h-persoonlijk">
				<h2 id="h-persoonlijk" tabindex="-1"><?php echo esc_html( $a['stap1_kop'] ); ?></h2>
				<p class="lead"><?php echo wp_kses_post( deedni_tekst( $a['stap1_lead'] ) ); ?></p>
				<div class="card stack">
					<div class="fields">
						<?php echo $veld( 'voornaam', 'Voornaam', $k['voornaam'], 'ja', array( 'auto' => 'given-name' ) ); // phpcs:ignore ?>
						<?php echo $veld( 'achternaam', 'Achternaam', $k['achternaam'], 'ja', array( 'auto' => 'family-name' ) ); // phpcs:ignore ?>
					</div>
					<?php echo $veld( 'functietitel', 'Functietitel', $k['functietitel'], 'iedereen', array( 'live' => 'titel', 'hint' => 'Bijvoorbeeld Hovenier, Webdesigner of Senior accountant.' ) ); // phpcs:ignore ?>
					<div class="fields">
						<?php echo $functie_select( $k['specialisme_ids'] ? $k['specialisme_ids'][0] : 0 ); // phpcs:ignore ?>
						<?php echo $veld( 'ervaring_jaren', 'Jaren ervaring', $k['ervaring_jaren'] ? $k['ervaring_jaren'] : '', 'iedereen', array( 'type' => 'number', 'live' => 'ervaring' ) ); // phpcs:ignore ?>
					</div>
					<div class="fields">
						<div class="field"><label for="p-email">E-mailadres <span class="tag tag--ink who-sees">Na jouw ja</span></label><input id="p-email" type="email" value="<?php echo esc_attr( $k['email'] ); ?>" readonly aria-describedby="p-email-hint"><span class="hint" id="p-email-hint">Je inlogadres. Wijzigen kan via je account.</span></div>
						<?php echo $veld( 'telefoon', 'Telefoon', $k['telefoon'], 'ja', array( 'type' => 'tel', 'auto' => 'tel' ) ); // phpcs:ignore ?>
					</div>
					<div class="fields">
						<?php echo $veld( 'linkedin', 'LinkedIn', $k['linkedin'], 'ja', array( 'type' => 'url', 'ph' => 'https://www.linkedin.com/in/…' ) ); // phpcs:ignore ?>
						<?php echo $veld( 'opleiding', 'Opleiding of certificaten', $k['opleiding'], 'iedereen' ); // phpcs:ignore ?>
					</div>
					<?php echo $veld( 'samenvatting', 'Over mij', $k['samenvatting'], 'iedereen', array( 'textarea' => true, 'hint' => 'Twee à drie zinnen. Geen namen van werkgevers.', 'live' => 'over' ) ); // phpcs:ignore ?>
					<p class="note">Bewust geen foto, geboortedatum of geslacht: bedrijven kiezen op vakmanschap.</p>
				</div>
				<?php echo $knoppen( 'persoonlijk' ); // phpcs:ignore ?>
			</section>

			<section class="stap" id="stap-werkervaring" aria-labelledby="h-werk">
				<h2 id="h-werk" tabindex="-1"><?php echo esc_html( $a['stap2_kop'] ); ?></h2>
				<p class="lead"><?php echo wp_kses_post( deedni_tekst( $a['stap2_lead'] ) ); ?></p>
				<?php echo $veld( 'huidige_werkgever', 'Huidige werkgever', $k['huidige_werkgever'], 'ja', array( 'hint' => 'Zet je huidige werkgever ook op de blokkeerlijst bij stap 4.' ) ); // phpcs:ignore ?>
				<ul class="item-list" data-lijst="werkervaring">
					<?php
					$rows = $k['werkervaring'] ? $k['werkervaring'] : array( array() );
					foreach ( $rows as $i => $w ) {
						echo $werk_item( $i, $w ); // phpcs:ignore
					}
					?>
				</ul>
				<template data-sjabloon="werkervaring"><?php echo $werk_item( '__i__', array() ); // phpcs:ignore ?></template>
				<div><button type="button" class="btn btn--ghost" data-plus="werkervaring">Werkervaring toevoegen</button></div>
				<?php echo $knoppen( 'werkervaring' ); // phpcs:ignore ?>
			</section>

			<section class="stap" id="stap-projecten" aria-labelledby="h-proj">
				<h2 id="h-proj" tabindex="-1"><?php echo esc_html( $a['stap3_kop'] ); ?></h2>
				<p class="lead"><?php echo wp_kses_post( deedni_tekst( $a['stap3_lead'] ) ); ?></p>
				<ul class="item-list" data-lijst="projecten">
					<?php
					$rows = $k['projecten'] ? $k['projecten'] : array( array() );
					foreach ( $rows as $i => $pr ) {
						echo $project_item( $i, $pr ); // phpcs:ignore
					}
					?>
				</ul>
				<template data-sjabloon="projecten"><?php echo $project_item( '__i__', array() ); // phpcs:ignore ?></template>
				<template data-sjabloon="resultaat"><?php echo $res_rij( '__i__', '__j__', array() ); // phpcs:ignore ?></template>
				<div><button type="button" class="btn btn--ghost" data-plus="projecten">Project toevoegen</button></div>

				<fieldset class="card stack-s" data-vaardigheden>
					<legend class="h3">Vaardigheden, tools en certificaten <span class="tag who-sees">Ingelogde bedrijven</span></legend>
					<p class="fine" data-vak-uitleg hidden>We tonen eerst wat bij jouw vakgebied past.</p>
					<div class="chips mt-s">
						<?php foreach ( deedni_filter_terms( 'vaardigheid' ) as $t ) : ?>
							<?php $bij = deedni_vaardigheid_vakgebieden( $t->term_id ); ?>
							<label class="chip" data-vakken="<?php echo esc_attr( $bij ? implode( ',', $bij ) : 'alle' ); ?>"><input type="checkbox" name="tools[]" value="<?php echo (int) $t->term_id; ?>" data-tool="<?php echo esc_attr( $t->name ); ?>"<?php checked( in_array( (int) $t->term_id, $k['tool_ids'], true ) ); ?>><?php echo esc_html( $t->name ); ?></label>
						<?php endforeach; ?>
					</div>
					<div><button type="button" class="link-btn" data-vak-alles hidden>Toon alle vaardigheden</button></div>
					<div class="field mt-s"><label for="p-tools-nieuw">Staat het er niet bij?</label><input id="p-tools-nieuw" name="tools_nieuw" placeholder="Bijvoorbeeld: Rijbewijs C, AutoCAD, Snoeien" aria-describedby="p-tools-nieuw-hint"><span class="hint" id="p-tools-nieuw-hint">Meerdere scheiden met een komma.</span></div>
				</fieldset>
				<?php echo $knoppen( 'projecten' ); // phpcs:ignore ?>
			</section>

			<section class="stap" id="stap-wat-zoek-je" aria-labelledby="h-zoek">
				<h2 id="h-zoek" tabindex="-1"><?php echo esc_html( $a['stap4_kop'] ); ?></h2>
				<p class="lead"><?php echo wp_kses_post( deedni_tekst( $a['stap4_lead'] ) ); ?></p>
				<div class="card stack">
					<div class="fields">
						<?php echo $veld( 'salaris_min', 'Salaris vanaf, per maand', $k['salaris_min'] ? deedni_number( $k['salaris_min'] ) : '', 'jij', array( 'euro' => true, 'live' => 'salaris', 'hint' => 'Bedrijven zien dit afgerond naar beneden op honderdtallen.' ) ); // phpcs:ignore ?>
						<?php echo $veld( 'uren', 'Uren per week', $k['uren'], 'iedereen', array( 'live' => 'uren', 'ph' => '32 tot 40' ) ); // phpcs:ignore ?>
					</div>
					<div class="fields">
						<?php echo $select( 'provincie', 'Provincie', $termen( 'provincie' ), $k['provincie_ids'] ? $k['provincie_ids'][0] : '', 'Kies een provincie' ); // phpcs:ignore ?>
						<?php echo $veld( 'regio', 'Regio in je eigen woorden', $k['regio'], 'iedereen', array( 'live' => 'regio', 'ph' => 'Utrecht, tot 30 km' ) ); // phpcs:ignore ?>
					</div>
					<div class="fields">
						<?php echo $select( 'beschikbaarheid', 'Beschikbaar', deedni_choices( 'beschikbaarheid' ), $k['beschikbaarheid'], 'Kies wanneer' ); // phpcs:ignore ?>
						<?php echo $select( 'zoekstatus', 'Hoe actief zoek je?', deedni_choices( 'zoekstatus' ), $k['zoekstatus'] ); // phpcs:ignore ?>
					</div>
					<fieldset class="stack-s"><legend>Werkplek</legend><div class="chips">
						<?php foreach ( deedni_choices( 'werkvorm' ) as $key => $label ) : ?>
							<label class="chip"><input type="checkbox" name="werkplekken[]" value="<?php echo esc_attr( $key ); ?>"<?php checked( in_array( $key, $k['werkplekken'], true ) ); ?>><?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
					</div></fieldset>
				</div>

				<div class="card stack" id="blokkeerlijst">
					<h3>Blokkeerlijst</h3>
					<p class="fine">Deze bedrijven zien je profiel nooit, ook niet zonder naam. Zet je huidige werkgever hier altijd op. Een website (zoals bedrijf.nl) werkt het best.</p>
					<div class="block-list" data-blocks aria-live="polite" hidden></div>
					<div class="fields blok-toevoegen" data-blok-toevoegen hidden>
						<div class="field"><label for="b-naam">Bedrijf toevoegen</label><input id="b-naam" placeholder="Naam of website" data-blok-input></div>
						<button type="button" class="btn btn--ghost" data-blok-plus>Toevoegen</button>
					</div>
					<div class="field" data-blok-tekst><label for="p-blokkeerlijst">Eén bedrijf per regel</label><textarea id="p-blokkeerlijst" name="blokkeerlijst" rows="4"><?php echo esc_textarea( implode( "\n", $geblokkeerd ) ); ?></textarea></div>
				</div>

				<div class="card stack">
					<h3>Cv <span class="tag tag--ink who-sees">Na jouw ja</span></h3>
					<?php if ( $k['cv'] ) : ?>
						<p class="fine">Je hebt een cv geüpload: <?php $cv_naam = basename( (string) get_attached_file( $k['cv'] ) ); ?><a href="<?php echo esc_url( deedni_cv_url( $k['id'] ) ); ?>" class="midden" data-midden title="<?php echo esc_attr( $cv_naam ); ?>"><?php echo esc_html( $cv_naam ); ?></a></p>
						<label class="check-label"><input type="checkbox" name="cv_verwijderen" value="1"> Cv verwijderen</label>
					<?php endif; ?>
					<div class="field"><label for="p-cv"><?php echo $k['cv'] ? 'Ander cv uploaden' : 'Cv uploaden (optioneel)'; ?></label><input id="p-cv" type="file" name="cv" accept=".pdf,.doc,.docx" aria-describedby="p-cv-hint"><span class="hint" id="p-cv-hint">Pdf of Word, maximaal 5 MB. Alleen bedrijven bij wie jij ja zei kunnen het downloaden.</span></div>
				</div>
				<?php echo $knoppen( 'wat-zoek-je' ); // phpcs:ignore ?>
			</section>

			<section class="stap" id="stap-controleren" aria-labelledby="h-check">
				<h2 id="h-check" tabindex="-1"><?php echo esc_html( $a['stap5_kop'] ); ?></h2>
				<p class="lead"><?php echo wp_kses_post( deedni_tekst( $a['stap5_lead'] ) ); ?></p>
				<ul class="card">
					<li class="check"><?php echo deedni_icon( 'check', 22 ); // phpcs:ignore ?><span>Je naam, werkgevers en contactgegevens zijn verborgen.</span></li>
					<li class="check"><?php echo deedni_icon( 'check', 22 ); // phpcs:ignore ?><span>
						<?php
						$aantal = count( $geblokkeerd );
						echo esc_html( 0 === $aantal ? 'Nog geen bedrijven op je blokkeerlijst.' : ( 1 === $aantal ? 'Eén bedrijf op je blokkeerlijst.' : $aantal . ' bedrijven op je blokkeerlijst.' ) );
						?>
					</span></li>
					<li class="check"><?php echo deedni_icon( 'check', 22 ); // phpcs:ignore ?><span>Elke maand vragen we of je profiel nog klopt. Zo staan er alleen actuele profielen online.</span></li>
				</ul>
				<?php if ( $c['todo'] ) : ?>
					<div class="card stack-s">
						<h3>Nog te doen (<?php echo (int) $c['procent']; ?>% compleet)</h3>
						<ul class="todo-lijst">
							<?php foreach ( $c['todo'] as $t ) : ?>
								<li><a href="#<?php echo esc_attr( $t[1] ); ?>" data-stap-link="<?php echo esc_attr( $t[1] ); ?>"><?php echo esc_html( $t[0] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
				<div class="row">
					<?php if ( $k['zichtbaar'] ) : ?>
						<button type="submit" class="btn btn--primary" name="naar" value="controleren">Wijzigingen opslaan</button>
						<button type="submit" class="btn btn--ghost" name="publiceren" value="0">Profiel offline halen</button>
						<a class="btn btn--ghost" href="<?php echo esc_url( $k['url'] ); ?>">Bekijk je publieke profiel</a>
					<?php else : ?>
						<button type="submit" class="btn btn--primary" name="publiceren" value="1">Profiel publiceren</button>
						<button type="submit" class="btn btn--ghost" name="naar" value="controleren">Alleen opslaan</button>
					<?php endif; ?>
				</div>
				<p class="offer-state<?php echo $k['zichtbaar'] ? ' is-ok' : ''; ?>" role="status">
					<?php echo esc_html( $k['beheer_verborgen'] ? 'Je profiel is tijdelijk verborgen door deedni. Bedrijven kunnen je nu niet vinden.' : ( $k['zichtbaar'] ? 'Je profiel staat online. Bedrijven kunnen je nu vinden, zonder je naam.' : 'Je profiel staat nog niet online. Alleen jij ziet het.' ) ); ?>
				</p>
				<?php echo $knoppen( 'controleren' ); // phpcs:ignore ?>
			</section>
		</form>

		<aside class="night preview sticky-side" aria-labelledby="pv-kop">
			<h2 id="pv-kop" class="h3">Zo zien anderen je</h2>
			<div class="seg" role="group" aria-label="Bekijk als">
				<button type="button" aria-pressed="true" data-als="bedrijf">Bedrijf</button>
				<button type="button" aria-pressed="false" data-als="bezoeker">Bezoeker</button>
			</div>
			<div class="pcard" aria-live="polite">
				<div class="between fine"><strong><?php echo esc_html( $k['code'] ); ?></strong><span data-pv-label>Bedrijf kijkt mee</span></div>
				<div class="t" data-pv="titel"><?php echo esc_html( $k['functietitel'] ? $k['functietitel'] : 'Je functietitel' ); ?></div>
				<p><span class="salaris">vanaf €<span data-pv="salaris"><?php echo esc_html( $k['salaris_min'] ? deedni_number( deedni_salaris_afgerond( $k['salaris_min'] ) ) : '…' ); ?></span></span> <span class="fine">per maand</span></p>
				<dl class="kv">
					<dt>Ervaring</dt><dd><span data-pv="ervaring"><?php echo esc_html( $k['ervaring_jaren'] ? $k['ervaring_jaren'] : '…' ); ?></span> jaar</dd>
					<dt>Regio</dt><dd data-pv="regio"><?php echo esc_html( deedni_regio_tekst( $k ) ? deedni_regio_tekst( $k ) : '…' ); ?></dd>
					<dt>Uren</dt><dd><span data-pv="uren"><?php echo esc_html( $k['uren'] ? $k['uren'] : '…' ); ?></span> per week</dd>
					<dt>Naam</dt><dd><?php echo deedni_redact(); // phpcs:ignore ?></dd>
					<dt>Werkgevers</dt><dd><?php echo deedni_redact( 'l' ); // phpcs:ignore ?></dd>
					<dt>Contact</dt><dd><?php echo deedni_redact( 'l' ); // phpcs:ignore ?></dd>
				</dl>
				<div data-alleen="bedrijf" class="stack-s">
					<div class="stack-s" data-pv-res>
						<?php
						foreach ( $k['projecten'] as $pr ) {
							foreach ( isset( $pr['resultaten'] ) ? (array) $pr['resultaten'] : array() as $r ) {
								echo '<div class="res">' . ( $r['cijfer'] ? '<strong>' . esc_html( $r['cijfer'] ) . '</strong>' : '' ) . esc_html( $r['tekst'] ) . '</div>';
							}
						}
						?>
					</div>
					<div data-pv-tools><?php echo deedni_tools_html( $k['tools'] ); // phpcs:ignore ?></div>
				</div>
				<p class="locked" data-alleen="bezoeker" hidden>Projecten en vaardigheden ziet alleen een ingelogde werkgever. Bezoekers zien deze korte versie.</p>
			</div>
			<p class="fine">Dit blok verandert mee terwijl je typt.</p>
		</aside>
	</div>
</div>
