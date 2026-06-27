<?php
/**
 * Plugin Name: AnnaCreation - Personnalisations du site
 * Description: Regroupe les personnalisations visuelles AnnaCreation : accueil, pages À propos/Contact, login, boutique WooCommerce, header et bannière.
 * Version: 1.0.0
 * Author: AnnaCreation
 */

defined( 'ABSPATH' ) || exit;

$annacreation_site_modules = array(
	'modules/about-page/annacreation-about-page.php',
	'modules/contact-page/annacreation-contact-page.php',
	'modules/login-branding/annacreation-login-branding.php',
	'modules/home-sections/annacreation-home-sections.php',
	'modules/woocommerce-style/annacreation-woocommerce-style.php',
	'modules/header-polish/annacreation-header-polish.php',
);

foreach ( $annacreation_site_modules as $annacreation_site_module ) {
	$annacreation_site_module_path = __DIR__ . '/' . $annacreation_site_module;

	if ( file_exists( $annacreation_site_module_path ) ) {
		require_once $annacreation_site_module_path;
	}
}
