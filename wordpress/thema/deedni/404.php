<?php
/**
 * Pagina niet gevonden.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap page-head">
	<h1>Deze pagina bestaat niet</h1>
	<p class="lead">De link klopt niet, of de pagina is verplaatst.</p>
</div>
<div class="wrap page-body">
	<a class="btn btn--accent" href="<?php echo esc_url( home_url( '/' ) ); ?>">Naar de homepage</a>
</div>
<?php
get_footer();
