<?php
/**
 * Overzicht voor kandidaten. Overschrijven in je thema: /deedni/dashboard-kandidaat.php
 *
 * @var WP_Post[] $offers       Ontvangen aanbiedingen.
 * @var int       $kandidaat_id
 */

defined( 'ABSPATH' ) || exit;

$complete = $kandidaat_id && deedni_profile_complete( $kandidaat_id );
$visible  = $kandidaat_id && deedni_field( $kandidaat_id, 'profiel_zichtbaar' );
$open     = array_filter(
	$offers,
	function ( $o ) {
		return 'verstuurd' === deedni_field( $o->ID, 'status' );
	}
);
?>
<div class="dn-dashboard">
	<div class="dn-status">
		<div>
			<p class="dn-status__code"><?php echo esc_html( $kandidaat_id ? deedni_code( $kandidaat_id ) : '' ); ?></p>
			<p class="dn-status__text">
				<?php
				if ( ! $complete ) {
					echo 'Je profiel is nog niet compleet, dus bedrijven kunnen je nog niet vinden.';
				} elseif ( ! $visible ) {
					echo 'Je profiel is verborgen. Zet het aan om weer aanbiedingen te krijgen.';
				} else {
					echo 'Je profiel is zichtbaar. Bedrijven kunnen je een aanbod sturen.';
				}
				?>
			</p>
		</div>
		<div class="cta-row">
			<a class="btn <?php echo $complete ? 'btn--ghost' : 'btn--accent'; ?> btn--sm" href="<?php echo esc_url( deedni_page_url( 'profiel' ) ); ?>"><?php echo $complete ? 'Profiel bewerken' : 'Profiel afmaken'; ?></a>
			<?php if ( $complete ) : ?>
				<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( get_permalink( $kandidaat_id ) ); ?>">Bekijk zoals bedrijven je zien</a>
			<?php endif; ?>
		</div>
	</div>

	<section aria-labelledby="dn-offers-title">
		<h2 id="dn-offers-title" class="dn-section-title">
			Aanbiedingen
			<?php if ( $open ) : ?>
				<span class="badge badge--new"><?php echo count( $open ); ?> nieuw</span>
			<?php endif; ?>
		</h2>
		<?php if ( $offers ) : ?>
			<div class="dn-grid dn-grid--offers">
				<?php
				foreach ( $offers as $offer ) {
					echo deedni_template( 'aanbod-kaart', array( 'aanbod_id' => $offer->ID ) ); // phpcs:ignore
				}
				?>
			</div>
		<?php else : ?>
			<div class="dn-panel dn-empty">
				<p><strong>Nog geen aanbiedingen.</strong></p>
				<p>Een volledig profiel met tools, ervaring en een korte introductie valt het meest op. Je krijgt een mail zodra er een aanbod binnenkomt.</p>
			</div>
		<?php endif; ?>
	</section>
</div>
