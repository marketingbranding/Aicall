import { GeminiLiveClient } from './gemini-live-client';
import { AiPcmPlaybackQueue } from './ai-pcm-playback-queue';
import { MicrophoneCapture } from './microphone-capture';
import { TranscriptEventBuffer } from './transcript-event-buffer';

class RoleplayRuntime {
    constructor(root) {
        this.root = root;
        this.sessionId = root.dataset.sessionId || '';
        this.credentialsUrl = root.dataset.credentialsUrl;
        this.transcriptUrl = root.dataset.transcriptUrl || root.dataset.credentialsUrl?.replace('/live-credentials', '/transcript') || '';
        this.statusUrl = this.credentialsUrl.replace('/live-credentials', '/status');
        this._reportedStatus = null;
        this.state = 'idle';
        this.ephemeralToken = null;
        this.credentials = null;
        this.liveClient = null;
        this.microphoneCapture = null;
        this.playbackQueue = null;
        this.audioStreaming = false;
        this.conversationState = 'idle';
        this.transcriptEvents = [];
        this.goAwayContext = null;
        this.pendingToolCalls = [];
        this.firstSpeaker = 'USER';
        this.aiOpeningTriggered = false;
        this.reconnectToken = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 2;
        this._reconnectPending = false;
        this.userSpeechTimer = null;
        this.waitingForAiTimer = null;
        this.interruptionResetTimer = null;
        this.sessionDurationMs = (parseInt(root.dataset.sessionDurationSeconds, 10) || 900) * 1000;
        this.sessionWarningMs = this.sessionDurationMs - 60000;
        this.sessionTimer = null;
        this.sessionWarningTimer = null;
        this.sessionTimerDisplay = null;
        this.sessionTimedOut = false;
        this.sessionStartedAt = null;
        this.sessionEndTime = null;
        this.sessionWarningShown = false;
        this.aiWarningSent = false;
        this.liveDebug = root.dataset.liveDebug === 'true';
        this.transcriptBuffer = null;

        this.status = root.querySelector('[data-runtime-status]');
        this.detail = root.querySelector('[data-runtime-detail]');
        this.conversationStatus = root.querySelector('[data-conversation-status]');
        this.conversationDetail = root.querySelector('[data-conversation-detail]');
        this.conversationIndicators = root.querySelectorAll('[data-conversation-indicator]');
        this.transcriptPanel = root.querySelector('[data-live-transcript-panel]');
        this.transcriptList = root.querySelector('[data-live-transcript-list]');
        this.startButton = root.querySelector('[data-roleplay-start]');
        this.stopButton = root.querySelector('[data-roleplay-stop]');
        this.timerDisplay = root.querySelector('[data-session-timer]');
        this.warningBanner = root.querySelector('[data-session-warning-banner]');
        this.reconnectIndicator = root.querySelector('[data-live-reconnect-indicator]');
    }

    _getStorageKey() {
        return `roleplay_reconnect_${this.sessionId}`;
    }

    _saveReconnectToken(token) {
        if (!this.sessionId || !token) return;
        try {
            sessionStorage.setItem(this._getStorageKey(), token);
        } catch (_) {
        }
    }

    _loadReconnectToken() {
        if (!this.sessionId) return null;
        try {
            const token = sessionStorage.getItem(this._getStorageKey());
            if (token) {
                this.reconnectToken = token;
            }
            return token;
        } catch (_) {
            return null;
        }
    }

    _clearReconnectToken() {
        if (!this.sessionId) return;
        try {
            sessionStorage.removeItem(this._getStorageKey());
        } catch (_) {
        }
        this.reconnectToken = null;
    }

    _clearStaleReconnectTokens() {
        if (!this.sessionId) return;
        try {
            const prefix = 'roleplay_reconnect_';
            for (let i = sessionStorage.length - 1; i >= 0; i--) {
                const key = sessionStorage.key(i);
                if (key && key.startsWith(prefix) && key !== this._getStorageKey()) {
                    sessionStorage.removeItem(key);
                }
            }
        } catch (_) {
        }
    }

    init() {
        this.setState('idle');
        this._clearStaleReconnectTokens();
        this._loadReconnectToken();
        this.initTranscriptDebugPanel();
        this.initTranscriptBuffer();
        this.startButton?.addEventListener('click', () => {
            this.ensurePlaybackQueue()?.prime();
            this.requestCredentials();
        });
        this.stopButton?.addEventListener('click', () => {
            this.reportStatus('ENDING', 'USER_END');
            this.stopSessionAudio('audio_stream_stopped');
        });

        document.addEventListener('roleplay:microphone-allowed', () => {
            this.startButton?.classList.remove('hidden');
        });

        window.addEventListener('beforeunload', () => this.stopSessionAudio('audio_stream_stopped'));
        window.addEventListener('pagehide', () => this.stopSessionAudio('audio_stream_stopped'));

        this.root.dataset.sessionWarning = 'false';
        this.updateTimerDisplay();
    }

    async requestCredentials() {
        if (!this.credentialsUrl || this.state === 'requesting_credentials') return;

        this.setState('requesting_credentials');
        this.reportStatus('PREPARING');

        try {
            const response = await fetch(this.credentialsUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                if (response.status === 409 && this.reconnectToken) {
                    this._clearReconnectToken();
                    this.setState('credentials_failed', 'Sesi sedang dalam status tersambung ulang. Mulai sesi baru.');
                    return;
                }
                this.handleCredentialError(response.status);
                return;
            }

            const data = await response.json();
            this.ephemeralToken = data.ephemeral_token || null;
            this.credentials = data;
            this.firstSpeaker = data.first_speaker === 'AI' ? 'AI' : 'USER';
            this.root.dataset.firstSpeaker = this.firstSpeaker;

            if (!this.ephemeralToken) {
                this.setState('credentials_failed');
                return;
            }

            if (this.reconnectToken) {
                this.reportStatus('RECONNECTING');
                this.setState('live_reconnecting');
                this.reconnectLive();
            } else {
                this.setState('credentials_ready');
                this.connectLive();
            }
        } catch (_) {
            if (this.reconnectToken) {
                this._clearReconnectToken();
            }
            this.setState('credentials_failed');
        }
    }

    connectLive() {
        if (!this.ephemeralToken || !this.credentials?.model) {
            this.setState('live_connection_failed');
            return;
        }

        this.setState('connecting_live');
        this.liveClient = new GeminiLiveClient({
            token: this.ephemeralToken,
            model: this.credentials.model,
            liveConfig: this.credentials.live_config || {},
            debug: this.liveDebug,
        });

        this.liveClient.connect({
            onSetupComplete: () => {
                this.reportStatus('READY');
                this.setState('live_connected');
                this.startMicrophoneCapture();
                this.triggerAiFirstOpening();
            },
            onAudioChunk: (chunk) => this.enqueueAiAudio(chunk),
            onTranscriptEvent: (event) => this.handleTranscriptEvent(event),
            onInterrupted: () => this.clearAiPlayback('model_interruption'),
            onGoAway: (context) => this.handleGoAway(context),
            onToolCall: (call) => this.handleToolCall(call),
            onError: () => {
                this.stopSessionAudio('audio_stream_failed');
                this.setState('live_connection_failed');
                this.reportStatus('FAILED', 'FAILURE', 'Gemini Live connection error');
            },
            onClose: (event) => this.handleClose(event),
        });
    }

    ensurePlaybackQueue() {
        if (this.playbackQueue) return this.playbackQueue;

        this.playbackQueue = new AiPcmPlaybackQueue({
            outputSampleRate: 24000,
            onSpeaking: () => this.setState('ai_speaking'),
            onIdle: () => {
                if (this.state === 'ai_speaking' || this.state === 'playback_error') {
                    this.setState('playback_idle');
                    if (this.audioStreaming) {
                        this.setConversationState('listening');
                    }
                }
            },
            onError: () => this.setState('playback_error'),
        });

        return this.playbackQueue;
    }

    enqueueAiAudio(chunk) {
        if (this.conversationState !== 'ai_speaking') {
            this.setConversationState('thinking');
        }

        this.ensurePlaybackQueue()?.enqueue(chunk);
    }

    clearAiPlayback(source = 'model_interruption') {
        this.playbackQueue?.clear();
        this.root.dataset.bargeIn = source;
        this.clearConversationTimers();
        this.setConversationState('interrupted');

        this.interruptionResetTimer = window.setTimeout(() => {
            this.root.dataset.bargeIn = 'idle';

            if (this.conversationState === 'interrupted' && this.audioStreaming) {
                this.setConversationState('listening');
            }
        }, 900);
    }

    triggerAiFirstOpening() {
        if (this.firstSpeaker !== 'AI' || this.aiOpeningTriggered || !this.liveClient) return;

        this.aiOpeningTriggered = true;
        this.root.dataset.aiOpeningTriggered = 'true';

        this.liveClient.sendClientContent(
            [{ role: 'user', parts: [{ text: '[mulai]' }] }],
            true,
        );
    }

    startSessionTimer() {
        if (this.sessionTimedOut || this.sessionTimer) return;

        this.clearSessionTimer();
        this.sessionStartedAt = Date.now();
        this.sessionEndTime = Date.now() + this.sessionDurationMs;
        this.sessionWarningShown = false;
        this.aiWarningSent = false;

        this.sessionWarningTimer = window.setTimeout(() => {
            this.root.dataset.sessionWarning = 'true';
            this.sessionWarningShown = true;
            this.showWarningBanner();
            this.sendAiWarning();
        }, this.sessionWarningMs);

        this.sessionTimer = window.setTimeout(() => {
            this.handleSessionTimeLimit();
        }, this.sessionDurationMs);

        this.startTimerDisplay();
    }

    startTimerDisplay() {
        this.clearTimerDisplay();
        this.sessionTimerDisplay = window.setInterval(() => {
            this.updateTimerDisplay();
        }, 200);
    }

    clearTimerDisplay() {
        window.clearInterval(this.sessionTimerDisplay);
        this.sessionTimerDisplay = null;
    }

    updateTimerDisplay() {
        if (!this.timerDisplay) return;

        if (!this.sessionEndTime || this.sessionTimedOut) {
            this.timerDisplay.textContent = '--:--';
            return;
        }

        const remaining = Math.max(0, this.sessionEndTime - Date.now());
        const seconds = Math.ceil(remaining / 1000);
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        this.timerDisplay.textContent = `${minutes}:${String(secs).padStart(2, '0')}`;
    }

    showWarningBanner() {
        if (!this.warningBanner) return;
        this.warningBanner.classList.remove('hidden');
    }

    hideWarningBanner() {
        if (!this.warningBanner) return;
        this.warningBanner.classList.add('hidden');
    }

    sendAiWarning() {
        if (this.aiWarningSent) return;
        if (!this.liveClient?.isReady()) return;

        this.aiWarningSent = true;
        this.liveClient.sendClientContent(
            [{ role: 'user', parts: [{ text: '[peringatan: 1 menit tersisa]' }] }],
            true,
        );
    }

    clearSessionTimer() {
        window.clearTimeout(this.sessionTimer);
        window.clearTimeout(this.sessionWarningTimer);
        this.sessionTimer = null;
        this.sessionWarningTimer = null;
        this.sessionEndTime = null;
        this.sessionWarningShown = false;
        this.aiWarningSent = false;
        this.clearTimerDisplay();
        this.updateTimerDisplay();
        this.root.dataset.sessionWarning = 'false';
        this.hideWarningBanner();
    }

    handleSessionTimeLimit() {
        if (this.sessionTimedOut) return;
        this.sessionTimedOut = true;
        this.transcriptBuffer?.flush();
        this.transcriptBuffer?.stop();
        this._clearReconnectToken();
        this.clearSessionTimer();
        this.reportStatus('ENDING', 'TIME_LIMIT');

        this.stopSessionAudio('time_limit_ending');
        this.liveClient?.close();
        this.liveClient = null;
        this.clearTimerDisplay();
        this.updateTimerDisplay();
    }

    handleTranscriptEvent(event) {
        if (!event?.speaker || !event?.text) return;

        const normalized = {
            speaker: event.speaker === 'AI' ? 'AI' : 'USER',
            text: String(event.text),
            status: event.status === 'final' ? 'final' : 'partial',
            timestamp: event.timestamp || new Date().toISOString(),
        };

        this.transcriptEvents.push(normalized);
        this.transcriptBuffer?.receive(normalized);
        this.root.dataset.transcriptEvents = String(this.transcriptEvents.length);
        this.root.dataset.transcriptLatestSpeaker = normalized.speaker;
        this.root.dataset.transcriptLatestStatus = normalized.status;

        if (this.liveDebug) {
            this.renderTranscriptDebugEvent(normalized);
        }
    }

    initTranscriptDebugPanel() {
        this.root.dataset.transcriptEvents = this.root.dataset.transcriptEvents || '0';
        this.root.dataset.transcriptLatestSpeaker = this.root.dataset.transcriptLatestSpeaker || 'none';
        this.root.dataset.transcriptLatestStatus = this.root.dataset.transcriptLatestStatus || 'none';

        if (!this.liveDebug || !this.transcriptPanel) return;

        this.transcriptPanel.classList.remove('hidden');
        this.transcriptPanel.setAttribute('aria-hidden', 'false');
    }

    initTranscriptBuffer() {
        if (this.transcriptBuffer || !this.transcriptUrl) return;

        this.transcriptBuffer = new TranscriptEventBuffer({
            transcriptUrl: this.transcriptUrl,
            debug: this.liveDebug,
            onSubmitted: (data) => {
                this.root.dataset.transcriptSubmitted = String(this.transcriptBuffer.getCurrentSequence());
            },
        });
    }

    renderTranscriptDebugEvent(event) {
        if (!this.transcriptList) return;

        const item = document.createElement('li');
        item.textContent = `[${event.timestamp}] ${event.speaker} ${event.status}: ${event.text}`;
        this.transcriptList.appendChild(item);
    }

    handleGoAway(context) {
        this.goAwayContext = context;
        this.reconnectToken = context.reconnectToken || null;
        this._reconnectPending = true;
        this.root.dataset.liveGoaway = 'true';
        this.root.dataset.liveGoawayReason = context.reason || 'unknown';
        this.root.dataset.liveGoawayReconnect = this.reconnectToken ? 'available' : 'none';
        this._saveReconnectToken(this.reconnectToken);

        this.playbackQueue?.clear();
        this.playbackQueue?.close();
        this.playbackQueue = null;

        this.audioStreaming = false;
        this.stopMicrophoneCapture();
        this.clearConversationTimers();
        this.root.dataset.bargeIn = 'idle';
        this.setConversationState('idle');

        this.reportStatus('RECONNECTING');
        this.setState('live_reconnecting');
    }

    handleToolCall(call) {
        if (!call?.name) return;

        this.pendingToolCalls.push(call);
        this.root.dataset.liveToolcalls = String(this.pendingToolCalls.length);
        this.root.dataset.liveToolcallLatest = call.name;

        if (this.liveDebug && typeof console !== 'undefined') {
            console.debug('Live tool call:', call.name, call.args);
        }
    }

    reportStatus(status, endingType = null, endingReason = null) {
        if (this._reportedStatus === status) return;

        const payload = { status };
        if (endingType) {
            payload.ending_type = endingType;
        }
        if (endingReason) {
            payload.ending_reason = endingReason;
        }

        fetch(this.statusUrl, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload),
        }).catch(() => {});

        this._reportedStatus = status;
    }

    handleClose(event) {
        if (this.sessionTimedOut) return;

        if (this._reconnectPending && this.reconnectToken) {
            this._reconnectPending = false;
            this.reconnectLive();
            return;
        }

        this.stopSessionAudio('audio_stream_stopped');

        if (this.state === 'live_connection_failed' || this.state === 'live_reconnection_failed') return;

        this.setState(
            event.expired ? 'live_connection_failed' : 'live_closed',
            event.expired ? 'Kredensial sementara sudah tidak berlaku. Muat ulang persiapan sesi lalu coba lagi.' : null,
        );
    }

    reconnectLive() {
        this._reconnectPending = false;
        this.reconnectAttempts++;

        if (this.reconnectAttempts > this.maxReconnectAttempts) {
            this._clearReconnectToken();
            this.reportStatus('FAILED', 'FAILURE', 'Reconnection failed after max attempts');
            this.setState('live_reconnection_failed', 'Gagal menyambung ulang setelah beberapa kali percobaan.');
            return;
        }

        this.liveClient?.close();
        this.liveClient = null;

        this.liveClient = new GeminiLiveClient({
            token: this.ephemeralToken,
            model: this.credentials.model,
            liveConfig: this.credentials.live_config || {},
            debug: this.liveDebug,
        });

        const currentReconnectToken = this.reconnectToken;

        this.liveClient.connect({
            reconnectToken: currentReconnectToken,
            onSetupComplete: () => {
                this.reconnectAttempts = 0;
                this._clearReconnectToken();
                this.root.dataset.liveGoaway = 'false';
                this.root.dataset.liveGoawayReason = 'none';
                this.root.dataset.liveGoawayReconnect = 'none';
                this.root.dataset.liveReconnect = 'none';

                this.reportStatus('ACTIVE');
                this.setState('live_reconnected');
                this.startMicrophoneCapture();

                window.setTimeout(() => {
                    if (this.state === 'live_reconnected') {
                        this.setState('live_connected');
                    }
                }, 2000);
            },
            onAudioChunk: (chunk) => this.enqueueAiAudio(chunk),
            onTranscriptEvent: (event) => this.handleTranscriptEvent(event),
            onInterrupted: () => this.clearAiPlayback('model_interruption'),
            onGoAway: (context) => this.handleGoAway(context),
            onToolCall: (call) => this.handleToolCall(call),
            onError: () => {
                this._clearReconnectToken();
                this.reportStatus('FAILED', 'FAILURE', 'Reconnection connection error');
                this.setState('live_reconnection_failed');
            },
            onClose: (event) => this.handleClose(event),
        });
    }

    stopSessionAudio(state = 'audio_stream_stopped') {
        this.transcriptBuffer?.flush();
        this.transcriptBuffer?.stop();
        this._clearReconnectToken();
        this.stopAudioStreaming(state);
        this.playbackQueue?.close();
        this.playbackQueue = null;
        this.clearConversationTimers();
        this.clearSessionTimer();
        this.root.dataset.bargeIn = 'idle';
        this.goAwayContext = null;
        this.root.dataset.liveGoaway = 'false';
        this.root.dataset.liveGoawayReason = 'none';
        this.root.dataset.liveGoawayReconnect = 'none';
        this.aiOpeningTriggered = false;
        this.root.dataset.aiOpeningTriggered = 'false';
        this.setConversationState('idle');
    }

    async startMicrophoneCapture() {
        if (this.microphoneCapture) return;

        this.microphoneCapture = new MicrophoneCapture({
            targetSampleRate: 16000,
            onAudioChunk: (chunk) => {
                if (!this.audioStreaming) return;

                if (!this.liveClient?.sendAudioChunk(chunk)) {
                    this.stopAudioStreaming('audio_stream_failed');
                    return;
                }

                this.handleMicrophoneActivity(chunk.level || 0);
            },
        });

        try {
            await this.microphoneCapture.start();
            this.audioStreaming = true;
            this.transcriptBuffer?.start();
            this.setState('audio_streaming');
            this.setConversationState('listening');
            this.startSessionTimer();
            this.reportStatus('ACTIVE');
        } catch (_) {
            this.microphoneCapture?.stop();
            this.microphoneCapture = null;
            this.audioStreaming = false;
            this.setState('microphone_capture_failed');
        }
    }

    stopAudioStreaming(state = 'audio_stream_stopped') {
        const wasStreaming = this.audioStreaming || Boolean(this.microphoneCapture);

        this.audioStreaming = false;
        this.stopMicrophoneCapture();

        if (wasStreaming) {
            this.setState(state);
        }

        this.clearConversationTimers();
        this.clearSessionTimer();
        this.root.dataset.bargeIn = 'idle';
        this.goAwayContext = null;
        this.root.dataset.liveGoaway = 'false';
        this.root.dataset.liveGoawayReason = 'none';
        this.root.dataset.liveGoawayReconnect = 'none';
        this.pendingToolCalls = [];
        this.root.dataset.liveToolcalls = '0';
        this.root.dataset.liveToolcallLatest = 'none';
        this.aiOpeningTriggered = false;
        this.root.dataset.aiOpeningTriggered = 'false';
        this.setConversationState('idle');
    }

    handleMicrophoneActivity(level) {
        if (level >= 0.015 && (this.state === 'ai_speaking' || this.conversationState === 'ai_speaking')) {
            this.clearAiPlayback('user_barge_in');
            return;
        }

        if (this.state === 'ai_speaking' || this.conversationState === 'interrupted') return;

        if (level >= 0.015) {
            this.setConversationState('user_speaking');
            window.clearTimeout(this.userSpeechTimer);
            window.clearTimeout(this.waitingForAiTimer);

            this.userSpeechTimer = window.setTimeout(() => {
                if (!this.audioStreaming || this.state === 'ai_speaking') return;

                this.setConversationState('waiting_for_ai');
                this.waitingForAiTimer = window.setTimeout(() => {
                    if (this.conversationState === 'waiting_for_ai' && this.audioStreaming) {
                        this.setConversationState('listening');
                    }
                }, 1200);
            }, 700);

            return;
        }

        if (this.conversationState === 'idle' && this.audioStreaming) {
            this.setConversationState('listening');
        }
    }

    clearConversationTimers() {
        window.clearTimeout(this.userSpeechTimer);
        window.clearTimeout(this.waitingForAiTimer);
        window.clearTimeout(this.interruptionResetTimer);
        this.userSpeechTimer = null;
        this.waitingForAiTimer = null;
        this.interruptionResetTimer = null;
    }

    stopMicrophoneCapture() {
        if (!this.microphoneCapture) return;

        this.microphoneCapture.stop();
        this.microphoneCapture = null;

        if (this.state === 'microphone_capturing') {
            this.setState('microphone_stopped');
        }
    }

    handleCredentialError(status) {
        if (status === 403) {
            this.setState('credentials_failed', 'Akun Anda belum dapat memulai sesi. Hubungi HQ jika status akun sudah berubah.');
            this.reportStatus('FAILED', 'FAILURE', 'Account status prevented credentials');
            return;
        }

        if (status === 503) {
            this.setState('credentials_failed', 'Koneksi Live belum tersedia. Coba lagi sebentar lagi.');
            this.reportStatus('FAILED', 'FAILURE', 'Live credentials service unavailable');
            return;
        }

        this.setState('credentials_failed');
        this.reportStatus('FAILED', 'FAILURE', 'Credential request failed');
    }

    setState(state, customDetail = null) {
        this.state = state;
        this.root.dataset.runtimeState = state;

        if (state === 'microphone_capturing') {
            this.root.dataset.microphoneCapture = 'capturing';
        } else if (state === 'microphone_capture_failed') {
            this.root.dataset.microphoneCapture = 'failed';
        } else if (state === 'microphone_stopped') {
            this.root.dataset.microphoneCapture = 'stopped';
        } else if (state === 'audio_streaming') {
            this.root.dataset.audioStream = 'streaming';
        } else if (state === 'audio_stream_failed') {
            this.root.dataset.audioStream = 'failed';
        } else if (state === 'audio_stream_stopped') {
            this.root.dataset.audioStream = 'stopped';
        } else if (state === 'ai_speaking') {
            this.root.dataset.aiPlayback = 'speaking';
        } else if (state === 'playback_error') {
            this.root.dataset.aiPlayback = 'error';
        } else if (state === 'playback_idle') {
            this.root.dataset.aiPlayback = 'idle';
        }

        if (state === 'live_reconnecting') {
            this.root.dataset.liveReconnect = 'connecting';
            this.reconnectIndicator?.classList.remove('hidden');
        } else if (state === 'live_reconnected') {
            this.root.dataset.liveReconnect = 'connected';
            this.reconnectIndicator?.classList.add('hidden');
        } else if (state === 'live_reconnection_failed') {
            this.root.dataset.liveReconnect = 'failed';
            this.reconnectIndicator?.classList.add('hidden');
        } else {
            this.reconnectIndicator?.classList.add('hidden');
        }

        if (state === 'ai_speaking') {
            this.setConversationState('ai_speaking');
        } else if (state === 'playback_error') {
            this.setConversationState('idle');
        }

        const messages = {
            idle: ['Siap memulai', 'Setelah mikrofon siap, tekan Mulai Sesi untuk menyiapkan koneksi Live.'],
            requesting_credentials: ['Menyiapkan koneksi', 'Mengambil kredensial sementara. Panggilan belum dimulai.'],
            credentials_ready: ['Kredensial siap', 'Kredensial sementara sudah siap di memori browser. Koneksi suara akan ditambahkan pada tahap berikutnya.'],
            credentials_failed: ['Belum bisa memulai', customDetail || 'Kredensial sementara belum berhasil dibuat. Coba lagi sebentar lagi.'],
            connecting_live: ['Menghubungkan', 'Membuka sesi Live. Audio belum dikirim.'],
            live_connected: ['Sesi Live tersambung', 'Handshake Live berhasil. Pengiriman audio akan ditambahkan pada tahap berikutnya.'],
            live_connection_failed: ['Koneksi belum berhasil', customDetail || 'Sesi Live belum bisa tersambung. Coba lagi sebentar lagi.'],
            live_reconnecting: ['Menghubungkan kembali...', 'Koneksi Live terputus. Mencoba menyambung ulang secara otomatis.'],
            live_reconnected: ['Koneksi dipulihkan', 'Sesi Live berhasil tersambung kembali. Mikrofon akan diaktifkan kembali.'],
            live_reconnection_failed: ['Gagal menghubungkan kembali', customDetail || 'Sesi Live gagal tersambung kembali. Muat ulang halaman dan coba lagi.'],
            live_closed: ['Koneksi Live tertutup', 'Sesi Live sudah tertutup. Muat ulang halaman jika ingin mencoba lagi.'],
            microphone_capturing: ['Mikrofon aktif', 'Audio mikrofon sedang disiapkan sebagai PCM 16 kHz di browser. Audio belum dikirim ke Gemini.'],
            microphone_capture_failed: ['Mikrofon belum aktif', 'Mikrofon belum bisa mulai merekam. Periksa izin browser dan perangkat mikrofon.'],
            microphone_stopped: ['Mikrofon berhenti', 'Capture mikrofon sudah dihentikan dan track browser sudah ditutup.'],
            audio_streaming: ['Latihan suara aktif', 'Audio mikrofon PCM 16 kHz sedang dikirim ke Gemini Live. Audio balasan belum diputar pada tahap ini.'],
            audio_stream_failed: ['Streaming audio terhenti', 'Audio mikrofon belum bisa dikirim. Periksa koneksi lalu mulai ulang persiapan sesi.'],
            audio_stream_stopped: ['Streaming audio berhenti', 'Pengiriman audio mikrofon sudah dihentikan dan track browser sudah ditutup.'],
            ai_speaking: ['AI sedang berbicara', 'Audio Gemini Live sedang diputar berurutan dari antrean PCM 24 kHz.'],
            playback_error: ['Audio AI bermasalah', 'Audio balasan belum bisa diputar. Sesi dapat dimulai ulang dari halaman persiapan.'],
            playback_idle: ['Audio AI selesai', 'Antrean audio Gemini kosong. Mikrofon tetap berjalan jika sesi masih aktif.'],
            time_limit_ending: ['Batas waktu tercapai', 'Sesi latihan telah mencapai batas 15 menit.'],
        };

        const [status, detail] = messages[state] || messages.idle;
        if (this.status) this.status.textContent = status;
        if (this.detail) this.detail.textContent = detail;

        if (this.startButton) {
            this.startButton.disabled = [
                'requesting_credentials',
                'credentials_ready',
                'connecting_live',
                'live_connected',
                'microphone_capturing',
                'audio_streaming',
                'ai_speaking',
            ].includes(state);
            this.startButton.disabled = this.startButton.disabled || this.audioStreaming;
            this.startButton.textContent = state === 'requesting_credentials' ? 'Menyiapkan...' : 'Mulai Sesi';
        }

        this.stopButton?.classList.toggle('hidden', !this.audioStreaming);
    }

    setConversationState(state) {
        this.conversationState = state;
        this.root.dataset.conversationState = state;

        const messages = {
            idle: ['Diam', 'Belum ada percakapan aktif.'],
            listening: ['Mendengarkan', 'Sistem siap menangkap suara Anda. Bicara dengan ritme normal.'],
            user_speaking: ['Anda sedang berbicara', 'Suara Anda sedang dikirim ke sesi Live.'],
            waiting_for_ai: ['Menunggu respons', 'Memberi waktu singkat agar AI merespons.'],
            thinking: ['AI menyiapkan respons', 'Tunggu sebentar.'],
            ai_speaking: ['AI sedang berbicara', 'Dengarkan respons pelanggan simulasi.'],
            interrupted: ['Interupsi terdeteksi', 'Audio AI lama dihentikan agar percakapan tetap alami.'],
        };

        const [status, detail] = messages[state] || messages.idle;
        if (this.conversationStatus) this.conversationStatus.textContent = status;
        if (this.conversationDetail) this.conversationDetail.textContent = detail;

        this.conversationIndicators.forEach((element) => {
            const active = element.dataset.conversationIndicator === state;
            element.classList.toggle('bg-sage-600', active);
            element.classList.toggle('text-white', active);
            element.classList.toggle('bg-stone-100', !active);
            element.classList.toggle('text-gray-600', !active);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-roleplay-runtime]').forEach((root) => {
        new RoleplayRuntime(root).init();
    });
});

export { RoleplayRuntime };
