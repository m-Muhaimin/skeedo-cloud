'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { listResources } from './helpers';

export function workspaceView() {
    Alpine.data('workspace', (workspace, ranges = [], range = 'last_30_days') => ({
        workspace: workspace,
        isProcessing: false,
        plans: [],
        orders: [],
        voices: [],
        ranges: ranges,
        range: null,
        datasets: {
            usage: []
        },

        init() {
            this.range = ranges.find(r => r.range === range);

            this.getPlans();
            this.getOrders();
            this.getVoices();

            this.getDatasets();
            this.$watch('range', () => this.getDatasets());
        },

        getDatasets() {
            let params = this.rangeToDate(this.range.range);
            params.wsid = this.workspace.id;

            this.getUsageDataset(params);
        },

        getUsageDataset(params = {}) {
            api.get(`/reports/dataset/usage`, params)
                .then(response => {
                    this.datasets.usage = response.data;
                });
        },

        rename(name) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.patch(`/workspaces/${this.workspace.id}`, { name: name })
                .then(response => {
                    this.workspace = response.data;
                    this.isProcessing = false;

                    toast.success('Workspace name updated!');

                    window.modal.close();
                }).catch(error => this.isProcessing = false);
        },

        subscribe(plan_id) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.post(`/subscriptions/`, {
                workspace_id: this.workspace.id,
                plan_id: plan_id
            })
                .then(response => {
                    this.workspace.subscription = response.data;
                    this.isProcessing = false;

                    toast.success('Subscription created successfully!');

                    window.modal.close();
                }).catch(error => this.isProcessing = false);
        },

        adjustCredits(count, total = false) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.patch(`/workspaces/${this.workspace.id}`, {
                credit_count: total ? count : 1 * count + this.workspace.credit_count
            })
                .then(response => {
                    this.workspace = { ...this.workspace, ...response.data };
                    this.isProcessing = false;

                    toast.success('Add-on credits adjusted successfully!');

                    window.modal.close();
                }).catch(error => this.isProcessing = false);
        },

        getPlans() {
            let cycles = ['monthly', 'yearly', 'lifetime'];

            listResources('/plans')
                .then(plans => {
                    plans.forEach(plan => {
                        if (cycles.includes(plan.billing_cycle)) {
                            this.plans.push(plan);
                        }
                    });
                });
        },

        getOrders() {
            api.get(`/orders?workspace=${this.workspace.id}&sort=created_at:desc&limit=3`)
                .then(response => {
                    this.orders = response.data.data;
                });
        },

        rangeToDate(range) {
            let today = new Date();
            let start = new Date(today);
            let end = new Date(today);

            switch (range) {
                case 'today':
                    // Start and end are the same (today)
                    break;
                case 'last_7_days':
                    start.setDate(today.getDate() - 6);
                    break;
                case 'last_30_days':
                    start.setDate(today.getDate() - 29);
                    break;
                case 'month_to_date':
                    start.setDate(1);
                    break;
                case 'last_month':
                    // Set end to the last day of the previous month
                    end = new Date(today.getFullYear(), today.getMonth(), 0);
                    // Set start to the first day of the previous month
                    start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    break;
                case 'last_3_months':
                    start.setDate(today.getDate() - 89); // Set start to 90 days ago
                    break;
            }

            return {
                start: this.formatDate(start),
                end: this.formatDate(end)
            };
        },

        formatDate(date) {
            return date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0');
        },

        getVoices() {
            api.get(`/voices?workspace=${this.workspace.id}&limit=3`)
                .then(response => this.voices = response.data.data);
        }
    }))
}