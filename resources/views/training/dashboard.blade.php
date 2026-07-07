<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Latihan Peran
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($scenarios->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-500 text-center">
                        Belum ada latihan yang tersedia saat ini.
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($scenarios as $scenario)
                        @php $version = $scenario->currentVersion; @endphp
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
                            <div class="p-6">
                                <div class="flex items-start justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ $scenario->name }}
                                    </h3>
                                    @if ($version)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sage-100 text-sage-800">
                                            {{ $version->difficulty_level }}
                                        </span>
                                    @endif
                                </div>

                                @if ($version && $version->description)
                                    <p class="text-sm text-gray-600 mb-4 line-clamp-3">
                                        {{ $version->description }}
                                    </p>
                                @endif

                                @if ($version)
                                    <div class="space-y-2 text-sm text-gray-500">
                                        @if ($version->max_duration_seconds)
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span>{{ gmdate('i:s', $version->max_duration_seconds) }} menit</span>
                                            </div>
                                        @endif

                                        @if ($version->allowed_persona_modes_json)
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                <span>
                                                    @php
                                                        $modeLabels = [
                                                            'CHOOSE_PERSONA' => 'Pilih Persona',
                                                            'RANDOM_PERSONA' => 'Persona Acak',
                                                            'HIDDEN_PERSONA' => 'Persona Tersembunyi',
                                                        ];
                                                        $modes = array_map(fn($m) => $modeLabels[$m] ?? $m, $version->allowed_persona_modes_json);
                                                    @endphp
                                                    {{ implode(', ', $modes) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <a href="{{ route('training.scenarios.briefing', $scenario) }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-sage-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Mulai Latihan
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
