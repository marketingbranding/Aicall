class MicrophoneCapture {
    constructor({
        targetSampleRate = 16000,
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
            const pcm = this.encodePcm16(input, this.audioContext.sampleRate);

            this.onAudioChunk?.({
                audio: pcm,
                sampleRate: this.targetSampleRate,
                mimeType: `audio/pcm;rate=${this.targetSampleRate}`,
            });
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

    encodePcm16(float32Samples, sourceSampleRate) {
        const samples = sourceSampleRate === this.targetSampleRate
            ? float32Samples
            : this.resample(float32Samples, sourceSampleRate, this.targetSampleRate);
        const pcm = new Int16Array(samples.length);

        for (let index = 0; index < samples.length; index += 1) {
            const sample = Math.max(-1, Math.min(1, samples[index]));
            pcm[index] = sample < 0 ? sample * 0x8000 : sample * 0x7fff;
        }

        return pcm.buffer;
    }

    resample(samples, sourceSampleRate, targetSampleRate) {
        if (!samples.length || sourceSampleRate <= 0 || targetSampleRate <= 0) {
            return new Float32Array();
        }

        const ratio = sourceSampleRate / targetSampleRate;
        const length = Math.max(1, Math.floor(samples.length / ratio));
        const output = new Float32Array(length);

        for (let index = 0; index < length; index += 1) {
            const position = index * ratio;
            const before = Math.floor(position);
            const after = Math.min(before + 1, samples.length - 1);
            const weight = position - before;

            output[index] = samples[before] + ((samples[after] - samples[before]) * weight);
        }

        return output;
    }
}

export { MicrophoneCapture };
