<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Persiapan Latihan
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">
                    <div>
                        <p class="text-sm text-sage-700 font-medium">Sesi latihan berhasil dibuat.</p>
                        <h3 class="text-2xl font-semibold text-gray-900 mt-2">{{ $scenarioName }}</h3>
                        <p class="text-sm text-gray-500 mt-2">
                            Halaman ini menyiapkan izin mikrofon dan kredensial sementara sebelum panggilan. Panggilan suara belum dimulai.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 text-sm">
                        <div class="bg-sage-50 rounded-lg p-4">
                            <div class="text-gray-500">ID Sesi</div>
                            <div class="font-medium text-gray-900 mt-1">{{ $session->public_id }}</div>
                        </div>

                        <div class="bg-sage-50 rounded-lg p-4">
                            <div class="text-gray-500">Status</div>
                            <div class="font-medium text-gray-900 mt-1">{{ $session->status }}</div>
                        </div>

                        <div class="bg-sage-50 rounded-lg p-4">
                            <div class="text-gray-500">Mode Persona</div>
                            <div class="font-medium text-gray-900 mt-1">{{ $session->persona_mode }}</div>
                        </div>

                        <div class="bg-sage-50 rounded-lg p-4">
                            <div class="text-gray-500">Tingkat Kesulitan</div>
                            <div class="font-medium text-gray-900 mt-1">{{ $session->difficulty_level }}</div>
                        </div>
                    </div>

                    <div id="microphone-permission-ui" class="rounded-2xl border border-sage-100 bg-sage-50/60 p-5 sm:p-6" data-microphone-ui>
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-sage-700">Pemeriksaan Mikrofon</p>
                                <h4 class="text-lg font-semibold text-gray-900">Tarik napas sebentar. Kita cek mikrofon dulu.</h4>
                                <p class="text-sm text-gray-600">
                                    Pemeriksaan ini hanya memakai izin browser. Token Gemini Live belum dibuat dan panggilan belum dimulai.
                                </p>
                            </div>

                            <div class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 shadow-sm" data-microphone-badge>
                                Menyiapkan
                            </div>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div data-microphone-state="preparing" class="space-y-2">
                                <p class="text-sm font-medium text-gray-900">Menyiapkan sesi</p>
                                <p class="text-sm text-gray-600">Saat Anda siap, tekan tombol di bawah untuk memeriksa akses mikrofon.</p>
                            </div>

                            <div data-microphone-state="checking" class="hidden space-y-2">
                                <p class="text-sm font-medium text-gray-900">Memeriksa mikrofon</p>
                                <p class="text-sm text-gray-600">Browser mungkin menampilkan permintaan izin. Pilih izinkan agar latihan suara bisa berjalan nanti.</p>
                            </div>

                            <div data-microphone-state="allowed" class="hidden space-y-2">
                                <p class="text-sm font-medium text-sage-800">Mikrofon siap</p>
                                <p class="text-sm text-gray-600">Izin mikrofon sudah diberikan. Anda bisa menyiapkan kredensial sementara untuk sesi Live.</p>
                            </div>

                            <div data-microphone-state="denied" class="hidden space-y-2">
                                <p class="text-sm font-medium text-amber-800">Izin mikrofon belum diberikan</p>
                                <p class="text-sm text-gray-600">
                                    Buka pengaturan izin situs di browser Anda, izinkan mikrofon untuk halaman ini, lalu coba lagi.
                                </p>
                            </div>

                            <div data-microphone-state="error" class="hidden space-y-2">
                                <p class="text-sm font-medium text-amber-800">Mikrofon belum tersedia</p>
                                <p class="text-sm text-gray-600" data-microphone-error>
                                    Pastikan perangkat mikrofon terhubung dan browser mendukung akses mikrofon.
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            <button type="button" data-microphone-check
                                class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-sage-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-sage-700 focus:bg-sage-700 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 active:bg-sage-800 sm:w-auto">
                                Periksa Mikrofon
                            </button>

                            <button type="button" data-microphone-retry
                                class="hidden inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 sm:w-auto">
                                Coba Lagi
                            </button>
                        </div>

                        <p class="mt-4 text-xs text-gray-500">
                            Jika Anda memakai headset, pastikan headset sudah terpasang sebelum memeriksa mikrofon.
                        </p>
                    </div>

                    <div id="roleplay-runtime"
                        class="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6"
                        data-roleplay-runtime
                        data-gemini-live-client="pending"
                        data-microphone-capture="pending"
                        data-audio-stream="pending"
                        data-ai-playback="pending"
                        data-barge-in="idle"
                        data-live-transcript="debug-hidden"
                        data-transcript-events="0"
                        data-transcript-latest-speaker="none"
                        data-transcript-latest-status="none"
                        data-live-goaway="false"
                        data-live-goaway-reason="none"
                        data-live-goaway-reconnect="none"
                        data-live-toolcalls="0"
                        data-live-toolcall-latest="none"
                        data-live-reconnect="none"
                        data-conversation-state="idle"
                        data-input-audio-format="pcm16-16000-le"
                        data-output-audio-format="pcm16-24000-le"
                        data-live-debug="false"
                        data-runtime-state="idle"
                        data-session-warning="false"
                        data-session-duration-seconds="{{ $maxDurationSeconds }}"
                        data-credentials-url="{{ route('training.sessions.live-credentials.store', $session->public_id) }}">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-sage-700">Koneksi Live</p>
                                <h4 class="text-lg font-semibold text-gray-900" data-runtime-status>Siap memulai</h4>
                                <p class="text-sm text-gray-600" data-runtime-detail>
                                    Setelah mikrofon siap, tekan Mulai Sesi untuk menyiapkan koneksi Live.
                                </p>
                            </div>

                            <button type="button" data-roleplay-start
                                class="hidden inline-flex w-full items-center justify-center rounded-md border border-transparent bg-sage-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-sage-700 focus:bg-sage-700 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 active:bg-sage-800 disabled:opacity-60 sm:w-auto">
                                Mulai Sesi
                            </button>

                            <button type="button" data-roleplay-stop
                                class="hidden inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 sm:w-auto">
                                Hentikan Audio
                            </button>
                        </div>

                        <p class="mt-4 text-xs text-gray-500">
                            Kredensial sementara hanya disimpan di memori browser. Mikrofon dikirim sebagai PCM 16 kHz setelah sesi Live tersambung. Audio Gemini diputar sebagai PCM 24 kHz dan dihentikan saat Anda menyela. Transkrip dan panggilan fungsi disadari di memori browser tanpa dikirim ke server pada tahap ini.
                        </p>

                        <div class="mt-5 flex items-center justify-center gap-3" data-session-timer-panel>
                            <span class="text-2xl font-mono font-semibold tabular-nums text-gray-900" data-session-timer>--:--</span>
                            <span class="text-xs text-gray-500">tersisa</span>
                        </div>

                        <div data-session-warning-banner class="hidden mt-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800" role="alert">
                            Sesi hampir mencapai batas 15 menit.
                        </div>

                        <div data-live-reconnect-indicator class="hidden mt-3 rounded-lg bg-sage-50 border border-sage-200 px-4 py-3 text-sm text-sage-800" role="status">
                            Menghubungkan kembali...
                        </div>

                        <div class="mt-5 rounded-xl border border-stone-100 bg-stone-50 p-4" data-conversation-state-panel>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-sage-700">Status Percakapan</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900" data-conversation-status>Diam</p>
                                    <p class="mt-1 text-xs text-gray-600" data-conversation-detail>Belum ada percakapan aktif.</p>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs" aria-label="Indikator status percakapan">
                                    <span class="rounded-full bg-stone-100 px-3 py-1 text-gray-600" data-conversation-indicator="listening">Mendengarkan</span>
                                    <span class="rounded-full bg-stone-100 px-3 py-1 text-gray-600" data-conversation-indicator="user_speaking">Anda bicara</span>
                                    <span class="rounded-full bg-stone-100 px-3 py-1 text-gray-600" data-conversation-indicator="waiting_for_ai">Menunggu AI</span>
                                    <span class="rounded-full bg-stone-100 px-3 py-1 text-gray-600" data-conversation-indicator="thinking">AI menyiapkan</span>
                                    <span class="rounded-full bg-stone-100 px-3 py-1 text-gray-600" data-conversation-indicator="ai_speaking">AI bicara</span>
                                    <span class="rounded-full bg-stone-100 px-3 py-1 text-gray-600" data-conversation-indicator="interrupted">Terinterupsi</span>
                                </div>
                            </div>
                        </div>

                        <div class="hidden mt-5 rounded-xl border border-stone-100 bg-stone-50 p-4" data-live-transcript-panel aria-hidden="true">
                            <p class="text-xs font-semibold uppercase tracking-wide text-sage-700">Debug Transkrip Live</p>
                            <p class="mt-1 text-xs text-gray-600">Panel ini hanya aktif saat debug Live dinyalakan. Transkrip tidak dikirim ke server pada tahap ini.</p>
                            <ol class="mt-3 space-y-2 text-xs text-gray-700" data-live-transcript-list></ol>
                        </div>
                    </div>

                    <div>
                        <a href="{{ route('training.dashboard') }}"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                            Kembali ke Latihan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-microphone-ui]');
            if (!root) return;

            const badge = root.querySelector('[data-microphone-badge]');
            const checkButton = root.querySelector('[data-microphone-check]');
            const retryButton = root.querySelector('[data-microphone-retry]');
            const errorText = root.querySelector('[data-microphone-error]');
            const states = root.querySelectorAll('[data-microphone-state]');

            const labels = {
                preparing: 'Menyiapkan',
                checking: 'Memeriksa',
                allowed: 'Mikrofon siap',
                denied: 'Izin ditolak',
                error: 'Belum tersedia',
            };

            const showState = (state) => {
                states.forEach((element) => {
                    element.classList.toggle('hidden', element.dataset.microphoneState !== state);
                });

                if (badge) badge.textContent = labels[state] || labels.preparing;
                checkButton?.classList.toggle('hidden', state !== 'preparing' && state !== 'allowed');
                retryButton?.classList.toggle('hidden', state !== 'denied' && state !== 'error');
            };

            const checkMicrophone = async () => {
                showState('checking');

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    if (errorText) {
                        errorText.textContent = 'Browser ini belum mendukung pemeriksaan mikrofon. Coba gunakan browser terbaru.';
                    }
                    showState('error');
                    return;
                }

                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    stream.getTracks().forEach((track) => track.stop());
                    showState('allowed');
                    document.dispatchEvent(new CustomEvent('roleplay:microphone-allowed'));
                } catch (error) {
                    if (error && (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError')) {
                        showState('denied');
                        return;
                    }

                    if (errorText) {
                        errorText.textContent = 'Mikrofon belum bisa digunakan. Periksa perangkat, izin browser, lalu coba lagi.';
                    }
                    showState('error');
                }
            };

            checkButton?.addEventListener('click', checkMicrophone);
            retryButton?.addEventListener('click', checkMicrophone);
            showState('preparing');
        });
    </script>
</x-app-layout>
