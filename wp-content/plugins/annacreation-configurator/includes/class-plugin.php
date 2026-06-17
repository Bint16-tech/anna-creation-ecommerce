<?php

if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| 🧠 CORE ENGINE
|--------------------------------------------------------------------------
*/

class AnnaCreation_Plugin {

    public function __construct() {

        $this->load_frontend();
        $this->load_admin();
        $this->load_assets();

    }

    private function load_frontend() {

        require_once __DIR__ . '/class-pricing.php';
        require_once __DIR__ . '/class-category-rules.php';
        require_once __DIR__ . '/class-woocommerce.php';
        require_once __DIR__ . '/class-shortcode.php';

        new AnnaCreation_WooCommerce();
        new AnnaCreation_Shortcode();

    }

    private function load_admin() {

        require_once __DIR__ . '/class-category-rules.php';
        require_once __DIR__ . '/class-admin.php';
        new AnnaCreation_Admin();

    }

    private function load_assets() {

        require_once __DIR__ . '/class-assets.php';
        new AnnaCreation_Assets();

    }
}
