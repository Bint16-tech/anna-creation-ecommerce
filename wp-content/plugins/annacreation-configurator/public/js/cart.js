(function () {
    if (!window.Anna) return;

    Object.assign(window.Anna, {
        validateCart() {
            const config = this.buildConfig();
            const required = this.requiredClipCount();
            const max = typeof this.maxClipCount === 'function' ? this.maxClipCount() : required;

            if (config.text.length > this.maxLetters) {
                return 'Maximum 9 lettres autorisées.';
            }

            if (typeof this.limitedLetterCategoryCount === 'function') {
                const letterLimit = typeof this.letterCategoryLimit === 'function' ? this.letterCategoryLimit() : 9;

                if (this.limitedLetterCategoryCount(config.motifs) > letterLimit) {
                    return 'Maximum 9 lettres autorisées.';
                }
            }

            if (typeof this.hasPhysicalBarLimit === 'function' && this.hasPhysicalBarLimit()) {
                const length = typeof this.availableBarLength === 'function' ? this.availableBarLength() : 0;
                const usedLength = typeof this.usedBarLength === 'function' ? this.usedBarLength(config.motifs) : 0;

                if (length > 0 && usedLength > length) {
                    return 'Impossible d\'ajouter cette fantaisie. La longueur maximale du produit est atteinte.';
                }
            }

            if (typeof this.isDoubleKeychain === 'function' && this.isDoubleKeychain()) {
                const length = typeof this.doubleBarLength === 'function' ? this.doubleBarLength() : 160;
                const barOneLength = typeof this.usedDoubleBarLength === 'function' ? this.usedDoubleBarLength(1, config.motifs) : 0;
                const barTwoLength = typeof this.usedDoubleBarLength === 'function' ? this.usedDoubleBarLength(2, config.motifs) : 0;

                if (barOneLength > length || barTwoLength > length) {
                    return 'Impossible d\'ajouter cette fantaisie. La longueur maximale de cette barre est atteinte.';
                }
            }

            if (typeof this.isTeethingRing === 'function' && this.isTeethingRing()) {
                const length = typeof this.teethingRingLength === 'function' ? this.teethingRingLength() : 180;
                const usedLength = typeof this.usedBarLength === 'function' ? this.usedBarLength(config.motifs) : 0;

                if (usedLength > length) {
                    return 'Impossible d\'ajouter cette fantaisie. L\'espace disponible sur l\'anneau est insuffisant.';
                }
            }

            if (required > 0 && config.clips.length < required) {
                if (this.normalize(config.product) === 'double-porte-cle') {
                    return 'Veuillez choisir 1 anneau.';
                }

                return required === 2 ? 'Veuillez choisir 2 clips.' : 'Veuillez choisir 1 clip.';
            }

            if (max > 0 && config.clips.length > max) {
                if (this.normalize(config.product) === 'double-porte-cle') {
                    return 'Un seul anneau maximum pour ce produit.';
                }

                return max === 1 ? 'Un seul clip maximum pour ce produit.' : `Maximum ${max} clips pour ce produit.`;
            }

            const rule = typeof this.productRule === 'function' ? this.productRule(config) : null;
            const allowedClipCategories = rule?.allowedClipCategories || [];

            if (allowedClipCategories.length) {
                const hasInvalidClip = config.clips.some((clip) => !allowedClipCategories.includes(this.normalize(clip.category)));

                if (hasInvalidClip) {
                    return this.normalize(config.product) === 'anneau-dentition'
                        ? 'Veuillez choisir un clip classique ou animé pour ce produit.'
                        : 'Veuillez choisir un anneau pour ce produit.';
                }
            }

            return '';
        },

        async addToCart() {
            const error = this.validateCart();
            if (error) {
                this.warning(error);
                return;
            }

            if (!this.wcProductId) {
                this.warning('Produit WooCommerce introuvable. Ajoutez wc_product_id au shortcode.');
                return;
            }

            const form = new FormData();
            form.append('action', 'anna_add_to_cart');
            form.append('nonce', window.annaData?.nonce || '');
            form.append('product_id', String(this.wcProductId));
            form.append('config', JSON.stringify(this.buildConfig()));

            const button = document.querySelector('.btn-add-cart');
            if (button) button.disabled = true;
            this.setStatus('Ajout au panier...');

            try {
                if (typeof this.captureModelDataUrl === 'function') {
                    const image = await this.captureModelDataUrl(1200);
                    if (image) {
                        form.append('image', image);
                    }
                }
            } catch (err) {
                this.warning('Impossible de générer l’image personnalisée.');
                if (button) button.disabled = false;
                return;
            }

            fetch(window.annaData?.ajaxUrl || '', {
                method: 'POST',
                credentials: 'same-origin',
                body: form
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.data?.message || 'Erreur ajout panier.');
                    }

                    const cartUrl = data.data?.cart_url || window.annaData?.cartUrl || '';
                    const message = cartUrl
                        ? 'Votre création a bien été ajoutée au panier. Vous pouvez finaliser votre commande depuis le panier.'
                        : 'Votre création a bien été ajoutée au panier.';

                    this.setStatus(message);

                    if (window.jQuery) {
                        window.jQuery(document.body).trigger('wc_fragment_refresh');
                    }
                })
                .catch((err) => this.warning(err.message))
                .finally(() => {
                    if (button) button.disabled = false;
                });
        }
    });
})();
