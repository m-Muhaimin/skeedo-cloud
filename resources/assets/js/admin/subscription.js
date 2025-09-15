'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function subscriptionView() {
    Alpine.data('subscription', (subscription) => ({
        subscription: subscription,
        isProcessing: false,

        cancelSubscription() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.delete(`/subscriptions/${subscription.id}`)
                .then(response => {
                    toast.show(
                        'Subscription cancelled!',
                        'ti ti-square-rounded-check-filled'
                    );

                    window.modal.close();

                    this.subscription = response.data;
                    this.isProcessing = false;
                });
        }
    }))
}