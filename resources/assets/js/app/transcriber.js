'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { __ } from '../base/translate';

export function transcriberView() {
    Alpine.data('transcriber', (transcription = null) => ({
        isProcessing: false,
        isDeleting: false,

        history: [],
        historyLoaded: false,

        preview: transcription,
        previewTime: 0,
        file: null,
        viewSegments: true,
        error: null,

        init() {
            this.$watch('preview', (value) => {
                // Update the item in the history list
                if (this.history && value) {
                    let index = this.history.findIndex(item => item.id === value.id);
                    if (index >= 0) {
                        this.history[index] = value;
                    }
                }
            });

            this.fetchHistory();
        },

        fetchHistory() {
            let params = {
                limit: 25
            };

            if (this.history && this.history.length > 0) {
                params.starting_after = this.history[this.history.length - 1].id;
            }

            api.get('/library/transcriptions', params)
                .then(response => response.json())
                .then(list => {
                    let data = list.data;
                    this.history.push(...data);

                    if (data.length < params.limit) {
                        this.historyLoaded = true;
                    }
                });
        },

        /** @param {File} file */
        selectFile(file) {
            let isValid = this.isFileValid(file);
            this.file = isValid ? file : null;
        },

        /** @param {File} file */
        isFileValid(file) {
            const validTypes = [
                'audio/flac',
                'audio/mp3',
                'audio/mpeg',
                'audio/mpga',
                'audio/m4a',
                'audio/ogg',
                'audio/wav',
                'audio/webm',
                'video/mp4',
                'video/webm'
            ];

            if (file.size > 25 * 1024 * 1024) {
                toast.error(__('File size must be less than 25MB.'));
                return false;
            }

            if (!validTypes.includes(file.type)) {
                toast.error('File must be in a supported audio/video format.');
                return false;
            }

            return true;
        },

        /** @param {HTMLFormElement} el */
        submit(el) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api
                .post('/ai/transcriptions', new FormData(el))
                .then((response) => response.json())
                .then((transcription) => {
                    if (this.history === null) {
                        this.history = [];
                    }

                    this.history.push(transcription);
                    this.select(transcription);

                    this.isProcessing = false;
                    el.reset();
                    this.file = null;
                })
                .catch((error) => this.isProcessing = false);
        },

        save(transcription) {
            api.post(`/library/transcriptions/${transcription.id}`, {
                title: transcription.title,
            });
        },

        select(transcription) {
            this.preview = transcription;

            let url = new URL(window.location.href);
            url.pathname = '/app/transcriber/' + transcription.id;
            window.history.pushState({}, '', url);
        },

        remove(transcription) {
            this.isDeleting = true;

            api.delete(`/library/transcriptions/${transcription.id}`)
                .then(() => {
                    this.preview = null;
                    window.modal.close();

                    toast.show("Transcription has been deleted successfully.", 'ti ti-trash');
                    this.isDeleting = false;

                    let url = new URL(window.location.href);
                    url.pathname = '/app/transcriber/';
                    window.history.pushState({}, '', url);

                    this.history.splice(this.history.indexOf(transcription), 1);
                })
                .catch(error => this.isDeleting = false);
        },

        getReadableDuration(duration) {
            let date = new Date(0);
            date.setSeconds(duration);

            if (duration > 3600) {
                return date.toISOString().substring(11, 19)
            }

            return date.toISOString().substring(14, 19)
        },

        download(transcription, format) {
            if (format == 'vtt') {
                mimeType = 'text/vtt';
                ext = 'vtt';
                content = this.toVTT(transcription);
            } else if (format == 'srt') {
                mimeType = 'text/srt';
                ext = 'srt';
                content = this.toSRT(transcription);
            } else {
                var mimeType = 'text/plain';
                var ext = 'txt';
                var content = transcription.content.text;
            }

            this.downloadFromUrl(
                `data:${mimeType};charset=utf-8,${encodeURIComponent(content)}`,
                transcription.title,
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

        toVTT(transcription) {
            let vtt = 'WEBVTT\n\n';


            transcription.content.segments.forEach((segment, index) => {
                let start = new Date(0);
                start.setSeconds(segment.start)
                start = start.toISOString().substring(11, 23);

                let end = new Date(0);
                end.setSeconds(segment.end);
                end = end.toISOString().substring(11, 23);

                vtt += `${index + 1}\n`;
                vtt += `${start} --> ${end}\n`;
                vtt += `${segment.text.trim()}\n\n`;
            });

            return vtt;
        },

        toSRT(transcription) {
            let srt = '';

            transcription.content.segments.forEach((segment, index) => {
                let start = new Date(0);
                start.setSeconds(segment.start);
                start = start.toISOString().substring(11, 23).replace('.', ',');

                let end = new Date(0);
                end.setSeconds(segment.end);
                end = end.toISOString().substring(11, 23).replace('.', ',');

                srt += `${index + 1}\n`;
                srt += `${start} --> ${end}\n`;
                srt += `${segment.text.trim()}\n\n`;
            });

            return srt;
        }
    }));
}