<?php
/*
Plugin Name: AnnaCreation Configurator
Description: Configurateur de personnalisation des produits Anna Creation.
Version: 1.0.0
Author: Bintou
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-plugin.php';

add_action('plugins_loaded', function () {
    new AnnaCreation_Plugin();
});

