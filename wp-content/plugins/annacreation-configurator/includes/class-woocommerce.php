<?php

if (!defined('ABSPATH')) exit;

class AnnaCreation_WooCommerce {

    public function __construct() {
        add_action('wp_ajax_anna_add_to_cart', [$this, 'add_to_cart']);
        add_action('wp_ajax_nopriv_anna_add_to_cart', [$this, 'add_to_cart']);

        add_action('woocommerce_before_calculate_totals', [$this, 'apply_custom_price'], 20);
        add_filter('woocommerce_cart_item_thumbnail', [$this, 'display_custom_thumbnail'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_data'], 10, 2);
        add_action('woocommerce_check_cart_items', [$this, 'validate_cart_items']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validate_checkout_items'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_order_item_meta'], 10, 4);
        add_action('woocommerce_after_order_itemmeta', [$this, 'display_order_item_preview'], 10, 3);
    }

    public function add_to_cart() {
        if (!function_exists('WC')) {
            wp_send_json_error(['message' => __('WooCommerce est indisponible.', 'annacreation-configurator')], 400);
        }

        if (function_exists('wc_load_cart') && (!WC()->cart || !WC()->session)) {
            wc_load_cart();
        }

        if (!WC()->cart) {
            wp_send_json_error(['message' => __('Le panier WooCommerce est indisponible.', 'annacreation-configurator')], 400);
        }

        check_ajax_referer('anna_nonce', 'nonce');

        $product_id = absint($_POST['product_id'] ?? 0);
        $raw_config = isset($_POST['config']) ? wp_unslash($_POST['config']) : '';
        $decoded = json_decode($raw_config, true);

        if (!$product_id || !is_array($decoded)) {
            wp_send_json_error(['message' => __('Configuration invalide.', 'annacreation-configurator')], 400);
        }

        $product = wc_get_product($product_id);

        if (!$product || !$product->is_purchasable()) {
            wp_send_json_error(['message' => __('Produit WooCommerce invalide ou non achetable.', 'annacreation-configurator')], 400);
        }

        $validation = AnnaCreation_Pricing::validate($decoded);

        if (!$validation['valid']) {
            wp_send_json_error(['message' => implode(' ', $validation['errors'])], 400);
        }

        $pricing = AnnaCreation_Pricing::calculate($validation['config']);
        $image = $this->save_configuration_image($_POST['image'] ?? '');
        $cart_item_data = [
            'anna_config' => $validation['config'],
            'anna_price' => $pricing['price'],
            'anna_price_formatted' => wp_strip_all_tags($pricing['formatted']),
            'unique_key' => md5(wp_json_encode($validation['config']) . microtime(true)),
        ];

        if ($image) {
            $cart_item_data['anna_image_url'] = $image['url'];
            $cart_item_data['anna_image_path'] = $image['path'];
        }

        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

        if (!$cart_item_key) {
            wp_send_json_error(['message' => __('Impossible d’ajouter au panier.', 'annacreation-configurator')], 400);
        }

        wp_send_json_success([
            'message' => __('Configuration ajoutée au panier.', 'annacreation-configurator'),
            'cart_url' => wc_get_cart_url(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
        ]);
    }

    public function apply_custom_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!$cart) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['anna_price'], $cart_item['data']) && is_numeric($cart_item['anna_price'])) {
                $cart_item['data']->set_price((float) $cart_item['anna_price']);
            }
        }
    }

    public function display_custom_thumbnail($thumbnail, $cart_item, $cart_item_key) {
        if (empty($cart_item['anna_image_url'])) {
            return $thumbnail;
        }

        return sprintf(
            '<img src="%s" alt="%s" class="anna-cart-thumbnail" style="max-width:90px;height:auto;" />',
            esc_url($cart_item['anna_image_url']),
            esc_attr__('Configuration personnalisée', 'annacreation-configurator')
        );
    }

    public function display_cart_item_data($item_data, $cart_item) {
        if (empty($cart_item['anna_config'])) {
            return $item_data;
        }

        $rows = $this->format_config_for_display(
            $cart_item['anna_config'],
            $cart_item['anna_price_formatted'] ?? ''
        );

        $rows = array_map(static function ($row) {
            $row['display'] = $row['value'];
            $row['value'] = wp_strip_all_tags((string) $row['value']);

            return $row;
        }, $rows);

        return array_merge($item_data, $rows);
    }

    public function validate_cart_items() {
        if (!function_exists('WC') || !WC()->cart) {
            return;
        }

        foreach ($this->cart_validation_errors() as $message) {
            wc_add_notice($message, 'error');
        }
    }

    public function validate_checkout_items($data, $errors) {
        foreach ($this->cart_validation_errors() as $message) {
            if (is_wp_error($errors)) {
                $errors->add('anna_configuration_invalid', $message);
            }
        }
    }

    public function add_order_item_meta($item, $cart_item_key, $values, $order) {
        if (empty($values['anna_config'])) {
            return;
        }

        foreach ($this->format_config_for_display($values['anna_config'], $values['anna_price_formatted'] ?? '') as $row) {
            $item->add_meta_data($row['name'], $row['value'], true);
        }

        if (!empty($values['anna_image_url'])) {
            $image_url = esc_url_raw($values['anna_image_url']);
            $item->add_meta_data(__('Apercu personnalise', 'annacreation-configurator'), $this->preview_image_html($image_url), true);
            $item->add_meta_data('_anna_image_url', $image_url, true);
        }

        $item->add_meta_data('_anna_config_json', wp_json_encode($values['anna_config']), true);
    }

    public function display_order_item_preview($item_id, $item, $product) {
        if (!is_admin() || !is_a($item, 'WC_Order_Item_Product')) {
            return;
        }

        if ($item->get_meta(__('Apercu personnalise', 'annacreation-configurator'), true)) {
            return;
        }

        $image_url = $item->get_meta('_anna_image_url', true);

        if (!$image_url) {
            $image_url = $item->get_meta(__('Image PNG finale', 'annacreation-configurator'), true);
        }

        if (!$image_url) {
            return;
        }

        ?>
        <div class="anna-order-preview" style="margin-top:12px;padding:12px;border:1px solid #dcdcde;background:#fff;border-radius:4px;">
            <strong style="display:block;margin-bottom:8px;">
                <?php esc_html_e('Apercu personnalise', 'annacreation-configurator'); ?>
            </strong>
            <a href="<?php echo esc_url($image_url); ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?php echo esc_url($image_url); ?>"
                     alt="<?php echo esc_attr__('PNG final de la personnalisation', 'annacreation-configurator'); ?>"
                     style="display:block;max-width:260px;width:100%;height:auto;border:1px solid #dcdcde;background:#f6f7f7;">
            </a>
        </div>
        <?php
    }

    private function save_configuration_image($raw_image) {
        $raw_image = is_string($raw_image) ? wp_unslash($raw_image) : '';

        if (!preg_match('/^data:image\/png;base64,/', $raw_image)) {
            return null;
        }

        $encoded = substr($raw_image, strpos($raw_image, ',') + 1);
        $decoded = base64_decode($encoded, true);

        if (!$decoded || strlen($decoded) > 8 * 1024 * 1024) {
            return null;
        }

        $upload = wp_upload_dir();

        if (!empty($upload['error'])) {
            return null;
        }

        $dir = trailingslashit($upload['basedir']) . 'anna-configurations';
        $url = trailingslashit($upload['baseurl']) . 'anna-configurations';

        if (!wp_mkdir_p($dir)) {
            return null;
        }

        $filename = wp_unique_filename($dir, 'configuration-' . time() . '.png');
        $path = trailingslashit($dir) . $filename;

        if (file_put_contents($path, $decoded) === false) {
            return null;
        }

        return [
            'path' => $path,
            'url' => trailingslashit($url) . $filename,
        ];
    }

    private function format_config_for_display($config, $price, $image_url = '') {
        $labels = AnnaCreation_Pricing::product_labels();
        $product = $labels[$config['product'] ?? ''] ?? ($config['product'] ?? '');
        $clips = $this->category_summary($config['clips'] ?? [], 'clip');
        $motifs = $this->category_summary($config['motifs'] ?? [], 'motif');
        $motif_count = count((array) ($config['motifs'] ?? []));

        $rows = [];

        if ($image_url) {
            $rows[] = [
                'name' => __('Apercu personnalise', 'annacreation-configurator'),
                'value' => $this->preview_image_html($image_url),
            ];
        }

        $rows = array_merge($rows, [
            ['name' => __('Produit personnalise', 'annacreation-configurator'), 'value' => esc_html($product)],
            ['name' => __('Clip choisi', 'annacreation-configurator'), 'value' => $clips ?: esc_html__('-', 'annacreation-configurator')],
            ['name' => __('Fantaisies', 'annacreation-configurator'), 'value' => $motifs ?: esc_html__('-', 'annacreation-configurator')],
            ['name' => __('Quantite de fantaisies', 'annacreation-configurator'), 'value' => esc_html((string) $motif_count)],
        ]);

        if (!empty($config['double'])) {
            $rows[] = ['name' => __('Option', 'annacreation-configurator'), 'value' => esc_html__('Double porte-clé', 'annacreation-configurator')];
        }

        $rows[] = ['name' => __('Prix personnalisé', 'annacreation-configurator'), 'value' => esc_html($price)];

        return $rows;
    }

    private function category_summary($items, $type) {
        $labels = [];

        foreach ((array) $items as $item) {
            $category = sanitize_key($item['category'] ?? '');

            if (!$category) {
                continue;
            }

            $labels[] = $this->summary_category_label($category, $type);
        }

        $labels = array_values(array_unique(array_filter($labels)));

        return esc_html(implode(', ', $labels));
    }

    private function summary_category_label($category, $type) {
        if ($type === 'motif' && in_array($category, ['lettre-blanche-doree', 'lettre-blanche-noir', 'lettre-blanche-noire', 'lettre-camel'], true)) {
            return __('Lettres', 'annacreation-configurator');
        }

        $label = $this->clean_category_label($category);

        if ($type === 'clip') {
            $label = preg_replace('/^clip\s+/i', '', $label);
        }

        return trim((string) $label);
    }

    private function clean_category_label($category) {
        $category = sanitize_key($category);
        $labels = [
            'lettre-blanche-doree' => __('Lettre blanche doree', 'annacreation-configurator'),
            'lettre-blanche-noir' => __('Lettre blanche noire', 'annacreation-configurator'),
            'lettre-blanche-noire' => __('Lettre blanche noire', 'annacreation-configurator'),
            'lettre-camel' => __('Lettre camel', 'annacreation-configurator'),
            'animee' => __('Animee', 'annacreation-configurator'),
            'fleur' => __('Fleur', 'annacreation-configurator'),
            'animaux' => __('Animaux', 'annacreation-configurator'),
            'arc-en-ciel' => __('Arc-en-ciel', 'annacreation-configurator'),
            'foot' => __('Foot', 'annacreation-configurator'),
            'perle-ronde' => __('Perle ronde', 'annacreation-configurator'),
            'lentilles' => __('Lentilles', 'annacreation-configurator'),
            'hexagone' => __('Hexagone', 'annacreation-configurator'),
        ];

        if (isset($labels[$category])) {
            return $labels[$category];
        }

        return ucwords(str_replace('-', ' ', $category));
    }

    private function preview_image_html($image_url) {
        return sprintf(
            '<img src="%s" alt="%s" style="display:block;max-width:220px;width:100%%;height:auto;border:1px solid #dcdcde;background:#f6f7f7;" />',
            esc_url($image_url),
            esc_attr__('Apercu personnalise', 'annacreation-configurator')
        );
    }

    private function cart_validation_errors() {
        $messages = [];

        foreach (WC()->cart->get_cart() as $cart_item) {
            if (empty($cart_item['anna_config'])) {
                continue;
            }

            $validation = AnnaCreation_Pricing::validate($cart_item['anna_config']);

            if (!$validation['valid']) {
                foreach ($validation['errors'] as $error) {
                    $messages[] = sanitize_text_field($error);
                }
            }
        }

        return array_values(array_unique($messages));
    }
}
