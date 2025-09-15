'use strict';

import Alpine from 'alpinejs';
import api from './api';

export function analyticsView() {
    Alpine.data('analytics', (ranges = [], range = 'last_30_days') => ({
        ranges: ranges,
        range: null,
        dateRange: null,
        datasets: {
            workspaceUsage: null,
            usage: [],
            users: [],
            orders: [],
            countries: null
        },

        init() {
            this.range = ranges.find(r => r.range === range);
            this.dateRange = this.rangeToDate(this.range.range);

            this.getDatasets();
            this.$watch('range', () => {
                this.dateRange = this.rangeToDate(this.range.range);
                this.getDatasets();
            });
        },

        getDatasets() {
            let params = this.dateRange;

            this.getUsageDataset(params);
            this.getWorkspaceUsageDataset(params);
            this.getUsersDataset(params);
            this.getOrdersDataset(params);
            this.getCountryDataset(params);
        },

        getUsageDataset(params = {}) {
            api.get(`/reports/dataset/usage`, params)
                .then(response => {
                    this.datasets.usage = response.data;
                });
        },

        getWorkspaceUsageDataset(params = {}) {
            api.get(`/reports/dataset/workspace-usage`, params)
                .then(response => {
                    this.datasets.workspaceUsage = response.data;
                });
        },

        getUsersDataset(params = {}) {
            api.get(`/reports/dataset/signup`, params)
                .then(response => {
                    this.datasets.users = response.data;
                });
        },

        getOrdersDataset(params = {}) {
            api.get(`/reports/dataset/order`, params)
                .then(response => {
                    let list = response.data;
                    list.sort((a, b) => b.value - a.value);

                    this.datasets.orders = list;
                });
        },

        getCountryDataset(params = {}) {
            api.get(`/reports/dataset/country`, params)
                .then(response => {
                    let list = response.data;
                    list.sort((a, b) => b.value - a.value);

                    this.datasets.countries = list;
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
        }
    }));
}
