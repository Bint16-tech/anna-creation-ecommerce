<?php
/**
 * Plugin Name: AnnaCreation - Sections de l'accueil
 * Description: Remplace uniquement les sections génériques situées sous la bannière de la page d'accueil par une section WooCommerce dynamique.
 * Version: 1.1.2
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
		'1.1.2'
	);
}
add_action( 'wp_enqueue_scripts', 'annacreation_home_sections_enqueue_assets', 30 );

function annacreation_home_get_products() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	return wc_get_products(
		array(
		'limit'      => 4,
		'status'     => 'publish',
		'visibility' => 'visible',
		'orderby'    => 'date',
		'order'      => 'DESC',
		'return'     => 'objects',
		)
	);
}

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
 * Appends the AnnaCreation WooCommerce product section after Elementor has rendered the page.
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
	$products = annacreation_home_get_products();

	ob_start();
	?>
	<section class="ac-home-products" aria-labelledby="ac-home-products-title">
		<div class="ac-home-products__shell">
			<header class="ac-home-products__heading">
				<p class="ac-home-products__eyebrow">L'univers AnnaCreation</p>
				<h2 id="ac-home-products-title">Nos créations personnalisées</h2>
				<p>Découvrez nos accessoires faits avec soin et personnalisez chaque détail selon vos envies.</p>
			</header>

			<?php if ( ! empty( $products ) ) : ?>
				<div class="ac-home-products__grid">
					<?php foreach ( $products as $product ) : ?>
						<?php
						$product_id          = $product->get_id();
						$personalization_url = function_exists( 'annacreation_get_product_personalization_url' )
							? annacreation_get_product_personalization_url( $product_id )
							: '';
						?>
						<article class="ac-home-product">
							<a class="ac-home-product__image" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
								<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); ?>
							</a>
							<div class="ac-home-product__content">
								<h3>
									<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
										<?php echo esc_html( $product->get_name() ); ?>
									</a>
								</h3>

								<?php if ( $product->get_short_description() ) : ?>
									<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 18 ) ); ?></p>
								<?php endif; ?>

								<?php if ( $product->get_price_html() ) : ?>
									<div class="ac-home-product__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
								<?php endif; ?>

								<div class="ac-home-product__actions">
									<?php
									echo apply_filters(
										'woocommerce_loop_add_to_cart_link',
										sprintf(
											'<a href="%s" data-quantity="1" class="%s" %s>%s</a>',
											esc_url( $product->add_to_cart_url() ),
											esc_attr( implode( ' ', array_filter( array( 'button', 'product_type_' . $product->get_type(), $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '', $product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : '' ) ) ) ),
											wc_implode_html_attributes(
												array(
													'data-product_id'  => $product_id,
													'data-product_sku' => $product->get_sku(),
													'aria-label'       => $product->add_to_cart_description(),
													'rel'              => 'nofollow',
												)
											),
											esc_html( $product->add_to_cart_text() )
										),
										$product
									);
									?>

									<?php if ( $personalization_url ) : ?>
										<a class="ac-home-product__button ac-home-product__button--outline" href="<?php echo esc_url( $personalization_url ); ?>">Personnaliser</a>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="ac-home-products__empty">Ajoutez ou mettez en avant des produits WooCommerce pour les afficher ici.</p>
			<?php endif; ?>
		</div>
	</section>
	<?php

	return $content . ob_get_clean();
}
add_filter( 'elementor/frontend/the_content', 'annacreation_home_sections_content', 20 );
