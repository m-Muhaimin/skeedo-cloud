'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { pins } from '../base/pin';

export function imagineView() {
    Alpine.data('imagine', (model, services = [], samples = [], image = null) => ({
        samples: samples,
        adapters: [],
        adapter: null,
        query: '',

        showSettings: false,

        history: [],
        historyLoaded: false,

        isProcessing: false,
        isDeleting: false,
        preview: null,

        prompt: null,
        negativePrompt: null,
        images: [],

        params: {},
        original: {},

        placeholder: null,
        timer: 0,
        form: true,

        init() {
            services.forEach(service => {
                service.models.forEach(model => {
                    let adapter = this.buildAdapter(service, model);
                    this.adapters.push(adapter)
                });
            });

            this.sortAdapters();
            this.selectModel(this.findModel());

            this.$watch('preview', (value) => {
                // Update the item in the history list
                if (this.history && value) {
                    let index = this.history.findIndex(item => item.id === value.id);
                    if (index >= 0) {
                        this.history[index] = value;
                    }
                }
            });

            this.$watch('adapter', () => this.reset());

            if (image) {
                this.select(image);
            }

            this.fetchHistory();
        },

        findModel() {
            // Parse query parameters and find the parameter 'q'
            let url = new URL(window.location.href);
            let m = url.searchParams.get('model');

            if (m) {
                let adapter = this.findAdapter(m);

                if (adapter) {
                    return adapter.model.key;
                }
            }

            return model;
        },

        sortAdapters() {
            // Sort adapters by granted first, then by pinned status and order
            this.adapters.sort((a, b) => {
                const grantedA = a.model.granted || false;
                const grantedB = b.model.granted || false;

                // First sort by granted status (granted models first)
                if (grantedA !== grantedB) {
                    return grantedB - grantedA; // Sort granted models first (true values come before false)
                }

                // If granted status is the same, sort by pinned status
                const pinnedA = a.pinned || false;
                const pinnedB = b.pinned || false;

                if (pinnedA !== pinnedB) {
                    return pinnedB - pinnedA; // Sort pinned models first
                }

                // If both are pinned, sort by pin order (most recently pinned first)
                if (pinnedA && pinnedB) {
                    const orderA = pins.getPinOrder(a.model.key, 'model');
                    const orderB = pins.getPinOrder(b.model.key, 'model');
                    return orderA - orderB; // Lower index (earlier pinned) comes first
                }

                return 0; // Both unpinned, maintain original order
            });
        },

        findAdapter(key) {
            for (let i = 0; i < this.adapters.length; i++) {
                const adapter = this.adapters[i];

                if (adapter.model.key == key) {
                    return adapter;
                }
            }

            return null;
        },

        buildAdapter(service, model) {
            let adapter = { ...service };
            adapter.model = model;
            delete adapter.models;

            let t = [];
            if (model.modalities?.input?.includes('image')) {
                t.push('image/jpeg', 'image/png', 'image/webp', 'image/gif');
            }

            if (model.capabilities?.includes('tools')) {
                t.push(...types);
            }

            adapter.file_types = t;
            adapter.pinned = pins.isPinned(model.key, 'model');
            return adapter;
        },

        togglePin(object) {
            pins.toggle(object.model.key, 'model');
            object.pinned = !object.pinned;
            this.sortAdapters();
        },

        selectModel(key) {
            const adapter = this.findAdapter(key);
            if (adapter) {
                this.adapter = adapter
                this.reset();
            }
        },

        fetchHistory() {
            let params = {
                limit: 24
            };

            if (this.history && this.history.length > 0) {
                params.starting_after = this.history[this.history.length - 1].id;
            }

            api.get('/library/images', params)
                .then(response => response.json())
                .then(list => {
                    let data = list.data;
                    this.history.push(...data);

                    if (data.length < params.limit) {
                        this.historyLoaded = true;
                    }
                })
        },

        reset() {
            for (let key in this.params) {
                if (this.original[key] === undefined) {
                    delete this.params[key];
                    continue;
                }

                this.params[key] = this.original[key];
            }

            this.images = [];
        },

        typeWrite(field, value) {
            let i = 0;
            let speed = 10;

            let typeWriter = () => {
                if (i < value.length) {
                    this[field] += value.charAt(i);
                    i++;

                    clearTimeout(this.timer);
                    this.timer = setTimeout(typeWriter, speed);
                }
            };

            this[field] = '';
            typeWriter();
        },

        surprise() {
            let prompt = this.samples[Math.floor(Math.random() * this.samples.length)];
            this.$refs.prompt.focus();
            this.typeWrite('prompt', prompt);
        },

        placeholderSurprise() {
            clearTimeout(this.timer);

            if (this.prompt) {
                return;
            }

            this.timer = setTimeout(() => {
                let randomPrompt = this.samples[Math.floor(Math.random() * this.samples.length)];
                this.typeWrite('placeholder', randomPrompt);
            }, 2000);
        },

        tab(e) {
            if (this.prompt != this.placeholder && this.placeholder) {
                e.preventDefault();
                this.prompt = this.placeholder;
            }
        },

        blur() {
            this.placeholder = null;
            clearTimeout(this.timer);
        },

        submit($el) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            this.preview = null;

            let data = {
                ...this.params,
                prompt: this.prompt,
                negative_prompt: this.negativePrompt,
                model: this.adapter.model.key || null,
            };

            let body = new FormData();
            for (let key in data) {
                body.append(key, data[key]);
            }

            for (let image of this.images) {
                body.append('images[]', image);
            }

            api.post(`/ai/images`, body)
                .then(response => response.json())
                .then(image => {
                    this.history.unshift(image);
                    this.select(image);
                    this.prompt = null;
                })
                .catch(error => {
                    let url = new URL(window.location.href);
                    url.pathname = '/app/imagine/';
                    window.history.pushState({}, '', url);
                }).finally(() => {
                    this.isProcessing = false;
                });
        },

        select(image) {
            this.preview = image;
            this.form = false;

            let url = new URL(window.location.href);
            url.pathname = '/app/imagine/' + image.id;
            window.history.pushState({}, '', url);

            this.checkProgress();
        },

        remove(image) {
            this.isDeleting = true;

            api.delete(`/library/images/${image.id}`)
                .then(() => {
                    this.preview = null;
                    this.form = true;

                    window.modal.close();

                    toast.show("Image has been deleted successfully.", 'ti ti-trash');
                    this.isDeleting = false;

                    let url = new URL(window.location.href);
                    url.pathname = '/app/imagine/';
                    window.history.pushState({}, '', url);

                    this.history.splice(this.history.indexOf(image), 1);
                })
                .catch(error => this.isDeleting = false);
        },

        copyImgToClipboard(image) {
            fetch(image.output_file.url)
                .then(res => res.blob())
                .then(blob => {
                    let item = new ClipboardItem({
                        [blob.type]: blob,
                    });

                    return navigator.clipboard.write([item])
                })
                .then(() => {
                    toast.success('Image copied to clipboard!');
                });
        },

        checkProgress() {
            if (this.preview.state >= 3) {
                return;
            }

            api.get(`/library/images/${this.preview.id}`)
                .then(response => response.json())
                .then(image => {
                    this.preview = image;
                    setTimeout(() => this.checkProgress(), 5000);
                });
        },

        save(resource) {
            api.post(`/library/images/${resource.id}`, {
                title: resource.title,
            }).then((resp) => {
                // Update the item in the history list
                this.updateHistory(resp.data);
            });
        },

        addImage($event) {
            const files = Array.from($event.target.files);
            const limit = this.adapter.model.config.images.limit || 1;

            this.images = [
                ...this.images,
                ...files.slice(0, limit - this.images.length)
            ];

            $event.target.value = null;
            window.modal.open('options');
        },

        removeImage(image) {
            this.images = this.images.filter(f => f !== image);
        },

        updateHistory(image) {
            let index = this.history.findIndex(item => item.id === image.id);

            if (index >= 0) {
                this.history[index] = image;
            }
        },

        actionNew() {
            this.prompt = null;
            this.negativePrompt = null;
            this.params = {};
            this.images = [];

            this.form = true;

            let url = new URL(window.location.href);
            url.pathname = '/app/imagine/';
            window.history.pushState({}, '', url);

            this.$nextTick(() => {
                this.$refs.prompt.focus();
            });
        },

        actionEdit() {
            this.prompt = this.preview.params?.prompt || null;
            this.negativePrompt = this.preview.params?.negative_prompt || null;

            let params = { ...this.preview.params };
            delete params.prompt;
            delete params.negative_prompt;

            this.selectModel(this.preview.model);
            this.form = true;

            this.$nextTick(() => {
                this.params = params;
            });

            let url = new URL(window.location.href);
            url.pathname = '/app/imagine/';
            window.history.pushState({}, '', url);

            this.$nextTick(() => {
                this.$refs.prompt.focus();
            });
        },

        // Helper function to safely get nested property values
        getNestedValue(obj, path) {
            if (!obj || !path) return null;

            // Handle optional chaining syntax (?.)
            const cleanPath = path.replace(/\?\./g, '.');
            const keys = cleanPath.split('.');

            let current = obj;
            for (let key of keys) {
                if (current === null || current === undefined) {
                    return null;
                }
                current = current[key];
            }

            return current;
        },

        search(query, object) {
            query = query.trim().toLowerCase();

            if (!query) {
                return true;
            }

            let fields = [];
            fields = ['name', 'model.name', 'model.provider?.name', 'model.description'];

            for (let field of fields) {
                const value = this.getNestedValue(object, field);
                if (value && typeof value === 'string' && value.toLowerCase().includes(query)) {
                    return true;
                }
            }

            return false;
        },

        chooseAdapter(adapter) {
            this.selectModel(adapter.model.key)
            window.modal.close();
        },
    }));
}