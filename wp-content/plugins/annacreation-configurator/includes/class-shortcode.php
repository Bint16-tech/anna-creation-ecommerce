<?php

if (!defined('ABSPATH')) exit;

class AnnaCreation_Shortcode {

    public function __construct() {
        add_shortcode('annacreation', [$this, 'render']);
        add_filter('body_class', [$this, 'add_body_class']);
    }

    public function add_body_class($classes) {
        if (is_singular()) {
            $post = get_post();

            if ($post && has_shortcode($post->post_content, 'annacreation')) {
                $classes[] = 'anna-personnalisation-page';
            }
        }

        return $classes;
    }

    public function render($atts) {
        $atts = shortcode_atts([
            'product' => 'attache-tetine',
            'wc_product_id' => 0,
        ], $atts, 'annacreation');

        $product_slug = sanitize_key($atts['product']);
        $data = get_option('anna_media', []);
        $media_labels = $this->media_labels_data();
        $clips = $this->media_for_product($product_slug, 'clip', $data);
        $motifs = $this->media_for_product($product_slug, 'motif', $data);
        $is_double_keychain = in_array(AnnaCreation_Category_Rules::normalized_product($product_slug), ['double-port-cle', 'double-porte-cle'], true);

        if (!isset(AnnaCreation_Category_Rules::products()[AnnaCreation_Category_Rules::normalized_product($product_slug)])) {
            return '<p>' . esc_html__('Produit introuvable', 'annacreation-configurator') . '</p>';
        }

        $assets_url = plugin_dir_url(dirname(__FILE__)) . 'assets/';
        $wc_product_id = absint($atts['wc_product_id']) ?: $this->find_product_id($product_slug);

        ob_start();
        ?>
        <div class="anna-layout"
             data-product="<?php echo esc_attr($product_slug); ?>"
             data-wc-product-id="<?php echo esc_attr($wc_product_id); ?>">

            <div class="anna-sidebar">
                <h3><?php esc_html_e('Personnalisation', 'annacreation-configurator'); ?></h3>

                <button type="button" class="btn-open-clip" onclick="Anna.openModal()">
                    <?php echo esc_html($is_double_keychain ? __('Choisir mon anneau', 'annacreation-configurator') : __('Choisir mon Clip', 'annacreation-configurator')); ?>
                </button>

                <div class="motifs-section">
                    <h4><?php esc_html_e('Catégories de motifs', 'annacreation-configurator'); ?></h4>

                    <?php if (!empty($motifs)): ?>
                        <?php foreach ($motifs as $cat => $items): ?>
                            <?php $category_label = $this->category_label('fantaisies', $cat); ?>
                            <button type="button" class="accordion" onclick="Anna.toggleAcc(this)">
                                <span><?php echo esc_html($category_label); ?></span>
                                <strong><?php echo esc_html($this->motif_price_hint($product_slug, $cat)); ?></strong>
                            </button>

                            <div class="panel">
                                <?php foreach ($items as $img): ?>
                                    <?php $name = $this->image_display_name('motif', $cat, $img, $media_labels); ?>
                                    <?php $img_url = $this->media_item_url($img); ?>
                                    <?php if (!$img_url) continue; ?>
                                    <img src="<?php echo esc_url($img_url); ?>"
                                         class="option-img"
                                         alt="<?php echo esc_attr($name); ?>"
                                         loading="lazy"
                                         decoding="async"
                                         onclick="Anna.placerMotif(this.src, '<?php echo esc_js($cat); ?>', '<?php echo esc_js($name); ?>')">
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php esc_html_e('Aucun motif disponible.', 'annacreation-configurator'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="anna-actions">
                    <div class="anna-price">
                        <span><?php esc_html_e('Prix :', 'annacreation-configurator'); ?></span>
                        <strong id="anna-price">0,00 €</strong>
                    </div>

                    <button type="button" class="btn-reset" onclick="Anna.reset()">
                        <?php esc_html_e('Réinitialiser', 'annacreation-configurator'); ?>
                    </button>

                    <button type="button" class="btn-add-cart" onclick="Anna.addToCart()">
                        <?php esc_html_e('Ajouter au panier', 'annacreation-configurator'); ?>
                    </button>

                    <button type="button" class="btn-download" onclick="Anna.downloadModel()">
                        <?php esc_html_e('Télécharger mon modèle', 'annacreation-configurator'); ?>
                    </button>

                    <div id="anna-status" class="anna-status" aria-live="polite"></div>
                </div>
            </div>

            <div class="anna-canvas" id="canvas">
                <img class="base-image" src="<?php echo esc_url($this->base_image_url($assets_url, $product_slug)); ?>" alt="">
                <div id="drop-zone-clip"></div>
                <div id="drop-zone-motifs"></div>
                <div id="drop-zone-text"></div>
            </div>

            <div id="anna-modal" class="modal">
                <div class="modal-content">
                    <span class="close-modal" onclick="Anna.closeModal()">&times;</span>
                    <h2><?php echo esc_html($is_double_keychain ? __('Choisissez votre anneau', 'annacreation-configurator') : __('Choisissez votre Clip', 'annacreation-configurator')); ?></h2>

                    <div class="clips-selection">
                        <?php if (!empty($clips)): ?>
                            <?php $clip_total = array_sum(array_map('count', $clips)); ?>

                            <div class="clip-browser-toolbar">
                                <span class="clip-count" data-total="<?php echo esc_attr($clip_total); ?>">
                                    <?php
                                    printf(
                                        esc_html(_n('%d image', '%d images', $clip_total, 'annacreation-configurator')),
                                        $clip_total
                                    );
                                    ?>
                                </span>
                            </div>

                            <div class="clip-browser">
                                <nav class="clip-categories" aria-label="<?php echo esc_attr($is_double_keychain ? __('Categories d\'anneaux', 'annacreation-configurator') : __('Categories de clips', 'annacreation-configurator')); ?>">
                                    <button type="button"
                                            class="clip-category-button is-active"
                                            data-category="all">
                                        <span><?php esc_html_e('Toutes', 'annacreation-configurator'); ?></span>
                                        <strong><?php echo esc_html($clip_total); ?></strong>
                                    </button>

                                    <?php foreach ($clips as $cat => $items): ?>
                                        <?php $items = is_array($items) ? $items : []; ?>
                                        <?php $category_label = $this->category_label('clips', $cat); ?>
                                        <?php $price_hint = $this->clip_price_hint($product_slug, $cat); ?>
                                        <button type="button"
                                                class="clip-category-button"
                                                data-category="<?php echo esc_attr($cat); ?>">
                                            <span><?php echo esc_html($category_label); ?></span>
                                            <strong>
                                                <?php echo esc_html(count($items)); ?>
                                                <?php if ($price_hint): ?>
                                                    <small><?php echo esc_html($price_hint); ?></small>
                                                <?php endif; ?>
                                            </strong>
                                        </button>
                                    <?php endforeach; ?>
                                </nav>

                                <div class="clip-results">
                                    <?php foreach ($clips as $cat => $items): ?>
                                        <?php $items = is_array($items) ? $items : []; ?>
                                        <?php $category_label = $this->category_label('clips', $cat); ?>
                                        <section class="clip-group" data-category="<?php echo esc_attr($cat); ?>">
                                            <div class="clip-cat-title">
                                                <span><?php echo esc_html($category_label); ?></span>
                                                <strong>
                                                    <?php
                                                    printf(
                                                        esc_html(_n('%d image', '%d images', count($items), 'annacreation-configurator')),
                                                        count($items)
                                                    );
                                                    ?>
                                                </strong>
                                            </div>
                                            <div class="clip-grid">
                                                <?php foreach ($items as $img): ?>
                                                    <?php $name = $this->image_display_name('clip', $cat, $img, $media_labels); ?>
                                                    <?php $img_url = $this->media_item_url($img); ?>
                                                    <?php if (!$img_url) continue; ?>
                                                    <img src="<?php echo esc_url($img_url); ?>"
                                                         class="img-opt-clip"
                                                         alt="<?php echo esc_attr($name); ?>"
                                                         data-category="<?php echo esc_attr($cat); ?>"
                                                         data-name="<?php echo esc_attr($name); ?>"
                                                         loading="lazy"
                                                         decoding="async"
                                                         onclick="Anna.placerClip(this.src, '<?php echo esc_js($cat); ?>', '<?php echo esc_js($name); ?>')">
                                                <?php endforeach; ?>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>

                                    <p class="clip-empty-results" hidden>
                                        <?php echo esc_html($is_double_keychain ? __('Aucun anneau trouvé.', 'annacreation-configurator') : __('Aucun clip trouvé.', 'annacreation-configurator')); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function media_for_product($product_slug, $type, $data) {
        $type = sanitize_key($type);
        $group = $type === 'clip' ? 'clips' : 'fantaisies';
        $global_media = is_array($data[$type] ?? null) ? $data[$type] : [];
        $filtered = AnnaCreation_Category_Rules::filter_media($product_slug, $group, $global_media);

        foreach ($filtered as $category => $items) {
            $items = is_array($items) ? $items : [];
            $filtered[$category] = array_values(array_filter(array_unique(array_map([$this, 'sanitize_media_item'], $items))));

            if (empty($filtered[$category])) {
                unset($filtered[$category]);
            }
        }

        return $filtered;
    }

    private function category_label($group, $slug) {
        $group = sanitize_key($group);
        $slug = sanitize_key($slug);
        $categories = AnnaCreation_Category_Rules::categories(true);

        return sanitize_text_field($categories[$group][$slug]['label'] ?? $slug);
    }

    private function image_display_name($type, $category, $url, $labels) {
        $type = sanitize_key($type);
        $category = sanitize_key($category);
        $key = $this->media_label_key($url);
        $fallback_key = $this->media_label_key($this->media_item_url($url));
        $label = sanitize_text_field($labels[$type][$category][$key] ?? $labels[$type][$category][$fallback_key] ?? '');

        return $label ?: $this->clean_category_label($category);
    }

    private function sanitize_media_item($item) {
        if (is_numeric($item)) {
            $attachment_id = absint($item);

            return $attachment_id > 0 ? $attachment_id : '';
        }

        return esc_url_raw(trim((string) $item));
    }

    private function media_item_url($item) {
        if (is_numeric($item)) {
            $url = wp_get_attachment_url(absint($item));

            return $url ? esc_url_raw($url) : '';
        }

        return esc_url_raw(trim((string) $item));
    }

    private function media_label_key($item) {
        if (is_numeric($item)) {
            return md5('attachment:' . absint($item));
        }

        return md5(esc_url_raw(trim((string) $item)));
    }

    private function media_labels_data() {
        $labels = get_option('anna_media_labels', []);

        return is_array($labels) ? $labels : [];
    }

    private function clean_category_label($category) {
        $category = sanitize_key($category);
        $labels = [
            'lettre-blanche-doree' => __('Lettre blanche doree', 'annacreation-configurator'),
            'lettre-blanche-noir' => __('Lettre blanche noire', 'annacreation-configurator'),
            'lettre-blanche-noire' => __('Lettre blanche noire', 'annacreation-configurator'),
            'lettre-camel' => __('Lettre camel', 'annacreation-configurator'),
            'animee' => __('Animée', 'annacreation-configurator'),
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

    private function clip_price_hint($product_slug, $category) {
        if (AnnaCreation_Category_Rules::normalized_product($product_slug) !== 'anneau-dentition') {
            return '';
        }

        return $this->normalize_label($category) === 'anneau-animee' ? '+5,50 EUR' : '+3,50 EUR';
    }

    private function motif_price_hint($product_slug, $category) {
        $slug = $this->normalize_label($category);

        if (in_array($slug, ['lettre-blanche-doree', 'lettre-blanche-noir', 'lettre-blanche-camel', 'lettre-camel'], true)) {
            return '0€';
        }

        $categories = AnnaCreation_Category_Rules::categories(true);
        $pricing_type = sanitize_key($categories['fantaisies'][$slug]['pricing_type'] ?? '');

        if (!in_array($pricing_type, ['classique', 'foot', 'anime'], true)) {
            if (str_contains($slug, 'foot')) {
                $pricing_type = 'foot';
            } elseif (str_contains($slug, 'anim')) {
                $pricing_type = 'anime';
            } else {
                $pricing_type = 'classique';
            }
        }

        if ($pricing_type === 'anime') {
            return '+2€';
        }

        if ($pricing_type === 'foot') {
            return '+1€';
        }

        return '1ère 0€, 2ème +1€';
    }

    private function normalize_label($value) {
        $value = remove_accents(strtolower((string) $value));

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $value), '-');
    }

    private function base_image_url($assets_url, $product_slug) {
        $products = AnnaCreation_Category_Rules::all_products();
        $normalized_product = AnnaCreation_Category_Rules::normalized_product($product_slug);
        $custom_base_image_id = absint($products[$normalized_product]['base_image_id'] ?? 0);

        if ($custom_base_image_id) {
            $custom_base_image_url = wp_get_attachment_url($custom_base_image_id);

            if ($custom_base_image_url) {
                return esc_url_raw($custom_base_image_url);
            }
        }

        $custom_base_image = esc_url_raw($products[$normalized_product]['base_image_url'] ?? '');

        if ($custom_base_image) {
            return $custom_base_image;
        }

        $assets_path = plugin_dir_path(dirname(__FILE__)) . 'assets/base/';
        $file = $product_slug . '.png';

        if ($product_slug === 'double-porte-cle') {
            $file = 'double-port-cle.png';
        }

        if ($product_slug === 'double-port-cle' && !file_exists($assets_path . $file)) {
            $file = 'porte-cle.png';
        }

        $version = file_exists($assets_path . $file) ? filemtime($assets_path . $file) : time();

        return add_query_arg('v', $version, $assets_url . 'base/' . $file);
    }

    private function find_product_id($slug) {
        if (!function_exists('wc_get_product')) {
            return 0;
        }

        $post = get_page_by_path($slug, OBJECT, 'product');

        if ($post && $this->is_purchasable_product((int) $post->ID)) {
            return (int) $post->ID;
        }

        $labels = AnnaCreation_Pricing::product_labels();
        $query = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            's' => $labels[$slug] ?? $slug,
            'fields' => 'ids',
        ]);

        foreach ($query->posts as $product_id) {
            if ($this->is_purchasable_product((int) $product_id)) {
                return (int) $product_id;
            }
        }

        return 0;
    }

    private function is_purchasable_product($product_id) {
        $product = wc_get_product($product_id);

        return $product && $product->is_purchasable();
    }
}
