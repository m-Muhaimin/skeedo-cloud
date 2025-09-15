'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function pluginsView() {
    Alpine.data('plugins', (strings = [], activeTheme = null) => ({
        state: 'initial',
        activeTheme: activeTheme,
        type: 'plugin',

        params: {},
        total: null,
        cursor: null,
        resources: [],
        isLoading: false,
        currentResource: null,
        isFiltered: false,
        isDeleting: false,
        isPublishing: false,
        all: null,

        init() {
            if (this.activeTheme) {
                this.type = 'theme';
            }

            this.getTotalCount();
            this.retrieveResources();

            let timer = null;
            window.addEventListener('lc.filtered', (e) => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    this.params = e.detail;
                    this.retrieveResources(true);
                }, 200);
            });
        },

        getTotalCount() {
            api.get(`plugins/count`, {
                type: this.type
            })
                .then(response => {
                    this.total = response.data.count;
                    this.all = response.data.count;
                });
        },

        retrieveResources(reset = false) {
            this.isLoading = true;
            let params = {
                type: this.type
            };
            let isFiltered = false;

            for (const key in this.params) {
                if (this.params[key]) {
                    isFiltered = true;
                    params[key] = this.params[key];
                }
            }

            this.isFiltered = isFiltered;

            if (!reset && this.cursor) {
                params.starting_after = this.cursor;
            }

            api.get(`plugins`, params)
                .then(response => {
                    this.state = 'loaded';

                    this.resources = reset
                        ? response.data.data
                        : this.resources.concat(response.data.data);

                    if (this.resources.length > 0) {
                        this.cursor = this.resources[this.resources.length - 1].id;
                    } else {
                        this.state = 'empty';
                    }

                    this.isLoading = false;
                });
        },

        toggleStatus(resource) {
            resource.status = resource.status == 'active' ? 'inactive' : 'active';

            api.post(`plugins/${resource.name}`, {
                status: resource.status
            });
        },

        publish(theme) {
            if (this.isPublishing) {
                return;
            }

            this.isPublishing = theme.name;

            api.post(`options/`, { theme: theme.name })
                .then(() => {
                    this.activeTheme = theme.name;
                    this.isPublishing = false;
                })
                .catch(error => {
                    this.isPublishing = false;
                });
        },

        deleteResource(resource) {
            this.isDeleting = true;

            api.delete(`plugins/${resource.name}`)
                .then(() => {
                    this.resources.splice(this.resources.indexOf(resource), 1);
                    window.modal.close();

                    this.currentResource = null;
                    toast.show(strings.delete_success, 'ti ti-trash');

                    this.isDeleting = false;
                })
                .catch(error => {
                    this.isDeleting = false;
                });
        }
    }))
}