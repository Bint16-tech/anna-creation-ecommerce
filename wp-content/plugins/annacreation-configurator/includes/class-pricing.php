<?php

if (!defined('ABSPATH')) exit;

class AnnaCreation_Pricing {

    private const OPTION_SETTINGS = 'anna_pricing_settings';
    private const MAX_LETTERS = 9;
    private const DEFAULT_MAX_MOTIFS = 9;
    private const DOUBLE_KEYCHAIN_MAX_MOTIFS = 18;
    private const FREE_MOTIF_CATEGORIES = [
        'lettre-blanche-doree',
        'lettre-blanche-noir',
        'lettre-blanche-noire',
        'lettre-blanche-camel',
        'lettre-camel',
    ];
    private const LIMITED_LETTER_CATEGORIES = [
        'lettre-blanche-doree',
        'lettre-blanche-noir',
        'lettre-blanche-noire',
        'lettre-camel',
    ];

    public static function product_labels() {
        return [
            'attache-tetine' => 'Attache-tétine',
            'attache-doudou' => 'Attache-doudou',
            'anneau-dentition' => 'Anneau de dentition',
            'porte-cle' => 'Porte-clé',
            'double-port-cle' => 'Double porte-clé',
            'double-porte-cle' => 'Double porte-clé',
        ];
    }

    public static function fixed_categories() {
        if (class_exists('AnnaCreation_Category_Rules')) {
            return AnnaCreation_Category_Rules::categories();
        }

        $path = __DIR__ . '/config/categories.php';

        return file_exists($path) ? require $path : ['clips' => [], 'fantaisies' => []];
    }

    public static function pricing_rules() {
        $settings = self::settings();
        $products = [
            'attache-tetine' => [
                'label' => 'Attache-tétine',
                'clip_count' => 1,
                'max_clips' => 2,
                'base' => $settings['products']['attache-tetine']['base'],
                'extra' => self::global_extra_for_rule($settings),
            ],
            'attache-doudou' => [
                'label' => 'Attache-doudou',
                'clip_count' => 1,
                'max_clips' => 2,
                'base' => $settings['products']['attache-doudou']['base'],
                'extra' => self::global_extra_for_rule($settings),
            ],
            'anneau-dentition' => [
                'label' => 'Anneau de dentition',
                'clip_count' => 0,
                'max_clips' => 1,
                'base' => $settings['products']['anneau-dentition']['base'],
                'extra' => self::global_extra_for_rule($settings),
                'clip_extra' => $settings['products']['anneau-dentition']['clip_extra'],
                'allowed_clip_types' => ['non-anime', 'anime'],
                'allowed_clip_categories' => ['classique', 'anneau-animee'],
            ],
            'porte-cle' => [
                'label' => 'Porte-clé',
                'clip_count' => 1,
                'base' => $settings['products']['porte-cle']['base'],
                'extra' => self::global_extra_for_rule($settings),
                'clip_extra' => ['any' => 0.0],
                'allowed_clip_categories' => ['anneau'],
            ],
            'double-porte-cle' => [
                'label' => 'Double porte-clé',
                'clip_count' => 1,
                'base' => $settings['products']['double-porte-cle']['base'],
                'extra' => self::global_extra_for_rule($settings),
                'clip_extra' => ['any' => 0.0],
                'allowed_clip_categories' => ['anneau'],
            ],
        ];

        return [
            'products' => $products,
            'aliases' => [
                'double-port-cle' => 'double-porte-cle',
            ],
            'clip_extras' => $settings['clip_extras'],
            'letter_category_limit' => $settings['letter_category_limit'],
            'limited_letter_categories' => self::LIMITED_LETTER_CATEGORIES,
        ];
    }

    public static function default_settings() {
        return [
            'products' => [
                'attache-tetine' => [
                    'base' => ['classique' => 14.0, 'foot' => 15.0, 'anime' => 16.0],
                ],
                'attache-doudou' => [
                    'base' => ['classique' => 16.0, 'foot' => 17.0, 'anime' => 18.0],
                ],
                'anneau-dentition' => [
                    'base' => ['classique' => 12.0],
                    'clip_extra' => ['non-anime' => 3.5, 'anime' => 5.5],
                ],
                'porte-cle' => [
                    'base' => ['classique' => 10.0, 'foot' => 11.0, 'anime' => 12.0],
                ],
                'double-porte-cle' => [
                    'base' => ['classique' => 20.0, 'foot' => 22.0, 'anime' => 24.0],
                ],
            ],
            'global_extra' => [
                'classique' => 1.0,
                'foot' => 1.0,
                'anime' => 2.0,
            ],
            'clip_extras' => [
                'ronde' => 0.0,
                'bois' => 0.0,
                'fleur' => 1.5,
                'foot' => 1.5,
                'autre-clip' => 1.5,
                'dessins-fleuris' => 1.5,
                'animee' => 2.0,
                'anneau-animee' => 0.0,
                'anneau' => 0.0,
                'classique' => 0.0,
            ],
            'letter_category_limit' => 9,
        ];
    }

    public static function settings() {
        return self::sanitize_settings(get_option(self::OPTION_SETTINGS, []));
    }

    public static function save_settings($settings) {
        update_option(self::OPTION_SETTINGS, self::sanitize_settings($settings), false);
    }

    public static function frontend_config() {
        $rules = self::pricing_rules();
        $products = [];
        $motif_types = [];

        foreach ((self::fixed_categories()['fantaisies'] ?? []) as $slug => $category) {
            $motif_types[sanitize_key($slug)] = sanitize_key($category['pricing_type'] ?? self::motif_type($slug));
        }

        foreach ($rules['products'] as $slug => $rule) {
            $products[$slug] = [
                'clipCount' => (int) ($rule['clip_count'] ?? 0),
                'base' => $rule['base'] ?? [],
                'extra' => $rule['extra'] ?? [],
            ];

            if (isset($rule['max_clips'])) {
                $products[$slug]['maxClips'] = (int) $rule['max_clips'];
            }

            if (!empty($rule['clip_extra'])) {
                $products[$slug]['clipExtra'] = $rule['clip_extra'];
            } else {
                $products[$slug]['pricedClips'] = true;
            }

            if (!empty($rule['allowed_clip_types'])) {
                $products[$slug]['allowedClipTypes'] = $rule['allowed_clip_types'];
            }

            if (!empty($rule['allowed_clip_categories'])) {
                $products[$slug]['allowedClipCategories'] = $rule['allowed_clip_categories'];
            }
        }

        return [
            'aliases' => $rules['aliases'],
            'products' => $products,
            'clipExtras' => $rules['clip_extras'],
            'motifTypes' => $motif_types,
            'freeMotifCategories' => self::FREE_MOTIF_CATEGORIES,
            'limitedLetterCategories' => self::LIMITED_LETTER_CATEGORIES,
            'letterCategoryLimit' => (int) $rules['letter_category_limit'],
            'motifLimits' => [
                'default' => self::DEFAULT_MAX_MOTIFS,
                'double-porte-cle' => self::DOUBLE_KEYCHAIN_MAX_MOTIFS,
            ],
        ];
    }

    public static function sanitize_config($config) {
        $config = is_array($config) ? $config : [];
        $product = self::canonical_product(sanitize_key($config['product'] ?? ''));

        return [
            'product' => $product,
            'double' => !empty($config['double']),
            'text' => self::limit_text(sanitize_text_field($config['text'] ?? '')),
            'clips' => self::sanitize_items($config['clips'] ?? []),
            'motifs' => self::sanitize_items($config['motifs'] ?? []),
        ];
    }

    public static function validate($config) {
        $config = self::sanitize_config($config);
        $errors = [];
        $rules = self::product_rule($config);

        if (!$rules) {
            $errors[] = __('Produit invalide.', 'annacreation-configurator');
        }

        if (self::text_length($config['text']) > self::MAX_LETTERS) {
            $errors[] = __('Maximum 9 lettres.', 'annacreation-configurator');
        }

        $letter_category_count = self::limited_letter_category_count($config['motifs']);
        $letter_category_limit = self::letter_category_limit();

        if ($letter_category_count > $letter_category_limit) {
            $errors[] = sprintf(
                __('Maximum %d elements pour les lettres blanches et camel.', 'annacreation-configurator'),
                $letter_category_limit
            );
        }

        if ($rules) {
            $clip_count = count($config['clips']);
            $required = (int) ($rules['clip_count'] ?? 0);
            $max = (int) ($rules['max_clips'] ?? $required);

            if ($required > 0 && $clip_count < $required) {
                $errors[] = $config['product'] === 'double-porte-cle'
                    ? __('Veuillez choisir 1 anneau.', 'annacreation-configurator')
                    : sprintf(__('Veuillez choisir %d clip(s).', 'annacreation-configurator'), $required);
            }

            if ($max > 0 && $clip_count > $max) {
                if ($config['product'] === 'double-porte-cle') {
                    $errors[] = __('Un seul anneau maximum pour ce produit.', 'annacreation-configurator');
                } else {
                    $errors[] = $max === 1
                        ? __('Un seul clip maximum pour ce produit.', 'annacreation-configurator')
                        : sprintf(__('Maximum %d clips pour ce produit.', 'annacreation-configurator'), $max);
                }
            }

            if ($config['product'] === 'anneau-dentition') {
                foreach ($config['clips'] as $clip) {
                    if (!in_array(self::clip_type($clip['category']), $rules['allowed_clip_types'], true)) {
                        $errors[] = __('Type de clip invalide pour anneau de dentition.', 'annacreation-configurator');
                        break;
                    }
                }
            }

            if (!empty($rules['allowed_clip_categories'])) {
                foreach ($config['clips'] as $clip) {
                    if (!in_array(self::normalize($clip['category']), $rules['allowed_clip_categories'], true)) {
                        $errors[] = $config['product'] === 'anneau-dentition'
                            ? __('Veuillez choisir un clip classique ou animé pour ce produit.', 'annacreation-configurator')
                            : __('Veuillez choisir un anneau pour ce produit.', 'annacreation-configurator');
                        break;
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'config' => $config,
        ];
    }

    public static function calculate($config) {
        $config = self::sanitize_config($config);
        $rules = self::product_rule($config);
        $price = 0.0;
        $breakdown = [];

        if (!$rules) {
            return [
                'price' => 0.0,
                'formatted' => self::format_price(0.0),
                'breakdown' => [],
            ];
        }

        $type = $config['product'] === 'double-porte-cle'
            ? self::motif_type($config['motifs'][0]['category'] ?? '')
            : self::base_motif_type($config['motifs']);
        $price = (float) ($rules['base'][$type] ?? $rules['base']['classique'] ?? 0.0);
        $breakdown[] = ['label' => 'Base ' . ($rules['label'] ?? $config['product']), 'amount' => $price];

        foreach ($config['clips'] as $clip) {
            $amount = self::clip_extra($clip['category'], $config['product']);
            if ($amount > 0) {
                $breakdown[] = ['label' => 'Supplement clip ' . $clip['category'], 'amount' => $amount];
            }
            $price += $amount;
        }

        if ($config['product'] === 'double-porte-cle') {
            foreach (array_slice($config['motifs'], 2) as $motif) {
                $amount = self::additional_motif_extra($motif['category'], $rules);
                if ($amount > 0) {
                    $breakdown[] = ['label' => 'Fantaisie supplementaire ' . $motif['category'], 'amount' => $amount];
                }
                $price += $amount;
            }
        } else {
            $category_counts = [];

            foreach ($config['motifs'] as $motif) {
                $category = self::normalize($motif['category'] ?? '');
                $category_counts[$category] = ($category_counts[$category] ?? 0) + 1;

                if ($category_counts[$category] <= 1) {
                    continue;
                }

                $amount = self::additional_motif_extra($motif['category'], $rules);
                if ($amount > 0) {
                    $breakdown[] = ['label' => 'Fantaisie supplementaire ' . $motif['category'], 'amount' => $amount];
                }
                $price += $amount;
            }
        }

        $price = round($price, 2);

        return [
            'price' => $price,
            'formatted' => self::format_price($price),
            'breakdown' => $breakdown,
        ];
    }

    private static function sanitize_items($items) {
        if (!is_array($items)) {
            return [];
        }

        $clean = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $clean[] = [
                'src' => esc_url_raw($item['src'] ?? ''),
                'category' => sanitize_text_field($item['category'] ?? ''),
                'name' => sanitize_text_field($item['name'] ?? ''),
                'x' => isset($item['x']) ? (float) $item['x'] : 0,
                'y' => isset($item['y']) ? (float) $item['y'] : 0,
                'angle' => isset($item['angle']) ? (float) $item['angle'] : 0,
            ];
        }

        return $clean;
    }

    private static function product_rule($config) {
        $rules = self::pricing_rules();
        $product = self::canonical_product($config['product'] ?? '');

        return $rules['products'][$product] ?? null;
    }

    private static function canonical_product($product) {
        $rules = [
            'double-port-cle' => 'double-porte-cle',
        ];

        return $rules[$product] ?? $product;
    }

    private static function clip_extra($category, $product) {
        $settings = self::settings();

        if ($product === 'anneau-dentition') {
            $key = self::clip_type($category);

            return (float) ($settings['products']['anneau-dentition']['clip_extra'][$key] ?? 0.0);
        }

        if (in_array($product, ['porte-cle', 'double-porte-cle', 'double-port-cle'], true)) {
            return 0.0;
        }

        $extras = $settings['clip_extras'];
        $slug = self::normalize($category);

        return (float) ($extras[$slug] ?? 0.0);
    }

    private static function additional_motif_extra($category, $rules) {
        $extras = $rules['extra'] ?? ['fantaisie' => 1.0, 'anime' => 2.0];

        if (in_array(self::normalize($category), self::FREE_MOTIF_CATEGORIES, true)) {
            return 0.0;
        }

        $type = self::motif_type($category);

        if ($type === 'anime') {
            return (float) ($extras['anime'] ?? 2.0);
        }

        if ($type === 'foot') {
            return (float) ($extras['foot'] ?? $extras['fantaisie'] ?? 1.0);
        }

        return (float) ($extras['fantaisie'] ?? 1.0);
    }

    private static function base_motif_type($motifs) {
        $types = [];

        foreach ((array) $motifs as $motif) {
            $types[] = self::motif_type($motif['category'] ?? '');
        }

        if (in_array('anime', $types, true)) {
            return 'anime';
        }

        if (in_array('foot', $types, true)) {
            return 'foot';
        }

        return $types[0] ?? 'classique';
    }

    private static function max_motifs($config) {
        $product = self::canonical_product($config['product'] ?? '');

        return $product === 'double-porte-cle' ? self::DOUBLE_KEYCHAIN_MAX_MOTIFS : self::DEFAULT_MAX_MOTIFS;
    }

    private static function motif_type($category) {
        $category = self::normalize($category);
        $categories = self::fixed_categories();
        $configured = $categories['fantaisies'][$category]['pricing_type'] ?? '';

        if (in_array($configured, ['classique', 'foot', 'anime'], true)) {
            return $configured;
        }

        if (str_contains($category, 'foot')) {
            return 'foot';
        }

        if (str_contains($category, 'anim')) {
            return 'anime';
        }

        return 'classique';
    }

    private static function clip_type($category) {
        return self::motif_type($category) === 'anime' ? 'anime' : 'non-anime';
    }

    private static function normalize($value) {
        $value = function_exists('remove_accents') ? remove_accents((string) $value) : (string) $value;
        $value = strtolower($value);

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $value), '-');
    }

    private static function limit_text($text) {
        return function_exists('mb_substr') ? mb_substr($text, 0, self::MAX_LETTERS) : substr($text, 0, self::MAX_LETTERS);
    }

    private static function text_length($text) {
        return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    }

    private static function format_price($price) {
        return function_exists('wc_price') ? wc_price($price) : number_format($price, 2, ',', ' ') . ' EUR';
    }

    private static function sanitize_settings($settings) {
        $defaults = self::default_settings();
        $settings = is_array($settings) ? $settings : [];
        $clean = $defaults;

        foreach ($defaults['products'] as $product => $product_defaults) {
            foreach (($product_defaults['base'] ?? []) as $type => $default_value) {
                $clean['products'][$product]['base'][$type] = self::sanitize_price($settings['products'][$product]['base'][$type] ?? $default_value);
            }

            foreach (($product_defaults['clip_extra'] ?? []) as $type => $default_value) {
                $clean['products'][$product]['clip_extra'][$type] = self::sanitize_price($settings['products'][$product]['clip_extra'][$type] ?? $default_value);
            }
        }

        foreach ($defaults['global_extra'] as $type => $default_value) {
            $clean['global_extra'][$type] = self::sanitize_price($settings['global_extra'][$type] ?? $default_value);
        }

        foreach ($defaults['clip_extras'] as $category => $default_value) {
            $clean['clip_extras'][$category] = self::sanitize_price($settings['clip_extras'][$category] ?? $default_value);
        }

        foreach (self::clip_categories_for_settings() as $category => $data) {
            if (isset($clean['clip_extras'][$category])) {
                continue;
            }

            $default_value = (float) ($data['extra'] ?? 0);
            $clean['clip_extras'][$category] = self::sanitize_price($settings['clip_extras'][$category] ?? $default_value);
        }

        $limit = absint($settings['letter_category_limit'] ?? $defaults['letter_category_limit']);
        $clean['letter_category_limit'] = $limit > 0 ? $limit : $defaults['letter_category_limit'];

        return $clean;
    }

    private static function sanitize_price($value) {
        return round((float) str_replace(',', '.', sanitize_text_field((string) $value)), 2);
    }

    private static function global_extra_for_rule($settings) {
        return [
            'fantaisie' => (float) ($settings['global_extra']['classique'] ?? 1.0),
            'foot' => (float) ($settings['global_extra']['foot'] ?? 1.0),
            'anime' => (float) ($settings['global_extra']['anime'] ?? 2.0),
        ];
    }

    private static function letter_category_limit() {
        $settings = self::settings();

        return (int) ($settings['letter_category_limit'] ?? 9);
    }

    private static function clip_categories_for_settings() {
        if (class_exists('AnnaCreation_Category_Rules')) {
            $categories = AnnaCreation_Category_Rules::categories(true);

            return is_array($categories['clips'] ?? null) ? $categories['clips'] : [];
        }

        $categories = self::fixed_categories();

        return is_array($categories['clips'] ?? null) ? $categories['clips'] : [];
    }

    private static function limited_letter_category_count($motifs) {
        $count = 0;

        foreach ((array) $motifs as $motif) {
            if (in_array(self::normalize($motif['category'] ?? ''), self::LIMITED_LETTER_CATEGORIES, true)) {
                $count++;
            }
        }

        return $count;
    }
}
