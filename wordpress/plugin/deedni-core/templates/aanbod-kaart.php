<?php
/**
 * Een ontvangen aanbod, zoals de kandidaat het ziet. Overschrijven in je thema: /deedni/aanbod-kaart.php
 *
 * @var int $aanbod_id
 */

defined( 'ABSPATH' ) || exit;

$status     = deedni_field( $aanbod_id, 'status' );
$bedrijf_id = (int) deedni_field( $aanbod_id, 'bedrijf' );
$bedrijf    = $bedrijf_id ? get_the_title( $bedrijf_id ) : 'Een werkgever';
$plaats     = deedni_field( $aanbod_id, 'standplaats' ) ? deedni_field( $aanbod_id, 'standplaats' ) : deedni_field( $bedrijf_id, 'plaats' );
$logo       = $bedrijf_id ? (int) deedni_field( $bedrijf_id, 'logo' ) : 0;
$min        = deedni_field( $aanbod_id, 'salaris_min' );
$max        = deedni_field( $aanbod_id, 'salaris_max' );
?>
<article class="card dn-offer<?php echo 'verstuurd' === $status ? '' : ' dn-offer--done'; ?>" id="aanbod-<?php echo (int) $aanbod_id; ?>">
	<div class="card-top">
		<?php if ( 'verstuurd' === $status ) : ?>
			<span class="badge badge--new">Nieuw aanbod</span>
		<?php else : ?>
			<span class="badge <?php echo 'afgewezen' === $status ? 'badge--soft' : 'badge--ok'; ?>"><?php echo esc_html( deedni_label( 'aanbod_status', $status ) ); ?></span>
		<?php endif; ?>
		<span><?php echo esc_html( get_the_date( 'j F Y', $aanbod_id ) ); ?></span>
	</div>
	<div class="dn-offer__head">
		<?php if ( $logo ) : ?>
			<?php echo wp_get_attachment_image( $logo, 'thumbnail', false, array( 'class' => 'dn-logo', 'alt' => '' ) ); ?>
		<?php endif; ?>
		<div>
			<h3 class="card-title"><?php echo esc_html( deedni_field( $aanbod_id, 'functie' ) ); ?></h3>
			<p class="card-sub"><?php echo esc_html( implode( ' · ', array_filter( array( $bedrijf, $plaats ) ) ) ); ?></p>
		</div>
	</div>
	<div class="facts">
		<div class="fact"><small>Salaris p/m</small><strong><?php echo esc_html( deedni_euro( $min ) . '–' . deedni_number( $max ) ); ?></strong></div>
		<div class="fact"><small>Uren</small><strong><?php echo esc_html( str_replace( ' uur', '', deedni_label( 'uren', deedni_field( $aanbod_id, 'uren' ) ) ) ); ?></strong></div>
		<div class="fact"><small>Werkplek</small><strong><?php echo esc_html( deedni_label( 'werkvorm', deedni_field( $aanbod_id, 'werkplek' ) ) ); ?></strong></div>
	</div>
	<?php if ( deedni_field( $aanbod_id, 'bericht' ) ) : ?>
		<blockquote><?php echo nl2br( esc_html( deedni_field( $aanbod_id, 'bericht' ) ) ); // phpcs:ignore ?></blockquote>
	<?php endif; ?>
	<?php if ( $bedrijf_id && ( deedni_field( $bedrijf_id, 'omschrijving' ) || deedni_field( $bedrijf_id, 'website' ) ) ) : ?>
		<details class="dn-details">
			<summary>Over <?php echo esc_html( $bedrijf ); ?></summary>
			<?php echo wpautop( esc_html( deedni_field( $bedrijf_id, 'omschrijving' ) ) ); // phpcs:ignore ?>
			<?php if ( deedni_field( $bedrijf_id, 'website' ) ) : ?>
				<p><a href="<?php echo esc_url( deedni_field( $bedrijf_id, 'website' ) ); ?>" target="_blank" rel="noopener">Website bekijken</a></p>
			<?php endif; ?>
		</details>
	<?php endif; ?>

	<?php if ( 'verstuurd' === $status ) : ?>
		<form method="post" class="card-actions">
			<input type="hidden" name="deedni_action" value="aanbod_reageren">
			<input type="hidden" name="aanbod_id" value="<?php echo (int) $aanbod_id; ?>">
			<?php wp_nonce_field( 'deedni_reageren_' . $aanbod_id, '_deedni_nonce' ); ?>
			<button class="btn btn--primary" type="submit" name="keuze" value="ja">Interesse, deel mijn naam</button>
			<button class="btn btn--ghost" type="submit" name="keuze" value="nee">Nee, bedankt</button>
		</form>
	<?php elseif ( 'afgewezen' === $status ) : ?>
		<div class="card-result card-result--muted">Afgewezen. <?php echo esc_html( $bedrijf ); ?> heeft een nette afwijzing gekregen en ziet je naam niet.</div>
	<?php else : ?>
		<div class="card-result">Je naam en contactgegevens zijn gedeeld met <?php echo esc_html( $bedrijf ); ?>.</div>
	<?php endif; ?>
</article>
