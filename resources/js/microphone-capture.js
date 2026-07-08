import { GEMINI_LIVE_INPUT_SAMPLE_RATE, prepareGeminiLivePcm16 } from './audio-pcm-utils';

class MicrophoneCapture {
    constructor({
        targetSampleRate = GEMINI_LIVE_INPUT_SAMPLE_RATE,
        AudioContextClass = window.AudioContext || window.webkitAudioContext,
        mediaDevices = navigator.mediaDevices,
        onAudioChunk,
    } = {}) {
        this.targetSampleRate = targetSampleRate;
        this.AudioContextClass = AudioContextClass;
        this.mediaDevices = mediaDevices;
        this.onAudioChunk = onAudioChunk;
        this.audioContext = null;
        this.stream = null;
        this.source = null;
        this.processor = null;
        this.silentGain = null;
        this.capturing = false;
    }

    async start() {
        if (this.capturing) return;

        if (!this.AudioContextClass || !this.mediaDevices?.getUserMedia) {
            throw new Error('microphone_capture_unsupported');
        }

        this.audioContext = new this.AudioContextClass();

        if (this.audioContext.state === 'suspended') {
            await this.audioContext.resume();
        }

        this.stream = await this.mediaDevices.getUserMedia({
            audio: {
                channelCount: 1,
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            },
        });

        this.source = this.audioContext.createMediaStreamSource(this.stream);
        this.processor = this.audioContext.createScriptProcessor(4096, 1, 1);
        this.silentGain = this.audioContext.createGain();
        this.silentGain.gain.value = 0;

        this.processor.onaudioprocess = (event) => {
            if (!this.capturing) return;

            const input = event.inputBuffer.getChannelData(0);
            this.onAudioChunk?.(prepareGeminiLivePcm16(input, this.audioContext.sampleRate));
        };

        this.source.connect(this.processor);
        this.processor.connect(this.silentGain);
        this.silentGain.connect(this.audioContext.destination);
        this.capturing = true;
    }

    stop() {
        this.capturing = false;

        if (this.processor) {
            this.processor.onaudioprocess = null;
            this.processor.disconnect();
        }

        this.source?.disconnect();
        this.silentGain?.disconnect();

        this.stream?.getTracks().forEach((track) => track.stop());

        if (this.audioContext && this.audioContext.state !== 'closed') {
            this.audioContext.close();
        }

        this.audioContext = null;
        this.stream = null;
        this.source = null;
        this.processor = null;
        this.silentGain = null;
    }

}

export { MicrophoneCapture };
