<?php
/**
 * Plugin Name: AnnaCreation - Page Contact
 * Description: Mise en page dédiée et stylisation de la page Contact AnnaCreation.
 * Version: 1.1.0
 * Author: AnnaCreation
 */

defined( 'ABSPATH' ) || exit;

function annacreation_is_contact_page() {
	return is_page( 'contact' ) || is_page( 117 );
}

function annacreation_contact_enqueue_assets() {
	if ( ! annacreation_is_contact_page() ) {
		return;
	}

	wp_enqueue_style(
		'annacreation-contact-page',
		plugin_dir_url( __FILE__ ) . 'assets/contact-page.css',
		array(),
		'1.1.0'
	);
}
add_action( 'wp_enqueue_scripts', 'annacreation_contact_enqueue_assets', 30 );

function annacreation_contact_template( $template ) {
	if ( annacreation_is_contact_page() ) {
		return plugin_dir_path( __FILE__ ) . 'templates/contact-page.php';
	}

	return $template;
}
add_filter( 'template_include', 'annacreation_contact_template', 99 );

function annacreation_contact_disable_footer( $enabled ) {
	return annacreation_is_contact_page() ? false : $enabled;
}
add_filter( 'blocksy:builder:footer:enabled', 'annacreation_contact_disable_footer' );

/**
 * Registers the editable contact details.
 */
function annacreation_contact_register_settings() {
	register_setting(
		'annacreation_contact',
		'annacreation_contact_email',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => get_option( 'admin_email' ),
		)
	);

	register_setting(
		'annacreation_contact',
		'annacreation_contact_phone',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'annacreation_contact_register_settings' );

/**
 * Adds a simple settings screen under Settings.
 */
function annacreation_contact_add_settings_page() {
	add_options_page(
		'Coordonnées AnnaCreation',
		'Coordonnées AnnaCreation',
		'manage_options',
		'annacreation-contact',
		'annacreation_contact_render_settings_page'
	);
}
add_action( 'admin_menu', 'annacreation_contact_add_settings_page' );

function annacreation_contact_render_settings_page() {
	?>
	<div class="wrap">
		<h1>Coordonnées AnnaCreation</h1>
		<p>Ces informations sont affichées dans les cartes de la page Contact.</p>

		<form action="options.php" method="post">
			<?php settings_fields( 'annacreation_contact' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="annacreation_contact_email">Email</label>
					</th>
					<td>
						<input
							id="annacreation_contact_email"
							name="annacreation_contact_email"
							type="email"
							class="regular-text"
							value="<?php echo esc_attr( get_option( 'annacreation_contact_email', get_option( 'admin_email' ) ) ); ?>"
						>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="annacreation_contact_phone">Téléphone / WhatsApp</label>
					</th>
					<td>
						<input
							id="annacreation_contact_phone"
							name="annacreation_contact_phone"
							type="text"
							class="regular-text"
							placeholder="+33 6 12 34 56 78"
							value="<?php echo esc_attr( get_option( 'annacreation_contact_phone', '' ) ); ?>"
						>
						<p class="description">Utilisez de préférence le format international, par exemple : +33 6 12 34 56 78.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Enregistrer les coordonnées' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Translates the existing WPForms contact form without changing or removing fields.
 *
 * @param array $form_data WPForms form configuration.
 * @return array
 */
function annacreation_translate_contact_form( $form_data ) {
	if ( empty( $form_data['id'] ) || 506 !== (int) $form_data['id'] ) {
		return $form_data;
	}

	$labels = array(
		0 => 'Nom et prénom',
		1 => 'Adresse email',
		3 => 'Objet',
		2 => 'Votre message',
	);

	foreach ( $labels as $field_id => $label ) {
		if ( isset( $form_data['fields'][ $field_id ] ) ) {
			$form_data['fields'][ $field_id ]['label'] = $label;
		}
	}

	$form_data['settings']['submit_text']            = 'Envoyer le message';
	$form_data['settings']['submit_text_processing'] = 'Envoi en cours…';

	if ( isset( $form_data['settings']['confirmations']['1']['message'] ) ) {
		$form_data['settings']['confirmations']['1']['message'] = '<p>Merci pour votre message. Nous vous répondrons rapidement.</p>';
	}

	return $form_data;
}
add_filter( 'wpforms_frontend_form_data', 'annacreation_translate_contact_form' );
