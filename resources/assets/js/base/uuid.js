'use strict';

import { inIframe } from "./helpers";
import { toast } from "./toast";

export class Uuid extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        if (inIframe()) {
            return;
        }

        this.classList.add(
            'cursor-pointer',
            'select-none'
        );

        this.setAttribute('title', "Click to copy")
        this.dataset.tippyPlacement = 'right';

        this.addEventListener('click', () => {
            navigator.clipboard.writeText(this.innerText)
                .then(() => {
                    toast.show(
                        'Resource UUID is copied to the clipboard.',
                        'ti ti-copy'
                    );
                });

        });
    }
}