import { __ } from "./base/translate";

export function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

export function getJwtToken() {
    return localStorage.getItem('jwt') || getCookie('user');
}

export function sleep(time) {
    return new Promise(resolve => setTimeout(resolve, time));
}

export function getBillingCycleLabel(cycle) {
    switch (cycle) {
        case 'one-time':
            return __('One time');
        case 'lifetime':
            return __('Lifetime');
        case 'monthly':
            return __('Monthly');
        case 'yearly':
            return __('Yearly');
        default:
            return cycle;
    }
}