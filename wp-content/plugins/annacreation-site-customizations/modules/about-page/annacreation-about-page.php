<?php
/**
 * Plugin Name: AnnaCreation - Page À propos
 * Description: Présentation de marque dédiée à la page About Us.
 * Version: 1.1.0
 * Author: AnnaCreation
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects the brand presentation page without relying only on a database ID.
 */
function annacreation_is_about_page() {
	return is_page( 'about' ) || is_page( 115 );
}

/**
 * Loads the page-specific stylesheet.
 */
function annacreation_about_enqueue_assets() {
	if ( ! annacreation_is_about_page() ) {
		return;
	}

	$style_path = plugin_dir_path( __FILE__ ) . 'assets/about-page.css';

	wp_enqueue_style(
		'annacreation-about-page',
		plugin_dir_url( __FILE__ ) . 'assets/about-page.css',
		array(),
		file_exists( $style_path ) ? filemtime( $style_path ) : '1.1.0'
	);
}
add_action( 'wp_enqueue_scripts', 'annacreation_about_enqueue_assets', 30 );

function annacreation_about_default_images() {
	$uploads_url = content_url( '/uploads/2026/06/' );

	return array(
		'hero_main'              => $uploads_url . 'attacheTetine1-e1780390784806-621x1024.jpeg',
		'hero_small'             => $uploads_url . 'anneau-e1780388692539-609x1024.jpeg',
		'attache_tetine'         => $uploads_url . 'attacheTetine1-e1780390784806-621x1024.jpeg',
		'attache_doudou'         => $uploads_url . 'attacheMaron-473x1024.jpeg',
		'porte_cle'              => $uploads_url . 'portcle-473x1024.jpeg',
		'double_porte_cle'       => $uploads_url . 'portClemaron-473x1024.jpeg',
		'anneau_de_dentition'    => $uploads_url . 'anneau-e1780388692539-609x1024.jpeg',
	);
}

function annacreation_about_get_images() {
	$defaults = annacreation_about_default_images();
	$saved    = get_option( 'anna_about_images', array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	$images = wp_parse_args( $saved, $defaults );

	foreach ( $defaults as $key => $default ) {
		if ( empty( $images[ $key ] ) ) {
			$images[ $key ] = $default;
		}
	}

	return $images;
}

function annacreation_about_get_image_url( $key ) {
	$images = annacreation_about_get_images();
	$value  = $images[ $key ] ?? '';

	if ( is_numeric( $value ) ) {
		$url = wp_get_attachment_image_url( (int) $value, 'large' );

		if ( $url ) {
			return $url;
		}
	}

	return esc_url_raw( $value );
}

function annacreation_about_sanitize_images( $input ) {
	$defaults = annacreation_about_default_images();
	$input    = is_array( $input ) ? $input : array();
	$output   = array();

	foreach ( $defaults as $key => $default ) {
		$value = $input[ $key ] ?? '';

		if ( is_numeric( $value ) ) {
			$output[ $key ] = absint( $value );
			continue;
		}

		$output[ $key ] = esc_url_raw( $value );
	}

	return $output;
}

function annacreation_about_register_settings() {
	register_setting(
		'anna_about_images_group',
		'anna_about_images',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'annacreation_about_sanitize_images',
			'default'           => annacreation_about_default_images(),
		)
	);
}
add_action( 'admin_init', 'annacreation_about_register_settings' );

function annacreation_about_add_settings_page() {
	add_submenu_page(
		'anna-creation',
		'Page À propos',
		'Page À propos',
		'manage_options',
		'anna-about-settings',
		'annacreation_about_render_settings_page'
	);
}
add_action( 'admin_menu', 'annacreation_about_add_settings_page', 99 );

function annacreation_about_admin_assets( $hook ) {
	if ( 'anna-creation_page_anna-about-settings' !== $hook ) {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_script(
		'jquery-core',
		'jQuery(function($) {
			$(document).on("click", ".anna-about-image-upload", function(e) {
				e.preventDefault();

				var $button = $(this);
				var $field = $("#" + $button.data("target"));
				var $preview = $("#" + $button.data("preview"));
				var frame = wp.media({
					title: "Choisir une image",
					button: { text: "Utiliser cette image" },
					multiple: false
				});

				frame.on("select", function() {
					var attachment = frame.state().get("selection").first().toJSON();
					$field.val(attachment.id).trigger("change");
					$preview.attr("src", attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url).show();
				});

				frame.open();
			});
		});'
	);
}
add_action( 'admin_enqueue_scripts', 'annacreation_about_admin_assets' );

function annacreation_about_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Vous n’avez pas l’autorisation d’accéder à cette page.', 'annacreation' ) );
	}

	$images = annacreation_about_get_images();
	$fields = array(
		'hero_main'           => 'Image principale du hero',
		'hero_small'          => 'Petite image du hero',
		'attache_tetine'      => 'Carte produit — Attache-tétine',
		'attache_doudou'      => 'Carte produit — Attache-doudou',
		'porte_cle'           => 'Carte produit — Porte-clé',
		'double_porte_cle'    => 'Carte produit — Double porte-clé',
		'anneau_de_dentition' => 'Carte produit — Anneau de dentition',
	);
	?>
	<div class="wrap">
		<h1>Page À propos</h1>
		<p>Modifiez les images affichées sur la page À propos sans toucher au code.</p>

		<form action="options.php" method="post">
			<?php settings_fields( 'anna_about_images_group' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( $fields as $key => $label ) : ?>
					<?php
					$value       = $images[ $key ] ?? '';
					$preview_url = is_numeric( $value ) ? wp_get_attachment_image_url( (int) $value, 'medium' ) : $value;
					$field_id    = 'anna_about_images_' . $key;
					$preview_id  = 'anna_about_preview_' . $key;
					?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
						</th>
						<td>
							<input
								id="<?php echo esc_attr( $field_id ); ?>"
								name="anna_about_images[<?php echo esc_attr( $key ); ?>]"
								type="hidden"
								value="<?php echo esc_attr( $value ); ?>"
							>
							<p>
								<img
									id="<?php echo esc_attr( $preview_id ); ?>"
									src="<?php echo esc_url( $preview_url ); ?>"
									alt=""
									style="max-width: 160px; height: auto; border-radius: 10px; border: 1px solid #ddd; background: #fff; padding: 4px; <?php echo $preview_url ? '' : 'display:none;'; ?>"
								>
							</p>
							<button
								type="button"
								class="button anna-about-image-upload"
								data-target="<?php echo esc_attr( $field_id ); ?>"
								data-preview="<?php echo esc_attr( $preview_id ); ?>"
							>
								Choisir une image
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button( 'Enregistrer les images' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Replaces the imported starter-site layout with the AnnaCreation presentation.
 */
function annacreation_about_template( $template ) {
	if ( annacreation_is_about_page() ) {
		return plugin_dir_path( __FILE__ ) . 'templates/about-page.php';
	}

	return $template;
}
add_filter( 'template_include', 'annacreation_about_template', 99 );

function annacreation_about_disable_footer( $enabled ) {
	return annacreation_is_about_page() ? false : $enabled;
}
add_filter( 'blocksy:builder:footer:enabled', 'annacreation_about_disable_footer' );
