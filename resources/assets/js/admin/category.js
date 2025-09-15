'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function categoryView() {
    Alpine.data('category', (category) => ({
        category: {},
        model: {},
        isProcessing: false,

        init() {
            this.category = category;
            this.model = { ...this.category };
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            this.category.id ? this.update() : this.create();
        },

        update() {
            api.patch(`/categories/${this.category.id}`, this.model)
                .then(response => {
                    this.category = response.data;
                    this.model = { ...this.category };

                    this.isProcessing = false;

                    toast.success('Category has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        },

        create() {
            api.post('/categories', this.model)
                .then(response => {
                    toast.defer('Category has been created successfully!');
                    window.location = '/admin/categories';
                })
                .catch(error => this.isProcessing = false);
        }
    }))
}