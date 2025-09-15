'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function workspace() {
    Alpine.data('workspace', (workspace = null, user = null) => ({
        workspace: workspace,
        user: user,
        isSwithcing: null,
        isProcessing: false,
        currentResource: null,

        voices: [],
        voiceCount: 0,

        init() {
        },

        switchWorkspace(id) {
            if (this.isSwithcing) {
                return;
            }

            this.isSwithcing = id;

            api.post('/account/workspace', { id: id }).then(() => {
                toast.defer('Workspace switched!');
                window.location.reload();
            });
        },

        rename(id, name) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.patch(`/workspaces/${id}`, { name: name })
                .then(response => response.json())
                .then(ws => {
                    this.$store.workspace = ws;
                    this.isProcessing = false;

                    toast.success('Workspace name updated!');

                    window.modal.close();
                }).catch(error => this.isProcessing = false);
        },

        saveKeys(id) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.patch(`/workspaces/${id}`, {
                openai_api_key: this.$store.workspace.openai_api_key,
                anthropic_api_key: this.$store.workspace.anthropic_api_key,
            })
                .then(response => {
                    this.$store.workspace = response.data;
                    this.isProcessing = false;

                    toast.success('Changes saved successfully!');
                });
        },

        create(name) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.post(`/workspaces`, { name: name })
                .then(() => {
                    toast.defer('Workspace created!');
                    window.location = '/app/workspace'
                }).catch(error => this.isProcessing = false);
        },

        removeMember(wsid, uid) {
            this.isProcessing = true;

            api.delete(`/workspaces/${wsid}/users/${uid}`)
                .then(response => {
                    this.isProcessing = false;

                    response.json()
                        .then(ws => {
                            this.$store.workspace = ws;
                            toast.success('Member removed!');
                        })
                        .catch(error => {
                            toast.show(
                                'Leaving workspace...',
                                'ti ti-progress'
                            );

                            setTimeout(() => window.location.reload(), 2000);
                        });
                }).catch(error => this.isProcessing = false);
        },

        inviteMember(wsid, email) {
            this.isProcessing = true;

            api.post(`/workspaces/${wsid}/invitations`, { email: email })
                .then(response => response.json())
                .then(ws => {
                    this.isProcessing = false;
                    this.$store.workspace = ws;

                    toast.success('Invitation sent!');

                    window.modal.close();
                }).catch(error => this.isProcessing = false);
        },

        deleteInvitation(wsid, uid) {
            this.isProcessing = true;

            api.delete(`/workspaces/${wsid}/invitations/${uid}`)
                .then(response => response.json())
                .then(ws => {
                    this.isProcessing = false;
                    this.$store.workspace = ws;

                    toast.success('Invitation removed!');
                }).catch(error => this.isProcessing = false);
        },

        deleteWorkspace(wsid) {
            this.isProcessing = true;

            api.delete(`/workspaces/${wsid}`)
                .then(() => {
                    toast.defer('Workspace deleted!', 'ti ti-trash');
                    window.location.reload();
                }).catch(error => this.isProcessing = false);
        },

        transferOwnership(wsid, uid) {
            this.isProcessing = true;

            api.patch(`/workspaces/${wsid}`, { owner_id: uid })
                .then(response => response.json())
                .then(ws => {
                    this.isProcessing = false;
                    this.$store.workspace = ws;

                    toast.success('Workspace ownership transferred!');
                    window.modal.close();
                }).catch(error => this.isProcessing = false);
        },

        fetchClonedVoices() {
            api.get(`/voices/count?owner=workspace`)
                .then(response => this.voiceCount = response.data.count);

            api.get(`/voices?owner=workspace&limit=3`)
                .then(response => this.voices = response.data.data);
        }
    }));
}
