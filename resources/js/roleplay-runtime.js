import { GeminiLiveClient } from './gemini-live-client';
import { MicrophoneCapture } from './microphone-capture';

class RoleplayRuntime {
    constructor(root) {
        this.root = root;
        this.credentialsUrl = root.dataset.credentialsUrl;
        this.state = 'idle';
        this.ephemeralToken = null;
        this.credentials = null;
        this.liveClient = null;
        this.microphoneCapture = null;
        this.audioStreaming = false;
        this.liveDebug = root.dataset.liveDebug === 'true';

        this.status = root.querySelector('[data-runtime-status]');
        this.detail = root.querySelector('[data-runtime-detail]');
        this.startButton = root.querySelector('[data-roleplay-start]');
        this.stopButton = root.querySelector('[data-roleplay-stop]');
    }

    init() {
        this.setState('idle');
        this.startButton?.addEventListener('click', () => this.requestCredentials());
        this.stopButton?.addEventListener('click', () => this.stopAudioStreaming('audio_stream_stopped'));

        document.addEventListener('roleplay:microphone-allowed', () => {
            this.startButton?.classList.remove('hidden');
        });

        window.addEventListener('beforeunload', () => this.stopAudioStreaming('audio_stream_stopped'));
        window.addEventListener('pagehide', () => this.stopAudioStreaming('audio_stream_stopped'));
    }

    async requestCredentials() {
        if (!this.credentialsUrl || this.state === 'requesting_credentials') return;

        this.setState('requesting_credentials');

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
                this.handleCredentialError(response.status);
                return;
            }

            const data = await response.json();
            this.ephemeralToken = data.ephemeral_token || null;
            this.credentials = data;

            if (!this.ephemeralToken) {
                this.setState('credentials_failed');
                return;
            }

            this.setState('credentials_ready');
            this.connectLive();
        } catch (_) {
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
                this.setState('live_connected');
                this.startMicrophoneCapture();
            },
            onError: () => {
                this.stopAudioStreaming('audio_stream_failed');
                this.setState('live_connection_failed');
            },
            onClose: (event) => {
                this.stopAudioStreaming('audio_stream_stopped');

                if (this.state === 'live_connection_failed') return;

                this.setState(
                    event.expired ? 'live_connection_failed' : 'live_closed',
                    event.expired ? 'Kredensial sementara sudah tidak berlaku. Muat ulang persiapan sesi lalu coba lagi.' : null,
                );
            },
        });
    }

    async startMicrophoneCapture() {
        if (this.microphoneCapture) return;

        this.microphoneCapture = new MicrophoneCapture({
            targetSampleRate: 16000,
            onAudioChunk: (chunk) => {
                if (!this.audioStreaming) return;

                if (!this.liveClient?.sendAudioChunk(chunk)) {
                    this.stopAudioStreaming('audio_stream_failed');
                }
            },
        });

        try {
            await this.microphoneCapture.start();
            this.audioStreaming = true;
            this.setState('audio_streaming');
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
            return;
        }

        if (status === 503) {
            this.setState('credentials_failed', 'Koneksi Live belum tersedia. Coba lagi sebentar lagi.');
            return;
        }

        this.setState('credentials_failed');
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
        }

        const messages = {
            idle: ['Siap memulai', 'Setelah mikrofon siap, tekan Mulai Sesi untuk menyiapkan koneksi Live.'],
            requesting_credentials: ['Menyiapkan koneksi', 'Mengambil kredensial sementara. Panggilan belum dimulai.'],
            credentials_ready: ['Kredensial siap', 'Kredensial sementara sudah siap di memori browser. Koneksi suara akan ditambahkan pada tahap berikutnya.'],
            credentials_failed: ['Belum bisa memulai', customDetail || 'Kredensial sementara belum berhasil dibuat. Coba lagi sebentar lagi.'],
            connecting_live: ['Menghubungkan', 'Membuka sesi Live. Audio belum dikirim.'],
            live_connected: ['Sesi Live tersambung', 'Handshake Live berhasil. Pengiriman audio akan ditambahkan pada tahap berikutnya.'],
            live_connection_failed: ['Koneksi belum berhasil', customDetail || 'Sesi Live belum bisa tersambung. Coba lagi sebentar lagi.'],
            live_closed: ['Koneksi Live tertutup', 'Sesi Live sudah tertutup. Muat ulang halaman jika ingin mencoba lagi.'],
            microphone_capturing: ['Mikrofon aktif', 'Audio mikrofon sedang disiapkan sebagai PCM 16 kHz di browser. Audio belum dikirim ke Gemini.'],
            microphone_capture_failed: ['Mikrofon belum aktif', 'Mikrofon belum bisa mulai merekam. Periksa izin browser dan perangkat mikrofon.'],
            microphone_stopped: ['Mikrofon berhenti', 'Capture mikrofon sudah dihentikan dan track browser sudah ditutup.'],
            audio_streaming: ['Latihan suara aktif', 'Audio mikrofon PCM 16 kHz sedang dikirim ke Gemini Live. Audio balasan belum diputar pada tahap ini.'],
            audio_stream_failed: ['Streaming audio terhenti', 'Audio mikrofon belum bisa dikirim. Periksa koneksi lalu mulai ulang persiapan sesi.'],
            audio_stream_stopped: ['Streaming audio berhenti', 'Pengiriman audio mikrofon sudah dihentikan dan track browser sudah ditutup.'],
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
            ].includes(state);
            this.startButton.textContent = state === 'requesting_credentials' ? 'Menyiapkan...' : 'Mulai Sesi';
        }

        this.stopButton?.classList.toggle('hidden', state !== 'audio_streaming');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-roleplay-runtime]').forEach((root) => {
        new RoleplayRuntime(root).init();
    });
});

export { RoleplayRuntime };
