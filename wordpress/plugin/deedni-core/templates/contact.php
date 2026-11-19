<?php
/**
 * Contactgegevens van een kandidaat, alleen na een geaccepteerd aanbod. Overschrijven in je thema: /deedni/contact.php
 *
 * @var int $id Kandidaat-ID.
 */

defined( 'ABSPATH' ) || exit;

$user  = get_userdata( get_post_field( 'post_author', $id ) );
$name  = trim( deedni_field( $id, 'voornaam' ) . ' ' . deedni_field( $id, 'achternaam' ) );
$cv    = deedni_cv_url( $id );
$phone = deedni_field( $id, 'telefoon' );
?>
<div class="dn-contact">
	<p class="dn-contact__title"><?php echo esc_html( $name ? $name : deedni_code( $id ) ); ?></p>
	<dl class="dn-dl">
		<?php if ( $user ) : ?>
			<dt>E-mail</dt><dd><a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a></dd>
		<?php endif; ?>
		<?php if ( $phone ) : ?>
			<dt>Telefoon</dt><dd><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></dd>
		<?php endif; ?>
		<?php if ( deedni_field( $id, 'linkedin' ) ) : ?>
			<dt>LinkedIn</dt><dd><a href="<?php echo esc_url( deedni_field( $id, 'linkedin' ) ); ?>" rel="noopener" target="_blank">Profiel bekijken</a></dd>
		<?php endif; ?>
		<?php if ( deedni_field( $id, 'huidige_werkgever' ) ) : ?>
			<dt>Huidige werkgever</dt><dd><?php echo esc_html( deedni_field( $id, 'huidige_werkgever' ) ); ?></dd>
		<?php endif; ?>
	</dl>
	<?php if ( $cv ) : ?>
		<a class="btn btn--primary btn--sm" href="<?php echo esc_url( $cv ); ?>">Cv downloaden</a>
	<?php endif; ?>
</div>
