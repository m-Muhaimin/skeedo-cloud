'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
export function licenseView() {
    Alpine.data('license', () => ({
        isProcessing: false,
        error: null,
        key: null,

        init() {
            api.config.toast = false;
        },


        async submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            let data = new FormData();
            data.append('key', this.key);

            try {
                await api.post(`/license`, data);

                toast.defer('License has been successfully activated.');
                window.location = '/admin';
            } catch (error) {
                let msg = 'An unexpected error occurred! Please try again later!';

                if (error.response && error.response.data.message) {
                    msg = error.response.data.message;
                }

                this.isProcessing = false;
                this.error = msg;

                return;
            }
        }
    }));
};
