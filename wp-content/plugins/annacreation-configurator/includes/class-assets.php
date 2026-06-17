<?php
if (!defined('ABSPATH')) exit;

class AnnaCreation_Assets {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'load_front_assets'], 99);
        add_action('admin_enqueue_scripts', [$this, 'load_admin_assets']);
    }

    public function load_front_assets() {
        // Interact.js - Essayer local d'abord, puis CDN en fallback
        $local_js = plugin_dir_url(__FILE__) . '../assets/js/interact.min.js';
        $style_path = plugin_dir_path(__FILE__) . '../public/css/style.css';
        $config_path = plugin_dir_path(__FILE__) . '../public/js/configurator.js';
        $pricing_path = plugin_dir_path(__FILE__) . '../public/js/pricing.js';
        $cart_path = plugin_dir_path(__FILE__) . '../public/js/cart.js';
        
        wp_enqueue_script(
            'interact-js',
            $local_js,
            [],
            '1.10.27',
            true
        );

        // CSS principal
        wp_enqueue_style(
            'anna-style',
            plugin_dir_url(__FILE__) . '../public/css/style.css',
            [],
            file_exists($style_path) ? filemtime($style_path) : '1.8'
        );

        wp_add_inline_style('anna-style', '
            body.anna-personnalisation-page footer,
            body.anna-personnalisation-page .ct-footer,
            body.anna-personnalisation-page [data-footer],
            body.anna-personnalisation-page .elementor-location-footer,
            body.anna-personnalisation-page .hero-section,
            body:has(.anna-layout) footer,
            body:has(.anna-layout) .ct-footer,
            body:has(.anna-layout) [data-footer],
            body:has(.anna-layout) .elementor-location-footer,
            body:has(.anna-layout) .hero-section {
                display: none !important;
            }

            body.anna-personnalisation-page .site-main,
            body.anna-personnalisation-page .entry-content,
            body.anna-personnalisation-page article,
            body.anna-personnalisation-page [data-vertical-spacing],
            body.anna-personnalisation-page .ct-container,
            body.anna-personnalisation-page .ct-container-full,
            body.anna-personnalisation-page .ct-container-narrow,
            body:has(.anna-layout) .site-main,
            body:has(.anna-layout) .entry-content,
            body:has(.anna-layout) article,
            body:has(.anna-layout) [data-vertical-spacing],
            body:has(.anna-layout) .ct-container,
            body:has(.anna-layout) .ct-container-full,
            body:has(.anna-layout) .ct-container-narrow {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }

            body.anna-personnalisation-page .site-main,
            body:has(.anna-layout) .site-main {
                --theme-content-vertical-spacing: 0px;
            }

            body.anna-personnalisation-page [class*="ct-container"] > article,
            body:has(.anna-layout) [class*="ct-container"] > article {
                border: 0 !important;
                box-shadow: none !important;
                padding-top: 0 !important;
            }

            body.anna-personnalisation-page #anna-modal {
                position: fixed !important;
                inset: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                z-index: 2147483647 !important;
            }

            body.anna-personnalisation-page #header,
            body.anna-personnalisation-page .ct-header,
            body.anna-personnalisation-page .site-header {
                z-index: 999999 !important;
            }

            .modal {
                z-index: 2147483647 !important;
            }
        ');

        // Configurateur principal.
        wp_enqueue_script(
            'anna-config',
            plugin_dir_url(__FILE__) . '../public/js/configurator.js',
            ['interact-js'],
            file_exists($config_path) ? filemtime($config_path) : '1.3',
            true
        );

        wp_enqueue_script(
            'anna-pricing',
            plugin_dir_url(__FILE__) . '../public/js/pricing.js',
            ['anna-config'],
            file_exists($pricing_path) ? filemtime($pricing_path) : '1.0',
            true
        );

        wp_enqueue_script(
            'anna-cart',
            plugin_dir_url(__FILE__) . '../public/js/cart.js',
            ['anna-pricing'],
            file_exists($cart_path) ? filemtime($cart_path) : '1.0',
            true
        );
        
        // Passer des données PHP au JS
        wp_localize_script('anna-config', 'annaData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('anna_nonce'),
            'cartUrl' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
            'currencySymbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '€',
            'decimalSeparator' => function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : ',',
            'thousandSeparator' => function_exists('wc_get_price_thousand_separator') ? wc_get_price_thousand_separator() : ' ',
            'decimals' => function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2,
            'physicalSettings' => $this->physical_settings(),
        ]);

        wp_localize_script('anna-pricing', 'annaPricingConfig', AnnaCreation_Pricing::frontend_config());
    }

    public function load_admin_assets($hook) {
        $allowed_hooks = [
            'toplevel_page_anna-creation',
            'anna-creation_page_anna-medias-fantaisies',
            'anna-creation_page_anna-medias-clips',
            'anna-creation_page_anna-tarification',
            'anna-creation_page_anna-dimensions-physiques',
            'anna-creation_page_anna-parametres',
        ];

        if (!in_array($hook, $allowed_hooks, true)) return;

        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_style('anna-admin', plugin_dir_url(__FILE__) . '../public/css/admin.css', [], time());
    }

    private function physical_settings() {
        return $this->sanitize_physical_settings(get_option('anna_physical_settings', []));
    }

    private function sanitize_physical_settings($settings) {
        $settings = is_array($settings) ? $settings : [];
        $defaults = [
            'attache-tetine' => 160,
            'attache-doudou' => 220,
            'porte-cle' => 160,
            'double-porte-cle' => 160,
            'anneau-dentition' => 180,
        ];
        $clean = [];

        foreach ($defaults as $slug => $default_value) {
            $value = absint(sanitize_text_field((string) ($settings[$slug] ?? $default_value)));
            $clean[$slug] = $value > 0 ? $value : $default_value;
        }

        $clean['category_sizes'] = $this->sanitize_physical_category_sizes($settings['category_sizes'] ?? []);

        return $clean;
    }

    private function sanitize_physical_category_sizes($category_sizes) {
        $category_sizes = is_array($category_sizes) ? $category_sizes : [];
        $clean = [];

        foreach ($category_sizes as $slug => $value) {
            $slug = sanitize_key($slug);

            if (!$slug) {
                continue;
            }

            $value = absint(sanitize_text_field((string) $value));

            if ($slug === 'perle-ronde' && $value === 32) {
                $value = $this->default_physical_category_size($slug);
            }

            $clean[$slug] = $value > 0 ? $value : $this->default_physical_category_size($slug);
        }

        return $clean;
    }

    private function default_physical_category_size($slug) {
        $slug = sanitize_key($slug);
        $exceptions = [
            'hexagone' => 15,
            'perle-ronde' => 12,
            'perle-ronde-15' => 15,
            'perle-ronde-12' => 12,
            'lentilles' => 12,
            'lettre-blanche-doree' => 12,
            'lettre-blanche-noir' => 12,
            'lettre-blanche-noire' => 12,
            'lettre-camel' => 12,
        ];

        return $exceptions[$slug] ?? 32;
    }
}
