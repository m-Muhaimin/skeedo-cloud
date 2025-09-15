'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { markdownToHtml } from './markdown';
import { toast } from '../base/toast';
import { EventSourceParserStream } from 'eventsource-parser/stream'
import populate from './populate';

export function writerView() {
    Alpine.data('writer', (preset = null, doc = null) => ({
        required: [],
        isProcessing: false,
        showForm: true,
        isDeleting: false,

        docs: [],
        index: 0,
        preset: preset,

        init() {
            if (doc) {
                this.showForm = false;
                this.docs.push(doc);

                populate(this.$refs.form, doc.params);
            }

            this.$watch('index', (index) => {
                if (this.docs[index].id) {
                    this.select(this.docs[index])
                }
            });
        },

        async submit(params = null) {
            if (this.isProcessing) {
                return;
            }

            this.docs.push({});
            this.index = this.docs.length - 1;

            let newIndex = this.docs.length - 1;

            this.isProcessing = true;
            this.showForm = false;

            if (!params) {
                params = {};
                new FormData(this.$refs.form).forEach((value, key) => params[key] = value);
            }

            try {
                let response = await api.post(`/ai/completions/${this.preset ? this.preset.id : ''}`, params);

                // Get the readable stream from the response body
                const stream = response.body
                    .pipeThrough(new TextDecoderStream())
                    .pipeThrough(new EventSourceParserStream());

                // Get the reader from the stream
                const reader = stream.getReader();

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) {
                        this.isProcessing = false;
                        break;
                    }

                    if (value.event == 'token') {
                        this.docs[newIndex].content = (this.docs[newIndex].content || '') + JSON.parse(value.data).data;
                        continue;
                    }

                    if (value.event == 'document') {
                        this.docs[newIndex] = JSON.parse(value.data);
                        this.select(this.docs[newIndex]);
                        continue;
                    }

                    if (value.event == 'error') {
                        this.isProcessing = false;
                        this.showForm = true;

                        this.docs.splice(newIndex, 1);
                        this.index = this.docs.length > 1 ? this.docs.length - 1 : 0;

                        toast.error(value.data);
                        break;
                    }
                }
            } catch (error) {
                this.isProcessing = false;
                this.showForm = true;
                this.docs.pop();
                this.index = this.docs.length > 1 ? this.docs.length - 1 : 0;
            }
        },

        format(content) {
            return markdownToHtml(content);
        },

        copyDocumentContents(doc) {
            navigator.clipboard.writeText(doc.content)
                .then(() => {
                    toast.success('Document copied to clipboard!');
                });
        },

        download(doc, format) {
            if (format == 'markdown') {
                var mimeType = 'text/markdown';
                var ext = 'md';
                var content = doc.content;
            } else if (format == 'html') {
                var mimeType = 'text/html';
                var ext = 'html';
                var content = `<html><head><meta charset="utf-8" /><title>${doc.title}</title></head><body>${this.format(doc.content)}</body></html>`;
            } else if (format == 'word') {
                var mimeType = 'application/vnd.ms-word';
                var ext = 'doc';
                var content = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8" /><title>${doc.title}</title></head><body>${this.format(doc.content)}</body></html>`;
            } else {
                var mimeType = 'text/plain';
                var ext = 'txt';
                var content = doc.content;
            }

            this.downloadFromUrl(
                `data:${mimeType};charset=utf-8,${encodeURIComponent(content)}`,
                doc.title,
                ext);
        },

        downloadFromUrl(url, filename, ext) {
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = `${filename}.${ext}`;

            document.body.appendChild(anchor);
            anchor.click();

            // Clean up
            document.body.removeChild(anchor);
        },

        saveDocument(doc) {
            api.post(`/library/documents/${doc.id}`, doc);
        },

        deleteDocument(doc) {
            this.isDeleting = true;
            api.delete(`/library/documents/${doc.id}`)
                .then(() => {
                    toast.success('Document deleted successfully!');
                    this.docs = this.docs.filter(d => d.id != doc.id);
                    this.index = this.docs.length > 1 ? this.docs.length - 1 : 0;

                    if (this.docs.length == 0) {
                        this.showForm = true;
                    }
                })
                .finally(() => {
                    this.isDeleting = false;
                    window.modal.close();
                });
        },

        select(doc) {
            let url = new URL(window.location.href);
            url.pathname = '/app/writer/' + (doc.id ?? '');
            window.history.pushState({}, '', url);
        }
    }));
}