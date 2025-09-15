'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { listResources } from './helpers';

export function couponView() {
    Alpine.data('coupon', (currency, coupon = {}) => ({
        coupon: coupon,
        currency: currency,
        model: {
            discount_type: 'percentage',
        },
        plans: [],
        plansFetched: false,
        isProcessing: false,
        orders: [],
        init() {
            this.fetchPlans();
            this.regenerateCode();

            if (this.coupon.id) {
                this.fetchOrders();
            }

            this.setModel({ ...this.coupon });
        },

        setModel(model) {
            this.model = { ...this.model, ...model };
            this.model.status = model.status == 1;

            if (model.plan) {
                this.model.plan = model.plan.id;
            }

            let fractionDigits = model.discount_type == 'percentage' ? 2 : currency.fraction_digits;
            this.model.amount = (model.amount / Math.pow(10, fractionDigits)).toFixed(fractionDigits);

            const event = new Event("input", { bubbles: true });
            this.$refs.amount.dispatchEvent(event);
        },

        fetchPlans() {
            listResources('/plans')
                .then(plans => {
                    this.plans = plans;
                    this.plansFetched = true;
                });
        },

        regenerateCode() {
            // Generate 8 characters alphanumeric code (uppercase letters and numbers)
            const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            this.model.code = Array.from(
                { length: 8 },
                () => characters.charAt(Math.floor(Math.random() * characters.length))
            ).join('');
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            let data = { ...this.model };
            data.status = data.status ? 1 : 0;

            let fractionDigits = data.discount_type == 'percentage' ? 2 : currency.fraction_digits;
            data.amount = (data.amount.replaceAll(' ', '') * Math.pow(10, fractionDigits)).toFixed(0);

            let fields = ['plan', 'billing_cycle', 'starts_at', 'expires_at', 'cycle_count', 'redemption_limit'];

            fields.forEach(field => {
                let value = data[field];
                data[field] = value < 0 || value == null || value.toString().trim() == "" ? null : value;
            });

            this.coupon.id ? this.update(data) : this.create(data);
        },

        update(data) {
            api.patch(`/coupons/${this.coupon.id}`, data)
                .then(response => {
                    this.coupon = { ...this.coupon, ...response.data };
                    this.setModel({ ...this.coupon });

                    this.isProcessing = false;

                    toast.success('Coupon has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        },

        create(data) {
            api.post('/coupons', data)
                .then(response => {
                    toast.defer('Coupon has been created successfully!');
                    window.location = `/admin/coupons/`;
                })
                .catch(error => this.isProcessing = false);
        },

        fetchOrders() {
            api.get(`/orders?coupon=${this.coupon.id}&sort=created_at:desc&limit=3`)
                .then(response => {
                    this.orders = response.data.data
                });
        },
    }))
}