<?php
/**
 * AnnaCreation About page template.
 */

defined( 'ABSPATH' ) || exit;

$uploads_url = content_url( '/uploads/2026/06/' );
$shop_url    = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
$about_image = function_exists( 'annacreation_about_get_image_url' )
	? 'annacreation_about_get_image_url'
	: static function ( $key ) use ( $uploads_url ) {
		$defaults = array(
			'hero_main'           => $uploads_url . 'attacheTetine1-e1780390784806-621x1024.jpeg',
			'hero_small'          => $uploads_url . 'anneau-e1780388692539-609x1024.jpeg',
			'attache_tetine'      => $uploads_url . 'attacheTetine1-e1780390784806-621x1024.jpeg',
			'attache_doudou'      => $uploads_url . 'attacheMaron-473x1024.jpeg',
			'porte_cle'           => $uploads_url . 'portcle-473x1024.jpeg',
			'double_porte_cle'    => $uploads_url . 'portClemaron-473x1024.jpeg',
			'anneau_de_dentition' => $uploads_url . 'anneau-e1780388692539-609x1024.jpeg',
		);

		return $defaults[ $key ] ?? '';
	};

$products = array(
	array(
		'title'       => 'Attache-tétine personnalisée',
		'description' => 'Un accessoire pratique et unique, composé avec le prénom, les couleurs et les fantaisies de votre choix.',
		'image'       => $about_image( 'attache_tetine' ),
		'url'         => home_url( '/attache-tetine/' ),
	),
	array(
		'title'       => 'Attache-doudou personnalisée',
		'description' => 'Gardez le doudou préféré de bébé toujours à portée de main avec une création douce et personnalisée.',
		'image'       => $about_image( 'attache_doudou' ),
		'url'         => home_url( '/attache-doudou/' ),
	),
	array(
		'title'       => 'Porte-clé personnalisé',
		'description' => 'Prénom, perles, motifs et couleurs s’assemblent pour créer un petit accessoire qui vous ressemble.',
		'image'       => $about_image( 'porte_cle' ),
		'url'         => home_url( '/porte-cle/' ),
	),
	array(
		'title'       => 'Double porte-clé personnalisé',
		'description' => 'Deux créations assorties à partager, idéales pour célébrer un lien précieux en famille ou entre proches.',
		'image'       => $about_image( 'double_porte_cle' ),
		'url'         => home_url( '/double-port-cle/' ),
	),
	array(
		'title'       => 'Anneau de dentition personnalisé',
		'description' => 'Un anneau pensé pour les petites mains, personnalisé avec un prénom et une harmonie de perles choisie.',
		'image'       => $about_image( 'anneau_de_dentition' ),
		'url'         => home_url( '/anneau-de-dentition/' ),
	),
);

get_header();
?>

<main id="main" class="ac-about">
	<section class="ac-about__hero" aria-labelledby="ac-about-title">
		<div class="ac-about__shell ac-about__hero-grid">
			<div class="ac-about__hero-copy">
				<p class="ac-about__eyebrow">AnnaCreation · Créations personnalisées</p>
				<h1 id="ac-about-title">Des créations uniques pour les tout-petits</h1>
				<p class="ac-about__lead">Chez AnnaCreation, chaque accessoire est imaginé pour accompagner les bébés au quotidien tout en reflétant leur personnalité. Personnalisez chaque création selon vos envies grâce à un large choix de lettres, motifs et fantaisies.</p>
				<a class="ac-about__button" href="<?php echo esc_url( $shop_url ); ?>">Découvrir nos créations</a>
			</div>

			<div class="ac-about__hero-visual" aria-label="Créations personnalisées AnnaCreation">
				<div class="ac-about__hero-orbit" aria-hidden="true"></div>
				<img class="ac-about__hero-image ac-about__hero-image--main" src="<?php echo esc_url( $about_image( 'hero_main' ) ); ?>" alt="Attache-tétine rose personnalisée AnnaCreation">
				<img class="ac-about__hero-image ac-about__hero-image--small" src="<?php echo esc_url( $about_image( 'hero_small' ) ); ?>" alt="Anneau de dentition personnalisé AnnaCreation">
				<span class="ac-about__hero-note">Fait avec soin<br><strong>pour chaque histoire</strong></span>
			</div>
		</div>
	</section>

	<section class="ac-about__story" aria-labelledby="ac-story-title">
		<div class="ac-about__shell ac-about__story-grid">
			<div class="ac-about__story-mark" aria-hidden="true">
				<span>A</span>
				<small>AnnaCreation</small>
			</div>
			<div>
				<p class="ac-about__eyebrow">Qui sommes-nous ?</p>
				<h2 id="ac-story-title">Notre histoire</h2>
				<p>AnnaCreation est née de la passion pour les créations artisanales personnalisées. Notre objectif est de proposer des accessoires uniques, conçus avec soin, afin d'offrir aux familles des souvenirs précieux et des créations adaptées à chaque enfant.</p>
				<div class="ac-about__choices" aria-label="Possibilités de personnalisation">
					<span>Lettres</span><span>Perles</span><span>Fantaisies</span><span>Motifs</span><span>Couleurs</span><span>Clips</span>
				</div>
			</div>
		</div>
	</section>

	<section class="ac-about__products" aria-labelledby="ac-products-title">
		<div class="ac-about__shell">
			<div class="ac-about__section-heading">
				<div>
					<p class="ac-about__eyebrow">À chacun sa création</p>
					<h2 id="ac-products-title">Nos produits personnalisés</h2>
				</div>
				<p>Choisissez votre modèle, puis imaginez chaque détail pour en faire une création vraiment personnelle.</p>
			</div>

			<div class="ac-about__product-grid">
				<?php foreach ( $products as $index => $product ) : ?>
					<article class="ac-about__product-card<?php echo 0 === $index ? ' ac-about__product-card--featured' : ''; ?>">
						<a class="ac-about__product-image" href="<?php echo esc_url( $product['url'] ); ?>">
							<img src="<?php echo esc_url( $product['image'] ); ?>" alt="<?php echo esc_attr( $product['title'] ); ?>" loading="lazy">
							<span aria-hidden="true">0<?php echo esc_html( $index + 1 ); ?></span>
						</a>
						<div class="ac-about__product-content">
							<h3><a href="<?php echo esc_url( $product['url'] ); ?>"><?php echo esc_html( $product['title'] ); ?></a></h3>
							<p><?php echo esc_html( $product['description'] ); ?></p>
							<a class="ac-about__text-link" href="<?php echo esc_url( $product['url'] ); ?>">Personnaliser <span aria-hidden="true">→</span></a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="ac-about__benefits" aria-labelledby="ac-benefits-title">
		<div class="ac-about__shell">
			<div class="ac-about__section-heading ac-about__section-heading--center">
				<div>
					<p class="ac-about__eyebrow">Pensé jusque dans les détails</p>
					<h2 id="ac-benefits-title">Pourquoi nous choisir</h2>
				</div>
			</div>

			<div class="ac-about__benefit-grid">
				<article>
					<span class="ac-about__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M4 21v-7m0-4V3m8 18v-9m0-4V3m8 18v-5m0-4V3M1 14h6M9 8h6m2 8h6"/></svg>
					</span>
					<h3>Personnalisation complète</h3>
					<p>Composez chaque création selon vos envies.</p>
				</article>
				<article>
					<span class="ac-about__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="m12 3 2.2 4.5L19 8.2l-3.5 3.4.8 4.8L12 14.1l-4.3 2.3.8-4.8L5 8.2l4.8-.7L12 3Z"/><path d="M5 20h14"/></svg>
					</span>
					<h3>Fabrication soignée</h3>
					<p>Chaque produit est préparé avec attention.</p>
				</article>
				<article>
					<span class="ac-about__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2.7A4 4 0 0 1 19 11c0 5.6-7 10-7 10Z"/><path d="m18 3 .5 1.5L20 5l-1.5.5L18 7l-.5-1.5L16 5l1.5-.5L18 3Z"/></svg>
					</span>
					<h3>Créations uniques</h3>
					<p>Aucune création ne ressemble à une autre.</p>
				</article>
				<article>
					<span class="ac-about__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/><path d="M5 4h7"/></svg>
					</span>
					<h3>Expédition rapide</h3>
					<p>Vos commandes sont préparées et envoyées rapidement.</p>
				</article>
			</div>
		</div>
	</section>

	<section class="ac-about__cta" aria-labelledby="ac-cta-title">
		<div class="ac-about__shell ac-about__cta-inner">
			<div>
				<p class="ac-about__eyebrow">Une création à votre image</p>
				<h2 id="ac-cta-title">Créez votre modèle dès aujourd'hui</h2>
				<p>Personnalisez facilement votre accessoire et visualisez votre création avant de commander.</p>
			</div>
			<a class="ac-about__button ac-about__button--light" href="<?php echo esc_url( home_url( '/attache-tetine/' ) ); ?>">Commencer ma personnalisation</a>
		</div>
	</section>
</main>

<?php
get_footer();
