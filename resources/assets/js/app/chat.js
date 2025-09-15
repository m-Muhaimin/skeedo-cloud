'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { EventSourceParserStream } from 'eventsource-parser/stream';
import { pins } from '../base/pin';

export function chat() {
    Alpine.data('chat', (
        model,
        services = [],
        assistant = null,
        conversation = null,
        types = []
    ) => ({
        // services: services,
        adapters: [],
        adapter: null,

        conversation: null,
        assistant: assistant,

        history: [],
        historyLoaded: false,

        assistants: null,

        tree: [],
        map: null,

        file: null,
        prompt: null,
        isProcessing: false,
        parent: null,
        quote: null,
        contentElement: null,
        isDeleting: false,
        query: '',

        options: null,

        init() {
            services.forEach(service => {
                service.models.forEach(model => {
                    let adapter = this.buildAdapter(service, model);
                    this.adapters.push(adapter)
                });
            });

            this.sortAdapters();

            this.contentElement = document.getElementById('content');
            this.selectModel(this.findModel());

            if (conversation) {
                this.select(conversation);
                setTimeout(() => this.scrollToBottom(), 500);
            }

            this.fetchHistory();
            this.getAssistants();

            window.addEventListener('mouseup', (e) => {
                this.$refs.quote.classList.add('hidden');
                this.$refs.quote.classList.remove('flex');
            });

            // Parse query parameters and find the parameter 'q'
            let url = new URL(window.location.href);
            let query = url.searchParams.get('q');

            if (query) {
                this.prompt = query;
                this.submit();
            }
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

            return this.assistant?.model || model;
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

        selectModel(key) {
            const adapter = this.findAdapter(key);
            if (adapter) {
                this.adapter = adapter
            }
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

        togglePin(object, type = 'model') {
            if (type == 'model') {
                pins.toggle(object.model.key, type);
                object.pinned = !object.pinned;
                this.sortAdapters();
            } else if (type == 'assistant') {
                pins.toggle(object.id, type);
                object.pinned = !object.pinned;
                this.sortAssistants();
            }
        },

        select(conversation) {
            this.conversation = conversation;

            this.map = new Map();
            this.conversation.messages.forEach(message => {
                this.map.set(message.id, message);
            });

            this.generateTree();

            let url = new URL(window.location.href);
            url.pathname = '/app/chat/' + conversation.id;
            url.search = '';
            window.history.pushState({}, '', url);

            // Find the first message in the last tree node
            if (this.tree.length === 0) {
                return;
            }

            let lastNode = this.tree[this.tree.length - 1];
            let lastMessage = lastNode.children[lastNode.index];

            // Set the assistant for the conversation based on the last message
            this.selectAssistant(lastMessage.assistant);
            this.selectModel(lastMessage.model);
        },

        generateTree(msgId = null) {
            if (msgId && !this.map.has(msgId)) {
                return;
            }

            this.tree.splice(0);
            let parentId = null;

            while (true) {
                let node = {
                    index: 0,
                    children: []
                }

                this.map.forEach(message => {
                    if (parentId === message.parent_id) {
                        node.children.push(message);
                    }
                });

                let ids = node.children.map(msg => msg.id);

                if (node.children.length > 0) {
                    if (msgId) {
                        let msg = this.map.get(msgId);

                        // Update indices to ensure the selected message is visible
                        while (msg) {
                            if (ids.indexOf(msg.id) >= 0) {
                                node.index = ids.indexOf(msg.id);
                                break;
                            }

                            if (msg.parent_id) {
                                msg = this.map.get(msg.parent_id);

                                continue;
                            }

                            break;
                        }
                    }

                    this.tree.push(node);
                    parentId = node.children[node.index].id;
                    continue;
                }

                break;
            }
        },

        findMessage(id) {
            if (this.map.has(id)) {
                return this.map.get(id);
            }

            if (!this.history) {
                return null;
            }

            for (const conversation of this.history) {
                let message = conversation.messages.find(m => m.id === id);

                if (message) {
                    return message;
                }
            }

            return null;
        },

        addMessage(msg) {
            let conversation = this.findConversation(msg.conversation.id || msg.conversation);

            if (!conversation) {
                return;
            }

            let regen = this.isMessageVisible(msg.id);

            if (msg.conversation.id) {
                for (const [key, value] of Object.entries(msg.conversation)) {
                    if (key === 'messages') {
                        continue;
                    }
                }

                conversation.title = msg.conversation.title;
                conversation.cost = msg.conversation.cost;
            }

            if (!conversation.messages.find(m => m.id === msg.id)) {
                conversation.messages.push(msg);
                if (conversation.id == this.conversation.id) {
                    regen = true;
                }


            } else {
                let index = conversation.messages.findIndex(m => m.id === msg.id);
                msg.reasoning = conversation.messages[index].reasoning;
                conversation.messages[index] = msg;
            }

            if (!this.conversation || conversation.id != this.conversation.id) {
                return;
            }

            this.map.set(msg.id, msg);

            if (regen) {
                this.generateTree(msg.id);
            }
        },

        findConversation(id) {
            if (this.conversation && this.conversation.id === id) {
                return this.conversation;
            }

            if (this.history) {
                return this.history.find(conversation => conversation.id === id);
            }

            return null;
        },

        markAsSilent() {
            // Mark processing messages as silent in both current conversation and history
            if (this.history) {
                this.history.forEach(conversation => {
                    conversation.messages.forEach(message => {
                        if (message.isProcessing) message.silent = true;
                    });
                });
            }

            if (this.conversation?.messages) {
                this.conversation.messages.forEach(message => {
                    if (message.isProcessing) message.silent = true;
                });
            }
        },

        isMessageVisible(messageId) {
            return this.tree.some(node => {
                // Check if the message at current node's index matches the messageId
                return node.children[node.index]?.id === messageId;
            });
        },

        fetchHistory() {
            let params = {
                limit: 25
            };

            if (this.history && this.history.length > 0) {
                params.starting_after = this.history[this.history.length - 1].id;
            }

            api.get('/library/conversations', params)
                .then(response => response.json())
                .then(list => {
                    let data = list.data;
                    this.history.push(...data);

                    if (data.length < params.limit) {
                        this.historyLoaded = true;
                    }
                });
        },

        getAssistants(cursor = null) {
            let params = {
                limit: 250,
                all: true
            };

            if (cursor) {
                params.starting_after = cursor;
            }

            api.get('/assistants', params)
                .then(response => response.json())
                .then(list => {
                    if (!this.assistants) {
                        this.assistants = [];
                    }

                    let data = list.data;
                    data.forEach(a => {
                        a.granted = !(
                            this.$store.workspace.subscription?.plan.config.assistants != null
                            && !this.$store.workspace.subscription?.plan.config.assistants.includes(a.id)
                        );

                        a.pinned = pins.isPinned(a.id, 'assistant');
                    });

                    this.assistants.push(...data);
                    this.sortAssistants();

                    if (list.data.length > 0 && list.data.length == params.limit) {
                        this.getAssistants(this.assistants[this.assistants.length - 1].id);
                    }
                });
        },

        sortAssistants() {
            // Sort assistants by granted first, then by pinned status and order (same as models)
            this.assistants.sort((a, b) => {
                const grantedA = a.granted || false;
                const grantedB = b.granted || false;

                // First sort by granted status (granted assistants first)
                if (grantedA !== grantedB) {
                    return grantedB - grantedA;
                }

                // If granted status is the same, sort by pinned status
                const pinnedA = a.pinned || false;
                const pinnedB = b.pinned || false;

                if (pinnedA !== pinnedB) {
                    return pinnedB - pinnedA;
                }

                // If both are pinned, sort by pin order (most recently pinned first)
                if (pinnedA && pinnedB) {
                    const orderA = pins.getPinOrder(a.id, 'assistant');
                    const orderB = pins.getPinOrder(b.id, 'assistant');
                    return orderA - orderB;
                }

                return 0;
            });
        },

        stopProcessing() {
            this.isProcessing = false;
        },

        async submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            if (!this.conversation) {
                try {
                    await this.createConversation();
                } catch (error) {
                    this.stopProcessing();
                    return;
                }
            }

            let data = new FormData();
            data.append('content', this.prompt);
            data.append('model', this.adapter.model.key);

            if (this.assistant?.id) {
                data.append('assistant_id', this.assistant.id);
            }

            if (this.quote) {
                data.append('quote', this.quote);
            }

            let msgs = document.getElementsByClassName('message');
            if (msgs.length > 0) {
                let pid = msgs[msgs.length - 1].dataset.id;

                if (pid) {
                    data.append('parent_id', pid);
                }
            }

            if (this.file) {
                data.append('file', this.file);
            }

            this.ask(data);
        },

        async ask(data) {
            try {
                let response = await api.post('/ai/conversations/' + this.conversation.id + '/messages', data);

                // Get the readable stream from the response body
                const stream = response.body
                    .pipeThrough(new TextDecoderStream())
                    .pipeThrough(new EventSourceParserStream());

                // Get the reader from the stream
                const reader = stream.getReader();

                this.file = null;
                let msg;

                while (true) {
                    if (this.isProcessing) {
                        this.quote = null;
                        this.prompt = null;
                        this.isProcessing = false;
                        this.markAsSilent();
                    }

                    const { value, done } = await reader.read();
                    if (done) {
                        this.stopProcessing();

                        if (msg) {
                            msg.call = null;
                            msg.isProcessing = false;

                            if (this.isMessageVisible(msg.id)) {
                                this.generateTree(msg.id);
                            }
                        }

                        break;
                    }

                    if (value.event == 'token' || value.event == 'reasoning-token') {
                        let chunk = JSON.parse(value.data);
                        msg = this.findMessage(chunk.attributes.message_id);

                        if (msg) {
                            const now = Date.now();
                            if (value.event == 'reasoning-token') {
                                if (msg.silent) {
                                    msg.pendingReasoning = (msg.pendingReasoning || '') + chunk.data;
                                } else {
                                    msg.reasoning += (msg.pendingReasoning || '') + chunk.data;
                                }

                                msg.call = {
                                    name: 'reasoning'
                                };
                            } else {
                                if (msg.silent) {
                                    msg.pendingContent = (msg.pendingContent || '') + chunk.data;
                                } else {
                                    msg.content += (msg.pendingContent || '') + chunk.data;
                                }

                                msg.call = null;
                            }

                            msg.isProcessing = true;
                        }

                        /**
                         * Ensure DOM synchronization before continuing the 
                         * stream processing. Alpine.js performs reactive 
                         * updates asynchronously, which can cause timing issues 
                         * when the conversation tree structure changes 
                         * dynamically. $nextTick() waits for Alpine's internal
                         * update queue to complete, guaranteeing that all
                         * reactive bindings and DOM manipulations are finished
                         * before processing the next stream chunk.
                         */
                        await this.$nextTick();

                        continue;
                    }

                    if (msg && value.event == 'call') {
                        let chunk = JSON.parse(value.data);
                        msg = this.findMessage(chunk.attributes.message_id);

                        if (msg) {
                            msg.call = chunk.data;

                            if (this.isMessageVisible(msg.id)) {
                                this.generateTree(msg.id);
                            }
                        }

                        continue;
                    }

                    if (value.event == 'message') {
                        msg = JSON.parse(value.data);

                        if (!msg.hasOwnProperty('reasoning')) {
                            msg.reasoning = '';
                        }

                        if (!msg.content) {
                            msg.isProcessing = true;
                        }

                        this.addMessage(msg);
                        this.scrollToBottom();

                        continue;
                    }

                    if (value.event == 'error') {
                        this.error(value.data);
                        break;
                    }
                }
            } catch (error) {
                this.error(error);
            }
        },

        scrollToBottom() {
            if (!this.contentElement) return;

            this.$nextTick(() => {
                this.contentElement.scrollTo({
                    top: this.contentElement.scrollHeight,
                    behavior: 'smooth'
                });
            });

        },

        error(msg) {
            this.stopProcessing();
            toast.error(msg);
            console.error(msg);
            this.generateTree();
        },

        async createConversation() {
            let resp = await api.post('/ai/conversations');
            let conversation = resp.data;

            if (this.history === null) {
                this.history = [];
            }

            this.history.unshift(conversation);
            this.select(conversation);
        },

        save(conversation) {
            api.post(`/library/conversations/${conversation.id}`, {
                title: conversation.title,
            }).then((resp) => {
                // Update the item in the history list
                if (this.history) {
                    let index = this.history.findIndex(item => item.id === resp.data.id);

                    if (index >= 0) {
                        this.history[index] = resp.data;
                    }
                }
            });
        },

        enter(e) {
            if (e.key === 'Enter' && !e.shiftKey && !this.isProcessing && this.prompt && this.prompt.trim() !== '') {
                e.preventDefault();
                this.submit();
            }
        },

        paste(e) {
            if (!this.adapter || this.adapter.file_types.length === 0) {
                return; // Allow default paste behavior if no file types are supported
            }

            const items = e.clipboardData.items;
            for (let i = 0; i < items.length; i++) {
                if (items[i].kind === 'file') {
                    const file = items[i].getAsFile();
                    if (file && this.adapter.file_types.includes(file.type)) {
                        this.file = file;
                        e.preventDefault(); // Prevent default paste only if we've found a supported file
                        break;
                    }
                }
            }
            // If no supported file is found, allow default paste behavior
        },

        copy(message) {
            navigator.clipboard.writeText(message.content)
                .then(() => {
                    toast.success('Copied to clipboard!');
                });
        },

        textSelect(e) {
            this.$refs.quote.classList.add('hidden');
            this.$refs.quote.classList.remove('flex');

            let selection = window.getSelection();

            if (selection.rangeCount <= 0) {
                return;
            }

            let range = selection.getRangeAt(0);
            let text = range.toString();

            if (text.trim() == '') {
                return;
            }

            e.stopPropagation();

            let startNode = range.startContainer;
            let startOffset = range.startOffset;

            let rect;
            if (startNode.nodeType === Node.TEXT_NODE) {
                // Create a temporary range to get the exact position of the start
                let tempRange = document.createRange();
                tempRange.setStart(startNode, startOffset);
                tempRange.setEnd(startNode, startOffset + 1); // Add one character to make the range visible
                rect = tempRange.getBoundingClientRect();
            } else if (startNode.nodeType === Node.ELEMENT_NODE) {
                // For element nodes, get the bounding rect directly
                rect = startNode.getBoundingClientRect();
            }

            // Adjust coordinates relative to the container (parent)
            let container = this.$refs.quote.parentElement;
            let containerRect = container.getBoundingClientRect();
            let x = rect.left - containerRect.left + container.scrollLeft;
            let y = rect.top - containerRect.top + container.scrollTop;

            this.$refs.quote.style.top = y + 'px';
            this.$refs.quote.style.left = x + 'px';

            this.$refs.quote.classList.add('flex');
            this.$refs.quote.classList.remove('hidden');

            this.$refs.quote.dataset.value = range.toString();

            return;

        },

        selectQuote() {
            this.quote = this.$refs.quote.dataset.value;
            this.$refs.quote.dataset.value = null;

            this.$refs.quote.classList.add('hidden');
            this.$refs.quote.classList.remove('flex');

            // Clear selection
            window.getSelection().removeAllRanges();
        },

        regenerate(message, model = null, assistant = null) {
            if (!message.parent_id) {
                return;
            }

            let parentMessage = this.conversation.messages.find(
                msg => msg.id === message.parent_id
            );

            if (!parentMessage) {
                return;
            }

            let data = new FormData();
            data.append('parent_id', parentMessage.id);
            data.append('model', model || message.model);

            if (assistant) {
                data.append('assistant_id', assistant.id);
            }

            // else if (message.assistant) {
            //     data.append('assistant_id', message.assistant.id);
            // }

            this.isProcessing = true;
            this.ask(data);
        },

        edit(message, content) {
            let data = new FormData();

            data.append('model', message.model);
            data.append('content', content);

            if (message.parent_id) {
                data.append('parent_id', message.parent_id);
            }

            if (message.assistant?.id) {
                data.append('assistant_id', message.assistant.id);
            }

            if (message.quote) {
                data.append('quote', message.quote);
            }

            this.isProcessing = true;
            this.ask(data);
        },

        remove(conversation) {
            this.isDeleting = true;

            api.delete(`/library/conversations/${conversation.id}`)
                .then(() => {
                    this.conversation = false;
                    window.modal.close();

                    toast.show("Conversation has been deleted successfully.", 'ti ti-trash');
                    this.isDeleting = false;

                    let url = new URL(window.location.href);
                    url.pathname = '/app/chat/';
                    window.history.pushState({}, '', url);

                    this.history.splice(this.history.indexOf(conversation), 1);

                    setTimeout(() => this.conversation = null, 100);
                })
                .catch(error => this.isDeleting = false);
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

        search(query, object, type) {
            query = query.trim().toLowerCase();

            if (!query) {
                return true;
            }

            let fields = [];

            if (type == 'assistant') {
                fields = ['name', 'expertise', 'description'];
            } else if (type == 'adapter') {
                fields = ['name', 'model.name', 'model.provider?.name', 'model.description'];
            }

            for (let field of fields) {
                const value = this.getNestedValue(object, field);
                if (value && typeof value === 'string' && value.toLowerCase().includes(query)) {
                    return true;
                }
            }

            return false;
        },

        selectAssistant(assistant) {
            this.assistant = assistant;
            window.modal.close();

            if (!this.conversation) {
                let url = new URL(window.location.href);
                url.pathname = '/app/chat/' + (assistant?.id || '');
                window.history.pushState({}, '', url);
            }
        },

        toolKey(item) {
            switch (item.object) {
                case 'image':
                    return 'imagine';

                default:
                    return null;
            }
        },

        chooseAdapter(adapter) {
            if (this.options) {
                this.options.adapter = adapter;

                if (this.assistants?.length > 0) {
                    window.modal.open('options');
                } else {
                    this.applyOptions();
                }
            } else {
                this.selectModel(adapter.model.key)
                window.modal.close();
            }
        },

        chooseAssistant(asssitant) {
            if (this.options) {
                this.options.assistant = asssitant;
                this.options.model = asssitant?.model || this.options.model;
                window.modal.open('options');
            } else {
                this.selectAssistant(asssitant)
                window.modal.close();
            }
        },

        showOptions(message) {
            let modal = 'options';
            if (message) {
                let adapter = this.findAdapter(message.model);

                this.options = {
                    assistant: message.assistant,
                    message: message,
                    adapter: adapter,
                };


            } else {
                this.options = null;
                modal = 'assistant-list-modal';
            }

            window.modal.open(this.assistants?.length > 0 ? modal : 'models');
        },

        applyOptions() {
            this.regenerate(
                this.options.message,
                this.options.adapter.model.key,
                this.options.assistant
            );

            this.options = null;
            window.modal.close();
        },
    }));
}