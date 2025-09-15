'use strict';

export class DropzoneElement extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        this.dataset.state = 'hidden';

        document.body.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.dataset.state = 'visible';
        });

        document.body.addEventListener('dragleave', (e) => {
            e.preventDefault();
            this.dataset.state = 'hidden';
        });

        document.body.addEventListener('drop', (e) => {
            e.preventDefault();

            this.dataset.state = 'hidden';

            const fileInput = this.querySelector('input[type="file"]');
            if (fileInput && e.dataTransfer.files.length > 0) {
                const acceptedTypes = fileInput.accept ? fileInput.accept.split(',') : null;
                const isMultiple = fileInput.hasAttribute('multiple');
                let selectedFiles = [];

                for (let file of e.dataTransfer.files) {
                    if (!acceptedTypes || acceptedTypes.some(type => {
                        if (type.startsWith('.')) {
                            return file.name.toLowerCase().endsWith(type.toLowerCase());
                        } else {
                            return file.type.match(new RegExp(type.replace('*', '.*')));
                        }
                    })) {
                        selectedFiles.push(file);
                        if (!isMultiple) break; // Only take the first if not multiple
                    }
                }

                if (selectedFiles.length > 0) {
                    const dataTransfer = new DataTransfer();
                    selectedFiles.forEach(file => dataTransfer.items.add(file));
                    fileInput.files = dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
    }
}