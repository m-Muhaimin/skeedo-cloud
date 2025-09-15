'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function affiliatesView() {
    Alpine.data('affiliates', () => ({
        payouts: [],
        payoutsFetched: false,

        affiliate: {},
        affiliateIsProcessing: false,
        withdrawIsProcessing: false,

        init() {
            this.affiliate = this.$store.user.affiliate;
            this.getPayouts();
        },

        setPayoutMethod() {
            this.affiliateIsProcessing = true;

            api.put('/account/affiliate', this.affiliate)
                .then(response => response.json())
                .then(data => {
                    this.affiliateIsProcessing = false;
                    this.$store.user = data;

                    window.modal.close();
                    toast.success('Changes saved successfully!');
                });
        },

        sendWithdrawalRequest() {
            this.withdrawIsProcessing = true;

            api.post('/account/affiliate/payouts')
                .then(response => response.json())
                .then(data => {
                    toast.defer('Withdrawal request sent successfully!');
                    window.location.reload();
                });
        },

        getPayouts() {
            let params = {
                limit: 5,
                sort: 'created_at:desc'
            }

            api.get('/account/affiliate/payouts', params)
                .then(response => response.json())
                .then(list => {
                    this.payoutsFetched = true;
                    this.payouts = list.data;
                });
        },
    }));
}