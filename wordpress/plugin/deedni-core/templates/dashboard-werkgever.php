<?php
/**
 * Overzicht voor werkgevers. Overschrijven in je thema: /deedni/dashboard-werkgever.php
 *
 * @var WP_Post[] $offers     Verstuurde aanbiedingen.
 * @var int       $bedrijf_id
 */

defined( 'ABSPATH' ) || exit;

$approved = deedni_werkgever_goedgekeurd();
$counts   = array_count_values(
	array_map(
		function ( $o ) {
			return (string) deedni_field( $o->ID, 'status' );
		},
		$offers
	)
);
?>
<div class="dn-dashboard">
	<div class="dn-status">
		<div>
			<p class="dn-status__code"><?php echo esc_html( $bedrijf_id ? get_the_title( $bedrijf_id ) : '' ); ?></p>
			<p class="dn-status__text">
				<?php echo $approved ? 'Je account is actief. Zoek kandidaten en stuur een aanbod.' : 'We controleren je account, meestal binnen één werkdag. Je krijgt een mail zodra je kunt zoeken.'; ?>
			</p>
		</div>
		<div class="cta-row">
			<?php if ( $approved ) : ?>
				<a class="btn btn--accent btn--sm" href="<?php echo esc_url( deedni_page_url( 'zoeken' ) ); ?>">Kandidaten zoeken</a>
			<?php endif; ?>
			<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( deedni_page_url( 'profiel' ) ); ?>">Bedrijfsprofiel</a>
		</div>
	</div>

	<?php if ( $offers ) : ?>
		<ul class="dn-stats" aria-label="Samenvatting">
			<li><strong><?php echo (int) ( isset( $counts['verstuurd'] ) ? $counts['verstuurd'] : 0 ); ?></strong> wachten op reactie</li>
			<li><strong><?php echo (int) ( ( isset( $counts['geaccepteerd'] ) ? $counts['geaccepteerd'] : 0 ) + ( isset( $counts['aangenomen'] ) ? $counts['aangenomen'] : 0 ) ); ?></strong> geaccepteerd</li>
			<li><strong><?php echo (int) ( isset( $counts['afgewezen'] ) ? $counts['afgewezen'] : 0 ); ?></strong> afgewezen</li>
		</ul>
	<?php endif; ?>

	<section aria-labelledby="dn-sent-title">
		<h2 id="dn-sent-title" class="dn-section-title">Verstuurde aanbiedingen</h2>
		<?php if ( ! $offers ) : ?>
			<div class="dn-panel dn-empty">
				<p><strong>Je hebt nog geen aanbod verstuurd.</strong></p>
				<p>Zoek op specialisme, tools en regio en stuur een aanbod met salaris. Zegt iemand ja, dan zie je hier naam, cv en contactgegevens.</p>
			</div>
		<?php else : ?>
			<div class="dn-list">
				<?php foreach ( $offers as $offer ) : ?>
					<?php
					$status       = deedni_field( $offer->ID, 'status' );
					$kandidaat_id = (int) deedni_field( $offer->ID, 'kandidaat' );
					$accepted     = in_array( $status, array( 'geaccepteerd', 'aangenomen' ), true );
					?>
					<article class="dn-row-card" id="aanbod-<?php echo (int) $offer->ID; ?>">
						<div class="dn-row-card__main">
							<p class="dn-row-card__meta">
								<?php if ( 'kandidaat' === get_post_type( $kandidaat_id ) ) : ?>
									<a href="<?php echo esc_url( get_permalink( $kandidaat_id ) ); ?>"><?php echo esc_html( deedni_code( $kandidaat_id ) ); ?></a>
								<?php else : ?>
									Profiel verwijderd
								<?php endif; ?>
								· <?php echo esc_html( get_the_date( 'j F Y', $offer ) ); ?>
							</p>
							<h3><?php echo esc_html( deedni_field( $offer->ID, 'functie' ) ); ?></h3>
							<p class="card-sub"><?php echo esc_html( deedni_euro( deedni_field( $offer->ID, 'salaris_min' ) ) . '–' . deedni_number( deedni_field( $offer->ID, 'salaris_max' ) ) . ' p/m' ); ?></p>
						</div>
						<span class="badge <?php echo $accepted ? 'badge--ok' : ( 'verstuurd' === $status ? 'badge--new' : 'badge--soft' ); ?>"><?php echo esc_html( deedni_label( 'aanbod_status', $status ) ); ?></span>

						<?php if ( $accepted && 'kandidaat' === get_post_type( $kandidaat_id ) ) : ?>
							<div class="dn-row-card__contact">
								<?php echo deedni_template( 'contact', array( 'id' => $kandidaat_id ) ); // phpcs:ignore ?>
								<?php if ( 'geaccepteerd' === $status ) : ?>
									<form method="post">
										<input type="hidden" name="deedni_action" value="aanbod_aangenomen">
										<input type="hidden" name="aanbod_id" value="<?php echo (int) $offer->ID; ?>">
										<?php wp_nonce_field( 'deedni_aangenomen_' . $offer->ID, '_deedni_nonce' ); ?>
										<button class="link-btn" type="submit">Kandidaat aangenomen? Meld het hier</button>
									</form>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
</div>
