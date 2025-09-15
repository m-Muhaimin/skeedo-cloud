'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { IpService } from '../ip';
import { CustomFormData } from '../formdata';

export function accountView() {
    Alpine.data('account', (user) => ({
        isProcessing: false,
        generatingApiKey: false,
        user: user,

        init() {
            if (this.user.ip) {
                this.fetchIpLocation();
            }
        },

        async fetchIpLocation() {
            const locationData = await IpService.getIpInfo(this.user.ip);
            if (locationData) {
                this.user.latitude = locationData.latitude;
                this.user.longitude = locationData.longitude;
            }
        },

        generateApiKey() {
            if (this.generatingApiKey) {
                return;
            }

            this.generatingApiKey = true;

            api.post('/account/rest-api-keys')
                .then(response => {
                    this.user = { ...this.user, ...response.data };
                    window.modal.open('api-modal');

                    this.generatingApiKey = false;
                })
                .catch(error => this.generatingApiKey = false);
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let data = new CustomFormData(this.$refs.form);

            api.post(`/account${this.$refs.form.dataset.path || ''}`, data)
                .then(response => response.json())
                .then(data => {
                    if (data.jwt) {
                        // Save the JWT to local storage 
                        // to be used for future api requests
                        localStorage.setItem('jwt', data.jwt);
                    }

                    this.user = { ...this.user, ...data };
                    this.isProcessing = false;

                    toast.success(
                        this.$refs.form.dataset.successMsg || 'Changes saved successfully!'
                    );
                })
                .catch(error => {
                    this.isProcessing = false
                });
        },

        resendIn: 0,
        resendVerificationEmail() {
            if (this.resent) {
                return;
            }

            this.resendIn = 60;

            let interval = setInterval(() => {
                this.resendIn--;

                if (this.resendIn <= 0) {
                    clearInterval(interval);
                }
            }, 1000);

            api.post('/account/verification')
                .then(() => toast.success('Email sent successfully!'));
        }
    }))
}