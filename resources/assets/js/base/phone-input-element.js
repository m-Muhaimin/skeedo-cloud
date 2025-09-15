'use strict';

import { AsYouType } from 'libphonenumber-js';
import parsePhoneNumber from 'libphonenumber-js';

export class PhoneInputElement extends HTMLElement {
    constructor() {
        super();
    }

    /**
    * Called when the phone input element is connected to the DOM.
    * Sets up event listeners and a mutation observer to track changes in the 
    * phone input element.
    */
    connectedCallback() {
        const input = this.querySelector('input[type="tel"]');
        if (!input) return;

        // Handle input events to format as user types
        input.addEventListener('input', (e) => {
            // Create new formatter instance for each input
            const formatter = new AsYouType();
            formatter.input(e.target.value);

            // Get formatted value and handle prefixes
            let value = formatter.getChars();

            // Only apply prefix logic if value is not empty
            if (value) {
                if (value.startsWith('00')) {
                    value = '+' + value.slice(2);
                } else if (!value.startsWith('+') && value !== '0') {
                    value = '+' + value;
                }
            }

            // Update input value with formatted number
            e.target.value = value;

            // Validate the number
            this.validateNumber(e.target);

            if (formatter.getNumber()?.country) {
                this.dataset.country = formatter.getNumber().country;
            } else {
                this.removeAttribute('data-country');
            }
        });

        // Handle blur event for final validation
        input.addEventListener('blur', (e) => {
            this.validateNumber(e.target);
        });

        // Validate initial value if exists
        setTimeout(() => {
            if (input.value) {
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }, 100);
    }

    /**
     * Validates the phone number and sets custom validity
     * @param {HTMLInputElement} input - The input element to validate
     */
    validateNumber(input) {
        if (!input.value) {
            input.setCustomValidity('');
            return;
        }

        try {
            const phoneNumber = parsePhoneNumber(input.value);

            if (phoneNumber) {
                // Format to international format on validation
                input.value = phoneNumber.format('E.164');

                if (phoneNumber.isValid()) {
                    input.setCustomValidity('');
                } else {
                    input.setCustomValidity('Please enter a valid phone number');
                }
            } else {
                input.setCustomValidity('Please enter a valid phone number');
            }
        } catch (error) {
            input.setCustomValidity('Please enter a valid phone number');
        }
    }
}