'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { filesize } from "filesize";

export function updateView() {
    Alpine.data('update', () => ({
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
                await api.post(`/update`, data);

                toast.defer('Updated successfully!');
                window.location.reload();
            } catch (error) {
                let msg = 'An unexpected error occurred! Please try again later!';

                if (error.response && error.response.data.message) {
                    msg = error.response.data.message;
                }

                this.isProcessing = false;
                this.error = msg;

                window.modal.close();

                return;
            }
        }
    }));
};
