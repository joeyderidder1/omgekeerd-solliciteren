<?php
/**
 * Profiel bewerken. Overschrijven in je thema: /deedni/profiel.php
 *
 * @var int    $profile_id Kandidaat- of bedrijfsprofiel.
 * @var bool   $werkgever  Of dit een bedrijfsprofiel is.
 * @var string $form       Het ACF-formulier (HTML).
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="dn-profile-edit">
	<?php if ( ! $werkgever ) : ?>
		<?php
		$complete = deedni_profile_complete( $profile_id );
		$visible  = (bool) deedni_field( $profile_id, 'profiel_zichtbaar' );
		?>
		<div class="dn-status">
			<div>
				<p class="dn-status__code"><?php echo esc_html( deedni_code( $profile_id ) ); ?></p>
				<p class="dn-status__text">
					<?php
					if ( ! $complete ) {
						echo 'Je profiel is nog niet compleet. Vul functie, specialisme en provincie in, dan kunnen bedrijven je vinden.';
					} elseif ( ! $visible ) {
						echo 'Je profiel is verborgen. Bedrijven kunnen je nu niet vinden.';
					} else {
						echo 'Je profiel is zichtbaar voor werkgevers.';
					}
					?>
				</p>
			</div>
			<?php if ( $complete ) : ?>
				<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( get_permalink( $profile_id ) ); ?>">Bekijk zoals bedrijven je zien</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="dn-panel">
		<?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML van acf_form(). ?>
	</div>
</div>
