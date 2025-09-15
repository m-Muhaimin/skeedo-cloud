'use strict';

import { markdownToHtml } from "../app/markdown";
import morphdom from 'morphdom';

export class MarkdownElement extends HTMLElement {
    static get observedAttributes() {
        return ['content', 'animate'];
    }

    constructor() {
        super();

        const initialContent = this.textContent?.trim() || '';
        this.textContent = '';

        // Create a container element
        this.container = document.createElement('div');
        this.appendChild(this.container);

        this.content = '';

        if (initialContent) {
            this.setContent(initialContent);
        }
    }

    setContent(content) {
        this.content = content;
        this.render();
    }

    connectedCallback() {
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name == 'content') {
            this.setContent(newValue);
        }
    }

    render() {
        let content = markdownToHtml(this.content, this.hasAttribute('animate'));
        // Create a temporary element to hold the new HTML
        const temp = document.createElement('div');
        temp.innerHTML = content;

        // Morph the container to match the new content
        morphdom(this.container, temp);
    }
}


