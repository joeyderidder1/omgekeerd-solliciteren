<?php
/**
 * Bedrijfsprofiel van een werkgever, met "Wie je zoekt" voor het matchpercentage.
 *
 * Variabelen: $a (velden van het blok).
 * Overschrijven? Kopieer dit bestand naar je-thema/deedni/bedrijfsprofiel.php.
 */

defined( 'ABSPATH' ) || exit;

if ( deedni_in_editor() && ! deedni_is_werkgever() ) {
	$ids = get_posts( array( 'post_type' => 'bedrijf', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	$id  = $ids ? (int) $ids[0] : 0;
} else {
	$id = deedni_ensure_profile( get_current_user_id(), 'bedrijf' );
}
$b = $id ? deedni_bedrijf( $id ) : null;
if ( ! $b ) {
	echo '<div class="wrap page"><p class="deedni-editor-leeg">Nog geen werkgevers. Maak een account als werkgever om dit blok te zien.</p></div>';
	return;
}

$veld = function ( $name, $label, $value, $extra = array() ) {
	$e = wp_parse_args( $extra, array( 'type' => 'text', 'hint' => '', 'ph' => '', 'textarea' => false, 'required' => false, 'euro' => false, 'auto' => '' ) );
	$id   = 'b-' . $name;
	$attr = ' id="' . $id . '" name="' . esc_attr( $name ) . '"' . ( $e['ph'] ? ' placeholder="' . esc_attr( $e['ph'] ) . '"' : '' ) . ( $e['required'] ? ' required' : '' ) . ( $e['auto'] ? ' autocomplete="' . esc_attr( $e['auto'] ) . '"' : '' ) . ( $e['hint'] ? ' aria-describedby="' . $id . '-hint"' : '' );
	$h    = '<div class="field"><label for="' . $id . '">' . esc_html( $label ) . '</label>';
	if ( $e['textarea'] ) {
		$h .= '<textarea' . $attr . '>' . esc_textarea( $value ) . '</textarea>';
	} else {
		$in = '<input type="' . esc_attr( $e['type'] ) . '"' . $attr . ' value="' . esc_attr( $value ) . '"' . ( $e['euro'] ? ' inputmode="numeric"' : '' ) . '>';
		$h .= $e['euro'] ? '<div class="euro">' . $in . '</div>' : $in;
	}
	if ( $e['hint'] ) {
		$h .= '<span class="hint" id="' . $id . '-hint">' . esc_html( $e['hint'] ) . '</span>';
	}
	return $h . '</div>';
};
$chips = function ( $name, $opties, $gekozen ) {
	$h = '<div class="chips mt-s">';
	foreach ( $opties as $v => $l ) {
		$h .= '<label class="chip"><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $v ) . '"' . checked( in_array( $v, (array) $gekozen ), true, false ) . '>' . esc_html( $l ) . '</label>'; // phpcs:ignore WordPress.PHP.StrictInArray
	}
	return $h . '</div>';
};
$termen = function ( $tax ) {
	$o = array();
	foreach ( deedni_filter_terms( $tax ) as $t ) {
		$o[ (int) $t->term_id ] = $t->name;
	}
	return $o;
};
?>
<div class="wrap page deedni-bedrijfsprofiel">
	<?php echo deedni_melding_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<div class="page-head stack-s">
		<h1><?php echo esc_html( $a['w_kop'] ); ?></h1>
		<p class="lead"><?php echo wp_kses_post( deedni_tekst( $a['w_lead'] ) ); ?></p>
		<?php if ( ! $b['goedgekeurd'] && ! deedni_in_editor() ) : ?>
			<?php echo deedni_notice( 'We controleren je bedrijf nog. Zodra je account is goedgekeurd, kun je kandidaten zoeken. Je krijgt daarover een mail.', 'info' ); // phpcs:ignore ?>
		<?php endif; ?>
	</div>

	<form class="stack" method="post" enctype="multipart/form-data" action="<?php echo esc_url( deedni_page_url( 'profiel' ) ); ?>">
		<input type="hidden" name="deedni_action" value="bedrijf">
		<?php echo deedni_nonce_field( 'deedni_bedrijf' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input type="hidden" name="zoek_aanwezig" value="1">
		<?php echo deedni_fouten_html( 'bedrijf' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="bedrijf">
			<section class="card stack" aria-labelledby="h-gegevens">
				<div class="row"><?php echo deedni_logo_html( $b, 'logo-blok' ); // phpcs:ignore ?><div><h2 id="h-gegevens" class="h3">Bedrijfsgegevens</h2><div class="fine">Kandidaten zien dit bij elk aanbod</div></div></div>
				<?php echo $veld( 'bedrijfsnaam', 'Naam van je bedrijf', $b['naam'], array( 'required' => true, 'auto' => 'organization' ) ); // phpcs:ignore ?>
				<div class="fields">
					<?php echo $veld( 'soort', 'Soort bedrijf', $b['soort'], array( 'ph' => 'Hoveniersbedrijf, accountantskantoor, webbureau' ) ); // phpcs:ignore ?>
					<?php echo $veld( 'plaats', 'Plaats', $b['plaats'] ); // phpcs:ignore ?>
				</div>
				<div class="fields">
					<?php echo $veld( 'website', 'Website', $b['website'], array( 'type' => 'url', 'ph' => 'https://' ) ); // phpcs:ignore ?>
					<?php echo $veld( 'medewerkers', 'Aantal medewerkers', $b['medewerkers'], array( 'ph' => '25 tot 50' ) ); // phpcs:ignore ?>
				</div>
				<div class="fields">
					<?php echo $veld( 'kvk_nummer', 'KvK-nummer', $b['kvk_nummer'], array( 'hint' => 'Hiermee controleren we je bedrijf.' ) ); // phpcs:ignore ?>
					<?php echo $veld( 'contactpersoon', 'Contactpersoon', $b['contactpersoon'], array( 'auto' => 'name' ) ); // phpcs:ignore ?>
				</div>
				<?php echo $veld( 'telefoon', 'Telefoon', $b['telefoon'], array( 'type' => 'tel', 'auto' => 'tel' ) ); // phpcs:ignore ?>
				<?php echo $veld( 'omschrijving', 'Over het bedrijf', $b['omschrijving'], array( 'textarea' => true, 'hint' => 'Twee à drie zinnen: wat jullie doen en hoe het is om er te werken.' ) ); // phpcs:ignore ?>
				<div class="field"><label for="b-logo">Logo (optioneel)</label><input id="b-logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" aria-describedby="b-logo-hint"><span class="hint" id="b-logo-hint">Jpg, png of webp, maximaal 2 MB.</span></div>
				<?php if ( $b['logo'] ) : ?>
					<label class="check-label"><input type="checkbox" name="logo_verwijderen" value="1"> Logo verwijderen</label>
				<?php endif; ?>
				<dl class="kv"><dt>KvK</dt><dd><?php echo $b['goedgekeurd'] ? 'Gecontroleerd <span class="tag tag--ok">Goedgekeurd</span>' : '<span class="tag tag--accent">Wacht op controle</span>'; // phpcs:ignore ?></dd></dl>
			</section>

			<section class="card stack" id="wie-je-zoekt" aria-labelledby="h-zoekt">
				<div><h2 id="h-zoekt" class="h3">Wie je zoekt</h2><p class="fine">Hierop berekenen we je matches. Laat leeg wat niet uitmaakt.</p></div>
				<fieldset class="stack-s"><legend>Vakgebied en functie</legend>
					<p class="fine">Kies een heel vakgebied, of open het en kies de functies die je zoekt.</p>
					<?php
					$gekozen_vak = deedni_vakgebieden_van( $b['zoek_specialisme'] );
					foreach ( deedni_vakgebieden_boom() as $rij ) :
						$opties_vak = array( $rij[0]->term_id => 'Alle functies in ' . mb_strtolower( $rij[0]->name ) );
						foreach ( $rij[1] as $t ) {
							$opties_vak[ $t->term_id ] = $t->name;
						}
						?>
						<details class="vak-groep"<?php echo in_array( (int) $rij[0]->term_id, $gekozen_vak, true ) ? ' open' : ''; ?>><summary><?php echo esc_html( $rij[0]->name ); ?></summary><?php echo $chips( 'zoek_specialisme', $opties_vak, $b['zoek_specialisme'] ); // phpcs:ignore ?></details>
					<?php endforeach; ?>
				</fieldset>
				<fieldset class="stack-s"><legend>Vaardigheden, tools en certificaten</legend>
					<?php
					$relevant = array();
					$overig   = array();
					foreach ( deedni_filter_terms( 'vaardigheid' ) as $t ) {
						$bij = deedni_vaardigheid_vakgebieden( $t->term_id );
						if ( ! $gekozen_vak || ! $bij || array_intersect( $bij, $gekozen_vak ) || in_array( (int) $t->term_id, $b['zoek_tools'], true ) ) {
							$relevant[ $t->term_id ] = $t->name;
						} else {
							$overig[ $t->term_id ] = $t->name;
						}
					}
					echo $gekozen_vak ? '<p class="fine">Passend bij de vakgebieden die je zoekt. Sla op na het kiezen van een vakgebied om de lijst bij te werken.</p>' : ''; // phpcs:ignore
					echo $chips( 'zoek_tools', $relevant, $b['zoek_tools'] ); // phpcs:ignore
					if ( $overig ) {
						echo '<details class="vak-groep"><summary>Meer vaardigheden (' . count( $overig ) . ')</summary>' . $chips( 'zoek_tools', $overig, $b['zoek_tools'] ) . '</details>'; // phpcs:ignore
					}
					?>
				</fieldset>
				<fieldset><legend>Salarisbudget per maand</legend><div class="fields mt-s">
					<?php echo $veld( 'zoek_salaris_min', 'Van', $b['zoek_salaris_min'] ? deedni_number( $b['zoek_salaris_min'] ) : '', array( 'euro' => true ) ); // phpcs:ignore ?>
					<?php echo $veld( 'zoek_salaris_max', 'Tot', $b['zoek_salaris_max'] ? deedni_number( $b['zoek_salaris_max'] ) : '', array( 'euro' => true ) ); // phpcs:ignore ?>
				</div></fieldset>
				<fieldset><legend>Regio</legend><?php echo $chips( 'zoek_provincie', $termen( 'provincie' ), $b['zoek_provincie'] ); // phpcs:ignore ?></fieldset>
				<fieldset><legend>Werkplek</legend><?php echo $chips( 'zoek_werkplek', deedni_choices( 'werkvorm' ), $b['zoek_werkplek'] ); // phpcs:ignore ?></fieldset>
				<?php echo $veld( 'zoek_uren', 'Uren per week', $b['zoek_uren'], array( 'ph' => '32 tot 40' ) ); // phpcs:ignore ?>
			</section>
		</div>
		<div class="row"><button type="submit" class="btn btn--primary">Opslaan</button><a class="btn btn--ghost" href="<?php echo esc_url( deedni_page_url( 'dashboard' ) ); ?>">Naar overzicht</a></div>
	</form>
</div>
