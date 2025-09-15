'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function assistantView() {
    Alpine.data('assistant', (assistant) => ({
        assistant: {},
        model: {},
        isProcessing: false,
        files: [],
        fileIndex: 0,
        isDeleting: false,
        currentResource: null,

        init() {
            this.assistant = assistant;
            this.model = { ...this.model, ...this.assistant };
        },

        async submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let data = { ... this.model }
            data.status = this.model.status ? 1 : 0;

            if (this.model.file) {
                data.avatar = await this.readFileAsBase64(this.model.file);
                delete data.file;
            }

            this.assistant.id ? this.update(data) : this.create(data);
        },

        update(data) {
            api.patch(`/assistants/${this.assistant.id}`, data)
                .then(response => {
                    this.assistant = response.data;
                    this.model = { ...this.assistant };

                    this.isProcessing = false;

                    toast.success('Assistant has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        },

        create(data) {
            api.post('/assistants', data)
                .then(response => {
                    toast.defer('Assistant has been created successfully!');
                    window.location = `/admin/assistants/`;
                })
                .catch(error => this.isProcessing = false);
        },

        readFileAsBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const base64String = e.target.result.split(',')[1];
                    resolve(base64String);
                };
                reader.onerror = function (error) {
                    reject(error);
                };
                reader.readAsDataURL(file);
            });
        },

        /**
         * Adds selected files to the files array
         * @param {File[]} files - Array of File objects selected by file input
         */
        pushFiles(files) {
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (!this.files.some(existingFile => existingFile.name === file.name)) {
                    this.files.push({
                        blob: file,
                        name: file.name,
                        type: file.type,
                        extension: file.name.split('.').pop(),
                        size: file.size,
                        lastModified: file.lastModified,
                        status: 'pending',
                        error: null,
                        id: this.fileIndex++
                    });
                }
            }

            this.processNextPendingFile();
        },

        processNextPendingFile() {
            const pendingFile = this.files.find(file => file.status === 'pending');

            if (pendingFile && !this.files.some(file => file.status === 'uploading')) {
                this.uploadFile(pendingFile);
            }
        },

        uploadFile(file) {
            file.status = 'uploading';

            let data = new FormData();
            data.append('file', file.blob);

            api.post(`/assistants/${this.assistant.id}/dataset`, data)
                .then(response => {
                    this.assistant.dataset.push(response.data);
                    this.removeFile(file);
                })
                .catch(error => {
                    file.status = 'error';
                    file.error = error.message;
                })
                .finally(() => this.processNextPendingFile());

        },

        removeFile(file) {
            this.files = this.files.filter(f => f !== file);
        },

        deleteResource(resource) {
            this.isDeleting = true;

            api.delete(`/assistants/${this.assistant.id}/dataset/${resource.id}`)
                .then(() => {
                    this.assistant.dataset.splice(this.assistant.dataset.indexOf(resource), 1);
                    window.modal.close();

                    this.currentResource = null;
                    toast.show('Data unit has been deleted successfully.', 'ti ti-trash');

                    this.isDeleting = false;
                })
                .catch(error => this.isDeleting = false);
        },

        addPage(url) {
            this.isProcessing = true;

            api.post(`/assistants/${this.assistant.id}/dataset`, { url })
                .then(response => {
                    this.assistant.dataset.push(response.data);
                    window.modal.close();
                    this.isProcessing = false;
                })
                .catch(error => this.isProcessing = false);
        }
    }))
}
