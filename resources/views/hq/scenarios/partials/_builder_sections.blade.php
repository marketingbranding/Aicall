@php
    $current = $scenario->currentVersion ?? null;
@endphp

<div class="space-y-8">
    {{-- Section 1: Deskripsi & Briefing --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">1. Deskripsi & Briefing</h3>
        <p class="text-sm text-gray-500 mb-4">Informasi dasar dan arahan untuk skenario ini.</p>

        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('description', $current?->description ?? '') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Deskripsi singkat skenario untuk tampilan Sales.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sales Briefing</label>
                <textarea name="sales_briefing" rows="3"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('sales_briefing', $current?->sales_briefing ?? '') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Arahan untuk salesperson sebelum memulai roleplay.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tujuan Training</label>
                <textarea name="training_objective" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('training_objective', $current?->training_objective ?? '') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Tujuan pembelajaran yang ingin dicapai.</p>
            </div>
        </div>
    </div>

    {{-- Section 2: Konteks Tersembunyi --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">2. Konteks Tersembunyi</h3>
        <p class="text-sm text-gray-500 mb-4">Informasi latar yang tidak terlihat oleh Sales. Ini digunakan untuk internal Director.</p>

        <div>
            <textarea name="hidden_context" rows="3"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('hidden_context', $current?->hidden_context ?? '') }}</textarea>
        </div>
    </div>

    {{-- Section 3: Konfigurasi Percakapan --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">3. Konfigurasi Percakapan</h3>
        <p class="text-sm text-gray-500 mb-4">Atur bagaimana percakapan dimulai dan konteks awalnya.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fase Awal</label>
                <select name="starting_phase"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="OPENING" @selected(old('starting_phase', $current?->starting_phase ?? '') == 'OPENING')>OPENING — Pembukaan</option>
                    <option value="RAPPORT" @selected(old('starting_phase', $current?->starting_phase ?? '') == 'RAPPORT')>RAPPORT — Membangun Hubungan</option>
                    <option value="DISCOVERY" @selected(old('starting_phase', $current?->starting_phase ?? '') == 'DISCOVERY')>DISCOVERY — Penggalian Kebutuhan</option>
                    <option value="NEED_EXPLORATION" @selected(old('starting_phase', $current?->starting_phase ?? '') == 'NEED_EXPLORATION')>NEED_EXPLORATION — Eksplorasi Kebutuhan</option>
                    <option value="EXPLANATION" @selected(old('starting_phase', $current?->starting_phase ?? '') == 'EXPLANATION')>EXPLANATION — Penjelasan</option>
                    <option value="OBJECTION_HANDLING" @selected(old('starting_phase', $current?->starting_phase ?? '') == 'OBJECTION_HANDLING')>OBJECTION_HANDLING — Menangani Keberatan</option>
                    <option value="COMMITMENT" @selected(old('starting_phase', $current?->starting_phase ?? '') == 'COMMITMENT')>COMMITMENT — Komitmen</option>
                    <option value="CLOSING" @selected(old('starting_phase', $current?->starting_phase ?? '') == 'CLOSING')>CLOSING — Penutupan</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Fase percakapan saat skenario dimulai.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Yang Bicara Pertama</label>
                <select name="first_speaker"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="AI" @selected(old('first_speaker', $current?->first_speaker ?? 'AI') == 'AI')>AI — Konsumen mulai bicara</option>
                    <option value="USER" @selected(old('first_speaker', $current?->first_speaker ?? 'AI') == 'USER')>USER — Salesperson mulai bicara</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Siapa yang memulai percakapan.</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">AI Opening Context</label>
                <textarea name="ai_opening_context" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('ai_opening_context', $current?->ai_opening_context ?? '') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Konteks pembuka untuk AI jika first_speaker adalah AI.</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Niat Awal Konsumen</label>
                <textarea name="initial_customer_intent" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('initial_customer_intent', $current?->initial_customer_intent ?? '') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Apa yang konsumen inginkan atau cari di awal percakapan.</p>
            </div>
        </div>
    </div>

    {{-- Section 4: Target & Kondisi --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">4. Target & Kondisi</h3>
        <p class="text-sm text-gray-500 mb-4">Perilaku target, poin discovery, topik wajib, dan klaim terlarang.</p>

        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Target Perilaku Sales</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: active listening, kebutuhan discovery, empathy</p>
                <input type="text" name="target_behaviors_text" value="{{ old('target_behaviors_text', isset($current?->target_behaviors_json) ? implode(', ', (array) $current->target_behaviors_json) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poin Penting yang Perlu Ditemukan</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma.</p>
                <input type="text" name="discovery_points_text" value="{{ old('discovery_points_text', isset($current?->important_discovery_points_json) ? implode(', ', (array) $current->important_discovery_points_json) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Topik Wajib</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: KPR subsidi, lokasi, cicilan</p>
                <input type="text" name="mandatory_topics_text" value="{{ old('mandatory_topics_text', isset($current?->mandatory_topics_json) ? implode(', ', (array) $current->mandatory_topics_json) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Klaim Terlarang</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: jaminan ACC KPR, harga pasti naik</p>
                <input type="text" name="prohibited_claims_text" value="{{ old('prohibited_claims_text', isset($current?->prohibited_claims_json) ? implode(', ', (array) $current->prohibited_claims_json) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>
        </div>
    </div>

    {{-- Section 5: Kondisi Sukses & Gagal --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">5. Kondisi Sukses & Gagal</h3>
        <p class="text-sm text-gray-500 mb-4">Kondisi yang menentukan keberhasilan atau kegagalan sesi roleplay.</p>

        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi Sukses</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: menemukan kekhawatiran utama, memberikan penjelasan akurat</p>
                <input type="text" name="success_conditions_text" value="{{ old('success_conditions_text', isset($current?->success_conditions_json) ? implode(', ', (array) $current->success_conditions_json) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi Gagal</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: mengabaikan kekhawatiran, klaim tidak didukung</p>
                <input type="text" name="failure_conditions_text" value="{{ old('failure_conditions_text', isset($current?->failure_conditions_json) ? implode(', ', (array) $current->failure_conditions_json) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>
        </div>
    </div>

    {{-- Section 6: Tingkat Kesulitan --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">6. Tingkat Kesulitan</h3>
        <p class="text-sm text-gray-500 mb-4">Konfigurasi tingkat kesulitan skenario.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Level Kesulitan</label>
                <select name="difficulty_level"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="BEGINNER" @selected(old('difficulty_level', $current?->difficulty_level ?? 'NORMAL') == 'BEGINNER')>BEGINNER — Pemula</option>
                    <option value="NORMAL" @selected(old('difficulty_level', $current?->difficulty_level ?? 'NORMAL') == 'NORMAL')>NORMAL — Normal</option>
                    <option value="DIFFICULT" @selected(old('difficulty_level', $current?->difficulty_level ?? 'NORMAL') == 'DIFFICULT')>DIFFICULT — Sulit</option>
                    <option value="EXPERT" @selected(old('difficulty_level', $current?->difficulty_level ?? 'NORMAL') == 'EXPERT')>EXPERT — Ahli</option>
                    <option value="CUSTOM" @selected(old('difficulty_level', $current?->difficulty_level ?? 'NORMAL') == 'CUSTOM')>CUSTOM — Kustom</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Durasi Maksimal (detik)</label>
                <input type="number" name="max_duration_seconds" value="{{ old('max_duration_seconds', $current?->max_duration_seconds ?? '') }}" min="60" max="900"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                <p class="mt-1 text-xs text-gray-500">Maksimal 900 detik (15 menit).</p>
            </div>
        </div>

        <div class="mt-4">
            <label class="flex items-center">
                <input type="checkbox" name="allow_ai_end_call" value="1" @checked(old('allow_ai_end_call', $current?->allow_ai_end_call ?? false)) class="rounded border-gray-300 text-sage-600 shadow-sm focus:ring-sage-500">
                <span class="ml-2 text-sm text-gray-700">Izinkan AI mengakhiri panggilan</span>
            </label>
        </div>

        <div class="mt-4 border-t border-gray-200 pt-4">
            <h4 class="text-sm font-semibold text-gray-800 mb-3">Kustom Difficulty Config (hanya untuk CUSTOM)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $dc = $current?->difficulty_config_json ?? [];
                @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trust Gain Multiplier</label>
                    <input type="number" name="difficulty_config[trust_gain_multiplier]" value="{{ old('difficulty_config.trust_gain_multiplier', $dc['trust_gain_multiplier'] ?? '') }}" step="0.1" min="0" max="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trust Loss Multiplier</label>
                    <input type="number" name="difficulty_config[trust_loss_multiplier]" value="{{ old('difficulty_config.trust_loss_multiplier', $dc['trust_loss_multiplier'] ?? '') }}" step="0.1" min="0" max="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Disclosure Resistance</label>
                    <input type="number" name="difficulty_config[disclosure_resistance]" value="{{ old('difficulty_config.disclosure_resistance', $dc['disclosure_resistance'] ?? '') }}" step="0.1" min="0" max="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Objection Persistence</label>
                    <input type="number" name="difficulty_config[objection_persistence]" value="{{ old('difficulty_config.objection_persistence', $dc['objection_persistence'] ?? '') }}" step="0.1" min="0" max="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Irritation Sensitivity</label>
                    <input type="number" name="difficulty_config[irritation_sensitivity]" value="{{ old('difficulty_config.irritation_sensitivity', $dc['irritation_sensitivity'] ?? '') }}" step="0.1" min="0" max="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weak Explanation Tolerance</label>
                    <input type="number" name="difficulty_config[weak_explanation_tolerance]" value="{{ old('difficulty_config.weak_explanation_tolerance', $dc['weak_explanation_tolerance'] ?? '') }}" step="0.1" min="0" max="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Closing Resistance</label>
                    <input type="number" name="difficulty_config[closing_resistance]" value="{{ old('difficulty_config.closing_resistance', $dc['closing_resistance'] ?? '') }}" step="0.1" min="0" max="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Boundary Persistence</label>
                    <input type="number" name="difficulty_config[boundary_persistence]" value="{{ old('difficulty_config.boundary_persistence', $dc['boundary_persistence'] ?? '') }}" step="0.1" min="0" max="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                </div>
            </div>
        </div>
    </div>

    {{-- Section 7: Mode Pemilihan Persona --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">7. Mode Pemilihan Persona</h3>
        <p class="text-sm text-gray-500 mb-4">Mode pemilihan persona yang diizinkan untuk skenario ini.</p>

        <div class="space-y-2">
            @php
                $allowedModes = old('allowed_persona_modes', $current?->allowed_persona_modes_json ?? []);
            @endphp
            <label class="flex items-center">
                <input type="checkbox" name="allowed_persona_modes[]" value="CHOOSE_PERSONA" @checked(in_array('CHOOSE_PERSONA', $allowedModes)) class="rounded border-gray-300 text-sage-600 shadow-sm focus:ring-sage-500">
                <span class="ml-2 text-sm text-gray-700">CHOOSE_PERSONA — Sales memilih persona</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="allowed_persona_modes[]" value="RANDOM_PERSONA" @checked(in_array('RANDOM_PERSONA', $allowedModes)) class="rounded border-gray-300 text-sage-600 shadow-sm focus:ring-sage-500">
                <span class="ml-2 text-sm text-gray-700">RANDOM_PERSONA — Persona dipilih acak</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" name="allowed_persona_modes[]" value="HIDDEN_PERSONA" @checked(in_array('HIDDEN_PERSONA', $allowedModes)) class="rounded border-gray-300 text-sage-600 shadow-sm focus:ring-sage-500">
                <span class="ml-2 text-sm text-gray-700">HIDDEN_PERSONA — Persona tersembunyi</span>
            </label>
        </div>
    </div>

    {{-- Section 8: Persona yang Tersedia --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">8. Persona yang Tersedia</h3>
        <p class="text-sm text-gray-500 mb-4">Pilih persona yang dapat digunakan dalam skenario ini.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @php
                $assignedPersonaIds = old('persona_ids', $current?->assignedPersonas?->pluck('persona_id')->toArray() ?? []);
            @endphp
            @foreach ($personas as $persona)
                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="persona_ids[]" value="{{ $persona->id }}" @checked(in_array($persona->id, $assignedPersonaIds))
                        class="rounded border-gray-300 text-sage-600 shadow-sm focus:ring-sage-500">
                    <span class="ml-2 text-sm text-gray-700">
                        <span class="font-mono text-xs text-gray-500">{{ $persona->code }}</span>
                        {{ $persona->name }}
                    </span>
                </label>
            @endforeach
        </div>

        @if ($personas->isEmpty())
            <p class="text-sm text-gray-400 italic">Belum ada persona aktif. Buat persona terlebih dahulu.</p>
        @endif
    </div>
</div>
