<?php

if (!defined('ABSPATH')) exit;

class AnnaCreation_Admin {

    private const OPTION_MEDIA = 'anna_media';
    private const OPTION_MEDIA_LABELS = 'anna_media_labels';
    private const OPTION_PHYSICAL_SETTINGS = 'anna_physical_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_anna_media_action', [$this, 'handle_media_action']);
        add_action('admin_post_anna_settings_action', [$this, 'handle_settings_action']);
        add_action('admin_post_anna_pricing_action', [$this, 'handle_pricing_action']);
        add_action('admin_post_anna_physical_settings_action', [$this, 'handle_physical_settings_action']);
    }

    public function menu() {
        add_menu_page(
            __('Anna Creation', 'annacreation-configurator'),
            __('Anna Creation', 'annacreation-configurator'),
            'manage_options',
            'anna-creation',
            [$this, 'dashboard_page'],
            'dashicons-art',
            25
        );

        add_submenu_page(
            'anna-creation',
            __('Dashboard', 'annacreation-configurator'),
            __('Dashboard', 'annacreation-configurator'),
            'manage_options',
            'anna-creation',
            [$this, 'dashboard_page']
        );

        add_submenu_page(
            'anna-creation',
            __('Medias Fantaisies', 'annacreation-configurator'),
            __('Medias Fantaisies', 'annacreation-configurator'),
            'manage_options',
            'anna-medias-fantaisies',
            [$this, 'fantasy_media_page']
        );

        add_submenu_page(
            'anna-creation',
            __('Medias Clips', 'annacreation-configurator'),
            __('Medias Clips', 'annacreation-configurator'),
            'manage_options',
            'anna-medias-clips',
            [$this, 'clip_media_page']
        );

        add_submenu_page(
            'anna-creation',
            __('Tarification', 'annacreation-configurator'),
            __('Tarification', 'annacreation-configurator'),
            'manage_options',
            'anna-tarification',
            [$this, 'pricing_page']
        );

        add_submenu_page(
            'anna-creation',
            __('Dimensions physiques', 'annacreation-configurator'),
            __('Dimensions physiques', 'annacreation-configurator'),
            'manage_options',
            'anna-dimensions-physiques',
            [$this, 'physical_settings_page']
        );

        add_submenu_page(
            'anna-creation',
            __('Parametres', 'annacreation-configurator'),
            __('Parametres', 'annacreation-configurator'),
            'manage_options',
            'anna-parametres',
            [$this, 'settings_page']
        );
    }

    public function dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acces refuse.', 'annacreation-configurator'));
        }

        $media = $this->media_data();
        $sections = $this->sections();
        $notice = $this->notice();

        ?>
        <div class="anna-admin">
            <div class="anna-main">
                <div class="anna-header">
                    <div>
                        <h1><?php esc_html_e('Anna Creation', 'annacreation-configurator'); ?></h1>
                        <p><?php esc_html_e('Dashboard du configurateur.', 'annacreation-configurator'); ?></p>
                    </div>
                </div>

                <?php if ($notice): ?>
                    <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                        <p><?php echo esc_html($notice['message']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="anna-dashboard-grid">
                    <?php foreach ($sections as $section): ?>
                        <?php $count = $this->count_section_images($media, $section); ?>
                        <a class="anna-dashboard-card" href="<?php echo esc_url(admin_url('admin.php?page=' . ($section['type'] === 'clip' ? 'anna-medias-clips' : 'anna-medias-fantaisies'))); ?>">
                            <h2><?php echo esc_html($section['label']); ?></h2>
                            <strong>
                                <?php
                                printf(
                                    esc_html(_n('%d image', '%d images', $count, 'annacreation-configurator')),
                                    $count
                                );
                                ?>
                            </strong>
                            <span><?php esc_html_e('Gerer les medias', 'annacreation-configurator'); ?></span>
                        </a>
                    <?php endforeach; ?>

                    <a class="anna-dashboard-card" href="<?php echo esc_url(admin_url('admin.php?page=anna-tarification')); ?>">
                        <h2><?php esc_html_e('Tarification', 'annacreation-configurator'); ?></h2>
                        <strong><?php echo esc_html(count(AnnaCreation_Pricing::pricing_rules()['products'] ?? [])); ?></strong>
                        <span><?php esc_html_e('Prix, supplements et regles', 'annacreation-configurator'); ?></span>
                    </a>

                    <a class="anna-dashboard-card" href="<?php echo esc_url(admin_url('admin.php?page=anna-dimensions-physiques')); ?>">
                        <h2><?php esc_html_e('Dimensions physiques', 'annacreation-configurator'); ?></h2>
                        <strong><?php echo esc_html(count($this->physical_settings())); ?></strong>
                        <span><?php esc_html_e('Longueurs disponibles en millimetres', 'annacreation-configurator'); ?></span>
                    </a>

                    <a class="anna-dashboard-card" href="<?php echo esc_url(admin_url('admin.php?page=anna-parametres')); ?>">
                        <h2><?php esc_html_e('Parametres', 'annacreation-configurator'); ?></h2>
                        <strong><?php echo esc_html(count(AnnaCreation_Category_Rules::products())); ?></strong>
                        <span><?php esc_html_e('Produits et categories dynamiques', 'annacreation-configurator'); ?></span>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    public function fantasy_media_page() {
        $this->render_media_page('motif');
    }

    public function clip_media_page() {
        $this->render_media_page('clip');
    }

    public function pricing_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acces refuse.', 'annacreation-configurator'));
        }

        $settings = AnnaCreation_Pricing::settings();
        $rules = AnnaCreation_Pricing::pricing_rules();
        $notice = $this->notice();

        ?>
        <div class="anna-admin">
            <div class="anna-main">
                <div class="anna-header">
                    <div>
                        <h1><?php esc_html_e('Tarification', 'annacreation-configurator'); ?></h1>
                        <p><?php esc_html_e('Prix, supplements et regles modifiables.', 'annacreation-configurator'); ?></p>
                    </div>
                </div>

                <?php if ($notice): ?>
                    <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                        <p><?php echo esc_html($notice['message']); ?></p>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="anna-pricing-form">
                    <?php wp_nonce_field('anna_pricing_action', 'anna_pricing_nonce'); ?>
                    <input type="hidden" name="action" value="anna_pricing_action">

                    <section class="anna-media-section">
                        <div class="anna-section-heading">
                            <h2><?php esc_html_e('Prix des produits', 'annacreation-configurator'); ?></h2>
                        </div>
                        <div class="anna-category-grid">
                            <?php foreach (($rules['products'] ?? []) as $slug => $rule): ?>
                                <article class="anna-category-card">
                                    <div class="anna-card-header">
                                        <div>
                                            <h3><?php echo esc_html($rule['label'] ?? $slug); ?></h3>
                                            <p><?php echo esc_html($slug); ?></p>
                                        </div>
                                    </div>
                                    <div class="anna-price-fields">
                                        <?php foreach (($settings['products'][$slug]['base'] ?? []) as $type => $value): ?>
                                            <label>
                                                <?php echo esc_html(ucfirst($type)); ?>
                                                <input type="number"
                                                       name="pricing[products][<?php echo esc_attr($slug); ?>][base][<?php echo esc_attr($type); ?>]"
                                                       step="0.01"
                                                       min="0"
                                                       value="<?php echo esc_attr((string) $value); ?>">
                                            </label>
                                        <?php endforeach; ?>
                                        <?php foreach (($settings['products'][$slug]['clip_extra'] ?? []) as $type => $value): ?>
                                            <label>
                                                <?php echo esc_html($type === 'non-anime' ? __('Clip classique', 'annacreation-configurator') : __('Clip anime', 'annacreation-configurator')); ?>
                                                <input type="number"
                                                       name="pricing[products][<?php echo esc_attr($slug); ?>][clip_extra][<?php echo esc_attr($type); ?>]"
                                                       step="0.01"
                                                       min="0"
                                                       value="<?php echo esc_attr((string) $value); ?>">
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="anna-media-section">
                        <div class="anna-section-heading">
                            <h2><?php esc_html_e('Supplements fantaisies', 'annacreation-configurator'); ?></h2>
                        </div>
                        <div class="anna-settings-form">
                            <?php foreach (($settings['global_extra'] ?? []) as $type => $value): ?>
                                <label>
                                    <?php echo esc_html($type === 'anime' ? __('Fantaisie animee supplementaire', 'annacreation-configurator') : sprintf(__('Fantaisie %s supplementaire', 'annacreation-configurator'), $type)); ?>
                                    <input type="number"
                                           name="pricing[global_extra][<?php echo esc_attr($type); ?>]"
                                           step="0.01"
                                           min="0"
                                           value="<?php echo esc_attr((string) $value); ?>">
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="anna-media-section">
                        <div class="anna-section-heading">
                            <h2><?php esc_html_e('Supplements clips', 'annacreation-configurator'); ?></h2>
                        </div>
                        <div class="anna-settings-form">
                            <?php foreach (($settings['clip_extras'] ?? []) as $slug => $value): ?>
                                <label>
                                    <?php echo esc_html($slug); ?>
                                    <input type="number"
                                           name="pricing[clip_extras][<?php echo esc_attr($slug); ?>]"
                                           step="0.01"
                                           min="0"
                                           value="<?php echo esc_attr((string) $value); ?>">
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="anna-media-section">
                        <div class="anna-section-heading">
                            <h2><?php esc_html_e('Regles', 'annacreation-configurator'); ?></h2>
                        </div>
                        <div class="anna-settings-form">
                            <label>
                                <?php esc_html_e('Limite totale lettres blanches et camel', 'annacreation-configurator'); ?>
                                <input type="number"
                                       name="pricing[letter_category_limit]"
                                       min="1"
                                       step="1"
                                       value="<?php echo esc_attr((string) ($settings['letter_category_limit'] ?? 9)); ?>">
                            </label>
                        </div>
                    </section>

                    <p>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Enregistrer la tarification', 'annacreation-configurator'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    public function physical_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acces refuse.', 'annacreation-configurator'));
        }

        $settings = $this->physical_settings();
        $labels = $this->physical_setting_labels();
        $product_settings = array_intersect_key($settings, $this->default_physical_settings());
        $category_sizes = $settings['category_sizes'] ?? [];
        $fantasy_categories = AnnaCreation_Category_Rules::categories(true)['fantaisies'] ?? [];
        $bar_products = array_intersect_key($product_settings, array_flip(['attache-tetine', 'attache-doudou', 'porte-cle']));
        $notice = $this->notice();

        ?>
        <div class="anna-admin">
            <div class="anna-main">
                <div class="anna-header">
                    <div>
                        <h1><?php esc_html_e('Dimensions physiques', 'annacreation-configurator'); ?></h1>
                        <p><?php esc_html_e('Longueurs et tailles occupees par les fantaisies, en millimetres.', 'annacreation-configurator'); ?></p>
                    </div>
                </div>

                <?php if ($notice): ?>
                    <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                        <p><?php echo esc_html($notice['message']); ?></p>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('anna_physical_settings_action', 'anna_physical_settings_nonce'); ?>
                    <input type="hidden" name="action" value="anna_physical_settings_action">

                    <div class="anna-physical-dashboard">
                        <div class="anna-metric-card">
                            <span><?php esc_html_e('Unite', 'annacreation-configurator'); ?></span>
                            <strong><?php esc_html_e('mm', 'annacreation-configurator'); ?></strong>
                            <small><?php esc_html_e('Millimetres', 'annacreation-configurator'); ?></small>
                        </div>
                        <div class="anna-metric-card">
                            <span><?php esc_html_e('Produits avec barre', 'annacreation-configurator'); ?></span>
                            <strong><?php echo esc_html((string) count($bar_products)); ?></strong>
                            <small><?php esc_html_e('Controle front actif', 'annacreation-configurator'); ?></small>
                        </div>
                        <div class="anna-metric-card">
                            <span><?php esc_html_e('Fantaisies', 'annacreation-configurator'); ?></span>
                            <strong><?php echo esc_html((string) count($fantasy_categories)); ?></strong>
                            <small><?php esc_html_e('Tailles configurables', 'annacreation-configurator'); ?></small>
                        </div>
                        <div class="anna-metric-card">
                            <span><?php esc_html_e('Defaut fantaisie', 'annacreation-configurator'); ?></span>
                            <strong><?php esc_html_e('32 mm', 'annacreation-configurator'); ?></strong>
                            <small><?php esc_html_e('Si aucune taille specifique', 'annacreation-configurator'); ?></small>
                        </div>
                    </div>

                    <section class="anna-physical-panel">
                        <div class="anna-section-heading">
                            <h2><?php esc_html_e('Longueurs produits', 'annacreation-configurator'); ?></h2>
                        </div>
                        <div class="anna-physical-grid">
                            <?php foreach ($product_settings as $slug => $value): ?>
                                <label class="anna-size-field">
                                    <span><?php echo esc_html($labels[$slug] ?? $slug); ?></span>
                                    <span class="anna-mm-input">
                                        <input type="number"
                                               name="physical_settings[<?php echo esc_attr($slug); ?>]"
                                               min="1"
                                               step="1"
                                               inputmode="numeric"
                                               value="<?php echo esc_attr((string) $value); ?>">
                                        <em><?php esc_html_e('mm', 'annacreation-configurator'); ?></em>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="anna-physical-panel">
                        <div class="anna-section-heading">
                            <h2><?php esc_html_e('Taille occupee par categorie de fantaisie', 'annacreation-configurator'); ?></h2>
                        </div>
                        <div class="anna-physical-grid anna-physical-grid-wide">
                            <?php foreach ($fantasy_categories as $slug => $category): ?>
                                <label class="anna-size-field">
                                    <span>
                                        <?php echo esc_html($category['label'] ?? $slug); ?>
                                        <small><?php echo esc_html($slug); ?></small>
                                    </span>
                                    <span class="anna-mm-input">
                                        <input type="number"
                                               name="physical_settings[category_sizes][<?php echo esc_attr($slug); ?>]"
                                               min="1"
                                               step="1"
                                               inputmode="numeric"
                                               value="<?php echo esc_attr((string) ($category_sizes[$slug] ?? $this->default_physical_category_size($slug))); ?>">
                                        <em><?php esc_html_e('mm', 'annacreation-configurator'); ?></em>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <p>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Enregistrer les dimensions', 'annacreation-configurator'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acces refuse.', 'annacreation-configurator'));
        }

        $products = AnnaCreation_Category_Rules::all_products();
        $categories = AnnaCreation_Category_Rules::categories(true);
        $active_clip_categories = AnnaCreation_Category_Rules::categories()['clips'] ?? [];
        $notice = $this->settings_notice();

        ?>
        <div class="anna-admin">
            <div class="anna-main">
                <div class="anna-header">
                    <div>
                        <h1><?php esc_html_e('Parametres', 'annacreation-configurator'); ?></h1>
                        <p><?php esc_html_e('Produits et categories dynamiques du configurateur.', 'annacreation-configurator'); ?></p>
                    </div>
                </div>

                <?php if ($notice): ?>
                    <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                        <p><?php echo esc_html($notice['message']); ?></p>
                    </div>
                <?php endif; ?>

                <section class="anna-media-section">
                    <div class="anna-section-heading">
                        <h2><?php esc_html_e('Produits', 'annacreation-configurator'); ?></h2>
                    </div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="anna-settings-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('anna_settings_action', 'anna_settings_nonce'); ?>
                        <input type="hidden" name="action" value="anna_settings_action">
                        <input type="hidden" name="settings_action" value="save_product">

                        <label>
                            <?php esc_html_e('Nom du produit', 'annacreation-configurator'); ?>
                            <input type="text" name="label" required>
                        </label>
                        <label>
                            <?php esc_html_e('Slug', 'annacreation-configurator'); ?>
                            <input type="text" name="slug" placeholder="nouveau-produit" required>
                        </label>
                        <label class="anna-checkline">
                            <input type="checkbox" name="active" value="1" checked>
                            <?php esc_html_e('Produit actif', 'annacreation-configurator'); ?>
                        </label>
                        <label>
                            <?php esc_html_e('Categories clips autorisees', 'annacreation-configurator'); ?>
                            <select name="clip_categories[]" multiple>
                                <?php foreach ($active_clip_categories as $slug => $category): ?>
                                    <option value="<?php echo esc_attr($slug); ?>">
                                        <?php echo esc_html($category['label'] ?? $slug); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <?php esc_html_e('Image de base', 'annacreation-configurator'); ?>
                            <input type="file" name="base_image" accept="image/*">
                        </label>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Ajouter / modifier le produit', 'annacreation-configurator'); ?>
                        </button>
                    </form>

                    <div class="anna-category-grid">
                        <?php foreach ($products as $slug => $product): ?>
                            <article class="anna-category-card">
                                <div class="anna-card-header">
                                    <div>
                                        <h3><?php echo esc_html($product['label'] ?? $slug); ?></h3>
                                        <p><?php echo esc_html($slug); ?></p>
                                    </div>
                                    <span class="anna-count"><?php echo !empty($product['active']) ? esc_html__('Actif', 'annacreation-configurator') : esc_html__('Inactif', 'annacreation-configurator'); ?></span>
                                </div>
                                <p class="anna-muted">
                                    <?php esc_html_e('Clips :', 'annacreation-configurator'); ?>
                                    <?php echo esc_html(implode(', ', (array) ($product['clip_categories'] ?? [])) ?: '-'); ?>
                                </p>
                                <?php if (!empty($product['base_image_url'])): ?>
                                    <figure class="anna-product-base-preview">
                                        <img src="<?php echo esc_url($product['base_image_url']); ?>" alt="">
                                        <figcaption><?php esc_html_e('Image de base personnalisee', 'annacreation-configurator'); ?></figcaption>
                                    </figure>
                                <?php endif; ?>
                                <div class="anna-inline-actions">
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <?php wp_nonce_field('anna_settings_action', 'anna_settings_nonce'); ?>
                                        <input type="hidden" name="action" value="anna_settings_action">
                                        <input type="hidden" name="settings_action" value="toggle_product">
                                        <input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
                                        <button type="submit" class="button">
                                            <?php echo !empty($product['active']) ? esc_html__('Desactiver', 'annacreation-configurator') : esc_html__('Activer', 'annacreation-configurator'); ?>
                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <?php wp_nonce_field('anna_settings_action', 'anna_settings_nonce'); ?>
                                        <input type="hidden" name="action" value="anna_settings_action">
                                        <input type="hidden" name="settings_action" value="delete_product">
                                        <input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
                                        <button type="submit" class="button button-link-delete">
                                            <?php esc_html_e('Supprimer', 'annacreation-configurator'); ?>
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php foreach (['fantaisies' => __('Categories fantaisies', 'annacreation-configurator'), 'clips' => __('Categories clips', 'annacreation-configurator')] as $group => $label): ?>
                    <section class="anna-media-section">
                        <div class="anna-section-heading">
                            <h2><?php echo esc_html($label); ?></h2>
                        </div>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="anna-settings-form">
                            <?php wp_nonce_field('anna_settings_action', 'anna_settings_nonce'); ?>
                            <input type="hidden" name="action" value="anna_settings_action">
                            <input type="hidden" name="settings_action" value="save_category">
                            <input type="hidden" name="group" value="<?php echo esc_attr($group); ?>">

                            <label>
                                <?php esc_html_e('Nom de la categorie', 'annacreation-configurator'); ?>
                                <input type="text" name="label" required>
                            </label>
                            <label>
                                <?php esc_html_e('Slug', 'annacreation-configurator'); ?>
                                <input type="text" name="slug" required>
                            </label>
                            <?php if ($group === 'fantaisies'): ?>
                                <label>
                                    <?php esc_html_e('Type tarifaire', 'annacreation-configurator'); ?>
                                    <select name="pricing_type">
                                        <option value="classique"><?php esc_html_e('Classique', 'annacreation-configurator'); ?></option>
                                        <option value="foot"><?php esc_html_e('Foot', 'annacreation-configurator'); ?></option>
                                        <option value="anime"><?php esc_html_e('Animee', 'annacreation-configurator'); ?></option>
                                    </select>
                                </label>
                            <?php else: ?>
                                <label>
                                    <?php esc_html_e('Supplement', 'annacreation-configurator'); ?>
                                    <input type="number" name="extra" step="0.01" value="0">
                                </label>
                            <?php endif; ?>
                            <label class="anna-checkline">
                                <input type="checkbox" name="active" value="1" checked>
                                <?php esc_html_e('Categorie active', 'annacreation-configurator'); ?>
                            </label>
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e('Ajouter / modifier la categorie', 'annacreation-configurator'); ?>
                            </button>
                        </form>

                        <div class="anna-category-grid">
                            <?php foreach (($categories[$group] ?? []) as $slug => $category): ?>
                                <article class="anna-category-card">
                                    <div class="anna-card-header">
                                        <div>
                                            <h3><?php echo esc_html($category['label'] ?? $slug); ?></h3>
                                            <p><?php echo esc_html($slug); ?></p>
                                        </div>
                                        <span class="anna-count"><?php echo !empty($category['active']) ? esc_html__('Active', 'annacreation-configurator') : esc_html__('Inactive', 'annacreation-configurator'); ?></span>
                                    </div>
                                    <p class="anna-muted">
                                        <?php if ($group === 'fantaisies'): ?>
                                            <?php esc_html_e('Type tarifaire :', 'annacreation-configurator'); ?>
                                            <?php echo esc_html($category['pricing_type'] ?? 'classique'); ?>
                                        <?php else: ?>
                                            <?php esc_html_e('Supplement :', 'annacreation-configurator'); ?>
                                            <?php echo esc_html((string) ($category['extra'] ?? 0)); ?>
                                        <?php endif; ?>
                                    </p>
                                    <div class="anna-inline-actions">
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                            <?php wp_nonce_field('anna_settings_action', 'anna_settings_nonce'); ?>
                                            <input type="hidden" name="action" value="anna_settings_action">
                                            <input type="hidden" name="settings_action" value="delete_category">
                                            <input type="hidden" name="group" value="<?php echo esc_attr($group); ?>">
                                            <input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
                                            <button type="submit" class="button button-link-delete">
                                                <?php esc_html_e('Supprimer', 'annacreation-configurator'); ?>
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function render_media_page($type) {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acces refuse.', 'annacreation-configurator'));
        }

        $type = sanitize_key($type);
        $section = $this->section_by_type($type);

        if (!$section) {
            wp_die(esc_html__('Type de media invalide.', 'annacreation-configurator'));
        }

        $media = $this->media_data();
        $media_labels = $this->media_labels_data();
        $notice = $this->notice();

        ?>
        <div class="anna-admin">
            <div class="anna-main">
                <div class="anna-header">
                    <div>
                        <h1><?php echo esc_html($section['label']); ?></h1>
                        <p><?php esc_html_e('Gestion des images de cette famille uniquement.', 'annacreation-configurator'); ?></p>
                    </div>
                </div>

                <?php if ($notice): ?>
                    <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
                        <p><?php echo esc_html($notice['message']); ?></p>
                    </div>
                <?php endif; ?>

                <section class="anna-upload-panel">
                    <h2><?php esc_html_e('Ajouter des images', 'annacreation-configurator'); ?></h2>

                    <form method="post"
                          action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                          enctype="multipart/form-data"
                          class="anna-upload-form">
                        <?php wp_nonce_field('anna_media_action', 'anna_media_nonce'); ?>
                        <input type="hidden" name="action" value="anna_media_action">
                        <input type="hidden" name="media_action" value="upload">
                        <input type="hidden" name="type" value="<?php echo esc_attr($type); ?>">

                        <label for="anna-media-category"><?php esc_html_e('Categorie', 'annacreation-configurator'); ?></label>
                        <select id="anna-media-category" class="anna-select" name="category" required>
                            <?php foreach ($section['categories'] as $slug => $category): ?>
                                <option value="<?php echo esc_attr($slug); ?>">
                                    <?php echo esc_html($category['label'] ?? $slug); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="anna-media-images"><?php esc_html_e('Images', 'annacreation-configurator'); ?></label>
                        <input id="anna-media-images"
                               type="file"
                               name="images[]"
                               accept="image/*"
                               multiple
                               required>

                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Ajouter les images', 'annacreation-configurator'); ?>
                        </button>
                    </form>
                </section>

                <section class="anna-media-section">
                    <div class="anna-section-heading">
                        <h2><?php echo esc_html($section['label']); ?></h2>
                    </div>

                    <div class="anna-category-grid">
                        <?php foreach ($section['categories'] as $slug => $category): ?>
                            <?php $items = $this->sanitize_media_items($media[$type][$slug] ?? []); ?>
                            <article class="anna-category-card">
                                <div class="anna-card-header">
                                    <div>
                                        <h3><?php echo esc_html($category['label'] ?? $slug); ?></h3>
                                        <p><?php echo esc_html($slug); ?></p>
                                    </div>
                                    <span class="anna-count">
                                        <?php
                                        printf(
                                            esc_html(_n('%d image', '%d images', count($items), 'annacreation-configurator')),
                                            count($items)
                                        );
                                        ?>
                                    </span>
                                </div>

                                <?php if ($items): ?>
                                    <form id="<?php echo esc_attr('anna-bulk-' . $type . '-' . $slug); ?>"
                                          method="post"
                                          action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                          class="anna-bulk-delete-form">
                                        <?php wp_nonce_field('anna_media_action', 'anna_media_nonce'); ?>
                                        <input type="hidden" name="action" value="anna_media_action">
                                        <input type="hidden" name="media_action" value="delete_bulk">
                                        <input type="hidden" name="type" value="<?php echo esc_attr($type); ?>">
                                        <input type="hidden" name="category" value="<?php echo esc_attr($slug); ?>">
                                        <button type="submit" class="button">
                                            <?php esc_html_e('Supprimer la selection', 'annacreation-configurator'); ?>
                                        </button>
                                    </form>
                                    <div class="anna-preview-grid">
                                        <?php foreach ($items as $url): ?>
                                            <?php $display_name = $this->media_display_name($media_labels, $type, $slug, $url); ?>
                                            <figure class="anna-media-item">
                                                <label>
                                                    <input type="checkbox" name="urls[]" value="<?php echo esc_attr($url); ?>" form="<?php echo esc_attr('anna-bulk-' . $type . '-' . $slug); ?>">
                                                    <img src="<?php echo esc_url($url); ?>" alt="" loading="lazy" decoding="async">
                                                </label>
                                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="anna-media-name-form">
                                                    <?php wp_nonce_field('anna_media_action', 'anna_media_nonce'); ?>
                                                    <input type="hidden" name="action" value="anna_media_action">
                                                    <input type="hidden" name="media_action" value="save_label">
                                                    <input type="hidden" name="type" value="<?php echo esc_attr($type); ?>">
                                                    <input type="hidden" name="category" value="<?php echo esc_attr($slug); ?>">
                                                    <input type="hidden" name="url" value="<?php echo esc_attr($url); ?>">
                                                    <label>
                                                        <?php esc_html_e('Nom affiche', 'annacreation-configurator'); ?>
                                                        <input type="text" name="display_name" value="<?php echo esc_attr($display_name); ?>" placeholder="<?php echo esc_attr($this->clean_category_label($slug)); ?>">
                                                    </label>
                                                    <button type="submit" class="button">
                                                        <?php esc_html_e('Enregistrer', 'annacreation-configurator'); ?>
                                                    </button>
                                                </form>
                                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                                    <?php wp_nonce_field('anna_media_action', 'anna_media_nonce'); ?>
                                                    <input type="hidden" name="action" value="anna_media_action">
                                                    <input type="hidden" name="media_action" value="delete">
                                                    <input type="hidden" name="type" value="<?php echo esc_attr($type); ?>">
                                                    <input type="hidden" name="category" value="<?php echo esc_attr($slug); ?>">
                                                    <input type="hidden" name="url" value="<?php echo esc_attr($url); ?>">
                                                    <button type="submit" class="button-link-delete anna-delete-one">
                                                        <?php esc_html_e('Supprimer', 'annacreation-configurator'); ?>
                                                    </button>
                                                </form>
                                            </figure>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="anna-empty">
                                        <?php esc_html_e('Aucune image dans cette categorie.', 'annacreation-configurator'); ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
        <?php
    }

    public function handle_media_action() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acces refuse.', 'annacreation-configurator'));
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['anna_media_nonce'] ?? ''));

        if (!$nonce || !wp_verify_nonce($nonce, 'anna_media_action')) {
            wp_die(esc_html__('Nonce invalide.', 'annacreation-configurator'));
        }

        check_admin_referer('anna_media_action', 'anna_media_nonce');

        $media_action = sanitize_key(wp_unslash($_POST['media_action'] ?? ''));
        $type = sanitize_key(wp_unslash($_POST['type'] ?? ''));
        $category = sanitize_key(wp_unslash($_POST['category'] ?? ''));

        if (!$this->is_valid_target($type, $category)) {
            $this->redirect('invalid', $type);
        }

        if ($media_action === 'upload') {
            $this->redirect($this->upload_media($type, $category), $type);
        }

        if ($media_action === 'delete') {
            $this->redirect($this->delete_media($type, $category), $type);
        }

        if ($media_action === 'delete_bulk') {
            $this->redirect($this->delete_media_bulk($type, $category), $type);
        }

        if ($media_action === 'save_label') {
            $this->redirect($this->save_media_label($type, $category), $type);
        }

        $this->redirect('invalid', $type);
    }

    public function handle_settings_action() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acces refuse.', 'annacreation-configurator'));
        }

        check_admin_referer('anna_settings_action', 'anna_settings_nonce');

        $settings_action = sanitize_key(wp_unslash($_POST['settings_action'] ?? ''));

        if ($settings_action === 'save_product') {
            $this->save_product();
            $this->settings_redirect('settings-saved');
        }

        if ($settings_action === 'toggle_product') {
            $this->toggle_product();
            $this->settings_redirect('settings-saved');
        }

        if ($settings_action === 'delete_product') {
            $this->delete_product();
            $this->settings_redirect('settings-deleted');
        }

        if ($settings_action === 'save_category') {
            $this->save_category();
            $this->settings_redirect('settings-saved');
        }

        if ($settings_action === 'delete_category') {
            $this->delete_category();
            $this->settings_redirect('settings-deleted');
        }

        $this->settings_redirect('settings-invalid');
    }

    public function handle_pricing_action() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acces refuse.', 'annacreation-configurator'));
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['anna_pricing_nonce'] ?? ''));

        if (!$nonce || !wp_verify_nonce($nonce, 'anna_pricing_action')) {
            wp_die(esc_html__('Nonce invalide.', 'annacreation-configurator'));
        }

        check_admin_referer('anna_pricing_action', 'anna_pricing_nonce');

        AnnaCreation_Pricing::save_settings(wp_unslash($_POST['pricing'] ?? []));

        wp_safe_redirect(add_query_arg(
            [
                'page' => 'anna-tarification',
                'anna_notice' => 'pricing-saved',
            ],
            admin_url('admin.php')
        ));
        exit;
    }

    public function handle_physical_settings_action() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Acces refuse.', 'annacreation-configurator'));
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['anna_physical_settings_nonce'] ?? ''));

        if (!$nonce || !wp_verify_nonce($nonce, 'anna_physical_settings_action')) {
            wp_die(esc_html__('Nonce invalide.', 'annacreation-configurator'));
        }

        check_admin_referer('anna_physical_settings_action', 'anna_physical_settings_nonce');

        update_option(
            self::OPTION_PHYSICAL_SETTINGS,
            $this->sanitize_physical_settings(wp_unslash($_POST['physical_settings'] ?? [])),
            false
        );

        wp_safe_redirect(add_query_arg(
            [
                'page' => 'anna-dimensions-physiques',
                'anna_notice' => 'physical-settings-saved',
            ],
            admin_url('admin.php')
        ));
        exit;
    }

    private function save_product() {
        $slug = sanitize_key(wp_unslash($_POST['slug'] ?? ''));

        if (!$slug) {
            return;
        }

        $products = AnnaCreation_Category_Rules::all_products();
        $base_image_url = esc_url_raw($products[$slug]['base_image_url'] ?? '');
        $uploaded_base_image = $this->upload_product_base_image();

        if ($uploaded_base_image) {
            $base_image_url = $uploaded_base_image;
        }

        $products[$slug] = [
            'label' => sanitize_text_field(wp_unslash($_POST['label'] ?? $slug)),
            'active' => !empty($_POST['active']),
            'clip_categories' => array_values(array_filter(array_map('sanitize_key', (array) ($_POST['clip_categories'] ?? [])))),
            'base_image_url' => $base_image_url,
        ];

        AnnaCreation_Category_Rules::save_products($products);
    }

    private function upload_product_base_image() {
        if (empty($_FILES['base_image']) || empty($_FILES['base_image']['name'])) {
            return '';
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $file = [
            'name' => sanitize_file_name((string) ($_FILES['base_image']['name'] ?? '')),
            'type' => sanitize_text_field((string) ($_FILES['base_image']['type'] ?? '')),
            'tmp_name' => (string) ($_FILES['base_image']['tmp_name'] ?? ''),
            'error' => (int) ($_FILES['base_image']['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($_FILES['base_image']['size'] ?? 0),
        ];

        $uploaded = wp_handle_upload($file, ['test_form' => false]);

        if (!empty($uploaded['error']) || empty($uploaded['url']) || empty($uploaded['file'])) {
            return '';
        }

        $filetype = wp_check_filetype($uploaded['file']);

        if (empty($filetype['type']) || strpos($filetype['type'], 'image/') !== 0) {
            return '';
        }

        return esc_url_raw($uploaded['url']);
    }

    private function toggle_product() {
        $slug = sanitize_key(wp_unslash($_POST['slug'] ?? ''));
        $products = AnnaCreation_Category_Rules::all_products();

        if (!$slug || !isset($products[$slug])) {
            return;
        }

        $products[$slug]['active'] = empty($products[$slug]['active']);
        AnnaCreation_Category_Rules::save_products($products);
    }

    private function delete_product() {
        $slug = sanitize_key(wp_unslash($_POST['slug'] ?? ''));
        $products = AnnaCreation_Category_Rules::all_products();

        unset($products[$slug]);
        AnnaCreation_Category_Rules::save_products($products);
    }

    private function save_category() {
        $group = sanitize_key(wp_unslash($_POST['group'] ?? ''));
        $slug = sanitize_key(wp_unslash($_POST['slug'] ?? ''));

        if (!in_array($group, ['clips', 'fantaisies'], true) || !$slug) {
            return;
        }

        $categories = AnnaCreation_Category_Rules::categories(true);
        $categories[$group][$slug] = [
            'label' => sanitize_text_field(wp_unslash($_POST['label'] ?? $slug)),
            'active' => !empty($_POST['active']),
        ];

        if ($group === 'clips') {
            $categories[$group][$slug]['extra'] = (float) str_replace(',', '.', sanitize_text_field(wp_unslash($_POST['extra'] ?? '0')));
        } else {
            $pricing_type = sanitize_key(wp_unslash($_POST['pricing_type'] ?? 'classique'));
            $categories[$group][$slug]['pricing_type'] = in_array($pricing_type, ['classique', 'foot', 'anime'], true) ? $pricing_type : 'classique';
        }

        AnnaCreation_Category_Rules::save_categories($categories);
    }

    private function delete_category() {
        $group = sanitize_key(wp_unslash($_POST['group'] ?? ''));
        $slug = sanitize_key(wp_unslash($_POST['slug'] ?? ''));
        $categories = AnnaCreation_Category_Rules::categories(true);

        if (isset($categories[$group][$slug])) {
            unset($categories[$group][$slug]);
            AnnaCreation_Category_Rules::save_categories($categories);
        }
    }

    private function upload_media($type, $category) {
        if (empty($_FILES['images']) || empty($_FILES['images']['name'])) {
            return 'empty';
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $files = $this->normalize_uploaded_files($_FILES['images']);
        $urls = [];

        foreach ($files as $file) {
            if (empty($file['name'])) {
                continue;
            }

            $uploaded = wp_handle_upload($file, ['test_form' => false]);

            if (!empty($uploaded['error']) || empty($uploaded['url'])) {
                continue;
            }

            $filetype = wp_check_filetype($uploaded['file']);

            if (empty($filetype['type']) || strpos($filetype['type'], 'image/') !== 0) {
                continue;
            }

            $urls[] = esc_url_raw($uploaded['url']);
        }

        $urls = $this->sanitize_media_items($urls);

        if (!$urls) {
            return 'empty';
        }

        $data = $this->media_data();
        $data = $this->ensure_media_path($data, $type, $category);
        $existing = $this->sanitize_media_items($data[$type][$category]);
        $merged = $this->sanitize_media_items(array_merge($existing, $urls));
        $added = count(array_diff($merged, $existing));
        $data[$type][$category] = $merged;

        update_option(self::OPTION_MEDIA, $data, false);

        return $added > 0 ? 'added-' . $added : 'duplicate';
    }

    private function delete_media($type, $category) {
        $url = esc_url_raw(trim((string) wp_unslash($_POST['url'] ?? '')));

        if (!$url) {
            return 'empty';
        }

        $data = $this->media_data();
        $data = $this->ensure_media_path($data, $type, $category);
        $current = $this->sanitize_media_items($data[$type][$category]);
        $data[$type][$category] = array_values(array_filter(
            $current,
            static function ($item) use ($url) {
                return $item !== $url;
            }
        ));

        update_option(self::OPTION_MEDIA, $data, false);
        $this->delete_media_label($type, $category, $url);

        return count($current) === count($data[$type][$category]) ? 'empty' : 'deleted';
    }

    private function delete_media_bulk($type, $category) {
        $urls = array_map(static function ($url) {
            return esc_url_raw(trim((string) wp_unslash($url)));
        }, (array) ($_POST['urls'] ?? []));
        $urls = array_values(array_filter(array_unique($urls)));

        if (!$urls) {
            return 'empty';
        }

        $data = $this->media_data();
        $data = $this->ensure_media_path($data, $type, $category);
        $current = $this->sanitize_media_items($data[$type][$category]);
        $remove = array_flip($urls);

        $data[$type][$category] = array_values(array_filter(
            $current,
            static function ($item) use ($remove) {
                return !isset($remove[$item]);
            }
        ));

        update_option(self::OPTION_MEDIA, $data, false);
        $this->delete_media_labels($type, $category, $urls);

        return count($current) === count($data[$type][$category]) ? 'empty' : 'deleted';
    }

    private function save_media_label($type, $category) {
        $url = esc_url_raw(trim((string) wp_unslash($_POST['url'] ?? '')));
        $display_name = sanitize_text_field(wp_unslash($_POST['display_name'] ?? ''));

        if (!$url) {
            return 'empty';
        }

        $labels = $this->media_labels_data();
        $key = $this->media_label_key($url);

        if (!isset($labels[$type]) || !is_array($labels[$type])) {
            $labels[$type] = [];
        }

        if (!isset($labels[$type][$category]) || !is_array($labels[$type][$category])) {
            $labels[$type][$category] = [];
        }

        if ($display_name === '') {
            unset($labels[$type][$category][$key]);
        } else {
            $labels[$type][$category][$key] = $display_name;
        }

        update_option(self::OPTION_MEDIA_LABELS, $labels, false);

        return 'settings-saved';
    }

    private function normalize_uploaded_files($files) {
        $normalized = [];

        foreach ((array) ($files['name'] ?? []) as $index => $name) {
            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }

        return $normalized;
    }

    private function sanitize_media_items($items) {
        if (!is_array($items)) {
            return [];
        }

        $items = array_map(static function ($url) {
            return esc_url_raw(trim((string) $url));
        }, $items);

        return array_values(array_filter(array_unique($items)));
    }

    private function media_labels_data() {
        $labels = get_option(self::OPTION_MEDIA_LABELS, []);

        return is_array($labels) ? $labels : [];
    }

    private function media_display_name($labels, $type, $category, $url) {
        $key = $this->media_label_key($url);

        return sanitize_text_field($labels[$type][$category][$key] ?? '');
    }

    private function delete_media_label($type, $category, $url) {
        $this->delete_media_labels($type, $category, [$url]);
    }

    private function delete_media_labels($type, $category, $urls) {
        $labels = $this->media_labels_data();

        foreach ((array) $urls as $url) {
            unset($labels[$type][$category][$this->media_label_key($url)]);
        }

        update_option(self::OPTION_MEDIA_LABELS, $labels, false);
    }

    private function media_label_key($url) {
        return md5(esc_url_raw(trim((string) $url)));
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

    private function is_valid_target($type, $category) {
        if (!in_array($type, ['clip', 'motif'], true)) {
            return false;
        }

        $sections = $this->sections();
        $categories = [];

        foreach ($sections as $section) {
            if ($section['type'] === $type) {
                $categories = $section['categories'];
                break;
            }
        }

        return isset($categories[$category]);
    }

    private function sections() {
        $categories = AnnaCreation_Pricing::fixed_categories();

        return [
            [
                'label' => __('Clips', 'annacreation-configurator'),
                'group' => 'clips',
                'type' => 'clip',
                'categories' => $categories['clips'] ?? [],
            ],
            [
                'label' => __('Fantaisies', 'annacreation-configurator'),
                'group' => 'fantaisies',
                'type' => 'motif',
                'categories' => $categories['fantaisies'] ?? [],
            ],
        ];
    }

    private function section_by_type($type) {
        foreach ($this->sections() as $section) {
            if ($section['type'] === $type) {
                return $section;
            }
        }

        return null;
    }

    private function count_section_images($media, $section) {
        $count = 0;
        $type = $section['type'];

        foreach (array_keys($section['categories']) as $category) {
            $count += count($this->sanitize_media_items($media[$type][$category] ?? []));
        }

        return $count;
    }

    private function format_rule_map($items) {
        $parts = [];

        foreach ((array) $items as $key => $value) {
            $parts[] = sanitize_key((string) $key) . ': ' . sanitize_text_field((string) $value);
        }

        return implode(', ', $parts) ?: '-';
    }

    private function media_data() {
        $data = get_option(self::OPTION_MEDIA, []);

        return is_array($data) ? $data : [];
    }

    private function physical_settings() {
        return $this->sanitize_physical_settings(get_option(self::OPTION_PHYSICAL_SETTINGS, []));
    }

    private function default_physical_settings() {
        return [
            'attache-tetine' => 160,
            'attache-doudou' => 220,
            'porte-cle' => 160,
            'double-porte-cle' => 160,
            'anneau-dentition' => 180,
        ];
    }

    private function physical_setting_labels() {
        return [
            'attache-tetine' => __('Attache-tetine', 'annacreation-configurator'),
            'attache-doudou' => __('Attache-doudou', 'annacreation-configurator'),
            'porte-cle' => __('Porte-cle', 'annacreation-configurator'),
            'double-porte-cle' => __('Double porte-cle - longueur par barre', 'annacreation-configurator'),
            'anneau-dentition' => __('Anneau de dentition - longueur utile par defaut', 'annacreation-configurator'),
        ];
    }

    private function sanitize_physical_settings($settings) {
        $defaults = $this->default_physical_settings();
        $settings = is_array($settings) ? $settings : [];
        $clean = [];

        foreach ($defaults as $slug => $default_value) {
            $raw_value = $settings[$slug] ?? $default_value;
            $value = absint(sanitize_text_field((string) $raw_value));
            $clean[$slug] = $value > 0 ? $value : $default_value;
        }

        $clean['category_sizes'] = $this->sanitize_physical_category_sizes($settings['category_sizes'] ?? []);

        return $clean;
    }

    private function sanitize_physical_category_sizes($category_sizes) {
        $category_sizes = is_array($category_sizes) ? $category_sizes : [];
        $categories = AnnaCreation_Category_Rules::categories(true)['fantaisies'] ?? [];
        $clean = [];

        foreach ($categories as $slug => $category) {
            $slug = sanitize_key($slug);
            $default_value = $this->default_physical_category_size($slug);
            $raw_value = $category_sizes[$slug] ?? $default_value;
            $value = absint(sanitize_text_field((string) $raw_value));

            if ($slug === 'perle-ronde' && $value === 32) {
                $value = $default_value;
            }

            $clean[$slug] = $value > 0 ? $value : $default_value;
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

    private function ensure_media_path($data, $type, $category) {
        if (!isset($data[$type]) || !is_array($data[$type])) {
            $data[$type] = [];
        }

        if (!isset($data[$type][$category]) || !is_array($data[$type][$category])) {
            $data[$type][$category] = [];
        }

        return $data;
    }

    private function notice() {
        $code = sanitize_key(wp_unslash($_GET['anna_notice'] ?? ''));

        if (preg_match('/^added-([0-9]+)$/', $code, $matches)) {
            $count = absint($matches[1]);

            return [
                'type' => 'success',
                'message' => sprintf(
                    _n('%d image ajoutee avec succes.', '%d images ajoutees avec succes.', $count, 'annacreation-configurator'),
                    $count
                ),
            ];
        }

        $messages = [
            'added' => ['type' => 'success', 'message' => __('Image(s) ajoutee(s). Les doublons ont ete ignores.', 'annacreation-configurator')],
            'deleted' => ['type' => 'success', 'message' => __('Image supprimee.', 'annacreation-configurator')],
            'duplicate' => ['type' => 'warning', 'message' => __('Aucune image ajoutee : les images selectionnees existent deja dans cette categorie.', 'annacreation-configurator')],
            'empty' => ['type' => 'warning', 'message' => __('Aucune image valide selectionnee.', 'annacreation-configurator')],
            'invalid' => ['type' => 'error', 'message' => __('Action media invalide.', 'annacreation-configurator')],
            'settings-saved' => ['type' => 'success', 'message' => __('Parametres enregistres.', 'annacreation-configurator')],
            'settings-deleted' => ['type' => 'success', 'message' => __('Element supprime.', 'annacreation-configurator')],
            'settings-invalid' => ['type' => 'error', 'message' => __('Action parametres invalide.', 'annacreation-configurator')],
            'pricing-saved' => ['type' => 'success', 'message' => __('Tarification enregistree.', 'annacreation-configurator')],
            'physical-settings-saved' => ['type' => 'success', 'message' => __('Dimensions physiques enregistrees.', 'annacreation-configurator')],
        ];

        return $messages[$code] ?? null;
    }

    private function settings_notice() {
        return $this->notice();
    }

    private function redirect($notice, $type = '') {
        $page = 'anna-creation';

        if ($type === 'clip') {
            $page = 'anna-medias-clips';
        } elseif ($type === 'motif') {
            $page = 'anna-medias-fantaisies';
        }

        wp_safe_redirect(add_query_arg(
            [
                'page' => $page,
                'anna_notice' => $notice,
            ],
            admin_url('admin.php')
        ));
        exit;
    }

    private function settings_redirect($notice) {
        wp_safe_redirect(add_query_arg(
            [
                'page' => 'anna-parametres',
                'anna_notice' => $notice,
            ],
            admin_url('admin.php')
        ));
        exit;
    }
}
