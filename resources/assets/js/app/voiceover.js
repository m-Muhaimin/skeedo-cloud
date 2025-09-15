'use strict';

import Alpine from 'alpinejs';
import api from './api';

export function voiceover() {
    Alpine.data('voiceover', (voice = null, speech = null) => ({
        isProcessing: false,
        isDeleting: false,

        history: [],
        historyLoaded: false,

        preview: speech,
        showSettings: false,
        voice: voice,
        prompt: null,
        query: '',

        voices: null,
        isLoading: false,
        hasMore: true,
        currentResource: null,
        showList: false,

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
            this.getVoices();
        },

        fetchHistory() {
            let params = {
                limit: 25
            };

            if (this.history && this.history.length > 0) {
                params.starting_after = this.history[this.history.length - 1].id;
            }

            api.get('/library/speeches', params)
                .then(response => response.json())
                .then(list => {
                    let data = list.data;
                    this.history.push(...data);

                    if (data.length < params.limit) {
                        this.historyLoaded = true;
                    }
                });
        },

        getVoices(cursor = null, reset = false) {
            if (reset) {
                this.isLoading = false;
                this.hasMore = true;
            }

            if (
                !this.hasMore
                || this.isLoading
            ) {
                return;
            }

            this.isLoading = true;
            let params = {
                limit: 25
            };

            if (cursor) {
                params.starting_after = cursor;
            }

            if (this.query) {
                params.query = this.query;
            }

            api.get('/voices', params)
                .then(response => response.json())
                .then(list => {
                    this.isLoading = false;

                    if (!this.voices) {
                        this.voices = [];
                    }

                    reset ? this.voices = list.data : this.voices.push(...list.data);
                    this.hasMore = list.data.length >= params.limit;
                });
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let data = {
                voice_id: this.voice.id,
                prompt: this.prompt,
            };

            api.post('/ai/speeches', data)
                .then(response => response.json())
                .then((speech) => {
                    if (this.history === null) {
                        this.history = [];
                    }

                    this.history.push(speech);
                    this.preview = speech;
                    this.isProcessing = false;
                    this.prompt = null;

                    this.select(speech);
                })
                .catch(error => {
                    this.isProcessing = false;
                    console.error(error);
                });
        },

        select(speech) {
            this.preview = speech;

            let url = new URL(window.location.href);
            url.pathname = '/app/voiceover/' + speech.id;
            window.history.pushState({}, '', url);

            if (speech.voice) {
                this.voice = speech.voice;
            }
        },

        save(speech) {
            api.post(`/library/speeches/${speech.id}`, {
                title: speech.title,
            });
        },

        remove(transcription) {
            this.isDeleting = true;

            api.delete(`/library/speeches/${transcription.id}`)
                .then(() => {
                    this.preview = null;
                    window.modal.close();

                    toast.show("Speech has been deleted successfully.", 'ti ti-trash');
                    this.isDeleting = false;

                    let url = new URL(window.location.href);
                    url.pathname = '/app/voiceover/' + (this.voice?.id || this.voices[0] || '');
                    window.history.pushState({}, '', url);

                    this.history.splice(this.history.indexOf(transcription), 1);
                })
                .catch(error => this.isDeleting = false);
        },

        selectVoice(voice) {
            this.voice = voice;
            window.modal.close();

            let url = new URL(window.location.href);
            url.pathname = '/app/voiceover/' + voice.id;
            window.history.pushState({}, '', url);
        }
    }));

    Alpine.data('clone', () => ({
        file: null,
        isProcessing: false,
        name: null,
        consent: false,
        isRecording: false,
        mediaRecorder: null,
        recordingTime: '00:00',
        recordingTimer: null,
        error: null,
        audioChunks: [],
        visualizerBars: Array.from({ length: 10 }, (_, i) => ({
            id: i,
            active: false,
            height: 20 // default height
        })),
        audioContext: null,
        analyser: null,
        dataArray: null,
        animationFrame: null,

        init() {
            // Pre-initialize audio context when component loads
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        },

        startRecording() {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    this.error = null;
                    this.isRecording = true;

                    // Use WebM format which is widely supported
                    this.mediaRecorder = new MediaRecorder(stream, {
                        mimeType: 'audio/webm;codecs=opus',
                        audioBitsPerSecond: 128000
                    });

                    this.audioChunks = [];

                    // Resume the pre-initialized audio context if suspended
                    if (this.audioContext.state === 'suspended') {
                        this.audioContext.resume();
                    }

                    const source = this.audioContext.createMediaStreamSource(stream);
                    this.analyser = this.audioContext.createAnalyser();
                    this.analyser.fftSize = 32;
                    source.connect(this.analyser);

                    this.dataArray = new Uint8Array(this.analyser.frequencyBinCount);
                    this.updateVisualizer();

                    this.mediaRecorder.ondataavailable = (event) => {
                        this.audioChunks.push(event.data);
                    };

                    this.mediaRecorder.onstop = async () => {
                        const webmBlob = new Blob(this.audioChunks, { type: 'audio/webm' });

                        // Convert WebM to WAV for upload
                        try {
                            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                            const arrayBuffer = await webmBlob.arrayBuffer();
                            const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);

                            // Create WAV file
                            const wavBlob = await this.audioBufferToWav(audioBuffer);

                            // Create file with specific name pattern
                            this.selectFile(new File([wavBlob], 'voice_sample.wav', {
                                type: 'audio/wav'
                            }));
                        } catch (error) {
                            console.error('Error converting to WAV:', error);
                            this.error = 'conversion_error';
                        }

                        this.isRecording = false;
                        clearInterval(this.recordingTimer);
                        this.recordingTime = '00:00';
                    };

                    this.mediaRecorder.start();

                    let seconds = 0;
                    this.recordingTimer = setInterval(() => {
                        seconds++;
                        const minutes = Math.floor(seconds / 60);
                        const remainingSeconds = seconds % 60;
                        this.recordingTime = `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;

                        // Stop recording after 30 seconds
                        if (seconds >= 30) {
                            this.stopRecording();
                        }
                    }, 1000);
                })
                .catch(error => {
                    console.error('Error accessing microphone:', error);
                    this.error = "microphone_error";
                });
        },

        updateVisualizer() {
            if (!this.isRecording) return;

            this.analyser.getByteFrequencyData(this.dataArray);

            // Get average volume level
            const average = Array.from(this.dataArray).slice(0, 10).reduce((a, b) => a + b, 0) / 10;
            const normalizedValue = average / 255;

            // Calculate how many bars should be active based on volume
            const activeBars = Math.floor(normalizedValue * this.visualizerBars.length);

            // Update bars active state and heights
            this.visualizerBars.forEach((bar, index) => {
                bar.active = index < activeBars;

                // Create wave-like pattern
                let baseHeight = 20; // minimum height
                let maxVariation = 12; // maximum additional height

                // Create a wave pattern using sine function
                let waveHeight = Math.sin((Date.now() / 200) + (index * 0.8)) * maxVariation;

                // If bar is active, add the wave height
                if (bar.active) {
                    bar.height = baseHeight + Math.abs(waveHeight);
                } else {
                    // Inactive bars maintain minimum height
                    bar.height = baseHeight;
                }
            });

            this.animationFrame = requestAnimationFrame(() => this.updateVisualizer());
        },

        stopRecording() {
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
                this.mediaRecorder.stream.getTracks().forEach(track => track.stop());

                // Only clean up visualization related resources
                if (this.animationFrame) {
                    cancelAnimationFrame(this.animationFrame);
                }

                // Reset visualizer bars
                this.visualizerBars = this.visualizerBars.map(bar => ({
                    ...bar,
                    active: false,
                    height: 20 // reset to default height
                }));

                // Clean up analyzer but keep audioContext
                if (this.analyser) {
                    this.analyser.disconnect();
                    this.analyser = null;
                }
            }
        },

        selectFile(file) {
            // Check file size (5MB limit)
            const maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if (file.size > maxSize) {
                this.error = "file_size";
                return;
            }

            // Check if file type starts with audio/ or video/
            if (!file.type.startsWith('audio/') && !file.type.startsWith('video/')) {
                this.error = "file_type";
                return;
            }

            this.error = null;
            this.file = file;
        },

        clone() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            let data = new FormData();
            data.append('name', this.name);
            data.append('consent', this.consent ? 1 : 0);
            data.append('file', this.file);

            api.post('/voices', data)
                .then(response => response.json())
                .then((voice) => {
                    this.file = null;
                    this.name = null;
                    this.consent = false;
                    this.isRecording = false;
                    this.audioContext.close();

                    this.resources.unshift(voice);
                    toast.success("Voice has been cloned successfully.");
                })
                .finally(() => {
                    this.isProcessing = false;
                    window.modal.close();
                });
        },

        audioBufferToWav(buffer) {
            const numberOfChannels = buffer.numberOfChannels;
            const sampleRate = buffer.sampleRate;
            const length = buffer.length * numberOfChannels * 2;
            const arrayBuffer = new ArrayBuffer(44 + length);
            const view = new DataView(arrayBuffer);

            // Write WAV header
            const writeString = (view, offset, string) => {
                for (let i = 0; i < string.length; i++) {
                    view.setUint8(offset + i, string.charCodeAt(i));
                }
            };

            writeString(view, 0, 'RIFF');                     // RIFF identifier
            view.setUint32(4, 36 + length, true);            // File length
            writeString(view, 8, 'WAVE');                     // WAVE identifier
            writeString(view, 12, 'fmt ');                    // fmt chunk
            view.setUint32(16, 16, true);                    // Length of fmt chunk
            view.setUint16(20, 1, true);                     // Format type (1 = PCM)
            view.setUint16(22, numberOfChannels, true);      // Number of channels
            view.setUint32(24, sampleRate, true);            // Sample rate
            view.setUint32(28, sampleRate * 2, true);        // Byte rate
            view.setUint16(32, numberOfChannels * 2, true);  // Block align
            view.setUint16(34, 16, true);                    // Bits per sample
            writeString(view, 36, 'data');                   // data chunk
            view.setUint32(40, length, true);                // Data length

            // Write audio data
            const channelData = [];
            for (let channel = 0; channel < numberOfChannels; channel++) {
                channelData[channel] = buffer.getChannelData(channel);
            }

            let position = 44;  // Starting position after WAV header
            for (let i = 0; i < buffer.length; i++) {
                for (let channel = 0; channel < numberOfChannels; channel++) {
                    const sample = Math.max(-1, Math.min(1, channelData[channel][i]));
                    view.setInt16(position, sample < 0 ? sample * 0x8000 : sample * 0x7FFF, true);
                    position += 2;
                }
            }

            return new Blob([view], { type: 'audio/wav' });
        }
    }));

    Alpine.data('voice', (voice) => ({
        voice: null,
        isProcessing: null,

        init() {
            this.voice = { ...voice };
        },

        edit() {
            this.isProcessing = true;

            api.post(`/voices/${this.voice.id}`, {
                name: this.voice.name,
                visibility: this.voice.visibility,
            })
                .then(resp => resp.json())
                .then(data => {
                    this.voice = { ...data };
                    Object.assign(voice, data);
                    window.modal.close();
                })
                .finally(() => {
                    this.isProcessing = false;
                });
        },
    }));

    Alpine.data('audience', (item) => ({
        item: item,
        isProcessing: null,

        changeAudience(visibility) {
            this.isProcessing = visibility;

            api.post(`/library/${this.item.id}`, { visibility: visibility })
                .then(resp => resp.json())
                .then(resp => {
                    window.modal.close();

                    this.isProcessing = null;
                    this.item = resp;
                });
        }
    }));
}