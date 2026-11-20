<?php
/**
 * Kandidaten zoeken. Eén GET-formulier: werkt zonder JavaScript, met JavaScript ververst de lijst meteen.
 *
 * Variabelen: $a (velden van het blok).
 * Overschrijven? Kopieer dit bestand naar je-thema/deedni/zoeken.php.
 */

defined( 'ABSPATH' ) || exit;

$mag = deedni_can_search() || deedni_in_editor();
?>
<div class="wrap page deedni-zoeken">
	<div class="stack">
		<h1><?php echo esc_html( $a['kop'] ); ?></h1>
		<p class="lead"><?php echo wp_kses_post( deedni_tekst( $a['lead'] ) ); ?></p>
	</div>
<?php
if ( ! $mag ) :
	$ingelogd = is_user_logged_in();
	?>
	<div class="card stack zoeken-dicht">
		<p><?php echo wp_kses_post( deedni_tekst( $a['gesloten'] ) ); ?></p>
		<?php if ( deedni_is_werkgever() ) : ?>
			<?php echo deedni_notice( 'We controleren je bedrijf nog. Je krijgt een mail zodra je kunt zoeken.', 'info' ); // phpcs:ignore ?>
			<div><a class="btn btn--ghost" href="<?php echo esc_url( deedni_page_url( 'profiel' ) ); ?>">Bedrijfsprofiel invullen</a></div>
		<?php elseif ( ! $ingelogd ) : ?>
			<div class="row">
				<?php echo deedni_cta( 'Account maken als werkgever', add_query_arg( 'rol', 'werkgever', deedni_page_url( 'registreren' ) ), 'user' ); // phpcs:ignore ?>
				<a class="btn btn--ghost" href="<?php echo esc_url( deedni_page_url( 'inloggen' ) ); ?>">Inloggen</a>
			</div>
		<?php endif; ?>
	</div>
</div>
	<?php
	return;
endif;

$f   = deedni_search_filters();
$res = deedni_zoek( $f, 10 );
$tel = $res['telling'];
$url = get_permalink() ? get_permalink() : deedni_page_url( 'zoeken' );

$opties = array(
	'spec'  => array( 'titel' => 'Specialisme', 'type' => 'checkbox', 'open' => true, 'items' => array() ),
	'tool'  => array( 'titel' => 'Tools', 'type' => 'checkbox', 'open' => true, 'items' => array() ),
	'erv'   => array( 'titel' => 'Ervaring', 'type' => 'radio', 'open' => true, 'items' => array( '5' => 'Vanaf 5 jaar', '2' => '2 tot 5 jaar', '0' => 'Alle niveaus' ) ),
	'regio' => array( 'titel' => 'Regio', 'type' => 'checkbox', 'open' => true, 'items' => array() ),
	'plek'  => array( 'titel' => 'Werkplek', 'type' => 'checkbox', 'open' => false, 'items' => deedni_choices( 'werkvorm' ) ),
	'besch' => array( 'titel' => 'Beschikbaar binnen', 'type' => 'radio', 'open' => false, 'items' => array( '1' => '1 maand', '3' => '3 maanden', '0' => 'Maakt niet uit' ) ),
);
foreach ( array( 'spec' => 'specialisme', 'tool' => 'vaardigheid', 'regio' => 'provincie' ) as $key => $tax ) {
	foreach ( deedni_filter_terms( $tax ) as $t ) {
		// Alleen termen tonen die profielen hebben, of die al gekozen zijn.
		if ( ! empty( $tel[ $key ][ $t->slug ] ) || in_array( $t->slug, $f[ $key ], true ) ) {
			$opties[ $key ]['items'][ $t->slug ] = $t->name;
		}
	}
}
$opties['regio']['items']['remote'] = 'Remote in heel NL';
if ( count( $opties['tool']['items'] ) > 12 ) {
	// Veel tools: de meest gebruikte eerst.
	$items = $opties['tool']['items'];
	uksort(
		$items,
		function ( $x, $y ) use ( $tel, $f ) {
			$sx = ( in_array( $x, $f['tool'], true ) ? 1000 : 0 ) + ( isset( $tel['tool'][ $x ] ) ? $tel['tool'][ $x ] : 0 );
			$sy = ( in_array( $y, $f['tool'], true ) ? 1000 : 0 ) + ( isset( $tel['tool'][ $y ] ) ? $tel['tool'][ $y ] : 0 );
			return $sy - $sx;
		}
	);
	$opties['tool']['items'] = $items;
}

// Actieve filters bovenin: elke knop is een link zonder dat filter.
$actief = array();
foreach ( $opties as $key => $o ) {
	$waarden = (array) $f[ $key ];
	foreach ( $waarden as $v ) {
		if ( '0' === (string) $v || ! isset( $o['items'][ $v ] ) ) {
			continue;
		}
		$zonder = $f;
		if ( 'radio' === $o['type'] ) {
			$zonder[ $key ] = '0';
		} else {
			$zonder[ $key ] = array_values( array_diff( $f[ $key ], array( $v ) ) );
		}
		$actief[] = array( $o['items'][ $v ], $zonder );
	}
}
$link = function ( $filters ) use ( $url ) {
	$q = array();
	foreach ( array( 'q', 'spec', 'tool', 'regio', 'plek' ) as $k ) {
		if ( ! empty( $filters[ $k ] ) ) {
			$q[ $k ] = $filters[ $k ];
		}
	}
	foreach ( array( 'erv', 'besch' ) as $k ) {
		if ( '0' !== $filters[ $k ] ) {
			$q[ $k ] = $filters[ $k ];
		}
	}
	if ( ! empty( $filters['actief'] ) ) {
		$q['actief'] = 1;
	}
	if ( ! empty( $filters['pagina'] ) && $filters['pagina'] > 1 ) {
		$q['pagina'] = $filters['pagina'];
	}
	return $q ? $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $q ) : $url;
};
?>
	<form class="search" id="dn-zoek" role="search" method="get" action="<?php echo esc_url( $url ); ?>" data-zoek-form>
			<label><?php echo deedni_icon( 'search', 22 ); // phpcs:ignore ?><span class="visually-hidden">Zoek op functie, tool of branche</span><input name="q" type="search" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="Zoek op functie, tool of branche"></label>
			<button type="submit" class="btn btn--primary btn--small">Zoeken</button>
	</form>

		<div class="split split--left">
			<aside class="stack-s" aria-labelledby="h-filters">
				<div class="between"><h2 id="h-filters" class="h3">Filters</h2><a class="link-btn" href="<?php echo esc_url( $url ); ?>" data-wis>Wis alles</a></div>
				<button type="button" class="btn btn--ghost filter-toggle" aria-expanded="false" aria-controls="filters-box" data-filter-toggle>Toon filters</button>
				<div class="filters filters-box" id="filters-box">
					<?php foreach ( $opties as $key => $o ) : ?>
						<?php
						if ( ! $o['items'] ) {
							continue;
						}
						$gekozen = (array) $f[ $key ];
						$open    = $o['open'] || array_filter( $gekozen, function ( $v ) { return '0' !== (string) $v; } );
						?>
						<details<?php echo $open ? ' open' : ''; ?>><summary><?php echo esc_html( $o['titel'] ); ?></summary><div class="opts">
							<?php foreach ( $o['items'] as $v => $label ) : ?>
								<?php
								$n = isset( $tel[ $key ][ $v ] ) ? (int) $tel[ $key ][ $v ] : 0;
								$name = 'checkbox' === $o['type'] ? $key . '[]' : $key;
								?>
								<label><input form="dn-zoek" type="<?php echo esc_attr( $o['type'] ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $v ); ?>"<?php checked( in_array( (string) $v, array_map( 'strval', $gekozen ), true ) ); ?>><?php echo esc_html( $label ); ?><span class="n"><?php echo (int) $n; ?></span></label>
							<?php endforeach; ?>
						</div></details>
					<?php endforeach; ?>
					<label class="check-label mt-s"><input form="dn-zoek" type="checkbox" name="actief" value="1"<?php checked( $f['actief'] ); ?>> Alleen wie actief zoekt</label>
					<noscript><button form="dn-zoek" type="submit" class="btn btn--primary mt-s">Filters toepassen</button></noscript>
				</div>
			</aside>

			<section class="stack" aria-labelledby="h-res" data-zoek-resultaten>
				<?php if ( $actief ) : ?>
					<div class="active-filters" aria-label="Actieve filters">
						<?php foreach ( $actief as $af ) : ?>
							<a href="<?php echo esc_url( $link( $af[1] ) ); ?>" aria-label="<?php echo esc_attr( 'Filter ' . $af[0] . ' weghalen' ); ?>"><?php echo esc_html( $af[0] ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<div class="result-head">
					<h2 id="h-res" class="h4" aria-live="polite"><?php echo (int) $res['totaal']; ?> van <?php echo (int) $res['alle']; ?> profielen</h2>
					<span class="fine"><?php echo deedni_bedrijf_voorkeuren_tekst( deedni_bedrijf( deedni_profile_id( get_current_user_id(), 'bedrijf' ) ) ) ? 'Gesorteerd op beste match met je bedrijfsprofiel' : 'Vul "Wie je zoekt" in je bedrijfsprofiel in voor een matchpercentage'; ?></span>
				</div>
				<?php if ( $res['items'] ) : ?>
					<div class="cands">
						<?php
						foreach ( $res['items'] as $k ) {
							echo deedni_kandidaat_kaart( $k ); // phpcs:ignore
						}
						?>
					</div>
					<?php if ( $res['paginas'] > 1 ) : ?>
						<nav class="pager" aria-label="Pagina's">
							<?php for ( $p = 1; $p <= $res['paginas']; $p++ ) : ?>
								<?php $pf = $f; $pf['pagina'] = $p; ?>
								<a href="<?php echo esc_url( $link( $pf ) ); ?>"<?php echo $p === $res['pagina'] ? ' aria-current="page"' : ''; ?>><?php echo (int) $p; ?></a>
							<?php endfor; ?>
						</nav>
					<?php endif; ?>
				<?php else : ?>
					<div class="card empty"><h3><?php echo $res['alle'] ? 'Geen profielen met deze filters' : 'Nog geen profielen online'; ?></h3><p class="fine"><?php echo $res['alle'] ? 'Haal een filter weg of zoek op een andere tool.' : 'Zodra marketeers hun profiel publiceren, zie je ze hier.'; ?></p></div>
				<?php endif; ?>
			</section>
		</div>
</div>
