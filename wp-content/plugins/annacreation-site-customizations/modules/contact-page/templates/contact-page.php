<?php
/**
 * AnnaCreation Contact page template.
 */

defined( 'ABSPATH' ) || exit;

$email     = sanitize_email( get_option( 'annacreation_contact_email', get_option( 'admin_email' ) ) );
$phone     = sanitize_text_field( get_option( 'annacreation_contact_phone', '' ) );
$phone_url = preg_replace( '/[^0-9+]/', '', $phone );

get_header();
?>

<main id="main" class="ac-contact">
	<section class="ac-contact__hero" aria-labelledby="ac-contact-title">
		<div class="ac-contact__hero-decoration ac-contact__hero-decoration--one" aria-hidden="true"></div>
		<div class="ac-contact__hero-decoration ac-contact__hero-decoration--two" aria-hidden="true"></div>
		<div class="ac-contact__shell ac-contact__hero-inner">
			<p class="ac-contact__eyebrow">Nous sommes à votre écoute</p>
			<h1 id="ac-contact-title">Contactez-nous</h1>
			<p>Une question sur une création personnalisée ? Besoin d’aide pour composer votre modèle ? L’équipe AnnaCreation vous accompagne avec plaisir.</p>
			<span class="ac-contact__hero-accent" aria-hidden="true"></span>
		</div>
	</section>

	<section class="ac-contact__details" aria-labelledby="ac-contact-details-title">
		<div class="ac-contact__shell">
			<div class="ac-contact__section-heading">
				<p class="ac-contact__eyebrow">Toutes les informations utiles</p>
				<h2 id="ac-contact-details-title">Restons en contact</h2>
			</div>

			<div class="ac-contact__cards">
				<article class="ac-contact__card">
					<span class="ac-contact__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
					</span>
					<h3>Adresse</h3>
					<p>France</p>
				</article>

				<article class="ac-contact__card">
					<span class="ac-contact__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
					</span>
					<h3>Horaires</h3>
					<p>Lundi au samedi<br>Réponse sous 24h à 48h</p>
				</article>

				<article class="ac-contact__card">
					<span class="ac-contact__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
					</span>
					<h3>Email</h3>
					<p><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
				</article>

				<article class="ac-contact__card">
					<span class="ac-contact__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M7 3h3l1.5 4-2 1.5a15 15 0 0 0 6 6l1.5-2L21 14v3a4 4 0 0 1-4 4C9.3 20.5 3.5 14.7 3 7a4 4 0 0 1 4-4Z"/><path d="M16 4a5 5 0 0 1 4 4M16 8a1 1 0 0 1 1 1"/></svg>
					</span>
					<h3>Téléphone / WhatsApp</h3>
					<?php if ( $phone ) : ?>
						<p><a href="tel:<?php echo esc_attr( $phone_url ); ?>"><?php echo esc_html( $phone ); ?></a></p>
					<?php else : ?>
						<p>À renseigner dans Réglages → Coordonnées AnnaCreation</p>
					<?php endif; ?>
				</article>
			</div>
		</div>
	</section>

	<section class="ac-contact__message" aria-labelledby="ac-contact-form-title">
		<div class="ac-contact__shell ac-contact__message-grid">
			<div class="ac-contact__map">
				<div class="ac-contact__map-label">
					<span aria-hidden="true"></span>
					AnnaCreation · France
				</div>
				<iframe
					title="Localisation AnnaCreation en France"
					src="https://maps.google.com/maps?q=France&amp;t=m&amp;z=5&amp;output=embed&amp;iwloc=near"
					loading="lazy"
					referrerpolicy="no-referrer-when-downgrade"
					allowfullscreen
				></iframe>
			</div>

			<div class="ac-contact__form-panel">
				<p class="ac-contact__eyebrow">Parlons de votre création</p>
				<h2 id="ac-contact-form-title">Envoyez-nous un message</h2>
				<p>Nous vous répondrons rapidement pour vous accompagner dans votre commande personnalisée.</p>
				<div class="ac-contact__form">
					<?php echo do_shortcode( '[wpforms id="506" title="false" description="false"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
