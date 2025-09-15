'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function payoutView() {
    Alpine.data('payout', (payout) => ({
        isProcessing: false,
        payout: payout,

        init() {
        },

        approve() {
            this.isProcessing = true;

            api.post(`/payouts/${this.payout.id}`, {
                status: 'approved'
            })
                .then(response => {
                    this.payout = response.data;

                    window.modal.close();
                    this.isProcessing = false;
                    toast.success('Payout has been approved successfully!');

                })
                .catch(error => this.isProcessing = false);
        },

        reject() {
            this.isProcessing = true;

            api.post(`/payouts/${this.payout.id}`, {
                status: 'rejected'
            })
                .then(response => {
                    this.payout = response.data;

                    window.modal.close();
                    this.isProcessing = false;
                    toast.success('Payout has been rejected successfully!');
                })
                .catch(error => this.isProcessing = false);
        }
    }))
}