<?php
/**
 * Plugin Name: AnnaCreation - Sections de l’accueil
 * Description: Remplace uniquement les sections génériques situées sous la bannière de la page d’accueil.
 * Version: 1.0.0
 * Author: AnnaCreation
 */

defined( 'ABSPATH' ) || exit;

function annacreation_is_home_page() {
	return is_front_page() || is_page( 14 );
}

function annacreation_home_sections_enqueue_assets() {
	if ( ! annacreation_is_home_page() ) {
		return;
	}

	wp_enqueue_style(
		'annacreation-home-sections',
		plugin_dir_url( __FILE__ ) . 'assets/css/home-sections.css',
		array(),
		'1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'annacreation_home_sections_enqueue_assets', 30 );

/**
 * Keeps the existing first Elementor section (the banner) exactly as configured
 * and removes the imported generic sections that followed it.
 *
 * @param array $data    Elementor document data.
 * @param int   $post_id Elementor document ID.
 * @return array
 */
function annacreation_home_keep_banner_only( $data, $post_id ) {
	if ( 14 !== (int) $post_id || ! annacreation_is_home_page() || empty( $data ) ) {
		return $data;
	}

	foreach ( $data as $section ) {
		if ( isset( $section['id'] ) && 'f3bca7a' === $section['id'] ) {
			return array( $section );
		}
	}

	return $data;
}
add_filter( 'elementor/frontend/builder_content_data', 'annacreation_home_keep_banner_only', 20, 2 );

/**
 * Appends the AnnaCreation product section after Elementor has rendered the page.
 * The existing banner remains untouched.
 *
 * @param string $content Rendered Elementor content.
 * @return string
 */
function annacreation_home_sections_content( $content ) {
	if ( ! annacreation_is_home_page() || ! in_the_loop() ) {
		return $content;
	}

	static $rendered = false;

	if ( $rendered ) {
		return $content;
	}

	$rendered = true;

	$uploads_url = content_url( '/uploads/2026/06/' );
	$products    = array(
		array(
			'title'       => 'Attache-tétine personnalisée',
			'description' => 'Créez une attache-tétine unique avec lettres, clips et fantaisies.',
			'image'       => $uploads_url . 'attacheTetine1-e1780390784806-621x1024.jpeg',
			'url'         => home_url( '/attache-tetine/' ),
		),
		array(
			'title'       => 'Attache-doudou personnalisée',
			'description' => 'Un accessoire doux et pratique à personnaliser pour accompagner bébé.',
			'image'       => $uploads_url . 'attacheMaron-473x1024.jpeg',
			'url'         => home_url( '/attache-doudou/' ),
		),
		array(
			'title'       => 'Anneau de dentition personnalisé',
			'description' => 'Un anneau pensé pour bébé, personnalisable avec motifs et couleurs.',
			'image'       => $uploads_url . 'anneau-e1780388692539-609x1024.jpeg',
			'url'         => home_url( '/anneau-de-dentition/' ),
		),
		array(
			'title'       => 'Porte-clé personnalisé',
			'description' => 'Un souvenir unique à composer selon votre style.',
			'image'       => $uploads_url . 'portcle-473x1024.jpeg',
			'url'         => home_url( '/porte-cle/' ),
		),
		array(
			'title'       => 'Double porte-clé personnalisé',
			'description' => 'Deux créations assorties pour un cadeau original et personnalisé.',
			'image'       => $uploads_url . 'portClemaron-473x1024.jpeg',
			'url'         => home_url( '/double-port-cle/' ),
		),
	);

	ob_start();
	?>
	<section class="ac-home-products" aria-labelledby="ac-home-products-title">
		<div class="ac-home-products__shell">
			<header class="ac-home-products__heading">
				<p class="ac-home-products__eyebrow">L’univers AnnaCreation</p>
				<h2 id="ac-home-products-title">Nos créations personnalisées</h2>
				<p>Découvrez nos accessoires faits avec soin et personnalisez chaque détail selon vos envies.</p>
			</header>

			<div class="ac-home-products__grid">
				<?php foreach ( $products as $product ) : ?>
					<article class="ac-home-product">
						<a class="ac-home-product__image" href="<?php echo esc_url( $product['url'] ); ?>">
							<img src="<?php echo esc_url( $product['image'] ); ?>" alt="<?php echo esc_attr( $product['title'] ); ?>" loading="lazy">
						</a>
						<div class="ac-home-product__content">
							<h3><?php echo esc_html( $product['title'] ); ?></h3>
							<p><?php echo esc_html( $product['description'] ); ?></p>
							<a class="ac-home-product__button" href="<?php echo esc_url( $product['url'] ); ?>">Personnaliser</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php

	return $content . ob_get_clean();
}
add_filter( 'elementor/frontend/the_content', 'annacreation_home_sections_content', 20 );
