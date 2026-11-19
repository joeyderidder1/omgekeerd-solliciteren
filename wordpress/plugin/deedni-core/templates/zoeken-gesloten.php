<?php
/**
 * Zoekpagina voor wie (nog) niet mag zoeken. Overschrijven in je thema: /deedni/zoeken-gesloten.php
 *
 * @var string $reason 'uitgelogd', 'kandidaat' of 'controle'.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="dn-panel dn-closed">
	<?php if ( 'controle' === $reason ) : ?>
		<h2>We controleren je account</h2>
		<p>Zodra je account is goedgekeurd, krijg je een mail en kun je kandidaten zoeken. Dat is meestal binnen één werkdag.</p>
		<p><a class="btn btn--ghost" href="<?php echo esc_url( deedni_page_url( 'profiel' ) ); ?>">Bedrijfsprofiel aanvullen</a></p>
	<?php elseif ( 'kandidaat' === $reason ) : ?>
		<h2>Deze pagina is voor werkgevers</h2>
		<p>Als marketeer hoef je niet te zoeken: bedrijven komen naar jou.</p>
		<p><a class="btn btn--primary" href="<?php echo esc_url( deedni_page_url( 'dashboard' ) ); ?>">Naar je overzicht</a></p>
	<?php else : ?>
		<h2>Log in om kandidaten te zoeken</h2>
		<p>Zoeken, profielen bekijken en aanbiedingen sturen is gratis. Je betaalt pas bij resultaat.</p>
		<div class="cta-row">
			<a class="btn btn--accent" href="<?php echo esc_url( deedni_page_url( 'registreren', array( 'rol' => 'werkgever' ) ) ); ?>">Start als werkgever</a>
			<a class="btn btn--ghost" href="<?php echo esc_url( deedni_page_url( 'inloggen', array( 'redirect_to' => rawurlencode( deedni_page_url( 'zoeken' ) ) ) ) ); ?>">Inloggen</a>
		</div>
	<?php endif; ?>
</div>
