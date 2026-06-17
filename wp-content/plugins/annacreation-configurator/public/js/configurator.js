const Anna = {
    product: '',
    wcProductId: 0,
    clipRequired: false,
    maxLetters: 9,
    state: {
        clips: [],
        motifs: [],
        text: '',
        double: false
    },

    init() {
        const container = document.querySelector('.anna-layout');
        if (!container) return;

        this.container = container;
        this.canvas = document.getElementById('canvas');
        this.product = container.dataset.product || '';
        this.wcProductId = parseInt(container.dataset.wcProductId || '0', 10);
        this.clipRequired = (this.product !== 'anneau-dentition');

        this.initModal();
        this.initInputs();
        this.initDrag();
        this.updatePrice();

        if (this.clipRequired) {
            setTimeout(() => this.openModal(), 100);
        }
    },

    initInputs() {
        const textInput = document.getElementById('anna-text');

        if (textInput) {
            textInput.addEventListener('input', () => {
                textInput.value = textInput.value.slice(0, this.maxLetters).toUpperCase();
                this.state.text = textInput.value;
                this.renderText();
                this.updatePrice();
            });
        }

    },

    initModal() {
        const modal = document.getElementById('anna-modal');
        if (!modal) return;

        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        const closeBtn = modal.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.onclick = () => {
                if (!this.clipRequired || this.hasRequiredClips()) {
                    this.closeModal();
                } else {
                    this.warning('Veuillez choisir le nombre de clips requis.');
                }
            };
        }

        modal.onclick = (e) => {
            if (e.target === modal && (!this.clipRequired || this.hasRequiredClips())) {
                this.closeModal();
            }
        };

        this.initClipBrowser(modal);
    },

    initClipBrowser(modal) {
        const count = modal.querySelector('.clip-count');
        const buttons = Array.from(modal.querySelectorAll('.clip-category-button'));
        const groups = Array.from(modal.querySelectorAll('.clip-group'));
        const images = Array.from(modal.querySelectorAll('.img-opt-clip'));
        const empty = modal.querySelector('.clip-empty-results');
        let activeCategory = 'all';

        if (!images.length) return;

        const update = () => {
            let visibleCount = 0;

            groups.forEach((group) => {
                const category = group.dataset.category || '';
                const categoryMatches = activeCategory === 'all' || activeCategory === category;
                let groupHasVisibleImages = false;

                group.querySelectorAll('.img-opt-clip').forEach((img) => {
                    const visible = categoryMatches;

                    img.hidden = !visible;

                    if (visible) {
                        visibleCount += 1;
                        groupHasVisibleImages = true;
                    }
                });

                group.hidden = !groupHasVisibleImages;
            });

            if (count) {
                count.textContent = visibleCount === 1 ? '1 image' : `${visibleCount} images`;
            }

            if (empty) {
                empty.hidden = visibleCount > 0;
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                activeCategory = button.dataset.category || 'all';

                buttons.forEach((item) => {
                    item.classList.toggle('is-active', item === button);
                });

                update();
            });
        });

        update();
    },

    warning(msg) {
        this.setStatus(msg, true);
        let w = document.querySelector('.anna-warning');
        if (!w) {
            w = document.createElement('div');
            w.className = 'anna-warning';
            document.body.appendChild(w);
        }
        w.textContent = msg;
        w.style.display = 'block';
        setTimeout(() => w.style.display = 'none', 2400);
    },

    setStatus(msg, isError = false) {
        const status = document.getElementById('anna-status');
        if (!status) return;
        status.textContent = msg || '';
        status.classList.toggle('is-error', !!isError);
    },

    openModal() {
        const modal = document.getElementById('anna-modal');
        if (modal) modal.style.display = 'flex';
    },

    closeModal() {
        const modal = document.getElementById('anna-modal');
        if (modal) modal.style.display = 'none';
    },

    toggleAcc(btn) {
        btn.classList.toggle('active');
        const panel = btn.nextElementSibling;
        if (panel) {
            panel.style.display = panel.style.display === 'grid' ? 'none' : 'grid';
        }
    },

    requiredClipCount() {
        if (this.product === 'attache-doudou') return 1;
        if (this.product === 'anneau-dentition') return 0;
        return 1;
    },

    hasRequiredClips() {
        const required = this.requiredClipCount();
        const max = typeof this.maxClipCount === 'function' ? this.maxClipCount() : required;

        return required === 0 || (this.state.clips.length >= required && this.state.clips.length <= max);
    },

    normalizedSlug(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    },

    canonicalBarProduct(product = this.product) {
        const slug = this.normalizedSlug(product);
        return slug === 'double-port-cle' ? 'double-porte-cle' : slug;
    },

    hasPhysicalBarLimit() {
        return ['attache-tetine', 'attache-doudou', 'porte-cle'].includes(this.canonicalBarProduct());
    },

    availableBarLength() {
        const settings = window.annaData?.physicalSettings || {};
        const product = this.canonicalBarProduct();
        const value = parseInt(settings[product] || '0', 10);

        return value > 0 ? value : 0;
    },

    motifPhysicalSize(category) {
        const sizes = window.annaData?.physicalSettings?.category_sizes || {};
        const slug = this.normalizedSlug(category);
        const configured = parseInt(sizes[slug] || '0', 10);

        if (configured > 0) return configured;

        const exceptions = {
            'hexagone': 15,
            'perle-ronde': 12,
            'perle-ronde-15': 15,
            'perle-ronde-12': 12,
            'lentilles': 12,
            'lettre-blanche-doree': 12,
            'lettre-blanche-noir': 12,
            'lettre-blanche-noire': 12,
            'lettre-camel': 12
        };

        return exceptions[slug] || 32;
    },

    usedBarLength(motifs = this.state.motifs) {
        return (Array.isArray(motifs) ? motifs : []).reduce((total, motif) => {
            return total + this.motifPhysicalSize(motif.category);
        }, 0);
    },

    canAddMotifPhysically(category) {
        if (!this.hasPhysicalBarLimit()) return true;

        const available = this.availableBarLength();
        if (available <= 0) return true;

        this.syncStateFromDom();

        return this.usedBarLength() + this.motifPhysicalSize(category) <= available;
    },

    isTeethingRing() {
        return this.canonicalBarProduct() === 'anneau-dentition';
    },

    teethingRingLength() {
        const settings = window.annaData?.physicalSettings || {};
        const value = parseInt(settings['anneau-dentition'] || '180', 10);

        return value > 0 ? value : 180;
    },

    canAddMotifOnTeethingRing(category) {
        if (!this.isTeethingRing()) return true;

        this.syncStateFromDom();

        return this.usedBarLength() + this.motifPhysicalSize(category) <= this.teethingRingLength();
    },

    isDoubleKeychain() {
        return this.canonicalBarProduct() === 'double-porte-cle';
    },

    doubleBarLength() {
        const settings = window.annaData?.physicalSettings || {};
        const value = parseInt(settings['double-porte-cle'] || '160', 10);

        return value > 0 ? value : 160;
    },

    availableMotifPhysicalSizes() {
        const categories = new Set();

        document.querySelectorAll('.motifs-section .option-img').forEach((img) => {
            const call = img.getAttribute('onclick') || '';
            const match = call.match(/placerMotif\(this\.src,\s*'([^']+)'/);

            if (match && match[1]) {
                categories.add(match[1]);
            }
        });

        if (!categories.size) {
            Object.keys(window.annaData?.physicalSettings?.category_sizes || {}).forEach((category) => {
                categories.add(category);
            });
        }

        const sizes = Array.from(categories).map((category) => this.motifPhysicalSize(category)).filter((size) => size > 0);

        return sizes.length ? sizes : [32];
    },

    smallestAvailableMotifSize() {
        return Math.min(...this.availableMotifPhysicalSizes());
    },

    doubleBarMotifs(bar, motifs = this.state.motifs) {
        return (Array.isArray(motifs) ? motifs : []).filter((motif) => Number(motif.bar || 1) === bar);
    },

    usedDoubleBarLength(bar, motifs = this.state.motifs) {
        return this.doubleBarMotifs(bar, motifs).reduce((total, motif) => {
            return total + this.motifPhysicalSize(motif.category);
        }, 0);
    },

    doubleBarCanAcceptAny(bar = 1) {
        return this.doubleBarLength() - this.usedDoubleBarLength(bar) >= this.smallestAvailableMotifSize();
    },

    nextDoubleBarForMotif(category) {
        return this.doubleKeychainTargetForMotif(category).bar;
    },

    doubleKeychainTargetForMotif(category) {
        this.syncStateFromDom();

        const size = this.motifPhysicalSize(category);
        const length = this.doubleBarLength();
        const remainingBarOne = length - this.usedDoubleBarLength(1);
        const remainingBarTwo = length - this.usedDoubleBarLength(2);

        if (remainingBarOne >= size) {
            return { bar: 1, message: '' };
        }

        if (this.doubleBarCanAcceptAny(1)) {
            return {
                bar: 0,
                message: 'Veuillez compléter la première barre avant de personnaliser la seconde.'
            };
        }

        if (remainingBarTwo >= size) {
            return { bar: 2, message: '' };
        }

        return {
            bar: 0,
            message: 'Impossible d\'ajouter cette fantaisie. La longueur maximale de cette barre est atteinte.'
        };
    },

    placerClip(src, category = '', name = '') {
        const zone = document.getElementById('drop-zone-clip');
        if (!zone) return;

        const maxClips = typeof this.maxClipCount === 'function' ? this.maxClipCount() : (this.product === 'attache-doudou' ? 2 : 1);

        if (this.state.clips.length >= maxClips) {
            this.state.clips.shift();
            const first = zone.querySelector('.clip-wrapper');
            if (first) first.remove();
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'item-wrapper clip-wrapper';
        wrapper.dataset.category = category;
        wrapper.dataset.name = name;
        wrapper.innerHTML = `
            <img src="${src}" class="img-placed" alt="">
            <span class="btn-del" aria-label="Supprimer">&times;</span>
        `;

        wrapper.querySelector('.btn-del').onclick = (e) => {
            e.stopPropagation();
            wrapper.remove();
            this.syncStateFromDom();
            this.positionClips(zone);
            this.updatePrice();
        };

        zone.appendChild(wrapper);
        this.positionClips(zone);
        this.syncStateFromDom();
        this.closeModal();
        this.updatePrice();
    },

    positionClips(zone) {
        const clips = zone.querySelectorAll('.clip-wrapper');
        const isDoubleKeychain = ['double-port-cle', 'double-porte-cle'].includes(this.product);

        clips.forEach((clip, index) => {
            if (this.product === 'attache-tetine') {
                clip.style.left = '0';
                clip.style.top = '0';
                clip.style.width = '100%';
                return;
            }

            if (this.product === 'attache-doudou') {
                clip.style.left = index === 0 ? '0' : '87%';
                clip.style.top = '0%';
                clip.style.width = '13%';
                return;
            }

            if (isDoubleKeychain) {
                clip.style.left = '0';
                clip.style.top = index === 0 ? '0%' : '53%';
                clip.style.width = '100%';
                return;
            }

            clip.style.left = '0';
            clip.style.top = '0';
            clip.style.width = '100%';
        });

        this.renderOptionalClipButton(zone);
    },

    renderOptionalClipButton(zone) {
        const existing = zone.querySelector('.anna-optional-clip-add');
        if (existing) existing.remove();

        const isDoubleKeychain = ['double-port-cle', 'double-porte-cle'].includes(this.product);

        if (!['attache-doudou', 'double-port-cle', 'double-porte-cle'].includes(this.product) || zone.querySelectorAll('.clip-wrapper').length !== 1) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = isDoubleKeychain ? 'anna-optional-clip-add is-double-keychain' : 'anna-optional-clip-add';
        button.textContent = '+';
        button.setAttribute('aria-label', isDoubleKeychain ? 'Ajouter le deuxieme clip' : 'Ajouter un clip optionnel à droite');
        button.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.openModal();
        };

        zone.appendChild(button);
    },

    placerMotif(src, category = '', name = '') {
        const zone = document.getElementById('drop-zone-motifs');
        if (!zone) return;

        if (typeof this.limitedLetterCategoryCount === 'function' && typeof this.normalize === 'function') {
            const letterCategories = this.pricingConfig?.limitedLetterCategories || [];
            const letterLimit = typeof this.letterCategoryLimit === 'function' ? this.letterCategoryLimit() : 9;
            this.syncStateFromDom();
            const nextMotifs = this.state.motifs.concat([{ category }]);

            if (letterCategories.includes(this.normalize(category)) && this.limitedLetterCategoryCount(nextMotifs) > letterLimit) {
                this.warning('Maximum 9 lettres autorisées.');
                return;
            }
        }

        let targetBar = 0;

        if (this.isTeethingRing()) {
            if (!this.canAddMotifOnTeethingRing(category)) {
                this.warning('Impossible d\'ajouter cette fantaisie. L\'espace disponible sur l\'anneau est insuffisant.');
                return;
            }
        } else if (this.isDoubleKeychain()) {
            const target = this.doubleKeychainTargetForMotif(category);
            targetBar = target.bar;

            if (!targetBar) {
                this.warning(target.message);
                return;
            }
        } else if (!this.canAddMotifPhysically(category)) {
            this.warning('Impossible d\'ajouter cette fantaisie. La longueur maximale du produit est atteinte.');
            return;
        }

        const div = document.createElement('div');
        div.className = 'item-wrapper motif-draggable';
        div.setAttribute('data-x', '0');
        div.dataset.category = category;
        div.dataset.name = name;
        if (targetBar) {
            div.dataset.bar = String(targetBar);
        }
        div.innerHTML = `
            <img src="${src}" class="img-placed" alt="">
            <span class="btn-del" aria-label="Supprimer">&times;</span>
        `;

        div.querySelector('.btn-del').onclick = (e) => {
            e.stopPropagation();
            div.remove();
            this.reposition();
            this.syncStateFromDom();
            this.updatePrice();
        };

        zone.appendChild(div);

        setTimeout(() => {
            this.reposition();
            this.initDrag();
            this.syncStateFromDom();
            this.updatePrice();
        }, 50);
    },

    reposition() {
        const zone = document.getElementById('drop-zone-motifs');
        if (!zone) return;

        const motifs = zone.querySelectorAll('.motif-draggable');
        if (motifs.length === 0) return;

        if (this.product === 'anneau-dentition') {
            this.arrangeOnCircle(motifs);
        } else if (['double-port-cle', 'double-porte-cle'].includes(this.product)) {
            this.arrangeOnDoubleBars(motifs, zone);
        } else {
            this.arrangeOnBar(motifs, zone);
        }
    },

    arrangeOnBar(motifs, zone) {
        const zoneRect = zone.getBoundingClientRect();
        const motifWidth = motifs[0]?.offsetWidth || 60;
        const gap = 10;
        const totalWidth = motifs.length * motifWidth + (motifs.length - 1) * gap;
        const startX = Math.max(5, (zoneRect.width - totalWidth) / 2);
        const top = Math.max(0, (zoneRect.height - motifWidth) / 2);

        motifs.forEach((motif, index) => {
            const x = startX + index * (motifWidth + gap);
            motif.style.left = x + 'px';
            motif.style.top = top + 'px';
            motif.style.transform = 'none';
            motif.setAttribute('data-x', x);
        });
    },

    arrangeOnDoubleBars(motifs, zone) {
        const zoneRect = zone.getBoundingClientRect();
        const motifWidth = motifs[0]?.offsetWidth || 60;
        const gap = 10;
        const rows = [[], []];

        motifs.forEach((motif, index) => {
            if (!motif.dataset.bar) {
                motif.dataset.bar = String((index % 2) + 1);
            }

            rows[motif.dataset.bar === '2' ? 1 : 0].push(motif);
        });

        rows.forEach((row, rowIndex) => {
            const totalWidth = row.length * motifWidth + Math.max(0, row.length - 1) * gap;
            const startX = Math.max(5, (zoneRect.width - totalWidth) / 2);
            const top = rowIndex === 0
                ? Math.max(0, zoneRect.height * 0.08)
                : Math.max(0, zoneRect.height * 0.58);

            row.forEach((motif, index) => {
                const x = startX + index * (motifWidth + gap);
                motif.style.left = x + 'px';
                motif.style.top = top + 'px';
                motif.style.transform = 'none';
                motif.setAttribute('data-x', x);
            });
        });
    },

    arrangeOnCircle(motifs) {
        const rect = this.canvas.getBoundingClientRect();
        const guide = this.getCircleGuide(rect);
        const step = (Math.PI * 2) / motifs.length;

        motifs.forEach((motif, index) => {
            const angle = parseFloat(motif.getAttribute('data-angle')) || step * index;
            const x = guide.centerX + guide.radius * Math.cos(angle) - motif.offsetWidth / 2;
            const y = guide.centerY + guide.radius * Math.sin(angle) - motif.offsetHeight / 2;
            motif.style.left = x + 'px';
            motif.style.top = y + 'px';
            motif.style.transform = 'none';
            motif.setAttribute('data-angle', angle);
        });
    },

    getCircleGuide(rect) {
        return {
            centerX: rect.width * 0.49,
            centerY: rect.height * 0.49,
            radius: Math.min(rect.width, rect.height) * 0.38
        };
    },

    initDrag() {
        if (typeof interact === 'undefined') return;

        try { interact('.motif-draggable').unset(); } catch (e) {}

        const self = this;

        interact('.motif-draggable').draggable({
            inertia: true,
            autoScroll: true,
            modifiers: [
                interact.modifiers.restrictRect({
                    restriction: 'parent',
                    endOnly: false
                })
            ],
            listeners: {
                move(event) {
                    const target = event.target;
                    if (self.product === 'anneau-dentition') {
                        self.dragOnCircle(event, target);
                    } else {
                        self.dragOnBar(event, target);
                    }
                    self.syncStateFromDom();
                },
                end() {
                    self.syncStateFromDom();
                    self.updatePrice();
                }
            }
        });
    },

    dragOnBar(event, target) {
        const zone = document.getElementById('drop-zone-motifs');
        const zoneRect = zone.getBoundingClientRect();
        const motifWidth = target.offsetWidth || 60;
        let newX = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;

        newX = Math.max(0, Math.min(zoneRect.width - motifWidth, newX));
        target.style.left = newX + 'px';
        target.style.transform = 'none';
        target.setAttribute('data-x', newX);
    },

    dragOnCircle(event, target) {
        const rect = this.canvas.getBoundingClientRect();
        const guide = this.getCircleGuide(rect);
        const mouseX = event.client.x - rect.left;
        const mouseY = event.client.y - rect.top;
        const angle = Math.atan2(mouseY - guide.centerY, mouseX - guide.centerX);
        const x = guide.centerX + guide.radius * Math.cos(angle) - target.offsetWidth / 2;
        const y = guide.centerY + guide.radius * Math.sin(angle) - target.offsetHeight / 2;

        target.style.left = x + 'px';
        target.style.top = y + 'px';
        target.style.transform = 'none';
        target.setAttribute('data-angle', angle);
    },

    renderText() {
        const zone = document.getElementById('drop-zone-text');
        if (!zone) return;
        zone.textContent = this.state.text;
    },

    syncStateFromDom() {
        this.state.clips = Array.from(document.querySelectorAll('#drop-zone-clip .clip-wrapper')).map((item) => this.domItemData(item));
        this.state.motifs = Array.from(document.querySelectorAll('#drop-zone-motifs .motif-draggable')).map((item) => this.domItemData(item));
        this.state.text = (document.getElementById('anna-text')?.value || '').slice(0, this.maxLetters);
        this.state.double = false;
    },

    domItemData(item) {
        const img = item.querySelector('img');
        return {
            src: img ? img.src : '',
            category: item.dataset.category || '',
            name: item.dataset.name || '',
            x: parseFloat(item.style.left) || 0,
            y: parseFloat(item.style.top) || 0,
            bar: parseInt(item.dataset.bar || '0', 10) || 0,
            angle: parseFloat(item.getAttribute('data-angle')) || 0
        };
    },

    buildConfig() {
        this.syncStateFromDom();
        return {
            product: this.product,
            double: this.state.double,
            text: this.state.text,
            clips: this.state.clips,
            motifs: this.state.motifs
        };
    },

    updatePrice() {
        // Defined in pricing.js when the price module is loaded.
    },


    reset() {
        document.getElementById('drop-zone-clip').innerHTML = '';
        document.getElementById('drop-zone-motifs').innerHTML = '';
        const textInput = document.getElementById('anna-text');
        if (textInput) textInput.value = '';
        this.state = { clips: [], motifs: [], text: '', double: false };
        this.renderText();
        this.updatePrice();
        this.setStatus('');
        if (this.clipRequired) this.openModal();
    },


    async captureModelDataUrl(maxWidth = 0) {
        if (!this.canvas) return;

        const baseImage = this.canvas.querySelector('.base-image');
        const rect = this.canvas.getBoundingClientRect();
        const output = document.createElement('canvas');
        const ratio = maxWidth > 0 && baseImage.naturalWidth > maxWidth
            ? maxWidth / baseImage.naturalWidth
            : 1;

        output.width = Math.round(baseImage.naturalWidth * ratio);
        output.height = Math.round(baseImage.naturalHeight * ratio);
        const ctx = output.getContext('2d');

        await this.drawImage(ctx, baseImage.src, 0, 0, output.width, output.height);

        const scaleX = output.width / rect.width;
        const scaleY = output.height / rect.height;
        const items = this.canvas.querySelectorAll('.clip-wrapper, .motif-draggable');

        for (const item of items) {
            const img = item.querySelector('img');
            if (!img) continue;
            const imgRect = img.getBoundingClientRect();
            const canvasRect = this.canvas.getBoundingClientRect();

            await this.drawImage(
                ctx,
                img.src,
                (imgRect.left - canvasRect.left) * scaleX,
                (imgRect.top - canvasRect.top) * scaleY,
                imgRect.width * scaleX,
                imgRect.height * scaleY
            );
        }

        if (this.state.text) {
            const textZone = document.getElementById('drop-zone-text');
            const textRect = textZone.getBoundingClientRect();
            const canvasRect = this.canvas.getBoundingClientRect();
            ctx.fillStyle = '#5b3728';
            ctx.font = `${Math.round(textRect.height * scaleY * 0.8)}px Arial`;
            ctx.textAlign = 'center';
            ctx.fillText(
                this.state.text,
                (textRect.left - canvasRect.left + textRect.width / 2) * scaleX,
                (textRect.top - canvasRect.top + textRect.height * 0.75) * scaleY
            );
        }

        return output.toDataURL('image/png');
    },

    async downloadModel() {
        const image = await this.captureModelDataUrl();
        if (!image) return;

        const link = document.createElement('a');
        link.download = 'configuration-client.png';
        link.href = image;
        link.click();
    },

    drawImage(ctx, src, x, y, w, h) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                ctx.drawImage(img, x, y, w, h);
                resolve();
            };
            img.onerror = reject;
            img.src = src;
        });
    }
};

window.Anna = Anna;

document.addEventListener('DOMContentLoaded', () => Anna.init());
