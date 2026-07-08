class GeminiLiveClient {
    constructor({ token, model, liveConfig = {}, debug = false, WebSocketClass = window.WebSocket }) {
        this.token = token;
        this.model = model;
        this.liveConfig = liveConfig;
        this.debug = debug;
        this.WebSocketClass = WebSocketClass;
        this.socket = null;
    }

    connect({ onOpen, onSetupComplete, onError, onClose } = {}) {
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
                onSetupComplete?.();
            }

            if (this.debug) {
                this.logMessageShape(message);
            }
        });

        this.socket.addEventListener('error', () => {
            onError?.({ kind: 'connection' });
        });

        this.socket.addEventListener('close', (event) => {
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

    parseMessage(data) {
        if (typeof data !== 'string') return null;

        try {
            return JSON.parse(data);
        } catch (_) {
            return null;
        }
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
