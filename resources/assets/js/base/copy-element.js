'use strict';

import { inIframe } from "./helpers";
import { toast } from "./toast";

export class CopyElement extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        if (inIframe()) {
            return;
        }

        if (!this.hasAttribute('title')) {
            this.setAttribute('title', "Click to copy")
        }

        this.classList.add(
            'cursor-pointer',
        );

        let copymsg = this.dataset.msg || 'Copied to clipboard';

        this.addEventListener('click', () => {
            let data = this.dataset.copy || this.innerText;

            navigator.clipboard.writeText(data)
                .then(() => {
                    toast.show(copymsg, 'ti ti-copy');
                });

        });
    }
}