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
        this.initCanvasResize();
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

    initCanvasResize() {
        if (!this.canvas) return;

        const recalculatePositions = () => this.reposition();
        const baseImage = this.canvas.querySelector('.base-image');

        if (baseImage && !baseImage.complete) {
            baseImage.addEventListener('load', recalculatePositions, { once: true });
        }

        if (typeof ResizeObserver !== 'undefined') {
            this.canvasResizeObserver = new ResizeObserver(recalculatePositions);
            this.canvasResizeObserver.observe(this.canvas);
        } else {
            window.addEventListener('resize', recalculatePositions);
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
                    this.warning(this.requiredClipMessage());
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

    warning(msg, persistStatus = true) {
        if (persistStatus) {
            this.setStatus(msg, true);
        }

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

    requiredClipMessage() {
        return this.isDoubleKeychain()
            ? 'Veuillez choisir 1 anneau.'
            : 'Veuillez choisir le nombre de clips requis.';
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
        return 180;
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
        return 160;
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
        return 12;
    },

    doubleBarMotifs(bar, motifs = this.state.motifs) {
        return (Array.isArray(motifs) ? motifs : []).filter((motif) => Number(motif.bar || 1) === bar);
    },

    usedDoubleBarLength(bar, motifs = this.state.motifs) {
        return this.doubleBarMotifs(bar, motifs).reduce((total, motif) => {
            return total + this.motifPhysicalSize(motif.category);
        }, 0);
    },

    remainingDoubleBarLength(bar, motifs = this.state.motifs) {
        return this.doubleBarLength() - this.usedDoubleBarLength(bar, motifs);
    },

    doubleBarCanAcceptAny(bar = 1) {
        return this.remainingDoubleBarLength(bar) >= this.smallestAvailableMotifSize();
    },

    doubleFirstBarIsComplete() {
        return this.remainingDoubleBarLength(1) < this.smallestAvailableMotifSize();
    },

    nextDoubleBarForMotif(category) {
        return this.doubleKeychainTargetForMotif(category).bar;
    },

    doubleKeychainTargetForMotif(category, requestedBar = 0) {
        this.syncStateFromDom();

        const size = this.motifPhysicalSize(category);
        const remainingBarOne = this.remainingDoubleBarLength(1);
        const remainingBarTwo = this.remainingDoubleBarLength(2);

        if (requestedBar === 2 && remainingBarOne >= this.smallestAvailableMotifSize()) {
            return {
                bar: 0,
                message: 'Veuillez compléter la première barre avant de personnaliser la seconde.'
            };
        }

        if (remainingBarOne >= size) {
            return { bar: 1, message: '' };
        }

        if (remainingBarTwo >= size) {
            return { bar: 2, message: '' };
        }

        return {
            bar: 0,
            message: 'Impossible d\'ajouter cette fantaisie. La longueur maximale du produit est atteinte.'
        };
    },

    placerClip(src, category = '', name = '') {
        const zone = document.getElementById('drop-zone-clip');
        if (!zone) return;

        const placementLimits = {
            'attache-tetine': 1,
            'attache-doudou': 2,
            'porte-cle': 1,
            'double-port-cle': 1,
            'double-porte-cle': 1
        };
        const maxClips = placementLimits[this.product]
            ?? (typeof this.maxClipCount === 'function' ? this.maxClipCount() : 1);
        const existingClips = Array.from(zone.querySelectorAll('.clip-wrapper'));

        if (maxClips === 1) {
            existingClips.forEach((clip) => clip.remove());
            this.state.clips = [];
        } else if (existingClips.length >= maxClips) {
            existingClips[0].remove();
            this.state.clips.shift();
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
                clip.style.left = index === 0 ? '0' : '86.3%';
                clip.style.top = '0%';
                clip.style.width = '13.7%';
                return;
            }

            if (isDoubleKeychain) {
                clip.style.left = '0';
                clip.style.top = '0';
                clip.style.width = '100%';
                clip.style.height = '100%';
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

        if (this.isDoubleKeychain && this.isDoubleKeychain()) {
            return;
        }

        if (this.product !== 'attache-doudou' || zone.querySelectorAll('.clip-wrapper').length !== 1) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'anna-optional-clip-add';
        button.textContent = '+';
        button.setAttribute('aria-label', 'Ajouter un clip optionnel à droite');
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
                this.warning(target.message, target.message !== 'Veuillez compléter la première barre avant de personnaliser la seconde.');
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
        div.dataset.size = String(this.motifPhysicalSize(category));
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
        const motifWidth = this.fitMotifsToTrack(motifs);
        const layout = this.horizontalBarLayout(motifs.length, motifWidth, zoneRect.width);

        motifs.forEach((motif, index) => {
            const x = layout.startX + index * layout.step;
            const motifHeight = motif.offsetHeight || motifWidth;
            const top = (zoneRect.height - motifHeight) / 2;
            motif.style.left = x + 'px';
            motif.style.top = top + 'px';
            motif.style.transform = 'none';
            motif.setAttribute('data-x', x);
            motif.setAttribute('data-y', top);
        });
    },

    fitMotifsToTrack(motifs) {
        let visualWidth = 0;

        Array.from(motifs).forEach((motif) => {
            motif.style.width = '';
            motif.style.height = '';

            const preferredWidth = motif.getBoundingClientRect().width || motif.offsetWidth || 60;
            visualWidth = Math.max(visualWidth, preferredWidth);
        });

        return visualWidth || 60;
    },

    horizontalBarLayout(count, motifWidth, availableWidth) {
        if (count <= 1) {
            return {
                startX: Math.max(0, (availableWidth - motifWidth) / 2),
                step: 0
            };
        }

        const preferredStep = motifWidth + 10;
        const preferredWidth = motifWidth + ((count - 1) * preferredStep);

        if (preferredWidth <= availableWidth) {
            return {
                startX: (availableWidth - preferredWidth) / 2,
                step: preferredStep
            };
        }

        return {
            startX: 0,
            step: Math.max(0, (availableWidth - motifWidth) / (count - 1))
        };
    },

    cssPercentage(element, property, fallback) {
        const value = parseFloat(getComputedStyle(element).getPropertyValue(property));
        return Number.isFinite(value) ? value : fallback;
    },

    physicalBarTracks(zone) {
        const zoneRect = zone.getBoundingClientRect();

        if (!this.isDoubleKeychain()) {
            return [{ top: 0, height: zoneRect.height }];
        }

        return [
            {
                top: zoneRect.height * this.cssPercentage(zone, '--anna-bar-1-top', 0) / 100,
                height: zoneRect.height * this.cssPercentage(zone, '--anna-bar-1-height', 36.07) / 100
            },
            {
                top: zoneRect.height * this.cssPercentage(zone, '--anna-bar-2-top', 63.11) / 100,
                height: zoneRect.height * this.cssPercentage(zone, '--anna-bar-2-height', 36.89) / 100
            }
        ];
    },

    arrangeOnDoubleBars(motifs, zone) {
        const zoneRect = zone.getBoundingClientRect();
        const rows = [[], []];
        const tracks = this.physicalBarTracks(zone);

        motifs.forEach((motif, index) => {
            if (!motif.dataset.bar) {
                motif.dataset.bar = String((index % 2) + 1);
            }
            if (!motif.dataset.size) {
                motif.dataset.size = String(this.motifPhysicalSize(motif.dataset.category || ''));
            }

            rows[motif.dataset.bar === '2' ? 1 : 0].push(motif);
        });

        rows.forEach((row, rowIndex) => {
            const motifWidth = this.fitMotifsToTrack(row);
            const layout = this.horizontalBarLayout(row.length, motifWidth, zoneRect.width);
            const track = tracks[rowIndex];

            row.forEach((motif, index) => {
                const x = layout.startX + index * layout.step;
                const motifHeight = motif.offsetHeight || motifWidth;
                const top = track.top + (track.height - motifHeight) / 2;
                motif.style.left = x + 'px';
                motif.style.top = top + 'px';
                motif.style.transform = 'none';
                motif.setAttribute('data-x', x);
                motif.setAttribute('data-y', top);
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
        const zone = document.getElementById('drop-zone-motifs');
        const centerX = zone ? this.cssPercentage(zone, '--anna-ring-center-x', 50.27) : 50.27;
        const centerY = zone ? this.cssPercentage(zone, '--anna-ring-center-y', 53.25) : 53.25;
        const radius = zone ? this.cssPercentage(zone, '--anna-ring-radius', 38.65) : 38.65;

        return {
            centerX: rect.width * centerX / 100,
            centerY: rect.height * centerY / 100,
            radius: Math.min(rect.width, rect.height) * radius / 100
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
                start(event) {
                    if (self.product === 'anneau-dentition') return;

                    const target = event.target;
                    target.dataset.dragStartBar = target.dataset.bar || '1';
                    target.dataset.dragStartX = target.getAttribute('data-x') || '0';
                    target.dataset.dragStartY = target.getAttribute('data-y') || String(parseFloat(target.style.top) || 0);
                },
                move(event) {
                    const target = event.target;
                    if (self.product === 'anneau-dentition') {
                        self.dragOnCircle(event, target);
                    } else if (self.isDoubleKeychain()) {
                        self.dragOnDoubleBar(event, target);
                    } else {
                        self.dragOnBar(event, target);
                    }
                    self.syncStateFromDom();
                },
                end(event) {
                    if (self.isDoubleKeychain()) {
                        self.finishDoubleBarDrag(event.target);
                    } else if (self.product !== 'anneau-dentition') {
                        self.finishSingleBarDrag(event.target);
                    }
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
        const motifHeight = target.offsetHeight || motifWidth;
        const top = (zoneRect.height - motifHeight) / 2;
        let newX = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;

        newX = Math.max(0, Math.min(zoneRect.width - motifWidth, newX));
        target.style.left = newX + 'px';
        target.style.top = top + 'px';
        target.style.transform = 'none';
        target.setAttribute('data-x', newX);
        target.setAttribute('data-y', top);
    },

    finishSingleBarDrag(target) {
        const zone = document.getElementById('drop-zone-motifs');
        if (!zone) return;

        const motifs = Array.from(zone.querySelectorAll('.motif-draggable'));
        const ordered = this.itemsSortedByX(motifs);

        ordered.forEach((item) => zone.appendChild(item));
        this.arrangeOnBar(ordered, zone);
        this.clearDragStart(target);
    },

    itemsSortedByX(items) {
        return Array.from(items).sort((a, b) => {
            const centerA = (parseFloat(a.getAttribute('data-x')) || parseFloat(a.style.left) || 0) + ((a.offsetWidth || 0) / 2);
            const centerB = (parseFloat(b.getAttribute('data-x')) || parseFloat(b.style.left) || 0) + ((b.offsetWidth || 0) / 2);

            return centerA - centerB;
        });
    },

    restoreDraggedItem(target) {
        const startX = parseFloat(target.dataset.dragStartX || '0') || 0;
        const startY = parseFloat(target.dataset.dragStartY || '0') || 0;
        const startBar = target.dataset.dragStartBar || target.dataset.bar || '1';

        target.dataset.bar = startBar;
        target.style.left = startX + 'px';
        target.style.top = startY + 'px';
        target.style.transform = 'none';
        target.setAttribute('data-x', startX);
        target.setAttribute('data-y', startY);
        this.clearDragStart(target);
    },

    clearDragStart(target) {
        delete target.dataset.dragStartBar;
        delete target.dataset.dragStartX;
        delete target.dataset.dragStartY;
    },

    dragOnDoubleBar(event, target) {
        const zone = document.getElementById('drop-zone-motifs');
        const zoneRect = zone.getBoundingClientRect();
        const motifWidth = target.offsetWidth || 60;
        const motifHeight = target.offsetHeight || motifWidth;
        let newX = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
        let newY = (parseFloat(target.getAttribute('data-y')) || parseFloat(target.style.top) || 0) + event.dy;

        newX = Math.max(0, Math.min(zoneRect.width - motifWidth, newX));
        newY = Math.max(0, Math.min(zoneRect.height - motifHeight, newY));
        target.style.left = newX + 'px';
        target.style.top = newY + 'px';
        target.style.transform = 'none';
        target.setAttribute('data-x', newX);
        target.setAttribute('data-y', newY);
    },

    doubleBarFromPosition(item) {
        const zone = document.getElementById('drop-zone-motifs');
        if (!zone) return Number(item.dataset.bar || 1);

        const tracks = this.physicalBarTracks(zone);
        const itemTop = parseFloat(item.style.top) || 0;
        const itemHeight = item.offsetHeight || 0;
        const itemCenter = itemTop + (itemHeight / 2);
        const firstCenter = tracks[0].top + (tracks[0].height / 2);
        const secondCenter = tracks[1].top + (tracks[1].height / 2);

        return Math.abs(itemCenter - secondCenter) < Math.abs(itemCenter - firstCenter) ? 2 : 1;
    },

    doubleBarTop(bar, motif = null) {
        const zone = document.getElementById('drop-zone-motifs');
        if (!zone) return 0;

        const track = this.physicalBarTracks(zone)[Number(bar) === 2 ? 1 : 0];
        const motifHeight = motif?.offsetHeight || zone.querySelector('.motif-draggable')?.offsetHeight || 60;

        return track.top + (track.height - motifHeight) / 2;
    },

    snapDoubleBarItemToBar(item, bar) {
        const top = this.doubleBarTop(bar, item);

        item.style.top = top + 'px';
        item.style.transform = 'none';
        item.setAttribute('data-y', top);
    },

    setItemX(item, x) {
        item.style.left = x + 'px';
        item.setAttribute('data-x', x);
    },

    usedDoubleBarLengthFromDom(bar, excludedItem = null) {
        return Array.from(document.querySelectorAll('#drop-zone-motifs .motif-draggable')).reduce((total, item) => {
            if (item === excludedItem || Number(item.dataset.bar || 1) !== bar) {
                return total;
            }

            const size = parseInt(item.dataset.size || '0', 10) || this.motifPhysicalSize(item.dataset.category || '');
            return total + size;
        }, 0);
    },

    finishDoubleBarDrag(target) {
        const verticalThreshold = 40;
        const originalBar = Number(target.dataset.dragStartBar || target.dataset.bar || 1);
        const startY = parseFloat(target.dataset.dragStartY || '0') || 0;
        const currentY = parseFloat(target.getAttribute('data-y')) || parseFloat(target.style.top) || startY;
        const verticalDelta = currentY - startY;
        const shouldChangeBar = Math.abs(verticalDelta) >= verticalThreshold;
        const targetBar = shouldChangeBar
            ? (verticalDelta > 0 ? 2 : 1)
            : originalBar;
        const zone = document.getElementById('drop-zone-motifs');

        if (!shouldChangeBar || targetBar === originalBar) {
            target.dataset.bar = String(originalBar);
            this.snapDoubleBarItemToBar(target, originalBar);
        } else {
            const targetUsedLength = this.usedDoubleBarLengthFromDom(targetBar, target);
            const targetSize = parseInt(target.dataset.size || '0', 10) || this.motifPhysicalSize(target.dataset.category || '');

            if (targetUsedLength + targetSize > this.doubleBarLength()) {
                this.restoreDraggedItem(target);
                this.warning('Espace insuffisant sur cette barre.');
                return;
            }

            target.dataset.bar = String(targetBar);
            this.snapDoubleBarItemToBar(target, targetBar);
        }

        if (zone) {
            this.arrangeDoubleBarsByDropOrder(zone);
        }

        this.clearDragStart(target);
    },

    arrangeDoubleBarsByDropOrder(zone) {
        const motifs = Array.from(zone.querySelectorAll('.motif-draggable'));
        const barOne = this.itemsSortedByX(motifs.filter((item) => Number(item.dataset.bar || 1) === 1));
        const barTwo = this.itemsSortedByX(motifs.filter((item) => Number(item.dataset.bar || 1) === 2));
        const ordered = barOne.concat(barTwo);

        ordered.forEach((item) => zone.appendChild(item));
        this.arrangeOnDoubleBars(ordered, zone);
    },

    dragOnCircle(event, target) {
        const rect = this.canvas.getBoundingClientRect();
        const guide = this.getCircleGuide(rect);
        const mouseX = event.client.x - rect.left;
        const mouseY = event.client.y - rect.top;
        const angle = Math.atan2(mouseY - guide.centerY, mouseX - guide.centerX);
        const position = this.resolveCircleCollision(target, guide, angle);

        if (!position) {
            return;
        }

        target.style.left = position.x + 'px';
        target.style.top = position.y + 'px';
        target.style.transform = 'none';
        target.setAttribute('data-angle', position.angle);
    },

    circlePositionForAngle(target, guide, angle) {
        return {
            angle,
            x: guide.centerX + guide.radius * Math.cos(angle) - target.offsetWidth / 2,
            y: guide.centerY + guide.radius * Math.sin(angle) - target.offsetHeight / 2
        };
    },

    resolveCircleCollision(target, guide, desiredAngle) {
        const desired = this.circlePositionForAngle(target, guide, desiredAngle);

        if (!this.circlePositionCollides(target, desired.x, desired.y)) {
            return desired;
        }

        const step = 0.04;
        const maxSteps = Math.ceil(Math.PI / step);

        for (let i = 1; i <= maxSteps; i += 1) {
            const before = this.circlePositionForAngle(target, guide, desiredAngle - (step * i));
            const after = this.circlePositionForAngle(target, guide, desiredAngle + (step * i));

            if (!this.circlePositionCollides(target, after.x, after.y)) {
                return after;
            }

            if (!this.circlePositionCollides(target, before.x, before.y)) {
                return before;
            }
        }

        return null;
    },

    circlePositionCollides(target, x, y) {
        const gap = 2;
        const targetWidth = target.offsetWidth || 60;
        const targetHeight = target.offsetHeight || targetWidth;
        const targetRect = {
            left: x,
            right: x + targetWidth,
            top: y,
            bottom: y + targetHeight
        };

        return Array.from(document.querySelectorAll('#drop-zone-motifs .motif-draggable')).some((item) => {
            if (item === target) return false;

            const itemLeft = parseFloat(item.style.left) || 0;
            const itemTop = parseFloat(item.style.top) || 0;
            const itemWidth = item.offsetWidth || targetWidth;
            const itemHeight = item.offsetHeight || itemWidth;

            return targetRect.left < itemLeft + itemWidth + gap
                && targetRect.right + gap > itemLeft
                && targetRect.top < itemTop + itemHeight + gap
                && targetRect.bottom + gap > itemTop;
        });
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
