<?php
/**
 * Inlogformulier. Overschrijven in je thema: /deedni/inloggen.php
 *
 * @var string $redirect Waar iemand na het inloggen heen gaat.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="signup-form dn-register">
	<form method="post" class="dn-form" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>">
		<div class="field">
			<label for="dn-log">E-mailadres</label>
			<input id="dn-log" name="log" type="text" autocomplete="username" inputmode="email" required>
		</div>
		<div class="field">
			<label for="dn-pwd">Wachtwoord</label>
			<input id="dn-pwd" name="pwd" type="password" autocomplete="current-password" required>
		</div>
		<div class="field dn-check">
			<label><input type="checkbox" name="rememberme" value="forever" checked> Ingelogd blijven</label>
		</div>
		<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ); ?>">
		<input type="hidden" name="deedni_login" value="1">
		<button class="btn btn--accent" type="submit">Inloggen</button>
		<p class="privacy">
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Wachtwoord vergeten?</a> ·
			Nog geen account? <a href="<?php echo esc_url( deedni_page_url( 'registreren' ) ); ?>">Meld je aan</a>
		</p>
	</form>
</div>
