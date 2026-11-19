<?php
/**
 * Het profiel van één kandidaat. Verborgen gegevens (naam, werkgevers, klanten, contact) staan alleen
 * in de HTML voor wie ze mag zien: de kandidaat zelf, een beheerder of een bedrijf waar de kandidaat ja op zei.
 *
 * Variabelen: $a (velden van het blok), $k (deedni_kandidaat()).
 * Overschrijven? Kopieer dit bestand naar je-thema/deedni/kandidaat.php.
 */

defined( 'ABSPATH' ) || exit;

$eigen    = is_user_logged_in() && (int) $k['user_id'] === get_current_user_id();
$toegang  = deedni_has_contact_access( $k['id'] ) || deedni_in_editor();
$werkgever = deedni_is_werkgever() && ! $eigen;
$match    = $werkgever ? deedni_match_huidig( $k['id'] ) : array( 'ingevuld' => false );
$bedrijf  = $werkgever ? deedni_bedrijf( deedni_profile_id( get_current_user_id(), 'bedrijf' ) ) : null;
$gedeeld_standaard = $toegang && $werkgever;

/** Verborgen waarde: balk, en voor wie mag ook de echte waarde (getoond in de gedeelde weergave). */
$v = function ( $waarde, $maat = '' ) use ( $toegang ) {
	$h = '<span class="v-pub">' . deedni_redact( $maat ) . '</span>';
	if ( $toegang ) {
		$h .= '<span class="v-shared">' . ( '' !== (string) $waarde ? esc_html( $waarde ) : '<span class="fine">niet ingevuld</span>' ) . '</span>';
	}
	return $h;
};

$opzeg = deedni_label( 'beschikbaarheid', $k['beschikbaarheid'] );
$terug = deedni_is_werkgever() ? deedni_page_url( 'zoeken' ) : deedni_page_url( $eigen ? 'profiel' : 'dashboard' );
$bestaand = $werkgever ? deedni_offers_between( $k['id'], get_current_user_id() ) : array();
$aanbod   = $bestaand ? deedni_aanbod( $bestaand[0] ) : null;
?>
<div class="wrap page deedni-kandidaat<?php echo $gedeeld_standaard ? ' is-shared' : ''; ?>" data-k-profiel>
	<?php echo deedni_melding_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<div class="topbar">
		<a class="crumb" href="<?php echo esc_url( $terug ); ?>"><?php echo esc_html( $eigen ? '← Terug naar mijn profiel' : $a['terug'] ); ?></a>
		<?php if ( $toegang ) : ?>
			<div class="seg" role="group" aria-label="Weergave van dit profiel">
				<button type="button" aria-pressed="<?php echo $gedeeld_standaard ? 'false' : 'true'; ?>" data-view="pub">Zo ziet iedereen het</button>
				<button type="button" aria-pressed="<?php echo $gedeeld_standaard ? 'true' : 'false'; ?>" data-view="shared"><?php echo $eigen ? 'Na jouw ja' : 'Na jouw aanbod en een ja'; ?></button>
			</div>
		<?php endif; ?>
	</div>
	<p class="view-note" role="status">
		<span class="v-pub"><?php echo esc_html( $eigen ? 'Zo zien bedrijven je profiel. De zwarte balken blijven verborgen tot jij ja zegt op een aanbod.' : $a['uitleg_pub'] ); ?></span>
		<?php if ( $toegang ) : ?>
			<span class="v-shared v-plain"><?php echo esc_html( $eigen ? 'Zo ziet een bedrijf je profiel nadat jij ja zei op zijn aanbod.' : $a['uitleg_ja'] ); ?></span>
		<?php endif; ?>
	</p>

	<div class="split">
		<article>
			<section class="hero-k" aria-labelledby="h-k">
				<div class="row">
					<strong><?php echo esc_html( $k['code'] ); ?></strong>
					<span class="tag <?php echo 'actief' === $k['zoekstatus'] ? 'tag--ok' : 'tag--accent'; ?>"><?php echo esc_html( deedni_label( 'zoekstatus', $k['zoekstatus'] ) ); ?></span>
					<?php if ( ! empty( $match['ingevuld'] ) ) : ?>
						<span class="match-tag"><?php echo (int) $match['procent']; ?>% match<?php echo $bedrijf && $bedrijf['naam'] ? ' met ' . esc_html( $bedrijf['naam'] ) : ''; ?></span>
					<?php endif; ?>
					<?php if ( $k['bevestigd_op'] ) : ?>
						<span class="fine">Bevestigd <?php echo esc_html( deedni_geleden( $k['bevestigd_op'] ) ); ?></span>
					<?php endif; ?>
					<?php if ( $eigen && ! $k['zichtbaar'] ) : ?>
						<span class="tag">Nog niet online</span>
					<?php endif; ?>
				</div>
				<h1 id="h-k"><?php echo esc_html( $k['functietitel'] ? $k['functietitel'] : 'Online marketeer' ); ?></h1>
				<p class="row">Naam <?php echo $v( deedni_naam( $k ), 'l' ); // phpcs:ignore ?></p>
				<dl class="facts">
					<div><dt>Ervaring</dt><dd><?php echo esc_html( $k['ervaring_jaren'] ? $k['ervaring_jaren'] . ' jaar' : '–' ); ?></dd></div>
					<div><dt>Regio</dt><dd><?php echo esc_html( deedni_regio_tekst( $k ) ? deedni_regio_tekst( $k ) : '–' ); ?></dd></div>
					<div><dt>Werkplek</dt><dd><?php echo esc_html( deedni_werkplek_tekst( $k['werkplekken'] ) ? deedni_werkplek_tekst( $k['werkplekken'] ) : '–' ); ?></dd></div>
					<div><dt>Beschikbaar</dt><dd><?php echo esc_html( $opzeg ? $opzeg : '–' ); ?></dd></div>
				</dl>
			</section>

			<?php if ( $k['samenvatting'] || $k['tools'] ) : ?>
				<section class="blok" aria-labelledby="h-over">
					<h2 id="h-over">Over mij</h2>
					<?php if ( $k['samenvatting'] ) : ?>
						<div class="lees"><?php echo wp_kses_post( wpautop( esc_html( $k['samenvatting'] ) ) ); ?></div>
					<?php endif; ?>
					<?php echo deedni_tools_html( $k['tools'] ); // phpcs:ignore ?>
				</section>
			<?php endif; ?>

			<?php if ( $k['werkervaring'] ) : ?>
				<section class="blok" aria-labelledby="h-werk">
					<div class="between"><h2 id="h-werk">Werkervaring</h2><span class="fine"><?php echo count( $k['werkervaring'] ); ?> <?php echo 1 === count( $k['werkervaring'] ) ? 'functie' : 'functies'; ?></span></div>
					<?php foreach ( $k['werkervaring'] as $w ) : ?>
						<?php $w = wp_parse_args( $w, array( 'functie' => '', 'werkgever' => '', 'soort' => '', 'periode' => '', 'tekst' => '' ) ); ?>
						<div class="job">
							<span class="when"><?php echo esc_html( $w['periode'] ); ?></span>
							<div class="stack-s">
								<span class="fine">Werkgever <?php echo $v( $w['werkgever'] ); // phpcs:ignore ?></span>
								<strong><?php echo esc_html( $w['functie'] ); ?></strong>
								<?php if ( $w['soort'] ) : ?><span class="fine"><?php echo esc_html( $w['soort'] ); ?></span><?php endif; ?>
								<?php if ( $w['tekst'] ) : ?><p><?php echo esc_html( $w['tekst'] ); ?></p><?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>

			<?php if ( $k['projecten'] ) : ?>
				<section class="blok" aria-labelledby="h-cases">
					<div class="between"><h2 id="h-cases">Cases</h2><span class="fine">Resultaten zijn altijd zichtbaar</span></div>
					<div class="cases">
						<?php foreach ( $k['projecten'] as $pr ) : ?>
							<?php
							$pr     = wp_parse_args( $pr, array( 'titel' => '', 'klant' => '', 'rol' => '', 'branche' => '', 'tools' => '', 'aanpak' => '', 'link' => '', 'resultaten' => array() ) );
							$res    = array_values( (array) $pr['resultaten'] );
							$eerste = $res ? array_shift( $res ) : null;
							?>
							<div class="card case stack-s">
								<?php if ( $eerste && $eerste['cijfer'] ) : ?><div class="big"><?php echo esc_html( $eerste['cijfer'] ); ?></div><?php endif; ?>
								<p><strong><?php echo esc_html( $pr['titel'] ); ?></strong><?php echo $eerste && $eerste['tekst'] ? '<br>' . esc_html( $eerste['tekst'] ) : ''; ?></p>
								<?php foreach ( $res as $r ) : ?>
									<p class="fine"><?php echo $r['cijfer'] ? '<strong>' . esc_html( $r['cijfer'] ) . '</strong> ' : ''; ?><?php echo esc_html( $r['tekst'] ); ?></p>
								<?php endforeach; ?>
								<dl class="kv">
									<?php if ( $pr['rol'] ) : ?><dt>Rol</dt><dd><?php echo esc_html( $pr['rol'] ); ?></dd><?php endif; ?>
									<?php if ( $pr['branche'] ) : ?><dt>Branche</dt><dd><?php echo esc_html( $pr['branche'] ); ?></dd><?php endif; ?>
									<dt>Klant</dt><dd><?php echo $v( $pr['klant'], 's' ); // phpcs:ignore ?></dd>
								</dl>
								<?php if ( $pr['aanpak'] ) : ?><p class="fine"><?php echo esc_html( $pr['aanpak'] ); ?></p><?php endif; ?>
								<?php echo deedni_tools_html( $pr['tools'] ); // phpcs:ignore ?>
								<?php if ( $toegang && $pr['link'] ) : ?><a class="v-shared" href="<?php echo esc_url( $pr['link'] ); ?>" rel="noopener nofollow" target="_blank">Bekijk het werk</a><?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $k['opleiding'] ) : ?>
				<section class="blok" aria-labelledby="h-opl">
					<h2 id="h-opl">Opleiding en certificaten</h2>
					<ul class="stack-s">
						<?php foreach ( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n|;/', $k['opleiding'] ) ) ) as $regel ) : ?>
							<li><?php echo esc_html( $regel ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<section class="blok blok--laatst" aria-labelledby="h-contact">
				<div class="between"><h2 id="h-contact">Contactgegevens</h2><span class="tag tag--ink">Alleen na ja</span></div>
				<dl class="contact-kv">
					<dt>Naam</dt><dd><?php echo $v( deedni_naam( $k ) ); // phpcs:ignore ?></dd>
					<dt>E-mail</dt><dd><span class="v-pub"><?php echo deedni_redact( 'l' ); // phpcs:ignore ?></span><?php if ( $toegang ) : ?><span class="v-shared"><a href="mailto:<?php echo esc_attr( $k['email'] ); ?>"><?php echo esc_html( $k['email'] ); ?></a></span><?php endif; ?></dd>
					<dt>Telefoon</dt><dd><?php echo $v( $k['telefoon'] ); // phpcs:ignore ?></dd>
					<dt>LinkedIn</dt><dd><span class="v-pub"><?php echo deedni_redact( 'l' ); // phpcs:ignore ?></span><?php if ( $toegang ) : ?><span class="v-shared"><?php echo $k['linkedin'] ? '<a href="' . esc_url( $k['linkedin'] ) . '" rel="noopener nofollow" target="_blank">' . esc_html( preg_replace( '#^https?://(www\.)?#', '', $k['linkedin'] ) ) . '</a>' : '<span class="fine">niet ingevuld</span>'; // phpcs:ignore ?></span><?php endif; ?></dd>
					<?php if ( $toegang && $k['cv'] && ! deedni_in_editor() ) : ?>
						<dt class="v-shared">Cv</dt><dd class="v-shared"><a href="<?php echo esc_url( deedni_cv_url( $k['id'] ) ); ?>">Download cv</a></dd>
					<?php endif; ?>
				</dl>
			</section>
		</article>

		<aside class="sticky-side stack">
			<div class="card side-card">
				<?php if ( $k['salaris_min'] ) : ?>
					<div><div class="fine">Salarisindicatie van de kandidaat</div><span class="salaris">vanaf <?php echo esc_html( deedni_euro( deedni_salaris_afgerond( $k['salaris_min'] ) ) ); ?></span> <span class="fine">per maand, bruto</span></div>
				<?php endif; ?>
				<?php if ( $eigen ) : ?>
					<p class="fine">Dit is je eigen profiel. Bedrijven zien het zonder de zwarte balken pas na jouw ja.</p>
					<a class="btn btn--primary" href="<?php echo esc_url( deedni_page_url( 'profiel' ) ); ?>">Profiel bewerken</a>
				<?php elseif ( deedni_can_search() ) : ?>
					<?php if ( $aanbod ) : ?>
						<p class="fine">Je stuurde <?php echo esc_html( deedni_geleden( $aanbod['datum'] ) ); ?> een aanbod: <?php echo deedni_status_tag( $aanbod['status'], $aanbod['akkoord'] ); // phpcs:ignore ?></p>
						<?php if ( 'tegenvoorstel' === $aanbod['status'] ) : ?>
							<?php echo deedni_aanbod_knop( $k, 'Aanbod aanpassen', 'btn btn--primary', $aanbod ); // phpcs:ignore ?>
						<?php endif; ?>
						<a class="btn btn--ghost" href="<?php echo esc_url( deedni_page_url( 'dashboard' ) . '#verstuurd' ); ?>">Bekijk op je overzicht</a>
					<?php else : ?>
						<p class="fine">Jij biedt een eigen bedrag, daarna beslist de kandidaat.</p>
						<?php echo deedni_aanbod_knop( $k, 'Aanbod sturen', 'btn btn--primary' ); // phpcs:ignore ?>
					<?php endif; ?>
					<?php echo deedni_bewaar_knop( $k['id'], 'tekst' ); // phpcs:ignore ?>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $match['ingevuld'] ) && $match['redenen'] ) : ?>
				<div class="card stack-s">
					<div class="between"><h3 class="why-kop">Waarom deze match</h3><span class="match-tag"><?php echo (int) $match['procent']; ?>%</span></div>
					<ul class="why">
						<?php foreach ( $match['redenen'] as $r ) : ?>
							<li<?php echo $r[1] ? '' : ' class="mis"'; ?>><?php echo esc_html( $r[0] ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="fine">Berekend op je bedrijfsprofiel en wie je zoekt.</p>
				</div>
			<?php endif; ?>

			<div class="card share-list">
				<h3>Wat deelt de kandidaat na een ja</h3>
				<p class="fine">Nu al zichtbaar</p>
				<ul>
					<?php foreach ( array( 'Functie, ervaring, regio en werkplek', 'Tools, opleiding en certificaten', 'Resultaten van de cases', 'Werkervaring zonder werkgever', 'Salarisindicatie, afgerond' ) as $t ) : ?>
						<li><span class="ok-ico"><?php echo deedni_icon( 'check', 16 ); // phpcs:ignore ?></span><?php echo esc_html( $t ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p class="fine">Pas na een ja</p>
				<ul>
					<?php foreach ( array( 'Voor- en achternaam', 'Namen van werkgevers', 'Projectnamen en klanten', 'Links naar werk', 'E-mail, telefoon en LinkedIn' ) as $t ) : ?>
						<li><span class="redact redact--ico" aria-hidden="true"></span><?php echo esc_html( $t ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</aside>
	</div>
</div>
