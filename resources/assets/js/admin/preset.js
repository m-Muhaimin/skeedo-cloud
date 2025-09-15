'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { listResources } from './helpers';
import { toast } from '../base/toast';

export function presetView() {
    Alpine.data('preset', (preset) => ({
        preset: {},
        model: {},
        isProcessing: false,
        categories: [],
        categoriesFethed: false,

        init() {
            this.setPreset(preset);

            listResources('/categories')
                .then(categories => {
                    this.categories = categories;
                    this.categoriesFethed = true;
                });
        },

        setPreset(preset) {
            this.preset = preset;
            this.model = { ...this.preset };
            this.model.category_id = preset.category?.id;
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            let data = this.model;
            data.status = data.status ? 1 : 0;
            data.category = this.model.category_id;

            this.preset.id ? this.update(data) : this.create(data);
        },

        update(data) {
            api.patch(`/presets/${this.preset.id}`, data)
                .then(response => {
                    this.setPreset(response.data);
                    this.isProcessing = false;

                    toast.success('Template has been updated successfully!');
                }).catch(error => this.isProcessing = false);
        },

        create(data) {
            api.post('/presets', data)
                .then(response => {
                    toast.defer('Template has been created successfully!');
                    window.location = '/admin/templates/'
                }).catch(error => this.isProcessing = false);
        },

        sanitizeColor(input, el) {
            const sanitizedInput = input.replace(/[^0-9A-Fa-f]/g, '').toUpperCase();

            if (/^([0-9A-Fa-f]{3}){1,2}$/.test(sanitizedInput)) {
                this.model.color = `#${sanitizedInput.padEnd(6, '0').slice(0, 6)}`;
            } else {
                this.model.color = "#000000";
            }
        }
    }))
}