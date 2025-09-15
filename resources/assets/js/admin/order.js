'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function orderView() {
    Alpine.data('order', (order = {}) => ({
        order: order,
        isProcessing: false,
        action: null,

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            api.delete(`/orders/${this.order.id}/${this.action}`)
                .then(response => {
                    this.order = response.data;
                    toast.success('Order has been updated successfully!');
                })
                .finally(() => {
                    this.isProcessing = false;
                    this.action = null;
                    window.modal.close();
                });
        }
    }))
}
