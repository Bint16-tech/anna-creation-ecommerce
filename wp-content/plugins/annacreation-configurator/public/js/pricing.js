(function () {
    if (!window.Anna) return;

    const DEFAULT_PRICING_CONFIG = {
        aliases: {
            'double-port-cle': 'double-porte-cle'
        },
        products: {
            'attache-tetine': {
                clipCount: 1,
                maxClips: 2,
                base: { classique: 14, foot: 15, anime: 16 },
                extra: { fantaisie: 1, anime: 2 },
                pricedClips: true
            },
            'attache-doudou': {
                clipCount: 1,
                maxClips: 2,
                base: { classique: 16, foot: 17, anime: 18 },
                extra: { fantaisie: 1, anime: 2 },
                pricedClips: true
            },
            'anneau-dentition': {
                clipCount: 0,
                maxClips: 1,
                base: { classique: 12 },
                extra: { fantaisie: 1, anime: 2 },
                clipExtra: { 'non-anime': 3.5, anime: 5.5 },
                allowedClipTypes: ['non-anime', 'anime'],
                allowedClipCategories: ['classique', 'anneau-animee']
            },
            'porte-cle': {
                clipCount: 1,
                base: { classique: 10, foot: 11, anime: 12 },
                extra: { fantaisie: 1, anime: 2 },
                clipExtra: { any: 0 },
                allowedClipCategories: ['anneau']
            },
            'double-porte-cle': {
                clipCount: 1,
                base: { classique: 20, foot: 22, anime: 24 },
                extra: { fantaisie: 1, anime: 2 },
                clipExtra: { any: 0 },
                allowedClipCategories: ['anneau']
            }
        },
        clipExtras: {
            ronde: 0,
            anneau: 0,
            classique: 0,
            bois: 0,
            fleur: 1.5,
            'autre-clip': 1.5,
            'dessins-fleuris': 1.5,
            animee: 2,
            'anneau-animee': 0,
            foot: 1.5
        },
        motifTypes: {},
        freeMotifCategories: [
            'lettre-blanche-doree',
            'lettre-blanche-noir',
            'lettre-blanche-noire',
            'lettre-blanche-camel',
            'lettre-camel'
        ],
        limitedLetterCategories: [
            'lettre-blanche-doree',
            'lettre-blanche-noir',
            'lettre-blanche-noire',
            'lettre-camel'
        ],
        letterCategoryLimit: 9,
        motifLimits: {
            default: 9,
            'double-porte-cle': 18
        }
    };

    const PRICING_CONFIG = window.annaPricingConfig || DEFAULT_PRICING_CONFIG;

    function normalize(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    }

    function canonicalProduct(product) {
        const slug = normalize(product);
        return PRICING_CONFIG.aliases[slug] || slug;
    }

    function productRule(config) {
        let product = canonicalProduct(config.product);

        return PRICING_CONFIG.products[product] || null;
    }

    function motifType(category) {
        const slug = normalize(category);
        const configured = PRICING_CONFIG.motifTypes?.[slug];

        if (['classique', 'foot', 'anime'].includes(configured)) {
            return configured;
        }

        if (slug.includes('foot')) return 'foot';
        if (slug.includes('anim')) return 'anime';

        return 'classique';
    }

    function clipType(category) {
        return motifType(category) === 'anime' ? 'anime' : 'non-anime';
    }

    function clipExtra(category, config, rule) {
        const product = canonicalProduct(config.product);

        if (product === 'anneau-dentition') {
            return rule.clipExtra[clipType(category)] || 0;
        }

        if (!rule.pricedClips) {
            return rule.clipExtra?.any || 0;
        }

        return PRICING_CONFIG.clipExtras[normalize(category)] || 0;
    }

    function additionalMotifExtra(category, rule) {
        if (PRICING_CONFIG.freeMotifCategories.includes(normalize(category))) {
            return 0;
        }

        const type = motifType(category);

        if (type === 'anime') {
            return rule.extra?.anime || 0;
        }

        if (type === 'foot') {
            return rule.extra?.foot ?? rule.extra?.fantaisie ?? 0;
        }

        return rule.extra?.fantaisie || 0;
    }

    function baseMotifType(motifs) {
        const types = (Array.isArray(motifs) ? motifs : []).map((motif) => motifType(motif.category));

        if (types.includes('anime')) return 'anime';
        if (types.includes('foot')) return 'foot';

        return types[0] || 'classique';
    }

    function limitedLetterCategoryCount(motifs) {
        const categories = PRICING_CONFIG.limitedLetterCategories || [];

        return (Array.isArray(motifs) ? motifs : []).filter((motif) => {
            return categories.includes(normalize(motif.category));
        }).length;
    }

    function calculatePrice(config) {
        const safeConfig = {
            product: config?.product || '',
            double: !!config?.double,
            clips: Array.isArray(config?.clips) ? config.clips : [],
            motifs: Array.isArray(config?.motifs) ? config.motifs : []
        };
        const rule = productRule(safeConfig);

        if (!rule) return 0;

        const baseType = canonicalProduct(safeConfig.product) === 'double-porte-cle'
            ? motifType(safeConfig.motifs[0]?.category || '')
            : baseMotifType(safeConfig.motifs);
        let price = rule.base[baseType] ?? rule.base.classique ?? 0;

        safeConfig.clips.forEach((clip) => {
            price += clipExtra(clip.category, safeConfig, rule);
        });

        if (canonicalProduct(safeConfig.product) === 'double-porte-cle') {
            safeConfig.motifs.slice(2).forEach((motif) => {
                price += additionalMotifExtra(motif.category, rule);
            });
        } else {
            const categoryCounts = {};

            safeConfig.motifs.forEach((motif) => {
                const category = normalize(motif.category);
                categoryCounts[category] = (categoryCounts[category] || 0) + 1;

                if (categoryCounts[category] > 1) {
                    price += additionalMotifExtra(motif.category, rule);
                }
            });
        }

        return Math.round(price * 100) / 100;
    }

    const originalInit = window.Anna.init;

    Object.assign(window.Anna, {
        pricingConfig: PRICING_CONFIG,
        calculatePrice(config = this.buildConfig()) {
            return calculatePrice(config);
        },
        normalize,
        motifType,
        limitedLetterCategoryCount,

        letterCategoryLimit() {
            return Number(PRICING_CONFIG.letterCategoryLimit || 9);
        },

        init() {
            if (typeof originalInit === 'function') {
                originalInit.call(this);
            }

            if (canonicalProduct(this.product) === 'anneau-dentition') {
                this.clipRequired = false;
                setTimeout(() => this.openModal(), 100);
            }
        },

        productRule(config = this.buildConfig()) {
            return productRule(config);
        },

        requiredClipCount() {
            return this.productRule()?.clipCount || 0;
        },

        maxClipCount() {
            const rule = this.productRule();
            return rule?.maxClips ?? rule?.clipCount ?? 0;
        },

        maxMotifCount(config = this.buildConfig()) {
            const product = canonicalProduct(config.product);
            return PRICING_CONFIG.motifLimits[product] || PRICING_CONFIG.motifLimits.default;
        },

        calculateCurrentPrice() {
            return this.calculatePrice();
        },

        updatePrice() {
            const el = document.getElementById('anna-price');
            if (el) {
                el.textContent = this.formatPrice(this.calculateCurrentPrice());
            }
        },

        formatPrice(price) {
            const decimals = Number(window.annaData?.decimals ?? 2);
            const decimal = window.annaData?.decimalSeparator || ',';
            const symbol = window.annaData?.currencySymbol || '\u20ac';
            return price.toFixed(decimals).replace('.', decimal) + ' ' + symbol;
        }
    });
})();
