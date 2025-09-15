'use strict';

import Alpine from 'alpinejs';
import { pins } from '../base/pin';

export function modelsView() {
    Alpine.data('models', (
        services = []
    ) => ({
        adapters: [],
        adapter: null,
        query: '',

        init() {
            services.forEach(service => {
                service.models.forEach(model => {
                    let adapter = this.buildAdapter(service, model);
                    this.adapters.push(adapter)
                });
            });

            this.sortAdapters();

            // Parse query parameters and find the parameter 'q'
            let url = new URL(window.location.href);
            let model = url.searchParams.get('model');

            if (model) {
                let adapter = this.findAdapter(model);

                if (adapter) {
                    this.chooseAdapter(adapter);
                }
            }
        },

        chooseAdapter(adapter) {
            this.adapter = adapter;
            window.modal.open('model');
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

        buildAdapter(service, model) {
            let adapter = { ...service };
            adapter.model = model;
            delete adapter.models;
            adapter.pinned = pins.isPinned(model.key, 'model');
            adapter.useLink = this.getUseLink(adapter);

            return adapter;
        },

        togglePin(object) {
            pins.toggle(object.model.key, 'model');
            object.pinned = !object.pinned;
            this.sortAdapters();
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

            let fields = ['name', 'model.name', 'model.provider?.name', 'model.description'];

            for (let field of fields) {
                const value = this.getNestedValue(object, field);
                if (value && typeof value === 'string' && value.toLowerCase().includes(query)) {
                    return true;
                }
            }

            return false;
        },

        getUseLink(adapter) {
            if (adapter.model.type == 'llm') {
                return `app/chat?model=${adapter.model.key}`;
            }

            if (adapter.model.type == 'image') {
                return `app/imagine?model=${adapter.model.key}`;
            }

            if (adapter.model.type == 'transcription') {
                return `app/transcriber?model=${adapter.model.key}`;
            }

            if (adapter.model.type == 'video') {
                return `app/video?model=${adapter.model.key}`;
            }

            if (adapter.model.type == 'voice-isolation') {
                return `app/voice-isolator?model=${adapter.model.key}`;
            }

            return null;
        }
    }));
}