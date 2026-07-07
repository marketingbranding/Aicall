<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $scenario->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (!$version)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-500 text-center">
                        Skenario ini belum memiliki konfigurasi.
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Deskripsi</h3>
                            <p class="text-sm text-gray-600">{{ $version->description ?? 'Tidak ada deskripsi.' }}</p>
                        </div>

                        @if ($version->sales_briefing)
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Briefing</h3>
                                <p class="text-sm text-gray-600">{{ $version->sales_briefing }}</p>
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                            <div class="flex items-center gap-2 bg-sage-50 px-3 py-1.5 rounded-full">
                                <span class="font-medium text-gray-700">Sulit:</span>
                                <span>{{ $version->difficulty_level }}</span>
                            </div>

                            @if ($version->max_duration_seconds)
                                <div class="flex items-center gap-2 bg-sage-50 px-3 py-1.5 rounded-full">
                                    <span class="font-medium text-gray-700">Durasi:</span>
                                    <span>{{ gmdate('i:s', $version->max_duration_seconds) }} menit</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Pilih Mode Persona</h3>

                        <form method="POST" action="{{ route('training.scenarios.sessions.store', $scenario) }}" class="space-y-4">
                            @csrf

                            @if ($errors->any())
                                <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            @php
                                $modeLabels = [
                                    'CHOOSE_PERSONA' => 'Pilih Persona',
                                    'RANDOM_PERSONA' => 'Persona Acak',
                                    'HIDDEN_PERSONA' => 'Persona Tersembunyi',
                                ];
                                $modeDescriptions = [
                                    'CHOOSE_PERSONA' => 'Pilih sendiri persona yang akan digunakan dalam latihan.',
                                    'RANDOM_PERSONA' => 'Sistem akan memilihkan persona secara acak.',
                                    'HIDDEN_PERSONA' => 'Persona akan ditentukan oleh sistem dan tidak ditampilkan kepada Anda.',
                                ];
                                $modes = $version->allowed_persona_modes_json ?? [];
                            @endphp

                            @if (empty($modes))
                                <p class="text-sm text-gray-500">Tidak ada mode persona yang tersedia untuk skenario ini.</p>
                            @else
                                <div class="space-y-3">
                                    @foreach ($modes as $mode)
                                        <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-sage-50 transition-colors duration-150">
                                            <input type="radio" name="persona_mode" value="{{ $mode }}"
                                                class="mt-1 text-sage-600 focus:ring-sage-500"
                                                @if ($loop->first) checked @endif
                                                @if ($mode === 'CHOOSE_PERSONA' && $availablePersonas->isEmpty()) disabled @endif>
                                            <div>
                                                <span class="font-medium text-gray-900">{{ $modeLabels[$mode] ?? $mode }}</span>
                                                <p class="text-sm text-gray-500 mt-0.5">{{ $modeDescriptions[$mode] ?? '' }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                @if (in_array('CHOOSE_PERSONA', $modes) && $availablePersonas->isNotEmpty())
                                    <div id="persona-selection" class="mt-6 space-y-3">
                                        <h4 class="font-medium text-gray-700">Pilih Persona</h4>
                                        @foreach ($availablePersonas as $persona)
                                            @php $pv = $persona->currentVersion; @endphp
                                            <label class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-sage-50 transition-colors duration-150">
                                                <input type="radio" name="persona_id" value="{{ $persona->id }}"
                                                    class="mt-1 text-sage-600 focus:ring-sage-500"
                                                    @if ($loop->first) checked @endif>
                                                <div>
                                                    <span class="font-medium text-gray-900">{{ $persona->name }}</span>
                                                    @if ($pv && $pv->public_profile_text)
                                                        <p class="text-sm text-gray-500 mt-0.5">{{ $pv->public_profile_text }}</p>
                                                    @endif
                                                    @if ($pv && $pv->identity_json)
                                                        <div class="mt-2 text-sm text-gray-500">
                                                            @foreach (array_slice($pv->identity_json, 0, 4) as $key => $value)
                                                                @if (is_string($value))
                                                                    <span class="inline-block bg-gray-100 px-2 py-0.5 rounded text-xs mr-1 mb-1">{{ $value }}</span>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="pt-4">
                                    <button type="submit"
                                        class="inline-flex items-center justify-center w-full px-4 py-2 bg-sage-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-sage-700 focus:bg-sage-700 active:bg-sage-800 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Mulai Latihan
                                    </button>
                                    <p class="text-xs text-gray-400 text-center mt-2">Sesi akan dibuat dulu. Token Gemini Live belum diproses di tahap ini.</p>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
