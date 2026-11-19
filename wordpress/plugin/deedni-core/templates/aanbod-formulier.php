<?php
/**
 * Formulier om een aanbod te sturen. Overschrijven in je thema: /deedni/aanbod-formulier.php
 *
 * @var int        $kandidaat_id
 * @var array|null $flash Fouten en eerder ingevulde waarden.
 */

defined( 'ABSPATH' ) || exit;

$errors = isset( $flash['errors'] ) ? $flash['errors'] : array();
$data   = isset( $flash['data'] ) ? $flash['data'] : array();
$val    = function ( $key ) use ( $data ) {
	return isset( $data[ $key ] ) ? $data[ $key ] : '';
};
$err    = function ( $key ) use ( $errors ) {
	return empty( $errors[ $key ] ) ? '' : '<span class="error" id="dn-a-' . esc_attr( $key ) . '-error">' . esc_html( $errors[ $key ] ) . '</span>';
};
$inv    = function ( $key ) use ( $errors ) {
	return empty( $errors[ $key ] ) ? '' : ' aria-invalid="true" aria-describedby="dn-a-' . esc_attr( $key ) . '-error"';
};
?>
<form method="post" class="dn-panel dn-form dn-offer-form" id="aanbod" novalidate>
	<h2>Stuur een aanbod</h2>
	<p class="hint">Zegt <?php echo esc_html( deedni_code( $kandidaat_id ) ); ?> ja, dan zie je naam, cv en contactgegevens. Zegt de kandidaat nee, dan blijft die anoniem.</p>

	<?php if ( ! empty( $errors['form'] ) ) : ?>
		<?php echo deedni_notice( $errors['form'], 'error' ); // phpcs:ignore ?>
	<?php endif; ?>

	<div class="field">
		<label for="dn-a-functie">Functie</label>
		<input id="dn-a-functie" name="functie" type="text" value="<?php echo esc_attr( $val( 'functie' ) ); ?>" placeholder="Bijv. Senior performance marketeer"<?php echo $inv( 'functie' ); // phpcs:ignore ?>>
		<?php echo $err( 'functie' ); // phpcs:ignore ?>
	</div>

	<fieldset class="field dn-salary">
		<legend>Salaris, bruto per maand</legend>
		<div class="dn-row">
			<label class="visually-hidden" for="dn-a-min">Vanaf</label>
			<input id="dn-a-min" name="salaris_min" type="number" min="0" step="50" inputmode="numeric" placeholder="Vanaf" value="<?php echo esc_attr( $val( 'salaris_min' ) ); ?>"<?php echo $inv( 'salaris' ); // phpcs:ignore ?>>
			<span aria-hidden="true">tot</span>
			<label class="visually-hidden" for="dn-a-max">Tot</label>
			<input id="dn-a-max" name="salaris_max" type="number" min="0" step="50" inputmode="numeric" placeholder="Tot" value="<?php echo esc_attr( $val( 'salaris_max' ) ); ?>"<?php echo $inv( 'salaris' ); // phpcs:ignore ?>>
		</div>
		<?php if ( deedni_field( $kandidaat_id, 'salaris_min' ) ) : ?>
			<span class="hint">Wens van de kandidaat: vanaf <?php echo esc_html( deedni_euro( deedni_field( $kandidaat_id, 'salaris_min' ) ) ); ?></span>
		<?php endif; ?>
		<?php echo $err( 'salaris' ); // phpcs:ignore ?>
	</fieldset>

	<div class="dn-row">
		<div class="field">
			<label for="dn-a-uren">Uren</label>
			<select id="dn-a-uren" name="uren">
				<?php foreach ( deedni_choices( 'uren' ) as $k => $v ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $val( 'uren' ) ? $val( 'uren' ) : deedni_field( $kandidaat_id, 'uren' ), $k ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field">
			<label for="dn-a-werkplek">Werkplek</label>
			<select id="dn-a-werkplek" name="werkplek">
				<?php foreach ( deedni_choices( 'werkvorm' ) as $k => $v ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $val( 'werkplek' ) ? $val( 'werkplek' ) : deedni_field( $kandidaat_id, 'werkvorm' ), $k ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="field">
		<label for="dn-a-standplaats">Standplaats</label>
		<input id="dn-a-standplaats" name="standplaats" type="text" value="<?php echo esc_attr( $val( 'standplaats' ) ? $val( 'standplaats' ) : deedni_field( deedni_profile_id(), 'plaats' ) ); ?>">
	</div>

	<div class="field">
		<label for="dn-a-bericht">Persoonlijk bericht</label>
		<textarea id="dn-a-bericht" name="bericht" rows="5" placeholder="Waarom past deze marketeer bij jullie?"<?php echo $inv( 'bericht' ); // phpcs:ignore ?>><?php echo esc_textarea( $val( 'bericht' ) ); ?></textarea>
		<?php echo $err( 'bericht' ); // phpcs:ignore ?>
	</div>

	<input type="hidden" name="deedni_action" value="aanbod_sturen">
	<input type="hidden" name="kandidaat_id" value="<?php echo (int) $kandidaat_id; ?>">
	<?php wp_nonce_field( 'deedni_aanbod_' . $kandidaat_id, '_deedni_nonce' ); ?>
	<button class="btn btn--accent" type="submit">Aanbod versturen</button>
</form>
