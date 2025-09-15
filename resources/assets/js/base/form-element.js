'use strict';

/**
 * Represents a custom form element that extends the HTMLFormElement class.
 * This class provides additional functionality for handling form inputs and 
 * validation.
 */
export class FormElement extends HTMLElement {
    constructor() {
        super();
        this.timer = 0;
    }

    /**
     * Called when the form element is connected to the DOM.
     * Sets up event listeners and a mutation observer to track changes in the 
     * form element.
     */
    connectedCallback() {
        this.addEventListener('input', () => this.callback());

        this.observer = new MutationObserver((mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    this.callback();
                }
            }
        });

        // Configuration of the observer
        const config = { attributes: false, childList: true, subtree: true };

        // Start observing the target node for configured mutations
        this.observer.observe(this, config);

        // Check if the form is valid and enable/disable the submit button(s)
        this.callback();
    }

    /**
     * Called when the form element is disconnected from the DOM.
     * Disconnects the mutation observer.
     */
    disconnectedCallback() {
        this.observer.disconnect();
    }

    /**
     * Checks if the form is valid and enables/disables the submit button(s)
     * accordingly. 
     */
    callback() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.checkSubmitable(), 200);
    }

    /**
     * Checks if the form is valid and enables/disables the submit button(s)
     * accordingly. 
     */
    checkSubmitable() {
        let forms = this.querySelectorAll('form');

        forms.forEach(form => {
            const btns = form.querySelectorAll('[type="submit"]');
            let isSubmitable = form.checkValidity();

            for (let i = 0; i < btns.length; i++) {
                btns[i].disabled = !isSubmitable;
            }
        });
    }
}