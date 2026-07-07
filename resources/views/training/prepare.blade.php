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
                            Halaman ini akan menjadi layar persiapan sebelum panggilan. Token Gemini Live belum dibuat pada tahap ini.
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

                    <div class="rounded-lg border border-gray-200 p-4 text-sm text-gray-600">
                        Siapkan mikrofon Anda. Pemeriksaan izin mikrofon dan koneksi Live API akan ditambahkan pada tahap berikutnya.
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
</x-app-layout>
