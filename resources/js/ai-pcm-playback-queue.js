const GEMINI_LIVE_OUTPUT_SAMPLE_RATE = 24000;

class AiPcmPlaybackQueue {
    constructor({
        outputSampleRate = GEMINI_LIVE_OUTPUT_SAMPLE_RATE,
        AudioContextClass = window.AudioContext || window.webkitAudioContext,
        onSpeaking,
        onIdle,
        onError,
    } = {}) {
        this.outputSampleRate = outputSampleRate;
        this.AudioContextClass = AudioContextClass;
        this.onSpeaking = onSpeaking;
        this.onIdle = onIdle;
        this.onError = onError;
        this.audioContext = null;
        this.queue = [];
        this.currentSource = null;
        this.nextStartTime = 0;
        this.playing = false;
    }

    async prime() {
        await this.ensureContext();
    }

    async enqueue({ data, mimeType }) {
        if (!data || !this.isPcmMimeType(mimeType)) return;

        try {
            const context = await this.ensureContext();
            const pcm = base64ToArrayBuffer(data);
            const audioBuffer = pcm16ToAudioBuffer(pcm, context, this.outputSampleRate);

            this.queue.push(audioBuffer);
            this.schedule();
        } catch (_) {
            this.clear();
            this.onError?.();
        }
    }

    clear() {
        this.queue = [];
        this.nextStartTime = this.audioContext?.currentTime || 0;
        this.playing = false;

        if (this.currentSource) {
            try {
                this.currentSource.onended = null;
                this.currentSource.stop();
            } catch (_) {
                // Already stopped.
            }
        }

        this.currentSource = null;
        this.onIdle?.();
    }

    close() {
        this.clear();

        if (this.audioContext && this.audioContext.state !== 'closed') {
            this.audioContext.close();
        }

        this.audioContext = null;
    }

    async ensureContext() {
        if (!this.AudioContextClass) {
            throw new Error('playback_unsupported');
        }

        if (!this.audioContext || this.audioContext.state === 'closed') {
            this.audioContext = new this.AudioContextClass();
            this.nextStartTime = this.audioContext.currentTime;
        }

        if (this.audioContext.state === 'suspended') {
            await this.audioContext.resume();
        }

        return this.audioContext;
    }

    schedule() {
        if (!this.audioContext || this.currentSource || !this.queue.length) return;

        const buffer = this.queue.shift();
        const source = this.audioContext.createBufferSource();
        const startAt = Math.max(this.audioContext.currentTime, this.nextStartTime);

        source.buffer = buffer;
        source.connect(this.audioContext.destination);
        source.onended = () => {
            if (this.currentSource !== source) return;

            this.currentSource = null;
            this.schedule();

            if (!this.currentSource && !this.queue.length) {
                this.playing = false;
                this.onIdle?.();
            }
        };

        this.currentSource = source;
        this.nextStartTime = startAt + buffer.duration;
        source.start(startAt);

        if (!this.playing) {
            this.playing = true;
            this.onSpeaking?.();
        }
    }

    isPcmMimeType(mimeType) {
        return String(mimeType || '').toLowerCase().startsWith('audio/pcm');
    }
}

function pcm16ToAudioBuffer(buffer, audioContext, sampleRate = GEMINI_LIVE_OUTPUT_SAMPLE_RATE) {
    const view = new DataView(buffer);
    const samples = Math.floor(view.byteLength / 2);
    const audioBuffer = audioContext.createBuffer(1, samples, sampleRate);
    const channel = audioBuffer.getChannelData(0);

    for (let index = 0; index < samples; index += 1) {
        channel[index] = view.getInt16(index * 2, true) / 0x8000;
    }

    return audioBuffer;
}

function base64ToArrayBuffer(base64) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);

    for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
    }

    return bytes.buffer;
}

export {
    AiPcmPlaybackQueue,
    GEMINI_LIVE_OUTPUT_SAMPLE_RATE,
    base64ToArrayBuffer,
    pcm16ToAudioBuffer,
};
