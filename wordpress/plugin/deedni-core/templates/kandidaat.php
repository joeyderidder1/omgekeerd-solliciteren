<?php
/**
 * Een kandidaatprofiel. Overschrijven in je thema: /deedni/kandidaat.php
 *
 * @var int        $id          Kandidaat-ID.
 * @var bool       $own         De kandidaat bekijkt het eigen profiel.
 * @var bool       $has_contact De bezoeker mag naam, contact en cv zien.
 * @var bool       $can_offer   De bezoeker mag een aanbod sturen.
 * @var int        $open_offer  Een openstaand aanbod van deze bezoeker, of 0.
 * @var array|null $flash       Fouten en waarden van het aanbodformulier.
 */

defined( 'ABSPATH' ) || exit;

$jaren        = (int) deedni_field( $id, 'ervaring_jaren' );
$regio        = deedni_field( $id, 'regio' );
$provincie    = implode( ', ', deedni_term_names( $id, 'provincie' ) );
$specialismen = deedni_term_names( $id, 'specialisme' );
$tools        = deedni_term_names( $id, 'vaardigheid' );
$status       = deedni_field( $id, 'zoekstatus' );
?>
<div class="dn-profile">
	<?php if ( $own ) : ?>
		<?php echo deedni_notice( 'Zo zien bedrijven je profiel. Je naam en contactgegevens zijn verborgen tot jij een aanbod accepteert. <a href="' . esc_url( deedni_page_url( 'profiel' ) ) . '">Profiel bewerken</a>' ); // phpcs:ignore ?>
	<?php endif; ?>

	<article class="card dn-profile__main">
		<div class="card-top">
			<span>Kandidaat <?php echo esc_html( deedni_code( $id ) ); ?></span>
			<span class="badge <?php echo 'actief' === $status ? 'badge--ok' : 'badge--soft'; ?>"><?php echo esc_html( deedni_label( 'zoekstatus', $status ) ? deedni_label( 'zoekstatus', $status ) : 'Staat open' ); ?></span>
		</div>
		<div>
			<h1 class="dn-profile__title"><?php echo esc_html( deedni_field( $id, 'functietitel' ) ); ?></h1>
			<p class="card-sub"><?php echo esc_html( implode( ' · ', array_filter( array( $jaren ? $jaren . ' jaar ervaring' : '', $regio ? $regio : $provincie, deedni_label( 'werkvorm', deedni_field( $id, 'werkvorm' ) ) ) ) ) ); ?></p>
		</div>

		<div class="facts dn-facts-4">
			<div class="fact"><small>Salaris vanaf p/m</small><strong><?php echo deedni_field( $id, 'salaris_min' ) ? esc_html( deedni_euro( deedni_field( $id, 'salaris_min' ) ) ) : 'Open'; ?></strong></div>
			<div class="fact"><small>Uren</small><strong><?php echo esc_html( deedni_label( 'uren', deedni_field( $id, 'uren' ) ) ? deedni_label( 'uren', deedni_field( $id, 'uren' ) ) : '–' ); ?></strong></div>
			<div class="fact"><small>Beschikbaar</small><strong><?php echo esc_html( deedni_label( 'beschikbaarheid', deedni_field( $id, 'beschikbaarheid' ) ) ? deedni_label( 'beschikbaarheid', deedni_field( $id, 'beschikbaarheid' ) ) : '–' ); ?></strong></div>
			<div class="fact"><small>Provincie</small><strong><?php echo esc_html( $provincie ? $provincie : '–' ); ?></strong></div>
		</div>

		<?php if ( $specialismen ) : ?>
			<div class="dn-block">
				<h2>Specialisme</h2>
				<div class="tags"><?php foreach ( $specialismen as $t ) { echo '<span class="tag">' . esc_html( $t ) . '</span>'; } ?></div>
			</div>
		<?php endif; ?>
		<?php if ( $tools ) : ?>
			<div class="dn-block">
				<h2>Tools en vaardigheden</h2>
				<div class="tags"><?php foreach ( $tools as $t ) { echo '<span class="tag">' . esc_html( $t ) . '</span>'; } ?></div>
			</div>
		<?php endif; ?>
		<?php if ( deedni_field( $id, 'samenvatting' ) ) : ?>
			<div class="dn-block">
				<h2>Introductie</h2>
				<?php echo wpautop( esc_html( deedni_field( $id, 'samenvatting' ) ) ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
		<?php if ( deedni_field( $id, 'wensen' ) ) : ?>
			<div class="dn-block">
				<h2>Wat ik zoek in een werkgever</h2>
				<?php echo wpautop( esc_html( deedni_field( $id, 'wensen' ) ) ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
		<?php if ( deedni_field( $id, 'soort_bedrijven' ) || deedni_field( $id, 'certificaten' ) ) : ?>
			<dl class="dn-dl">
				<?php if ( deedni_field( $id, 'soort_bedrijven' ) ) : ?>
					<dt>Ervaring bij</dt><dd><?php echo esc_html( deedni_field( $id, 'soort_bedrijven' ) ); ?></dd>
				<?php endif; ?>
				<?php if ( deedni_field( $id, 'certificaten' ) ) : ?>
					<dt>Certificaten</dt><dd><?php echo esc_html( deedni_field( $id, 'certificaten' ) ); ?></dd>
				<?php endif; ?>
			</dl>
		<?php endif; ?>
	</article>

	<aside class="dn-profile__side">
		<?php if ( $has_contact && ! $own ) : ?>
			<?php echo deedni_template( 'contact', array( 'id' => $id ) ); // phpcs:ignore ?>
		<?php else : ?>
			<div class="locked dn-locked" aria-label="Verborgen tot de kandidaat ja zegt">
				<p class="dn-locked__title">Pas zichtbaar na een ja</p>
				<div><span>Naam</span><i></i></div>
				<div><span>Cv en contactgegevens</span><i></i></div>
				<div><span>Huidige werkgever</span><i></i></div>
				<div><span>LinkedIn</span><i></i></div>
			</div>
		<?php endif; ?>

		<?php if ( $can_offer && $open_offer ) : ?>
			<div class="dn-panel">
				<p><strong>Je aanbod is verstuurd</strong> op <?php echo esc_html( get_the_date( 'j F Y', $open_offer ) ); ?>.</p>
				<p>Je hoort het zodra <?php echo esc_html( deedni_code( $id ) ); ?> reageert.</p>
			</div>
		<?php elseif ( $can_offer && ! $has_contact ) : ?>
			<?php echo deedni_template( 'aanbod-formulier', array( 'kandidaat_id' => $id, 'flash' => $flash ) ); // phpcs:ignore ?>
		<?php endif; ?>
	</aside>
</div>
