import template from './search-normalization-tester.html.twig';
import './search-normalization-tester.scss';

const { Component } = Shopware;

Component.register('search-normalization-tester', {
    template,

    inject: ['systemConfigApiService'],

    props: {
        searchTerm: {
            type: String,
            required: false,
            default: ''
        },
        salesChannelId: {
            type: String,
            required: false,
            default: null
        }
    },

    data() {
        return {
            testInput: this.searchTerm || '',
            variations: [],
            characterMappings: [],
            isLoading: false
        };
    },

    computed: {
        hasVariations() {
            return this.variations.length > 0;
        },

        formattedMappings() {
            return this.characterMappings.map(mapping => {
                return {
                    source: mapping[0],
                    target: mapping[1],
                    example: this.getExampleForMapping(mapping)
                };
            });
        }
    },

    watch: {
        searchTerm(newValue) {
            this.testInput = newValue;
            this.generateVariations();
        }
    },

    created() {
        this.loadConfiguration();
    },

    methods: {
        async loadConfiguration() {
            this.isLoading = true;
            
            try {
                const config = await this.systemConfigApiService.getValues(
                    'IntelligentSearchOptimizer.config',
                    this.salesChannelId
                );

                if (config['IntelligentSearchOptimizer.config.characterMappings']) {
                    this.parseCharacterMappings(config['IntelligentSearchOptimizer.config.characterMappings']);
                } else {
                    // Default mappings
                    this.characterMappings = [
                        ['-', ' '],
                        ['_', ' '],
                        ['/', ' ']
                    ];
                }

                if (this.testInput) {
                    this.generateVariations();
                }
            } catch (error) {
                console.error('Failed to load configuration:', error);
            } finally {
                this.isLoading = false;
            }
        },

        parseCharacterMappings(configString) {
            const mappings = [];
            const lines = configString.split('\n');
            
            lines.forEach(line => {
                line = line.trim();
                if (!line || line.startsWith('#')) {
                    return;
                }
                
                const parts = line.split(/\s+/);
                if (parts.length >= 2) {
                    const sources = parts[0].split(',');
                    const target = parts.slice(1).join(' ');
                    
                    sources.forEach(source => {
                        if (source.trim()) {
                            mappings.push([source.trim(), target]);
                        }
                    });
                }
            });
            
            this.characterMappings = mappings;
        },

        generateVariations() {
            if (!this.testInput || this.testInput.trim() === '') {
                this.variations = [];
                return;
            }

            const input = this.testInput.trim();
            const variations = new Set([input]);

            // Apply each mapping
            this.characterMappings.forEach(([source, target]) => {
                const currentVariations = Array.from(variations);
                
                currentVariations.forEach(variant => {
                    if (variant.includes(source)) {
                        variations.add(variant.replace(new RegExp(this.escapeRegex(source), 'g'), target));
                    }
                    
                    // Bidirectional mapping
                    if (variant.includes(target) && target !== source) {
                        variations.add(variant.replace(new RegExp(this.escapeRegex(target), 'g'), source));
                    }
                });
            });

            // Add fully normalized version
            let normalized = input.toLowerCase();
            this.characterMappings.forEach(([source, target]) => {
                if (target === ' ' || source === ' ') {
                    normalized = normalized.replace(
                        new RegExp(this.escapeRegex(source === ' ' ? target : source), 'g'),
                        ' '
                    );
                }
            });
            normalized = normalized.replace(/\s+/g, ' ').trim();
            variations.add(normalized);

            // Convert to array with metadata
            this.variations = Array.from(variations).map((value, index) => {
                let type = 'variant';
                let label = this.$tc('search-optimizer.normalization.variant');
                
                if (value === input) {
                    type = 'original';
                    label = this.$tc('search-optimizer.normalization.original');
                } else if (value === normalized) {
                    type = 'normalized';
                    label = this.$tc('search-optimizer.normalization.normalized');
                }
                
                return { type, value, label };
            });
        },

        getExampleForMapping(mapping) {
            const [source, target] = mapping;
            return `"Test${source}Product" ↔ "Test${target}Product"`;
        },

        escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },

        onInputChange() {
            this.generateVariations();
            this.$emit('change', this.variations);
        },

        onRefreshMappings() {
            this.loadConfiguration();
        }
    }
});