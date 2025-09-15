'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function voiceIsolator() {
    Alpine.data('voiceIsolator', (voice = null) => ({
        isProcessing: false,
        isDeleting: false,

        history: [],
        historyLoaded: false,

        preview: voice,
        previewTime: 0,
        file: null,
        viewInputFile: false,

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

            api.get('/library/isolated-voices', params)
                .then(response => response.json())
                .then(list => {
                    let data = list.data;
                    this.history.push(...data);

                    if (data.length < params.limit) {
                        this.historyLoaded = true;
                    }
                });
        },

        /** @param {HTMLFormElement} el */
        submit(el) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api
                .post('/ai/isolated-voices', new FormData(el))
                .then((response) => response.json())
                .then((voice) => {
                    if (this.history === null) {
                        this.history = [];
                    }

                    this.history.push(voice);
                    this.select(voice);

                    this.isProcessing = false;
                    el.reset();
                    this.file = null;
                })
                .catch((error) => this.isProcessing = false);
        },

        save(voice) {
            api.post(`/library/isolated-voices/${voice.id}`, {
                title: voice.title,
            });
        },

        select(voice) {
            this.preview = voice;

            let url = new URL(window.location.href);
            url.pathname = '/app/voice-isolator/' + voice.id;
            window.history.pushState({}, '', url);
        },

        remove(voice) {
            this.isDeleting = true;

            api.delete(`/library/isolated-voices/${voice.id}`)
                .then(() => {
                    this.preview = null;
                    window.modal.close();

                    toast.show("Resource has been deleted successfully.", 'ti ti-trash');
                    this.isDeleting = false;

                    let url = new URL(window.location.href);
                    url.pathname = '/app/voice-isolator/';
                    window.history.pushState({}, '', url);

                    this.history.splice(this.history.indexOf(voice), 1);
                })
                .catch(error => this.isDeleting = false);
        },
    }));
}