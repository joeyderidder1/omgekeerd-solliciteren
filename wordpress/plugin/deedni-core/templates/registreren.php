<?php
/**
 * Registratieformulier. Overschrijven in je thema: /deedni/registreren.php
 *
 * @var array  $errors Foutmeldingen per veld.
 * @var array  $data   Eerder ingevulde waarden.
 * @var string $rol    'kandidaat' of 'werkgever'.
 */

defined( 'ABSPATH' ) || exit;

$val = function ( $key ) use ( $data ) {
	return isset( $data[ $key ] ) ? $data[ $key ] : '';
};
$err = function ( $key ) use ( $errors ) {
	return empty( $errors[ $key ] ) ? '' : '<span class="error" id="dn-' . esc_attr( $key ) . '-error">' . wp_kses_post( $errors[ $key ] ) . '</span>';
};
$invalid = function ( $key ) use ( $errors ) {
	return empty( $errors[ $key ] ) ? '' : ' aria-invalid="true" aria-describedby="dn-' . esc_attr( $key ) . '-error"';
};
?>
<div class="signup-form dn-register">
	<form method="post" class="dn-form" novalidate>
		<?php if ( ! empty( $errors['form'] ) ) : ?>
			<?php echo deedni_notice( $errors['form'], 'error' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php elseif ( $errors ) : ?>
			<?php echo deedni_notice( 'Controleer de velden hieronder.', 'error' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>

		<fieldset class="seg">
			<legend>Ik ben</legend>
			<label><input type="radio" name="rol" id="rol-kandidaat" value="kandidaat" <?php checked( $rol, 'kandidaat' ); ?>>Marketeer</label>
			<label><input type="radio" name="rol" id="rol-werkgever" value="werkgever" <?php checked( $rol, 'werkgever' ); ?>>Werkgever</label>
		</fieldset>

		<div class="field" data-for="werkgever" <?php echo 'werkgever' === $rol ? '' : 'hidden'; ?>>
			<label for="dn-bedrijfsnaam">Bedrijfsnaam</label>
			<input id="dn-bedrijfsnaam" name="bedrijfsnaam" type="text" autocomplete="organization" value="<?php echo esc_attr( $val( 'bedrijfsnaam' ) ); ?>"<?php echo $invalid( 'bedrijfsnaam' ); // phpcs:ignore ?>>
			<?php echo $err( 'bedrijfsnaam' ); // phpcs:ignore ?>
		</div>

		<div class="field">
			<label for="dn-voornaam">Je voornaam</label>
			<input id="dn-voornaam" name="voornaam" type="text" autocomplete="given-name" value="<?php echo esc_attr( $val( 'voornaam' ) ); ?>"<?php echo $invalid( 'voornaam' ); // phpcs:ignore ?>>
			<span class="hint" data-for="kandidaat" <?php echo 'kandidaat' === $rol ? '' : 'hidden'; ?>>Bedrijven zien je naam pas als jij ja zegt.</span>
			<?php echo $err( 'voornaam' ); // phpcs:ignore ?>
		</div>

		<div class="field">
			<label for="dn-email">E-mailadres</label>
			<input id="dn-email" name="email" type="email" autocomplete="email" inputmode="email" placeholder="naam@voorbeeld.nl" value="<?php echo esc_attr( $val( 'email' ) ); ?>"<?php echo $invalid( 'email' ); // phpcs:ignore ?>>
			<span class="hint" data-for="werkgever" <?php echo 'werkgever' === $rol ? '' : 'hidden'; ?>>Gebruik je zakelijke e-mailadres.</span>
			<?php echo $err( 'email' ); // phpcs:ignore ?>
		</div>

		<div class="field">
			<label for="dn-wachtwoord">Wachtwoord</label>
			<input id="dn-wachtwoord" name="wachtwoord" type="password" autocomplete="new-password" minlength="8"<?php echo $invalid( 'wachtwoord' ); // phpcs:ignore ?>>
			<span class="hint">Minstens 8 tekens.</span>
			<?php echo $err( 'wachtwoord' ); // phpcs:ignore ?>
		</div>

		<div class="field dn-check">
			<label><input type="checkbox" name="akkoord" value="1" <?php checked( ! empty( $data['akkoord'] ) ); ?><?php echo $invalid( 'akkoord' ); // phpcs:ignore ?>>
				Ik ga akkoord met de voorwaarden en de <?php echo get_privacy_policy_url() ? '<a href="' . esc_url( get_privacy_policy_url() ) . '">privacyverklaring</a>' : 'privacyverklaring'; ?>.</label>
			<?php echo $err( 'akkoord' ); // phpcs:ignore ?>
		</div>

		<div class="dn-hp" aria-hidden="true">
			<label for="dn-website-url">Laat dit veld leeg</label>
			<input id="dn-website-url" name="website_url" type="text" tabindex="-1" autocomplete="off">
		</div>

		<input type="hidden" name="deedni_action" value="registreren">
		<?php wp_nonce_field( 'deedni_registreren', '_deedni_nonce' ); ?>

		<button class="btn btn--accent" type="submit">
			<span data-for="kandidaat" <?php echo 'kandidaat' === $rol ? '' : 'hidden'; ?>>Maak gratis je profiel</span>
			<span data-for="werkgever" <?php echo 'werkgever' === $rol ? '' : 'hidden'; ?>>Account aanmaken</span>
		</button>
		<p class="privacy">Al een account? <a href="<?php echo esc_url( deedni_page_url( 'inloggen' ) ); ?>">Log in</a></p>
	</form>
</div>
