<?php
/**
 * Plugin Name: AnnaCreation - Connexion personnalisée
 * Description: Personnalise uniquement la page de connexion WordPress aux couleurs d’AnnaCreation.
 * Version: 1.0.0
 * Author: AnnaCreation
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads branding assets only on wp-login.php.
 */
function annacreation_login_enqueue_assets() {
	wp_enqueue_style(
		'annacreation-login',
		plugin_dir_url( __FILE__ ) . 'assets/css/login.css',
		array(),
		'1.0.0'
	);
}
add_action( 'login_enqueue_scripts', 'annacreation_login_enqueue_assets' );

/**
 * Sends the login logo back to the storefront.
 */
function annacreation_login_logo_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'annacreation_login_logo_url' );

/**
 * Gives the logo an accessible AnnaCreation label.
 */
function annacreation_login_logo_title() {
	return 'AnnaCreation';
}
add_filter( 'login_headertext', 'annacreation_login_logo_title' );
add_filter( 'login_headertitle', 'annacreation_login_logo_title' );

