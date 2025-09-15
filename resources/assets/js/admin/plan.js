'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { listResources } from './helpers';

export function planView() {
    Alpine.data('plan', (currency, plan = null) => ({
        plan: plan || {},
        currency: currency,
        model: {
            billing_cycle: 'monthly',
            config: {
                writer: {},
                coder: {},
                video: {},
                imagine: {},
                transcriber: {},
                voiceover: {
                    clone_cap: null
                },
                chat: {},
                voice_isolator: {},
                classifier: {},
                composer: {},
                titler: {},
                models: {},
                tools: {},
                assistants: null,
                presets: null,
            },
            update_snapshots: false,
        },
        isProcessing: false,

        init() {
            this.setModel({ ...this.plan })
            this.watch()
        },

        setModel(model) {
            this.model = { ...this.model, ...model };
            this.model.price = (model.price / Math.pow(10, currency.fraction_digits)).toFixed(currency.fraction_digits);
            this.model.status = model.status == 1;
            this.model.is_featured = model.is_featured == 1;

            if (Object.keys(this.model.config.models).length === 0) {
                this.model.config.models = {}
            }

            const event = new Event("input", { bubbles: true });
            this.$refs.price.dispatchEvent(event);
        },

        watch() {
            this.$watch(
                `model.credit_count`,
                (value) => {
                    this.model.credit_count = value < 0 || value === null || value.toString().trim() === "" ? null : value;
                }
            );

            this.$watch(
                `model.member_cap`,
                (value) => {
                    this.model.member_cap = value < 0 || value === null || value.toString().trim() === "" ? null : value;
                }
            );

            this.$watch(
                `model.config.voiceover.clone_cap`,
                (value) => {
                    this.model.config.voiceover.clone_cap = value < 0 || value === null || value.toString().trim() === "" ? null : value;
                }
            );
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            let data = { ...this.model }

            data.status = data.status ? 1 : 0;
            data.is_featured = data.is_featured ? 1 : 0;
            data.price = (data.price.replaceAll(' ', '') * Math.pow(10, currency.fraction_digits)).toFixed(0);

            this.isProcessing = true;
            this.plan.id ? this.update(data) : this.create(data);
        },

        update(data) {
            api.patch(`/plans/${this.plan.id}`, data)
                .then(response => {
                    this.plan = response.data;
                    this.setModel({ ...this.plan });

                    this.isProcessing = false;

                    toast.success('Plan has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        },

        create(data) {
            api.post('/plans', data)
                .then(response => {
                    toast.defer('Plan has been created successfully!');
                    window.location = `/admin/plans/`;
                })
                .catch(error => this.isProcessing = false);
        },

        assistants: null,
        async fetchAssistants() {
            if (this.assistants == null) {
                this.assistants = await listResources('/assistants');
            }

            return this.assistants;
        },

        presets: null,
        async fetchPresets() {
            if (this.presets == null) {
                this.presets = await listResources('/presets');
            }
        }
    }))
}