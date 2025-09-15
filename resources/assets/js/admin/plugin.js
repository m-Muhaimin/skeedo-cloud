'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { filesize } from "filesize";

export function pluginView() {
    Alpine.data('plugin', () => ({
        file: null,
        isProcessing: false,
        error: null,

        init() {
        },

        filesize() {
            return filesize(this.file.size);
        },

        async submit() {
            if (!this.file || this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            let data = new FormData();
            data.append('file', this.file);

            try {
                var resp = await api.post(`/plugins`, data);
            } catch (error) {
                let msg = 'An unexpected error occurred! Please try again later!';

                if (error.response && error.response.data.message) {
                    msg = error.response.data.message;
                }

                this.isProcessing = false;
                this.error = msg;

                return;
            }

            api.post(`/plugins/${resp.data.name}/initialize`)
                .then(() => {
                    if (resp.data.type === 'theme') {
                        toast.defer('Theme installed successfully!');
                        window.location.href = `admin/themes/`;
                        return;
                    }

                    toast.defer('Plugin installed successfully!');
                    window.location.href = `admin/plugins/`;
                })
                .catch(error => {
                    let msg = 'An unexpected error occurred! Please try again later!';

                    if (error.response && error.response.data.message) {
                        msg = error.response.data.message;
                    }

                    this.isProcessing = false;
                    this.error = msg;
                });

        }
    }));
};
