<?php
/**
 * Plugin Name: AnnaCreation - Header et bannière
 * Description: Ajustements ciblés du menu principal et du bouton de bannière AnnaCreation.
 * Version: 1.0.0
 * Author: AnnaCreation
 */

defined( 'ABSPATH' ) || exit;

function annacreation_header_polish_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'annacreation-header-polish',
		plugin_dir_url( __FILE__ ) . 'assets/css/header-polish.css',
		array(),
		'1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'annacreation_header_polish_enqueue_assets', 35 );
