'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { listResources } from './helpers';
import { toast } from '../base/toast';
import { CustomFormData } from '../formdata';

export function settingsView() {
    Alpine.data('settings', (path) => ({
        required: [],
        isProcessing: false,
        plans: [],
        plansFetched: false,

        init() {
            // Try to find the form and set up price inputs
            this.setupPriceInputs();

            listResources('/plans')
                .then(plans => {
                    this.plans = plans;
                    this.plansFetched = true;
                });
        },

        setupPriceInputs() {
            this.priceInputs();
            this.watchForNewPriceInputs();
        },

        priceInputs() {
            const targetForm = this.$refs.form;
            if (!targetForm) return;

            const inputs = targetForm.querySelectorAll('[data-format="price"]') || [];

            inputs.forEach(input => {
                this.formatPriceInput(input);
            });
        },

        formatPriceInput(input) {
            let fractionDigits = input.dataset.fractionDigits || 0;

            if (input.value && input.value !== '') {
                const formattedValue = (input.value / Math.pow(10, fractionDigits)).toFixed(fractionDigits);
                input.value = formattedValue;
            }
        },

        watchForNewPriceInputs() {
            const targetForm = this.$refs.form;
            if (!targetForm) return;

            // Watch for DOM changes to catch template-rendered inputs
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                // Check if the added node is a price input
                                if (node.matches && node.matches('[data-format="price"]')) {
                                    this.formatPriceInput(node);
                                }
                                // Check for price inputs within the added node
                                const priceInputs = node.querySelectorAll && node.querySelectorAll('[data-format="price"]');
                                if (priceInputs && priceInputs.length > 0) {
                                    priceInputs.forEach(input => this.formatPriceInput(input));
                                }
                            }
                        });
                    }
                });
            });

            observer.observe(targetForm, {
                childList: true,
                subtree: true
            });

            // Clean up observer when component is destroyed
            this.$cleanup = () => {
                observer.disconnect();
            };
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let data = new CustomFormData(this.$refs.form);

            api.post(`/options${this.$refs.form.dataset.path || ''}`, data)
                .then(response => {
                    this.isProcessing = false;

                    toast.show(
                        'Changes saved successfully!',
                        'ti ti-square-rounded-check-filled'
                    );
                })
                .catch(error => this.isProcessing = false);
        },

        clearCache() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.delete(`/cache`)
                .then(() => {
                    this.isProcessing = false;

                    toast.show(
                        'Cache cleared successfully!',
                        'ti ti-square-rounded-check-filled'
                    );
                })
                .catch(error => this.isProcessing = false);
        }
    }));

    Alpine.data('models', (directory = [], enabled = [], types = {}) => ({
        directory: [],
        enabled: enabled,
        types: types,
        isProcessing: false,

        init() {
            this.directory = directory.filter(service => service.key != 'capabilities');
        },

        update(service, model, data) {
            let body = {};
            body[service.key + '.' + model.key] = data;

            api.post(`/options/models`, body);
        }
    }));

    Alpine.data('llm', (llm = {}) => ({
        isProcessing: false,
        llmKey: '',
        currentResource: null,
        isDeleting: false,
        llm: {
            key: null,
            models: [],
            headers: [],
            name: null,
            server: '',
            api_key: '',
        },

        init() {
            this.llm = { ...this.llm, ...llm };
            if (Array.isArray(this.llm.models)) {
                this.llm.models.forEach(model => {
                    if (typeof model.provider !== 'object' || model.provider === null) {
                        model.provider = {};
                    }
                });
            }
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.post(`/options/llms/${this.llm.key}`, this.llm)
                .then(response => {
                    if (llm.models?.length > 0) {
                        this.isProcessing = false;
                        toast.success(
                            'Changes saved successfully!',
                            'ti ti-square-rounded-check-filled'
                        );
                    } else {
                        toast.defer(
                            'New LLM server added successfully!',
                            'ti ti-square-rounded-check-filled'
                        );

                        window.location = '/admin/settings';
                    }
                })
                .catch(error => this.isProcessing = false);
        },

        deleteLlmServer(id) {
            this.isDeleting = true;

            api.delete(`/options/llms/${id}`)
                .then(() => {
                    this.isDeleting = false;
                    window.modal.close();
                    toast.success('Deleted successfully!');

                    this.$refs[`llm-${id}`].remove();
                });
        },

        setLlmKey(value) {
            if (!value) {
                this.llmKey = '';
                return;
            }

            this.llmKey = value.toLowerCase().replace(/[^a-z0-9]/g, '');
        },

        maskAuthKey(key, first = 3, last = 0, prefix = 'Bearer ') {
            key = key.trim();

            return key.length > first + last
                ? `${prefix}${key.slice(0, first)}${'*'.repeat(key.length - first - last)}${last > 0 ? key.slice(-last) : ''}`
                : `${prefix}${key}`;
        },

        setModelName(value, model) {
            let modelString = value.includes('/') ? value.split('/').slice(1).join('/') : value;
            modelString = modelString.split(':')[0];
            modelString = modelString.replace(/-/g, ' ');
            modelString = modelString.replace(/_/g, '.');
            modelString = modelString.replace(/(\d+(?:\.\d+)?)/g, ' $1 ');
            modelString = modelString
                .split('/')
                .map(part =>
                    part
                        .trim()
                        .split(' ')
                        .filter(word => word)
                        .map(word =>
                            word.charAt(0).toUpperCase() +
                            (word.slice(1).match(/[A-Z]/) ? word.slice(1) : word.slice(1).toLowerCase())
                        )
                        .join(' ')
                )
                .join('/');

            model.name = modelString;
        },

        addModel() {
            this.llm.models.push({
                key: '',
                name: '',
                provider: {},
                config: {
                    tools: false,
                    vision: false
                }
            });
        },

        removeModel(index) {
            this.llm.models.splice(index, 1);
        },

        addHeader() {
            this.llm.headers.push({ key: '', value: '' });
        },

        removeHeader(index) {
            this.llm.headers.splice(index, 1);
        }
    }));

    Alpine.data('colorSchemes', (light, dark, def) => ({
        light: light,
        dark: dark,
        def: def,

        init() {
            ['light', 'dark'].forEach((scheme) => {
                this.$watch(scheme, (val) => {
                    if (!val) {
                        scheme == 'light' ? this.dark = true : this.light = true;

                        if (this.def == scheme) {
                            this.def = 'system';
                        }
                    }
                });
            });
        },
    }))
}