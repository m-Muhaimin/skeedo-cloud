'use strict';

import Alpine from 'alpinejs';
import api from './api';
import helper from '../redirect';

export function dashboardView() {
    Alpine.data('quickAccess', () => ({
        processing: false,
        showResults: false,
        results: [],
        prompt: '',

        init() {
            this.bindKeyboardShortcuts();
        },

        bindKeyboardShortcuts() {
            window.addEventListener('keydown', (e) => {
                if (e.metaKey && e.key === 'k') {
                    e.preventDefault();
                    this.$refs.input.focus();
                } else if (e.key === 'Escape') {
                    this.$refs.input.blur();
                    this.showResults = false;
                }
            });
        },

        enter(e) {
            if (e.key === 'Enter' && !e.shiftKey && !this.processing && this.prompt && this.prompt.trim() !== '') {
                e.preventDefault();
                this.processing = true;
                this.$refs.form.submit();
            }
        },
    }));

    Alpine.data('dashboard', () => ({
        documents: [],
        documentsFetched: false,
        usageDataset: [],

        init() {
            this.checkPendingRedirection();
            this.getRecentDocuments();
            this.getUsageDataset();
        },

        getRecentDocuments() {
            let params = {
                limit: 5,
                sort: 'created_at:desc'
            }

            api.get('/library/documents', params)
                .then(response => response.json())
                .then(list => {
                    this.documentsFetched = true;
                    this.documents = list.data;
                });
        },

        checkPendingRedirection() {
            let path = helper.getRedirectPath();
            if (path) {
                // Remove the redirect path from local storage
                helper.clearRedirectPath();

                // Redirect the user to the path
                window.location.href = path;
            }
        },

        getUsageDataset() {
            api.get('/workspaces/' + this.$store.workspace.id + '/datasets/usage', {
                limit: 30
            })
                .then(response => response.json())
                .then(list => {
                    this.usageDataset = list;
                });
        }
    }));
}
