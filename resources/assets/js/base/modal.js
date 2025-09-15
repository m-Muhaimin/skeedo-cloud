import { sleep } from "../helpers";
import { width } from './scrollbar.js';

`use strict`;

export class Modal extends HTMLElement {
    constructor() {
        super();

        let timer = 0;

        this.classList.add('group/modal');
        this.addEventListener('click', (e) => {
            if (e.target === this) {
                clearTimeout(timer);

                this.setAttribute('clicked', '');
                timer = setTimeout(() => {
                    this.removeAttribute('clicked')
                }, 100);
            }
        });
    }
}

export class ModalController {
    constructor() {
        document.body.addEventListener('keydown', (e) => {
            if (e.key === "Escape") {
                this.close();
            }
        });
    }

    async open(name) {
        this.close();
        let el = document.querySelector(`modal-element[name="${name}"]`);

        if (!el) {
            return;
        }

        if (document.body.scrollHeight > document.body.clientHeight) {
            document.body.style.setProperty(`--scrollbar-width`, `${width}px`);
        }

        document.body.setAttribute('data-modal', name);
        el.classList.add('open');
        await sleep(100);

        // Find first focusable input or submit button
        let focusable = el.querySelector('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"])');

        if (!focusable) {
            focusable = el.querySelector('button[type="submit"]');
        }

        if (focusable) {
            focusable.focus();
        }
    }

    close() {
        document.querySelectorAll(`modal-element`).forEach((modal, index, array) => {
            if (modal.hasAttribute('data-persistent')) {
                return;
            } else {
                modal.classList.remove('open');

                if (index === array.length - 1) {
                    document.body.removeAttribute('data-modal');
                }
            }

        });

        if (document.activeElement) {
            document.activeElement.blur();
        }
    }
}