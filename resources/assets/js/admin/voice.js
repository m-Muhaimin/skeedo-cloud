'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { CustomFormData } from '../formdata';

export function voiceView() {
    Alpine.data('voice', (voice = {}) => ({
        voice: voice,
        isProcessing: false,

        init() {
            this.voice = voice;
        },

        async submit(form) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let data = new CustomFormData(form);
            this.update(await data.toJson());
        },

        update(data) {
            api.patch(`/voices/${this.voice.id}`, data)
                .then(response => {
                    this.voice = response.data;
                    this.isProcessing = false;

                    toast.success('Voice has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        }
    }))
}