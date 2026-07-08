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

    connect({ onOpen, onSetupComplete, onAudioChunk, onTranscriptEvent, onInterrupted, onGoAway, onToolCall, onError, onClose } = {}) {
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
            if (!message) return;

            const goAway = this.extractGoAway(message);
            const interrupted = this.isInterrupted(message);
            const audioChunks = this.extractAudioChunks(message);
            const transcriptEvents = this.extractTranscriptEvents(message);
            const toolCalls = this.extractToolCalls(message);

            if (message?.setupComplete) {
                this.setupComplete = true;
                onSetupComplete?.();
            }

            if (goAway) {
                onGoAway?.(goAway);
            }

            if (interrupted) {
                onInterrupted?.();
            }

            audioChunks.forEach((chunk) => onAudioChunk?.(chunk));
            transcriptEvents.forEach((event) => onTranscriptEvent?.(event));
            toolCalls.forEach((call) => onToolCall?.(call));

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

    sendClientContent(turns, turnComplete = true) {
        if (!this.isReady()) return false;

        try {
            this.socket.send(JSON.stringify({
                clientContent: { turns, turnComplete },
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

    extractTranscriptEvents(message) {
        const timestamp = new Date().toISOString();
        const serverContent = message?.serverContent || message?.server_content || {};
        const candidates = [
            { speaker: 'USER', payload: serverContent.inputTranscription || serverContent.input_transcription },
            { speaker: 'AI', payload: serverContent.outputTranscription || serverContent.output_transcription },
            { speaker: 'USER', payload: message?.inputTranscription || message?.input_transcription },
            { speaker: 'AI', payload: message?.outputTranscription || message?.output_transcription },
        ];

        return candidates.flatMap(({ speaker, payload }) => this.normalizeTranscriptPayload(payload, speaker, timestamp));
    }

    normalizeTranscriptPayload(payload, speaker, timestamp) {
        const items = Array.isArray(payload) ? payload : [payload];

        return items
            .map((item) => {
                if (!item) return null;

                const text = typeof item === 'string'
                    ? item
                    : String(item.text || item.transcript || item.content || '').trim();

                if (!text) return null;

                return {
                    speaker,
                    text,
                    status: this.transcriptStatus(item),
                    timestamp,
                };
            })
            .filter(Boolean);
    }

    transcriptStatus(item) {
        if (typeof item !== 'object' || item === null) return 'partial';

        const finalValue = item.final ?? item.isFinal ?? item.is_final ?? item.finished ?? item.complete;

        if (finalValue === true) return 'final';
        if (finalValue === false) return 'partial';

        const explicit = String(item.status || item.type || '').toLowerCase();

        if (explicit.includes('final') || explicit.includes('complete')) return 'final';

        return 'partial';
    }

    extractGoAway(message) {
        const ga = message?.goAway || message?.go_away || null;
        if (!ga) return null;

        return {
            reason: ga.reason || 'unknown',
            reconnectToken: ga.reconnectToken || ga.reconnect_token || null,
            timeout: ga.timeout || null,
        };
    }

    extractToolCalls(message) {
        const parts = message?.serverContent?.modelTurn?.parts
            || message?.server_content?.model_turn?.parts
            || [];

        const fromParts = parts
            .map((part) => part.functionCall || part.function_call || null)
            .filter(Boolean)
            .map((fc) => ({
                name: fc.name || fc.functionName || '',
                args: fc.args || fc.arguments || {},
            }));

        const msgLevel = message?.toolCall || message?.tool_call || null;
        if (msgLevel) {
            fromParts.push({
                name: msgLevel.name || msgLevel.functionName || '',
                args: msgLevel.args || msgLevel.arguments || {},
            });
        }

        return fromParts;
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
