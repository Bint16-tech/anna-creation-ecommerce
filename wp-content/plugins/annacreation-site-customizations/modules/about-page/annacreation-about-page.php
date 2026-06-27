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

	wp_enqueue_style(
		'annacreation-about-page',
		plugin_dir_url( __FILE__ ) . 'assets/about-page.css',
		array(),
		'1.1.0'
	);
}
add_action( 'wp_enqueue_scripts', 'annacreation_about_enqueue_assets', 30 );

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
