<?php
/**
 * Plugin Name: AnnaCreation - Style WooCommerce
 * Description: Style la boutique, les catégories produits et ajoute un lien de personnalisation administrable sur les produits WooCommerce.
 * Version: 1.1.0
 * Author: AnnaCreation
 */

defined( 'ABSPATH' ) || exit;

function annacreation_wc_style_is_shop_context() {
	return function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_taxonomy() || is_product() );
}

function annacreation_wc_style_enqueue_assets() {
	if ( ! annacreation_wc_style_is_shop_context() ) {
		return;
	}

	wp_enqueue_style(
		'annacreation-woocommerce-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/woocommerce-style.css',
		array(),
		'1.1.0'
	);
}
add_action( 'wp_enqueue_scripts', 'annacreation_wc_style_enqueue_assets', 30 );

function annacreation_personalization_category_map() {
	return array(
		'attache-tetine'      => '/attache-tetine/',
		'attache-doudou'      => '/attache-doudou/',
		'anneau-de-dentition' => '/anneau-de-dentition/',
		'porte-cle'           => '/porte-cle/',
		'double-porte-cle'    => '/double-port-cle/',
	);
}

function annacreation_normalize_personalization_url( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	if ( 0 === strpos( $url, '/' ) ) {
		return home_url( $url );
	}

	return $url;
}

function annacreation_get_personalization_url_from_categories( $term_ids ) {
	$term_ids = array_values( array_filter( array_map( 'absint', (array) $term_ids ) ) );

	if ( empty( $term_ids ) ) {
		return '';
	}

	$selected_slugs = array();

	foreach ( $term_ids as $term_id ) {
		$term = get_term( $term_id, 'product_cat' );

		if ( $term && ! is_wp_error( $term ) ) {
			$selected_slugs[] = $term->slug;
		}
	}

	foreach ( annacreation_personalization_category_map() as $slug => $url ) {
		if ( in_array( $slug, $selected_slugs, true ) ) {
			return $url;
		}
	}

	return '';
}

function annacreation_get_product_personalization_url( $product_id ) {
	$url = get_post_meta( $product_id, '_annacreation_personalization_url', true );
	$url = annacreation_normalize_personalization_url( $url );

	if ( $url ) {
		return $url;
	}

	$term_ids = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

	if ( is_wp_error( $term_ids ) ) {
		return '';
	}

	return annacreation_normalize_personalization_url(
		annacreation_get_personalization_url_from_categories( $term_ids )
	);
}

function annacreation_wc_style_product_field() {
	echo '<div class="options_group">';

	woocommerce_wp_text_input(
		array(
			'id'          => '_annacreation_personalization_url',
			'label'       => 'URL de personnalisation',
			'placeholder' => '/attache-tetine/',
			'description' => 'Optionnel. Si renseigné, un bouton “Personnaliser” apparaîtra sur la boutique, les catégories et l’accueil.',
			'desc_tip'    => true,
			'type'        => 'text',
		)
	);

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'annacreation_wc_style_product_field' );

function annacreation_wc_style_save_product_field( $product ) {
	if ( ! isset( $_POST['_annacreation_personalization_url'] ) ) {
		return;
	}

	$url = wc_clean( wp_unslash( $_POST['_annacreation_personalization_url'] ) );

	if ( '' === trim( (string) $url ) ) {
		$term_ids = array();

		if ( isset( $_POST['tax_input']['product_cat'] ) ) {
			$term_ids = (array) wp_unslash( $_POST['tax_input']['product_cat'] );
		} elseif ( isset( $_POST['product_cat'] ) ) {
			$term_ids = (array) wp_unslash( $_POST['product_cat'] );
		} else {
			$term_ids = $product->get_category_ids();
		}

		$url = annacreation_get_personalization_url_from_categories( $term_ids );
	}

	$product->update_meta_data( '_annacreation_personalization_url', $url );
}
add_action( 'woocommerce_admin_process_product_object', 'annacreation_wc_style_save_product_field' );

function annacreation_wc_style_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}

	$term_map = array();

	foreach ( annacreation_personalization_category_map() as $slug => $url ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );

		if ( $term ) {
			$term_map[ (string) $term->term_id ] = $url;
		}
	}

	wp_add_inline_script(
		'jquery-core',
		'window.annaPersonalizationCategoryMap = ' . wp_json_encode( $term_map ) . ';
		jQuery(function($) {
			var $field = $("#_annacreation_personalization_url");
			var map = window.annaPersonalizationCategoryMap || {};

			function fillPersonalizationUrl() {
				if (!$field.length || $.trim($field.val()) !== "") {
					return;
				}

				$.each(map, function(termId, url) {
					var $checkbox = $("#in-product_cat-" + termId + ", #in-popular-product_cat-" + termId);

					if ($checkbox.filter(":checked").length) {
						$field.val(url).trigger("change");
						return false;
					}
				});
			}

			$(document).on("change", "#product_catchecklist input[type=checkbox], #product_catchecklist-pop input[type=checkbox]", fillPersonalizationUrl);
			fillPersonalizationUrl();
		});'
	);
}
add_action( 'admin_enqueue_scripts', 'annacreation_wc_style_admin_assets' );

function annacreation_wc_style_category_nav() {
	if ( ! ( is_shop() || is_product_taxonomy() ) ) {
		return;
	}

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( empty( $categories ) || is_wp_error( $categories ) ) {
		return;
	}

	echo '<nav class="ac-shop-categories" aria-label="Catégories produits">';
	echo '<a class="ac-shop-category' . ( is_shop() ? ' is-active' : '' ) . '" href="' . esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) . '">Tous les produits</a>';

	foreach ( $categories as $category ) {
		$link = get_term_link( $category );

		if ( is_wp_error( $link ) ) {
			continue;
		}

		$is_active = is_product_category( $category->slug );

		echo '<a class="ac-shop-category' . ( $is_active ? ' is-active' : '' ) . '" href="' . esc_url( $link ) . '">' . esc_html( $category->name ) . '</a>';
	}

	echo '</nav>';
}
add_action( 'woocommerce_before_shop_loop', 'annacreation_wc_style_category_nav', 4 );

function annacreation_wc_style_category_fallback_description() {
	if ( ! is_product_category() ) {
		return;
	}

	$term = get_queried_object();

	if ( ! $term instanceof WP_Term || '' !== trim( (string) $term->description ) ) {
		return;
	}

	echo '<div class="term-description ac-term-description"><p>Découvrez nos créations de la catégorie ' . esc_html( $term->name ) . ', pensées pour un univers bébé doux, artisanal et personnalisable.</p></div>';
}
add_action( 'woocommerce_archive_description', 'annacreation_wc_style_category_fallback_description', 12 );

function annacreation_wc_style_add_to_cart_text( $text, $product ) {
	if ( $product instanceof WC_Product && $product->is_purchasable() && $product->is_in_stock() ) {
		return 'Ajouter au panier';
	}

	return $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'annacreation_wc_style_add_to_cart_text', 10, 2 );
add_filter( 'woocommerce_product_single_add_to_cart_text', 'annacreation_wc_style_add_to_cart_text', 10, 2 );

function annacreation_wc_style_personalization_loop_button() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$url = annacreation_get_product_personalization_url( $product->get_id() );

	if ( ! $url ) {
		return;
	}

	echo '<a class="button ac-personalize-button" href="' . esc_url( $url ) . '">Personnaliser</a>';
}
add_action( 'woocommerce_after_shop_loop_item', 'annacreation_wc_style_personalization_loop_button', 12 );

function annacreation_wc_style_personalization_single_button() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$url = annacreation_get_product_personalization_url( $product->get_id() );

	if ( ! $url ) {
		return;
	}

	echo '<p class="ac-single-personalize"><a class="button ac-personalize-button" href="' . esc_url( $url ) . '">Personnaliser ce produit</a></p>';
}
add_action( 'woocommerce_single_product_summary', 'annacreation_wc_style_personalization_single_button', 32 );
