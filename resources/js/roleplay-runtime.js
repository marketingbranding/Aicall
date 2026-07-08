import { GeminiLiveClient } from './gemini-live-client';

class RoleplayRuntime {
    constructor(root) {
        this.root = root;
        this.credentialsUrl = root.dataset.credentialsUrl;
        this.state = 'idle';
        this.ephemeralToken = null;
        this.credentials = null;
        this.liveClient = null;
        this.liveDebug = root.dataset.liveDebug === 'true';

        this.status = root.querySelector('[data-runtime-status]');
        this.detail = root.querySelector('[data-runtime-detail]');
        this.startButton = root.querySelector('[data-roleplay-start]');
    }

    init() {
        this.setState('idle');
        this.startButton?.addEventListener('click', () => this.requestCredentials());

        document.addEventListener('roleplay:microphone-allowed', () => {
            this.startButton?.classList.remove('hidden');
        });
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
            onSetupComplete: () => this.setState('live_connected'),
            onError: () => this.setState('live_connection_failed'),
            onClose: (event) => {
                if (this.state === 'live_connection_failed') return;

                this.setState(
                    event.expired ? 'live_connection_failed' : 'live_closed',
                    event.expired ? 'Kredensial sementara sudah tidak berlaku. Muat ulang persiapan sesi lalu coba lagi.' : null,
                );
            },
        });
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

        const messages = {
            idle: ['Siap memulai', 'Setelah mikrofon siap, tekan Mulai Sesi untuk menyiapkan koneksi Live.'],
            requesting_credentials: ['Menyiapkan koneksi', 'Mengambil kredensial sementara. Panggilan belum dimulai.'],
            credentials_ready: ['Kredensial siap', 'Kredensial sementara sudah siap di memori browser. Koneksi suara akan ditambahkan pada tahap berikutnya.'],
            credentials_failed: ['Belum bisa memulai', customDetail || 'Kredensial sementara belum berhasil dibuat. Coba lagi sebentar lagi.'],
            connecting_live: ['Menghubungkan', 'Membuka sesi Live. Audio belum dikirim.'],
            live_connected: ['Sesi Live tersambung', 'Handshake Live berhasil. Pengiriman audio akan ditambahkan pada tahap berikutnya.'],
            live_connection_failed: ['Koneksi belum berhasil', customDetail || 'Sesi Live belum bisa tersambung. Coba lagi sebentar lagi.'],
            live_closed: ['Koneksi Live tertutup', 'Sesi Live sudah tertutup. Muat ulang halaman jika ingin mencoba lagi.'],
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
            ].includes(state);
            this.startButton.textContent = state === 'requesting_credentials' ? 'Menyiapkan...' : 'Mulai Sesi';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-roleplay-runtime]').forEach((root) => {
        new RoleplayRuntime(root).init();
    });
});

export { RoleplayRuntime };
