`use strict`;

import { __ } from "./translate";

export class Toast extends HTMLElement {
    constructor() {
        super();
        this.timer = 0;

        this.addEventListener('click', () => this.hide());
    }

    connectedCallback() {
        let data = sessionStorage.getItem('toast');

        if (data) {
            let { message, icon } = JSON.parse(data);
            this.show(message, icon);
            sessionStorage.removeItem('toast');
        }
    }

    show(message, icon) {
        clearTimeout(this.timer);
        this.hide();

        this.innerHTML = '';

        if (icon) {
            if (icon == 'success') {
                icon = 'ti ti-square-rounded-check-filled';
            } else if (icon == 'error') {
                icon = 'ti ti-square-rounded-x-filled';
            }

            this.innerHTML = `<i class="text-2xl transition-all delay-100 translate-y-2 rotate-45 opacity-0 group-data-open/toast:rotate-0 group-data-open/toast:translate-y-0 group-data-open/toast:opacity-100 ${icon}"></i>`
        }

        let el = document.createElement('div');
        el.innerHTML = __(message);

        this.appendChild(el);

        setTimeout(() =>
            this.dataset.open = true, 100)

        this.timer = setTimeout(() => this.hide(), 5000);
    }

    hide() {
        delete this.dataset.open;
    }

    defer(message, icon = 'success') {
        sessionStorage.setItem('toast', JSON.stringify({ message, icon }));
    }

    success(message) {
        this.show(message, 'success');
    }

    error(message) {
        this.show(message, 'error');
    }
}

/** @type {Toast} */
let toast = document.querySelector('toast-message');
window.toast = toast;

export { toast };