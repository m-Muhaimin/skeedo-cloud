'use strict';

/**
 * Usage: 
 * <x-filesize data-value="{bytes}"></x-filesize>
 */
export class FilesizeElement extends HTMLElement {
    static observedAttributes = ["data-value"];

    constructor() {
        super();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        this.render();
    }

    render() {
        let bytes = this.getAttribute('data-value') || this.textContent;
        bytes = parseInt(bytes, 10);


        let lang = this.lang || document.documentElement.lang || 'en';
        let unit = 'byte';

        if (bytes > 1024) {
            bytes = bytes / 1024;
            unit = 'kilobyte'
        }

        if (bytes > 1024) {
            bytes = bytes / 1024;
            unit = 'megabyte';
        }

        if (bytes > 1024) {
            bytes = bytes / 1024;
            unit = 'gigabyte';
        }

        if (bytes > 1024) {
            bytes = bytes / 1024;
            unit = 'terabyte';
        }

        if (bytes > 1024) {
            bytes = bytes / 1024;
            unit = 'petabyte';
        }

        // round to 2 decimal places
        bytes = Math.round(bytes * 100) / 100;

        const formatter = new Intl.NumberFormat(lang, {
            style: 'unit',
            unit: unit,
            unitDisplay: 'short'
        });

        this.textContent = formatter.format(bytes);
    }
}