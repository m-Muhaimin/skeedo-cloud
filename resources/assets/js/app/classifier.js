'use strict';

import Alpine from 'alpinejs';
import api from './api';

export function classifier() {
    Alpine.data('classifier', (classification = null) => ({
        isProcessing: false,
        isDeleting: false,

        history: [],
        historyLoaded: false,

        preview: classification,
        prompt: null,
        currentResource: null,

        init() {
            this.$watch('preview', (value) => {
                // Update the item in the history list
                if (this.history && value) {
                    let index = this.history.findIndex(item => item.id === value.id);
                    if (index >= 0) {
                        this.history[index] = value;
                    }
                }
            });

            this.fetchHistory();
        },

        fetchHistory() {
            let params = {
                limit: 25
            };

            if (this.history && this.history.length > 0) {
                params.starting_after = this.history[this.history.length - 1].id;
            }

            api.get('/library/classifications', params)
                .then(response => response.json())
                .then(list => {
                    let data = list.data;
                    this.history.push(...data);

                    if (data.length < params.limit) {
                        this.historyLoaded = true;
                    }
                });
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let data = {
                input: this.prompt,
            };

            api.post('/ai/classifications', data)
                .then(response => response.json())
                .then((classification) => {
                    if (this.history === null) {
                        this.history = [];
                    }

                    this.history.push(classification);
                    this.preview = classification;
                    this.isProcessing = false;
                    this.prompt = null;

                    this.select(classification);
                })
                .catch(error => {
                    this.isProcessing = false;
                    console.error(error);
                });
        },

        select(classification) {
            this.preview = classification;

            let url = new URL(window.location.href);
            url.pathname = '/app/classifier/' + classification.id;
            window.history.pushState({}, '', url);
        },

        save(classification) {
            api.post(`/library/classifications/${classification.id}`, {
                title: classification.title,
            });
        },

        remove(classification) {
            this.isDeleting = true;

            api.delete(`/library/classifications/${classification.id}`)
                .then(() => {
                    this.preview = null;
                    window.modal.close();

                    toast.show("Classification has been deleted successfully.", 'ti ti-trash');
                    this.isDeleting = false;

                    let url = new URL(window.location.href);
                    url.pathname = '/app/classifier/';
                    window.history.pushState({}, '', url);

                    this.history.splice(this.history.indexOf(classification), 1);
                })
                .catch(error => this.isDeleting = false);
        },
    }));
}