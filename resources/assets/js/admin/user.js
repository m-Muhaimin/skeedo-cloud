'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { IpService } from '../ip';

export function userView() {
    Alpine.data('user', (user) => ({
        user: {},
        model: {
            role: 0,
            workspace_cap: 0,
        },
        isProcessing: false,

        init() {
            this.user = user;
            this.model = { ...this.model, ...this.user };

            if (this.user.ip) {
                this.fetchIpLocation();
            }

            this.$watch(
                `model.workspace_cap`,
                (value) => {
                    this.model.workspace_cap = value < 0 || value === null || value.toString().trim() === "" ? null : value;
                }
            );
        },

        async fetchIpLocation() {
            const locationData = await IpService.getIpInfo(this.user.ip);
            if (locationData) {
                this.user.latitude = locationData.latitude;
                this.user.longitude = locationData.longitude;
            }
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            let data = this.model;
            data.status = data.status ? 1 : 0;

            this.user.id ? this.update(data) : this.create(data);
        },

        update(data) {
            api.patch(`/users/${this.user.id}`, data)
                .then(response => {
                    this.user = { ...this.user, ...response.data };
                    this.model = { ...this.user };

                    this.isProcessing = false;

                    toast.success('User has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        },

        create(data) {
            api.post('/users', data)
                .then(response => {
                    toast.defer('User has been created successfully!');
                    window.location = `/admin/users/`;
                })
                .catch(error => this.isProcessing = false);
        }
    }))
}