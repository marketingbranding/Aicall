const GEMINI_LIVE_INPUT_SAMPLE_RATE = 16000;

function resampleTo16k(samples, sourceSampleRate) {
    return resample(samples, sourceSampleRate, GEMINI_LIVE_INPUT_SAMPLE_RATE);
}

function resample(samples, sourceSampleRate, targetSampleRate) {
    if (!samples?.length || sourceSampleRate <= 0 || targetSampleRate <= 0) {
        return new Float32Array();
    }

    if (sourceSampleRate === targetSampleRate) {
        return samples instanceof Float32Array ? samples : new Float32Array(samples);
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

function float32ToPcm16(samples) {
    const buffer = new ArrayBuffer(samples.length * 2);
    const view = new DataView(buffer);

    for (let index = 0; index < samples.length; index += 1) {
        const sample = Math.max(-1, Math.min(1, samples[index]));
        const value = sample < 0 ? sample * 0x8000 : sample * 0x7fff;

        view.setInt16(index * 2, value, true);
    }

    return buffer;
}

function prepareGeminiLivePcm16(inputSamples, sourceSampleRate) {
    const samples = sourceSampleRate === GEMINI_LIVE_INPUT_SAMPLE_RATE
        ? inputSamples
        : resampleTo16k(inputSamples, sourceSampleRate);

    return {
        audio: float32ToPcm16(samples),
        sampleRate: GEMINI_LIVE_INPUT_SAMPLE_RATE,
        mimeType: `audio/pcm;rate=${GEMINI_LIVE_INPUT_SAMPLE_RATE}`,
        encoding: 'LINEAR16',
        littleEndian: true,
    };
}

function encodeBase64(buffer) {
    const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
    let binary = '';
    const chunkSize = 0x8000;

    for (let index = 0; index < bytes.length; index += chunkSize) {
        const chunk = bytes.subarray(index, index + chunkSize);
        binary += String.fromCharCode(...chunk);
    }

    return btoa(binary);
}

export {
    GEMINI_LIVE_INPUT_SAMPLE_RATE,
    encodeBase64,
    float32ToPcm16,
    prepareGeminiLivePcm16,
    resampleTo16k,
};
