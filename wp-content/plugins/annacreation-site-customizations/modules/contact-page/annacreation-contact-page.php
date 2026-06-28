<?php
/**
 * Plugin Name: AnnaCreation - Page Contact
 * Description: Mise en page dédiée et stylisation de la page Contact AnnaCreation.
 * Version: 1.1.1
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

	$style_path = plugin_dir_path( __FILE__ ) . 'assets/contact-page.css';

	wp_enqueue_style(
		'annacreation-contact-page',
		plugin_dir_url( __FILE__ ) . 'assets/contact-page.css',
		array(),
		file_exists( $style_path ) ? filemtime( $style_path ) : '1.1.1'
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
 * Returns the default editable contact details.
 *
 * @return array<string, string>
 */
function annacreation_contact_default_settings() {
	return array(
		'address'    => 'France',
		'hours'      => 'Lundi au samedi — Réponse sous 24h à 48h',
		'email'      => 'contact@annacreation.fr',
		'phone'      => 'À renseigner',
		'whatsapp'   => 'À renseigner',
		'intro_text' => 'Une question sur une création personnalisée ? Besoin d’aide pour composer votre modèle ? L’équipe AnnaCreation vous accompagne avec plaisir.',
		'form_text'  => 'Nous vous répondrons rapidement pour vous accompagner dans votre commande personnalisée.',
	);
}

/**
 * Returns saved contact details with clean defaults for empty values.
 *
 * @return array<string, string>
 */
function annacreation_contact_get_settings() {
	$defaults = annacreation_contact_default_settings();
	$saved    = get_option( 'anna_contact_settings', array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	if ( empty( $saved ) ) {
		$legacy_email = get_option( 'annacreation_contact_email', '' );
		$legacy_phone = get_option( 'annacreation_contact_phone', '' );

		if ( $legacy_email ) {
			$saved['email'] = $legacy_email;
		}

		if ( $legacy_phone ) {
			$saved['phone']    = $legacy_phone;
			$saved['whatsapp'] = $legacy_phone;
		}
	}

	$settings = wp_parse_args( $saved, $defaults );

	foreach ( $defaults as $key => $default ) {
		if ( '' === trim( (string) $settings[ $key ] ) ) {
			$settings[ $key ] = $default;
		}
	}

	return $settings;
}

/**
 * Sanitizes editable contact details before saving.
 *
 * @param mixed $input Raw option value.
 * @return array<string, string>
 */
function annacreation_contact_sanitize_settings( $input ) {
	$defaults = annacreation_contact_default_settings();
	$input    = is_array( $input ) ? $input : array();

	return array(
		'address'    => sanitize_textarea_field( $input['address'] ?? $defaults['address'] ),
		'hours'      => sanitize_textarea_field( $input['hours'] ?? $defaults['hours'] ),
		'email'      => sanitize_email( $input['email'] ?? $defaults['email'] ),
		'phone'      => sanitize_text_field( $input['phone'] ?? $defaults['phone'] ),
		'whatsapp'   => sanitize_text_field( $input['whatsapp'] ?? $defaults['whatsapp'] ),
		'intro_text' => sanitize_textarea_field( $input['intro_text'] ?? $defaults['intro_text'] ),
		'form_text'  => sanitize_textarea_field( $input['form_text'] ?? $defaults['form_text'] ),
	);
}

/**
 * Registers the editable contact details.
 */
function annacreation_contact_register_settings() {
	register_setting(
		'anna_contact_settings_group',
		'anna_contact_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'annacreation_contact_sanitize_settings',
			'default'           => annacreation_contact_default_settings(),
		)
	);
}
add_action( 'admin_init', 'annacreation_contact_register_settings' );

/**
 * Adds the contact settings screen under the AnnaCreation admin menu.
 */
function annacreation_contact_add_settings_page() {
	add_submenu_page(
		'anna-creation',
		'Coordonnées / Contact',
		'Coordonnées / Contact',
		'manage_options',
		'anna-contact-settings',
		'annacreation_contact_render_settings_page'
	);
}
add_action( 'admin_menu', 'annacreation_contact_add_settings_page', 99 );

function annacreation_contact_rename_admin_submenu() {
	global $submenu;

	if ( empty( $submenu['anna-creation'] ) ) {
		return;
	}

	foreach ( $submenu['anna-creation'] as &$item ) {
		if ( isset( $item[2] ) && 'anna-contact-settings' === $item[2] ) {
			$item[0] = 'Coordonnées / Contact';
		}
	}
	unset( $item );
}
add_action( 'admin_menu', 'annacreation_contact_rename_admin_submenu', 100 );

function annacreation_contact_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Vous n’avez pas l’autorisation d’accéder à cette page.', 'annacreation' ) );
	}

	$settings = annacreation_contact_get_settings();
	$fields   = array(
		'address'    => array(
			'label' => 'Adresse',
			'type'  => 'textarea',
			'rows'  => 2,
		),
		'hours'      => array(
			'label' => 'Horaires',
			'type'  => 'textarea',
			'rows'  => 3,
		),
		'email'      => array(
			'label' => 'Email',
			'type'  => 'email',
		),
		'phone'      => array(
			'label'       => 'Téléphone',
			'type'        => 'text',
			'placeholder' => '+33 6 12 34 56 78',
		),
		'whatsapp'   => array(
			'label'       => 'WhatsApp',
			'type'        => 'text',
			'placeholder' => '+33 6 12 34 56 78',
		),
		'intro_text' => array(
			'label' => 'Texte d’introduction de la page Contact',
			'type'  => 'textarea',
			'rows'  => 4,
		),
		'form_text'  => array(
			'label' => 'Texte du formulaire',
			'type'  => 'textarea',
			'rows'  => 3,
		),
	);
	?>
	<div class="wrap">
		<h1>Coordonnées / Contact</h1>
		<p>Ces informations sont affichées sur la page Contact AnnaCreation.</p>

		<form action="options.php" method="post">
			<?php settings_fields( 'anna_contact_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $fields as $key => $field ) : ?>
					<tr>
						<th scope="row">
							<label for="anna_contact_settings_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
						</th>
						<td>
							<?php if ( 'textarea' === $field['type'] ) : ?>
								<textarea
									id="anna_contact_settings_<?php echo esc_attr( $key ); ?>"
									name="anna_contact_settings[<?php echo esc_attr( $key ); ?>]"
									class="large-text"
									rows="<?php echo esc_attr( $field['rows'] ?? 3 ); ?>"
								><?php echo esc_textarea( $settings[ $key ] ); ?></textarea>
							<?php else : ?>
								<input
									id="anna_contact_settings_<?php echo esc_attr( $key ); ?>"
									name="anna_contact_settings[<?php echo esc_attr( $key ); ?>]"
									type="<?php echo esc_attr( $field['type'] ); ?>"
									class="regular-text"
									placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
									value="<?php echo esc_attr( $settings[ $key ] ); ?>"
								>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
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
