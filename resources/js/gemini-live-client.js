import { encodeBase64 } from './audio-pcm-utils';

class GeminiLiveClient {
    constructor({ token, model, liveConfig = {}, debug = false, WebSocketClass = window.WebSocket }) {
        this.token = token;
        this.model = model;
        this.liveConfig = liveConfig;
        this.debug = debug;
        this.WebSocketClass = WebSocketClass;
        this.socket = null;
        this.setupComplete = false;
    }

    connect({ onOpen, onSetupComplete, onAudioChunk, onInterrupted, onError, onClose } = {}) {
        if (!this.token || !this.model || !this.WebSocketClass) {
            onError?.({ kind: 'configuration' });
            return;
        }

        this.socket = new this.WebSocketClass(this.websocketUrl());

        this.socket.addEventListener('open', () => {
            onOpen?.();
            this.sendSetup();
        });

        this.socket.addEventListener('message', (event) => {
            const message = this.parseMessage(event.data);

            if (message?.setupComplete) {
                this.setupComplete = true;
                onSetupComplete?.();
            }

            this.extractAudioChunks(message).forEach((chunk) => onAudioChunk?.(chunk));

            if (this.isInterrupted(message)) {
                onInterrupted?.();
            }

            if (this.debug) {
                this.logMessageShape(message);
            }
        });

        this.socket.addEventListener('error', () => {
            onError?.({ kind: 'connection' });
        });

        this.socket.addEventListener('close', (event) => {
            this.setupComplete = false;

            onClose?.({
                code: event.code,
                reason: event.reason,
                expired: this.looksLikeTokenFailure(event),
            });
        });
    }

    close() {
        this.socket?.close();
        this.socket = null;
        this.setupComplete = false;
    }

    isReady() {
        return this.setupComplete && this.socket?.readyState === 1;
    }

    websocketUrl() {
        const baseUrl = 'wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContentConstrained';

        return `${baseUrl}?access_token=${encodeURIComponent(this.token)}`;
    }

    sendSetup() {
        const responseModalities = this.liveConfig.response_modalities || ['AUDIO'];

        this.socket?.send(JSON.stringify({
            setup: {
                model: this.model.startsWith('models/') ? this.model : `models/${this.model}`,
                generationConfig: {
                    responseModalities,
                },
            },
        }));
    }

    sendAudioChunk(chunk) {
        if (!this.isReady() || !chunk?.audio || !chunk?.mimeType) {
            return false;
        }

        try {
            this.socket.send(JSON.stringify({
                realtimeInput: {
                    mediaChunks: [{
                        mimeType: chunk.mimeType,
                        data: encodeBase64(chunk.audio),
                    }],
                },
            }));
        } catch (_) {
            return false;
        }

        return true;
    }

    parseMessage(data) {
        if (typeof data !== 'string') return null;

        try {
            return JSON.parse(data);
        } catch (_) {
            return null;
        }
    }

    extractAudioChunks(message) {
        const parts = message?.serverContent?.modelTurn?.parts
            || message?.server_content?.model_turn?.parts
            || [];

        return parts
            .map((part) => part.inlineData || part.inline_data || null)
            .filter((inlineData) => inlineData?.data && String(inlineData.mimeType || inlineData.mime_type || '').toLowerCase().startsWith('audio/pcm'))
            .map((inlineData) => ({
                data: inlineData.data,
                mimeType: inlineData.mimeType || inlineData.mime_type,
            }));
    }

    isInterrupted(message) {
        return Boolean(
            message?.serverContent?.interrupted
            || message?.serverContent?.generationInterrupted
            || message?.server_content?.interrupted
            || message?.server_content?.generation_interrupted,
        );
    }

    looksLikeTokenFailure(event) {
        const reason = (event.reason || '').toLowerCase();

        return event.code === 1008
            || reason.includes('token')
            || reason.includes('auth')
            || reason.includes('expired');
    }

    logMessageShape(message) {
        if (!message || typeof console === 'undefined') return;

        const keys = Object.keys(message).filter((key) => key !== 'usageMetadata');
        console.debug('Gemini Live message fields:', keys);
    }
}

export { GeminiLiveClient };
