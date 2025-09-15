'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function composerView() {
    Alpine.data('composer', (model, adapters = [], composition = null) => ({
        adapters: [],
        adapter: null,

        showSettings: false,

        history: [],
        historyLoaded: false,

        isProcessing: false,
        isDeleting: false,
        preview: null,

        prompt: null,
        tags: null,
        instrumental: false,
        model: null,

        init() {
            this.$watch('adapter', () => this.model = this.adapter?.model);

            this.$watch('preview', (value) => {
                // Update the item in the history list
                if (this.history && value) {
                    let index = this.history.findIndex(item => item.id === value.id);
                    if (index >= 0) {
                        this.history[index] = value;
                    }
                }
            });

            adapters.forEach(adapter => {
                if (adapter.is_available) {
                    adapter.models.forEach(model => {
                        if (model.is_available) {
                            this.adapters.push(model);
                        }
                    });
                }
            });

            this.adapter = this.adapters.find(adapter => adapter.model == model);
            if (!this.adapter && this.adapters.length > 0) {
                this.adapter = this.adapters[0];
            }

            if (composition) {
                this.select(composition);
            }

            this.$watch('model', () => this.adapter = this.adapters.find(adapter => adapter.model == this.model));
            this.fetchHistory();
        },

        fetchHistory() {
            let params = {
                limit: 25
            };

            if (this.history && this.history.length > 0) {
                params.starting_after = this.history[this.history.length - 1].id;
            }

            api.get('/library/compositions', params)
                .then(response => response.json())
                .then(list => {
                    let data = list.data;
                    this.history.push(...data);

                    if (data.length < params.limit) {
                        this.historyLoaded = true;
                    }
                })
        },

        submit($el) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let data = {
                prompt: this.prompt,
                tags: this.tags,
                instrumental: this.instrumental,
                model: this.adapter.model || null,
            };

            api.post(`/ai/compositions`, data)
                .then(response => response.json())
                .then(compositions => {
                    compositions.forEach(composition => {
                        this.history.push(composition);
                    });

                    this.select(compositions[0]);

                    this.prompt = null;
                    this.isProcessing = false;
                })
                .catch(error => {
                    this.isProcessing = false;
                    this.preview = null;

                    let url = new URL(window.location.href);
                    url.pathname = '/app/composer/';
                    window.history.pushState({}, '', url);

                    this.history.splice(this.history.indexOf(composition), 1);
                });
        },

        select(composition) {
            this.preview = composition;

            let url = new URL(window.location.href);
            url.pathname = '/app/composer/' + composition.id;
            window.history.pushState({}, '', url);
        },

        remove(composition) {
            this.isDeleting = true;

            api.delete(`/library/compositions/${composition.id}`)
                .then(() => {
                    this.preview = null;
                    window.modal.close();

                    toast.show("Composition has been deleted successfully.", 'ti ti-trash');
                    this.isDeleting = false;

                    let url = new URL(window.location.href);
                    url.pathname = '/app/composer/';
                    window.history.pushState({}, '', url);

                    this.history.splice(this.history.indexOf(composition), 1);
                })
                .catch(error => this.isDeleting = false);
        }
    }));
}