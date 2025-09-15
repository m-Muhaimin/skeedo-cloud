'use strict';

import { __ } from "./translate";

export class AvatarElement extends HTMLElement {
    static observedAttributes = [
        'data-title',
        'title',
        'data-src',
        'src',
        'data-mask',
        'mask',
        'data-icon',
        'icon',
        'data-length',
        'length',
        'data-hash',
        'hash',
    ];

    constructor() {
        super();
    }

    connectedCallback() {
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        this.render();
    }

    render() {
        let title = this.getAttribute('title') || this.dataset.title;
        let icon = this.getAttribute('icon') || this.dataset.icon;
        let src = this.getAttribute('src') || this.dataset.src;
        let mask = this.getAttribute('mask') || this.dataset.mask;
        let length = this.getAttribute('length') || this.dataset.length || 2;
        let hash = this.getAttribute('hash') || this.dataset.hash;
        let initials = null;

        if (title) {
            initials = title
                .split(/\s+/)
                .filter(word => word.length > 0)
                .map(word => word[0])
                .join('')
                .slice(0, length)
                .toUpperCase();
        }

        this.innerHTML = '';

        if (icon) {
            if (icon.startsWith('<svg')) {
                this.innerHTML += icon;
            } else {
                let iconDom = document.createElement('i');
                iconDom.classList.add('ti');
                iconDom.classList.add('ti-' + icon);
                this.appendChild(iconDom);
            }
        } else if (initials) {
            let initialsDom = document.createElement('span');
            initialsDom.textContent = initials;
            this.appendChild(initialsDom);
        } else if (this.hasAttribute('icon')) {
            let initialsDom = document.createElement('i');
            initialsDom.classList.add('ti');
            initialsDom.classList.add('ti-line-dotted');
            this.appendChild(initialsDom);
        }

        if (hash) {
            let blurhashDom = document.createElement('canvas');
            blurhashDom.setAttribute('is', 'x-blurhash');
            blurhashDom.setAttribute('width', '56');
            blurhashDom.setAttribute('height', '56');
            blurhashDom.setAttribute('hash', hash);
        }

        if (src) {
            let el = document.createElement('img');
            el.src = src;
            el.alt = title;

            if (el) {
                this.appendChild(el);
            }
        }

        if (mask) {
            let el = document.createElement('div');
            el.classList.add('mask');
            el.style.mask = 'url(' + mask + ')';

            this.appendChild(el);
        }
    }

}