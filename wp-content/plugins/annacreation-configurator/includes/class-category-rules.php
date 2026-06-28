<?php

if (!defined('ABSPATH')) exit;

class AnnaCreation_Category_Rules {

    private const OPTION_PRODUCTS = 'anna_products';
    private const OPTION_CATEGORIES = 'anna_categories';

    public static function products($include_disabled = false) {
        $products = [];

        foreach (self::all_products() as $slug => $product) {
            if (!$include_disabled && empty($product['active'])) {
                continue;
            }

            $products[$slug] = $product['label'];
        }

        return $products;
    }

    public static function all_products() {
        $products = self::default_products();
        $custom = get_option(self::OPTION_PRODUCTS, []);
        $custom = is_array($custom) ? $custom : [];

        foreach ($custom as $slug => $product) {
            $slug = sanitize_key($slug);

            if (!$slug || !is_array($product)) {
                continue;
            }

            $products[$slug] = array_merge(
                $products[$slug] ?? [
                    'label' => $slug,
                    'active' => true,
                    'clip_categories' => [],
                    'base_image_id' => 0,
                    'base_image_url' => '',
                ],
                self::sanitize_product($slug, $product)
            );
        }

        return $products;
    }

    public static function save_products($products) {
        update_option(self::OPTION_PRODUCTS, is_array($products) ? $products : [], false);
    }

    public static function categories($include_disabled = false) {
        $categories = self::default_categories();
        $custom = get_option(self::OPTION_CATEGORIES, []);
        $custom = is_array($custom) ? $custom : [];

        foreach (['clips', 'fantaisies'] as $group) {
            foreach (($custom[$group] ?? []) as $slug => $category) {
                $slug = sanitize_key($slug);

                if (!$slug || !is_array($category)) {
                    continue;
                }

                $categories[$group][$slug] = array_merge(
                    $categories[$group][$slug] ?? ['label' => $slug],
                    self::sanitize_category($group, $slug, $category)
                );
            }

            if (!$include_disabled) {
                foreach ($categories[$group] as $slug => $category) {
                    if (isset($category['active']) && !$category['active']) {
                        unset($categories[$group][$slug]);
                    }
                }
            }
        }

        return $categories;
    }

    public static function save_categories($categories) {
        update_option(self::OPTION_CATEGORIES, is_array($categories) ? $categories : [], false);
    }

    public static function normalized_product($product) {
        $product = sanitize_key((string) $product);

        return $product === 'double-porte-cle' ? 'double-port-cle' : $product;
    }

    public static function clip_categories($product) {
        $product = self::normalized_product($product);
        $products = self::all_products();

        if ($product === 'anneau-dentition') {
            return ['classique', 'anneau-animee'];
        }

        if (in_array($product, ['porte-cle', 'double-port-cle'], true)) {
            return ['anneau'];
        }

        if (in_array($product, ['attache-tetine', 'attache-doudou'], true)) {
            return [
                'ronde',
                'bois',
                'fleur',
                'foot',
                'animee',
                'dessins-fleuris',
                'autre-clip',
            ];
        }

        if (!empty($products[$product]['clip_categories'])) {
            return array_values(array_filter(array_map('sanitize_key', (array) $products[$product]['clip_categories'])));
        }

        return [];
    }

    public static function categories_for_product($group, $product, $categories = null) {
        $group = sanitize_key((string) $group);
        $categories = is_array($categories) ? $categories : self::categories();

        if ($group === 'clips') {
            return array_intersect_key(
                $categories['clips'] ?? [],
                array_flip(self::clip_categories($product))
            );
        }

        if ($group === 'fantaisies') {
            return $categories['fantaisies'] ?? [];
        }

        return [];
    }

    public static function is_category_allowed($product, $type, $category) {
        $group = $type === 'clip' ? 'clips' : 'fantaisies';
        $category = sanitize_key((string) $category);
        $allowed = self::categories_for_product($group, $product);

        return isset($allowed[$category]);
    }

    public static function filter_media($product, $group, $media) {
        $allowed = self::categories_for_product($group, $product);
        $media = is_array($media) ? $media : [];

        return array_intersect_key($media, $allowed);
    }

    private static function default_products() {
        return [
            'attache-tetine' => [
                'label' => __('Attache-tétine', 'annacreation-configurator'),
                'active' => true,
                'clip_categories' => ['ronde', 'bois', 'fleur', 'foot', 'animee', 'dessins-fleuris', 'autre-clip'],
                'base_image_id' => 0,
                'base_image_url' => '',
            ],
            'attache-doudou' => [
                'label' => __('Attache doudou', 'annacreation-configurator'),
                'active' => true,
                'clip_categories' => ['ronde', 'bois', 'fleur', 'foot', 'animee', 'dessins-fleuris', 'autre-clip'],
                'base_image_id' => 0,
                'base_image_url' => '',
            ],
            'anneau-dentition' => [
                'label' => __('Anneau dentition', 'annacreation-configurator'),
                'active' => true,
                'clip_categories' => ['classique', 'anneau-animee'],
                'base_image_id' => 0,
                'base_image_url' => '',
            ],
            'porte-cle' => [
                'label' => __('Porte-clé', 'annacreation-configurator'),
                'active' => true,
                'clip_categories' => ['anneau'],
                'base_image_id' => 0,
                'base_image_url' => '',
            ],
            'double-port-cle' => [
                'label' => __('Double porte-clé', 'annacreation-configurator'),
                'active' => true,
                'clip_categories' => ['anneau'],
                'base_image_id' => 0,
                'base_image_url' => '',
            ],
            'double-porte-cle' => [
                'label' => __('Double porte-clé', 'annacreation-configurator'),
                'active' => true,
                'clip_categories' => ['anneau'],
                'base_image_id' => 0,
                'base_image_url' => '',
            ],
        ];
    }

    private static function default_categories() {
        $path = __DIR__ . '/config/categories.php';
        $categories = file_exists($path) ? require $path : ['clips' => [], 'fantaisies' => []];

        foreach (($categories['clips'] ?? []) as $slug => $category) {
            $categories['clips'][$slug]['active'] = true;
            $categories['clips'][$slug]['extra'] = (float) ($category['extra'] ?? 0);
        }

        foreach (($categories['fantaisies'] ?? []) as $slug => $category) {
            $categories['fantaisies'][$slug]['active'] = true;
            $categories['fantaisies'][$slug]['pricing_type'] = self::default_fantasy_pricing_type($slug);
        }

        return $categories;
    }

    private static function default_fantasy_pricing_type($slug) {
        $slug = sanitize_key($slug);

        if ($slug === 'animaux') {
            return 'classique';
        }

        if (str_contains($slug, 'foot')) {
            return 'foot';
        }

        if (str_contains($slug, 'anim')) {
            return 'anime';
        }

        return 'classique';
    }

    private static function sanitize_product($slug, $product) {
        return [
            'label' => sanitize_text_field($product['label'] ?? $slug),
            'active' => !empty($product['active']),
            'clip_categories' => array_values(array_filter(array_map('sanitize_key', (array) ($product['clip_categories'] ?? [])))),
            'base_image_id' => absint($product['base_image_id'] ?? 0),
            'base_image_url' => esc_url_raw($product['base_image_url'] ?? ''),
        ];
    }

    private static function sanitize_category($group, $slug, $category) {
        $clean = [
            'label' => sanitize_text_field($category['label'] ?? $slug),
            'active' => !isset($category['active']) || !empty($category['active']),
        ];

        if ($group === 'clips') {
            $clean['extra'] = (float) str_replace(',', '.', (string) ($category['extra'] ?? 0));
        } else {
            $type = sanitize_key($category['pricing_type'] ?? 'classique');
            $clean['pricing_type'] = in_array($type, ['classique', 'foot', 'anime'], true) ? $type : 'classique';
        }

        return $clean;
    }
}
