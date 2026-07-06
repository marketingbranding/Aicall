@php
    $current = $persona->currentVersion ?? null;
@endphp

<div class="space-y-8">
    {{-- Section 1: Identitas --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">1. Identitas</h3>
        <p class="text-sm text-gray-500 mb-4">Informasi dasar konsumen simulasi.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Usia</label>
                <input type="number" name="identity[age]" value="{{ old('identity.age', $current?->identity_json['age'] ?? '') }}" min="18" max="100"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                <select name="identity[gender]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Pria" @selected(old('identity.gender', $current?->identity_json['gender'] ?? '') == 'Pria')>Pria</option>
                    <option value="Wanita" @selected(old('identity.gender', $current?->identity_json['gender'] ?? '') == 'Wanita')>Wanita</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status Pernikahan</label>
                <select name="identity[marital_status]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Belum Menikah" @selected(old('identity.marital_status', $current?->identity_json['marital_status'] ?? '') == 'Belum Menikah')>Belum Menikah</option>
                    <option value="Menikah" @selected(old('identity.marital_status', $current?->identity_json['marital_status'] ?? '') == 'Menikah')>Menikah</option>
                    <option value="Cerai" @selected(old('identity.marital_status', $current?->identity_json['marital_status'] ?? '') == 'Cerai')>Cerai</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Anak</label>
                <input type="number" name="identity[children]" value="{{ old('identity.children', $current?->identity_json['children'] ?? '') }}" min="0" max="20"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pekerjaan</label>
                <input type="text" name="identity[occupation]" value="{{ old('identity.occupation', $current?->identity_json['occupation'] ?? '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pekerjaan</label>
                <select name="identity[employment_type]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Karyawan Swasta" @selected(old('identity.employment_type', $current?->identity_json['employment_type'] ?? '') == 'Karyawan Swasta')>Karyawan Swasta</option>
                    <option value="PNS" @selected(old('identity.employment_type', $current?->identity_json['employment_type'] ?? '') == 'PNS')>PNS</option>
                    <option value="Wirausaha" @selected(old('identity.employment_type', $current?->identity_json['employment_type'] ?? '') == 'Wirausaha')>Wirausaha</option>
                    <option value="Buruh" @selected(old('identity.employment_type', $current?->identity_json['employment_type'] ?? '') == 'Buruh')>Buruh</option>
                    <option value="Tidak Bekerja" @selected(old('identity.employment_type', $current?->identity_json['employment_type'] ?? '') == 'Tidak Bekerja')>Tidak Bekerja</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rentang Penghasilan</label>
                <select name="identity[income_range]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="< 2 Juta" @selected(old('identity.income_range', $current?->identity_json['income_range'] ?? '') == '< 2 Juta')>< Rp2 Juta</option>
                    <option value="2-4 Juta" @selected(old('identity.income_range', $current?->identity_json['income_range'] ?? '') == '2-4 Juta')>Rp2-4 Juta</option>
                    <option value="4-6 Juta" @selected(old('identity.income_range', $current?->identity_json['income_range'] ?? '') == '4-6 Juta')>Rp4-6 Juta</option>
                    <option value="6-10 Juta" @selected(old('identity.income_range', $current?->identity_json['income_range'] ?? '') == '6-10 Juta')>Rp6-10 Juta</option>
                    <option value="> 10 Juta" @selected(old('identity.income_range', $current?->identity_json['income_range'] ?? '') == '> 10 Juta')>> Rp10 Juta</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pekerjaan Pasangan</label>
                <input type="text" name="identity[spouse_occupation]" value="{{ old('identity.spouse_occupation', $current?->identity_json['spouse_occupation'] ?? '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Penghasilan Pasangan</label>
                <select name="identity[spouse_income]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="< 2 Juta" @selected(old('identity.spouse_income', $current?->identity_json['spouse_income'] ?? '') == '< 2 Juta')>< Rp2 Juta</option>
                    <option value="2-4 Juta" @selected(old('identity.spouse_income', $current?->identity_json['spouse_income'] ?? '') == '2-4 Juta')>Rp2-4 Juta</option>
                    <option value="4-6 Juta" @selected(old('identity.spouse_income', $current?->identity_json['spouse_income'] ?? '') == '4-6 Juta')>Rp4-6 Juta</option>
                    <option value="6-10 Juta" @selected(old('identity.spouse_income', $current?->identity_json['spouse_income'] ?? '') == '6-10 Juta')>Rp6-10 Juta</option>
                    <option value="> 10 Juta" @selected(old('identity.spouse_income', $current?->identity_json['spouse_income'] ?? '') == '> 10 Juta')>> Rp10 Juta</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tempat Tinggal Saat Ini</label>
                <input type="text" name="identity[current_residence]" value="{{ old('identity.current_residence', $current?->identity_json['current_residence'] ?? '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Latar Belakang Pendidikan</label>
                <input type="text" name="identity[education_background]" value="{{ old('identity.education_background', $current?->identity_json['education_background'] ?? '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Identitas</label>
                <textarea name="identity[notes]" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('identity.notes', $current?->identity_json['notes'] ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Section 2: Kondisi & Kebutuhan Rumah --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">2. Kondisi & Kebutuhan Rumah</h3>
        <p class="text-sm text-gray-500 mb-4">Situasi tempat tinggal dan kebutuhan perumahan konsumen.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Situasi Tempat Tinggal Saat Ini</label>
                <select name="housing_context[current_housing_situation]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Kontrak" @selected(old('housing_context.current_housing_situation', $current?->housing_context_json['current_housing_situation'] ?? '') == 'Kontrak')>Kontrak</option>
                    <option value="Kost" @selected(old('housing_context.current_housing_situation', $current?->housing_context_json['current_housing_situation'] ?? '') == 'Kost')>Kost</option>
                    <option value="Tinggal dengan Orang Tua" @selected(old('housing_context.current_housing_situation', $current?->housing_context_json['current_housing_situation'] ?? '') == 'Tinggal dengan Orang Tua')>Tinggal dengan Orang Tua</option>
                    <option value="Rumah Sendiri" @selected(old('housing_context.current_housing_situation', $current?->housing_context_json['current_housing_situation'] ?? '') == 'Rumah Sendiri')>Rumah Sendiri</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Target</label>
                <input type="text" name="housing_context[target_location]" value="{{ old('housing_context.target_location', $current?->housing_context_json['target_location'] ?? '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pindah</label>
                <input type="text" name="housing_context[reason_for_moving]" value="{{ old('housing_context.reason_for_moving', $current?->housing_context_json['reason_for_moving'] ?? '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rentang Anggaran</label>
                            <input type="text" name="housing_context[budget_range]" value="{{ old('housing_context.budget_range', $current?->housing_context_json['budget_range'] ?? '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kebutuhan</label>
                <textarea name="housing_context[needs]" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('housing_context.needs', $current?->housing_context_json['needs'] ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Target Waktu</label>
                <select name="housing_context[timeline]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Segera" @selected(old('housing_context.timeline', $current?->housing_context_json['timeline'] ?? '') == 'Segera')>Segera</option>
                    <option value="1-3 Bulan" @selected(old('housing_context.timeline', $current?->housing_context_json['timeline'] ?? '') == '1-3 Bulan')>1-3 Bulan</option>
                    <option value="3-6 Bulan" @selected(old('housing_context.timeline', $current?->housing_context_json['timeline'] ?? '') == '3-6 Bulan')>3-6 Bulan</option>
                    <option value="> 6 Bulan" @selected(old('housing_context.timeline', $current?->housing_context_json['timeline'] ?? '') == '> 6 Bulan')>> 6 Bulan</option>
                    <option value="Masih Bertanya" @selected(old('housing_context.timeline', $current?->housing_context_json['timeline'] ?? '') == 'Masih Bertanya')>Masih Bertanya-tanya</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Konteks Keluarga</label>
                <input type="text" name="housing_context[family_context]" value="{{ old('housing_context.family_context', $current?->housing_context_json['family_context'] ?? '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="housing_context[notes]" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('housing_context.notes', $current?->housing_context_json['notes'] ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Section 3: Pengetahuan & Keyakinan --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">3. Pengetahuan & Keyakinan</h3>
        <p class="text-sm text-gray-500 mb-4">Pengetahuan konsumen tentang KPR, subsidi, dan keyakinan terkait properti.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pengetahuan KPR</label>
                <select name="knowledge_beliefs[kpr_knowledge]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Tidak Tahu" @selected(old('knowledge_beliefs.kpr_knowledge', $current?->knowledge_beliefs_json['kpr_knowledge'] ?? '') == 'Tidak Tahu')>Tidak Tahu</option>
                    <option value="Sedikit Tahu" @selected(old('knowledge_beliefs.kpr_knowledge', $current?->knowledge_beliefs_json['kpr_knowledge'] ?? '') == 'Sedikit Tahu')>Sedikit Tahu</option>
                    <option value="Cukup Tahu" @selected(old('knowledge_beliefs.kpr_knowledge', $current?->knowledge_beliefs_json['kpr_knowledge'] ?? '') == 'Cukup Tahu')>Cukup Tahu</option>
                    <option value="Salah Paham" @selected(old('knowledge_beliefs.kpr_knowledge', $current?->knowledge_beliefs_json['kpr_knowledge'] ?? '') == 'Salah Paham')>Salah Paham (Misinformasi)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pengetahuan Subsidi</label>
                <select name="knowledge_beliefs[subsidy_knowledge]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Tidak Tahu" @selected(old('knowledge_beliefs.subsidy_knowledge', $current?->knowledge_beliefs_json['subsidy_knowledge'] ?? '') == 'Tidak Tahu')>Tidak Tahu</option>
                    <option value="Sedikit Tahu" @selected(old('knowledge_beliefs.subsidy_knowledge', $current?->knowledge_beliefs_json['subsidy_knowledge'] ?? '') == 'Sedikit Tahu')>Sedikit Tahu</option>
                    <option value="Cukup Tahu" @selected(old('knowledge_beliefs.subsidy_knowledge', $current?->knowledge_beliefs_json['subsidy_knowledge'] ?? '') == 'Cukup Tahu')>Cukup Tahu</option>
                    <option value="Salah Paham" @selected(old('knowledge_beliefs.subsidy_knowledge', $current?->knowledge_beliefs_json['subsidy_knowledge'] ?? '') == 'Salah Paham')>Salah Paham (Misinformasi)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pengetahuan SLIK OJK</label>
                <select name="knowledge_beliefs[slik_knowledge]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Tidak Tahu" @selected(old('knowledge_beliefs.slik_knowledge', $current?->knowledge_beliefs_json['slik_knowledge'] ?? '') == 'Tidak Tahu')>Tidak Tahu</option>
                    <option value="Sedikit Tahu" @selected(old('knowledge_beliefs.slik_knowledge', $current?->knowledge_beliefs_json['slik_knowledge'] ?? '') == 'Sedikit Tahu')>Sedikit Tahu</option>
                    <option value="Cukup Tahu" @selected(old('knowledge_beliefs.slik_knowledge', $current?->knowledge_beliefs_json['slik_knowledge'] ?? '') == 'Cukup Tahu')>Cukup Tahu</option>
                    <option value="Salah Paham" @selected(old('knowledge_beliefs.slik_knowledge', $current?->knowledge_beliefs_json['slik_knowledge'] ?? '') == 'Salah Paham')>Salah Paham (Misinformasi)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pengetahuan Developer</label>
                <select name="knowledge_beliefs[developer_knowledge]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Tidak Tahu" @selected(old('knowledge_beliefs.developer_knowledge', $current?->knowledge_beliefs_json['developer_knowledge'] ?? '') == 'Tidak Tahu')>Tidak Tahu</option>
                    <option value="Pernah Dengar" @selected(old('knowledge_beliefs.developer_knowledge', $current?->knowledge_beliefs_json['developer_knowledge'] ?? '') == 'Pernah Dengar')>Pernah Dengar</option>
                    <option value="Tahu" @selected(old('knowledge_beliefs.developer_knowledge', $current?->knowledge_beliefs_json['developer_knowledge'] ?? '') == 'Tahu')>Tahu</option>
                    <option value="Pernah Beli" @selected(old('knowledge_beliefs.developer_knowledge', $current?->knowledge_beliefs_json['developer_knowledge'] ?? '') == 'Pernah Beli')>Pernah Beli Sebelumnya</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pengalaman Membeli</label>
                <select name="knowledge_beliefs[buying_experience]"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    <option value="">-- Pilih --</option>
                    <option value="Belum Pernah" @selected(old('knowledge_beliefs.buying_experience', $current?->knowledge_beliefs_json['buying_experience'] ?? '') == 'Belum Pernah')>Belum Pernah</option>
                    <option value="Pertama Kali" @selected(old('knowledge_beliefs.buying_experience', $current?->knowledge_beliefs_json['buying_experience'] ?? '') == 'Pertama Kali')>Pertama Kali</option>
                    <option value="Pernah Gagal" @selected(old('knowledge_beliefs.buying_experience', $current?->knowledge_beliefs_json['buying_experience'] ?? '') == 'Pernah Gagal')>Pernah Gagal Sebelumnya</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Kesalahpahaman (Misconceptions)</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: KPR butuh DP besar, rumah subsidi kualitas rendah</p>
                <input type="text" name="knowledge_beliefs[misconceptions_text]" value="{{ old('knowledge_beliefs.misconceptions_text', isset($current?->knowledge_beliefs_json['misconceptions']) ? implode(', ', $current->knowledge_beliefs_json['misconceptions']) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Rumor / Informasi dari Media Sosial</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma.</p>
                <input type="text" name="knowledge_beliefs[rumors_text]" value="{{ old('knowledge_beliefs.rumors_text', isset($current?->knowledge_beliefs_json['rumors']) ? implode(', ', $current->knowledge_beliefs_json['rumors']) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sumber Informasi Utama</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: TikTok, teman, keluarga, sales lain</p>
                <input type="text" name="knowledge_beliefs[information_sources_text]" value="{{ old('knowledge_beliefs.information_sources_text', isset($current?->knowledge_beliefs_json['information_sources']) ? implode(', ', $current->knowledge_beliefs_json['information_sources']) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="knowledge_beliefs[notes]" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('knowledge_beliefs.notes', $current?->knowledge_beliefs_json['notes'] ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Section 4: Kepribadian --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">4. Kepribadian</h3>
        <p class="text-sm text-gray-500 mb-4">Kecenderungan kepribadian konsumen (0=Sangat Rendah, 100=Sangat Tinggi).</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $personalityTraits = [
                    'friendliness' => 'Keramahan',
                    'openness' => 'Keterbukaan',
                    'skepticism' => 'Skeptis',
                    'trust_tendency' => 'Kepercayaan',
                    'patience' => 'Kesabaran',
                    'impulsiveness' => 'Impulsif',
                    'talkativeness' => 'Banyak Bicara',
                    'assertiveness' => 'Ketegasan',
                    'curiosity' => 'Rasa Ingin Tahu',
                    'anxiety_tendency' => 'Kecemasan',
                    'politeness' => 'Kesopanan',
                    'social_confidence' => 'Percaya Diri Sosial',
                    'financial_sensitivity' => 'Sensitivitas Finansial',
                    'risk_aversion' => 'Menghindari Risiko',
                    'decision_confidence' => 'Kepercayaan dalam Memutuskan',
                ];
                $personalityValues = old('personality', $current?->personality_profile_json ?? []);
            @endphp
            @foreach ($personalityTraits as $key => $label)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                    <select name="personality[{{ $key }}]"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                        <option value="">-- Pilih --</option>
                        <option value="0" @selected(($personalityValues[$key] ?? '') === 0 || ($personalityValues[$key] ?? '') === '0')>Sangat Rendah</option>
                        <option value="25" @selected(($personalityValues[$key] ?? '') === 25 || ($personalityValues[$key] ?? '') === '25')>Rendah</option>
                        <option value="50" @selected(($personalityValues[$key] ?? '') === 50 || ($personalityValues[$key] ?? '') === '50')>Sedang</option>
                        <option value="75" @selected(($personalityValues[$key] ?? '') === 75 || ($personalityValues[$key] ?? '') === '75')>Tinggi</option>
                        <option value="100" @selected(($personalityValues[$key] ?? '') === 100 || ($personalityValues[$key] ?? '') === '100')>Sangat Tinggi</option>
                    </select>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Section 5: Human Behavior Traits --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">5. Human Behavior Traits</h3>
        <p class="text-sm text-gray-500 mb-4">Kecenderungan perilaku manusiawi konsumen dalam percakapan.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $behaviorTraits = [
                    'interrupting_tendency' => 'Kecenderungan Menyela',
                    'dominance' => 'Dominan',
                    'dismissiveness' => 'Meremehkan',
                    'passive_aggression' => 'Agresif Pasif',
                    'social_superiority' => 'Rasa Superior',
                    'salesperson_distrust' => 'Tidak Percaya Sales',
                    'false_friendliness' => 'Ramah Palsu',
                    'commitment_avoidance' => 'Menghindari Komitmen',
                    'contradiction_tendency' => 'Suka Membantah',
                    'promise_extraction' => 'Meminta Janji',
                    'status_display' => 'Pamer Status',
                    'age_based_condescension' => 'Merendahkan (Usia)',
                    'gender_based_condescension' => 'Merendahkan (Gender)',
                    'personal_boundary_testing' => 'Menguji Batas Personal',
                    'flirtatiousness' => 'Genit / Menggoda',
                    'inappropriate_humor' => 'Humor Tidak Pantas',
                    'suggestiveness' => 'Sugestif',
                    'personal_contact_seeking' => 'Mencari Kontak Personal',
                    'isolation_seeking' => 'Mencari Isolasi',
                ];
                $behaviorValues = old('human_behavior_traits', $current?->human_behavior_traits_json ?? []);
            @endphp
            @foreach ($behaviorTraits as $key => $label)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                    <select name="human_behavior_traits[{{ $key }}]"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                        <option value="">-- Pilih --</option>
                        <option value="0" @selected(($behaviorValues[$key] ?? '') === 0 || ($behaviorValues[$key] ?? '') === '0')>Sangat Rendah</option>
                        <option value="25" @selected(($behaviorValues[$key] ?? '') === 25 || ($behaviorValues[$key] ?? '') === '25')>Rendah</option>
                        <option value="50" @selected(($behaviorValues[$key] ?? '') === 50 || ($behaviorValues[$key] ?? '') === '50')>Sedang</option>
                        <option value="75" @selected(($behaviorValues[$key] ?? '') === 75 || ($behaviorValues[$key] ?? '') === '75')>Tinggi</option>
                        <option value="100" @selected(($behaviorValues[$key] ?? '') === 100 || ($behaviorValues[$key] ?? '') === '100')>Sangat Tinggi</option>
                    </select>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Section 6: Cara Berkomunikasi --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">6. Cara Berkomunikasi</h3>
        <p class="text-sm text-gray-500 mb-4">Gaya komunikasi konsumen dalam percakapan.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $commStyles = [
                    'formality' => ['label' => 'Formal vs Santai', 'options' => ['Sangat Formal', 'Cenderung Formal', 'Seimbang', 'Cenderung Santai', 'Sangat Santai']],
                    'verbosity' => ['label' => 'Ringkas vs Panjang', 'options' => ['Sangat Ringkas', 'Cenderung Ringkas', 'Seimbang', 'Cenderung Panjang', 'Sangat Panjang']],
                    'directness' => ['label' => 'Langsung vs Tidak Langsung', 'options' => ['Sangat Langsung', 'Cenderung Langsung', 'Seimbang', 'Cenderung Tidak Langsung', 'Sangat Tidak Langsung']],
                    'hesitation' => ['label' => 'Tingkat Keraguan', 'options' => ['Tidak Ragu', 'Sedikit Ragu', 'Kadang Ragu', 'Sering Ragu', 'Sangat Sering Ragu']],
                    'interruption_tendency' => ['label' => 'Kecenderungan Menyela', 'options' => ['Tidak Pernah', 'Jarang', 'Kadang', 'Sering', 'Sangat Sering']],
                    'storytelling' => ['label' => 'Bercerita', 'options' => ['Tidak Suka', 'Jarang', 'Kadang', 'Suka', 'Sangat Suka']],
                    'repeated_question_tendency' => ['label' => 'Mengulang Pertanyaan', 'options' => ['Tidak Pernah', 'Jarang', 'Kadang', 'Sering', 'Sangat Sering']],
                    'local_expression_tendency' => ['label' => 'Ekspresi Daerah', 'options' => ['Tidak Pernah', 'Jarang', 'Kadang', 'Sering', 'Sangat Sering']],
                    'disclosure_willingness' => ['label' => 'Kemauan Berbagi Informasi', 'options' => ['Sangat Tertutup', 'Cenderung Tertutup', 'Selektif', 'Cenderung Terbuka', 'Sangat Terbuka']],
                    'sensitive_topic_avoidance' => ['label' => 'Menghindari Topik Sensitif', 'options' => ['Tidak Menghindar', 'Sedikit Menghindar', 'Netral', 'Cenderung Menghindar', 'Sangat Menghindar']],
                ];
                $commValues = old('communication_style', $current?->communication_style_json ?? []);
            @endphp
            @foreach ($commStyles as $key => $config)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $config['label'] }}</label>
                    <select name="communication_style[{{ $key }}]"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach ($config['options'] as $i => $opt)
                            <option value="{{ $opt }}" @selected(old('communication_style.' . $key, $commValues[$key] ?? '') == $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Komunikasi</label>
                <textarea name="communication_style[notes]" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('communication_style.notes', $commValues['notes'] ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Section 7: Objections --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">7. Keberatan (Objections)</h3>
        <p class="text-sm text-gray-500 mb-4">Keberatan/objeksi yang mungkin muncul dari konsumen selama percakapan.</p>

        @php
            $objectionModels = $current?->objections ?? collect();
            $oldObjections = old('objections', []);
        @endphp

        @for ($slot = 0; $slot < 4; $slot++)
            @php
                $objData = $oldObjections[$slot] ?? $objectionModels->values()[$slot] ?? null;
                $objKey = $objData['key'] ?? ($objData?->key ?? '');
                $objTitle = $objData['title'] ?? ($objData?->title ?? '');
                $objContext = $objData['context'] ?? ($objData?->context ?? '');
                $objVisibility = $objData['visibility'] ?? ($objData?->visibility ?? 'VISIBLE');
                $objSeverity = $objData['severity'] ?? ($objData?->severity ?? '50');
                $objEmoImp = $objData['emotional_importance'] ?? ($objData?->emotional_importance ?? '50');
                $objPersistence = $objData['persistence'] ?? ($objData?->persistence ?? '50');
                $objTrigText = $objData['trigger_conditions_text'] ?? (isset($objData['trigger_conditions_json']) ? implode(', ', (array) $objData['trigger_conditions_json']) : '');
                $objDiscText = $objData['disclosure_conditions_text'] ?? (isset($objData['disclosure_conditions_json']) ? implode(', ', (array) $objData['disclosure_conditions_json']) : '');
                $objResText = $objData['resolution_conditions_text'] ?? (isset($objData['resolution_conditions_json']) ? implode(', ', (array) $objData['resolution_conditions_json']) : '');
                $objResolvable = $objData['is_resolvable'] ?? ($objData?->is_resolvable ?? true);
                $objArchived = $objData['is_archived'] ?? (isset($objData['is_active']) ? !$objData['is_active'] : false);
            @endphp
            <div class="border border-gray-200 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-semibold text-gray-800 mb-3">Keberatan {{ $slot + 1 }}</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Key</label>
                        <input type="text" name="objections[{{ $slot }}][key]" value="{{ $objKey }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Kode unik, contoh: CICILAN_BERAT</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                        <input type="text" name="objections[{{ $slot }}][title]" value="{{ $objTitle }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Nama keberatan, contoh: Cicilan Terlalu Berat</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konteks / Deskripsi</label>
                        <textarea name="objections[{{ $slot }}][context]" rows="2"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ $objContext }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Visibilitas</label>
                        <select name="objections[{{ $slot }}][visibility]"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                            <option value="VISIBLE" @selected($objVisibility === 'VISIBLE')>VISIBLE — Konsumen dapat mengungkapkan secara terbuka</option>
                            <option value="HIDDEN" @selected($objVisibility === 'HIDDEN')>HIDDEN — Tersembunyi, perlu digali</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Arsipkan</label>
                        <div class="flex items-center mt-2">
                            <input type="checkbox" name="objections[{{ $slot }}][is_archived]" value="1" @checked($objArchived)
                                class="rounded border-gray-300 text-sage-600 shadow-sm focus:ring-sage-500">
                            <span class="ml-2 text-sm text-gray-600">Arsipkan keberatan ini</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Severitas</label>
                        <select name="objections[{{ $slot }}][severity]"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                            <option value="">-- Pilih --</option>
                            <option value="25" @selected((int) $objSeverity === 25)>Rendah</option>
                            <option value="50" @selected((int) $objSeverity === 50)>Sedang</option>
                            <option value="75" @selected((int) $objSeverity === 75)>Tinggi</option>
                            <option value="100" @selected((int) $objSeverity === 100)>Sangat Tinggi</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kepentingan Emosional</label>
                        <select name="objections[{{ $slot }}][emotional_importance]"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                            <option value="">-- Pilih --</option>
                            <option value="25" @selected((int) $objEmoImp === 25)>Rendah</option>
                            <option value="50" @selected((int) $objEmoImp === 50)>Sedang</option>
                            <option value="75" @selected((int) $objEmoImp === 75)>Tinggi</option>
                            <option value="100" @selected((int) $objEmoImp === 100)>Sangat Tinggi</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Persistensi</label>
                        <select name="objections[{{ $slot }}][persistence]"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                            <option value="">-- Pilih --</option>
                            <option value="25" @selected((int) $objPersistence === 25)>Mudah Hilang</option>
                            <option value="50" @selected((int) $objPersistence === 50)>Normal</option>
                            <option value="75" @selected((int) $objPersistence === 75)>Cukup Persisten</option>
                            <option value="100" @selected((int) $objPersistence === 100)>Sangat Persisten</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dapat Diresolusi</label>
                        <div class="flex items-center mt-2">
                            <input type="checkbox" name="objections[{{ $slot }}][is_resolvable]" value="1" @checked($objResolvable)
                                class="rounded border-gray-300 text-sage-600 shadow-sm focus:ring-sage-500">
                            <span class="ml-2 text-sm text-gray-600">Keberatan ini dapat diresolusi</span>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi Pemicu</label>
                        <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: sales menyebut harga, sales bertanya tentang cicilan</p>
                        <input type="text" name="objections[{{ $slot }}][trigger_conditions_text]" value="{{ $objTrigText }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi Pengungkapan</label>
                        <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: trust > 40, sales bertanya relevan</p>
                        <input type="text" name="objections[{{ $slot }}][disclosure_conditions_text]" value="{{ $objDiscText }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi Resolusi</label>
                        <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: acknowledge, clear explanation, sales janji follow-up</p>
                        <input type="text" name="objections[{{ $slot }}][resolution_conditions_text]" value="{{ $objResText }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                    </div>
                </div>
            </div>
        @endfor
    </div>

    {{-- Section 8: Initial Dynamic State & Sensitivity --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">8. Initial State & Sensitivitas</h3>
        <p class="text-sm text-gray-500 mb-4">Nilai awal dynamic state dan sensitivitas transisi.</p>

        <div class="mb-6">
            <h4 class="text-md font-medium text-gray-800 mb-3">Nilai Awal Dynamic State</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $stateFields = [
                        'trust' => 'Trust (Kepercayaan)',
                        'interest' => 'Interest (Ketertarikan)',
                        'confusion' => 'Confusion (Kebingungan)',
                        'anxiety' => 'Anxiety (Kecemasan)',
                        'irritation' => 'Irritation (Iritasi)',
                        'pressure_perception' => 'Tekanan',
                        'engagement' => 'Engagement (Keterlibatan)',
                    ];
                    $stateValues = old('initial_state', $current?->initial_dynamic_state_json ?? []);
                @endphp
                @foreach ($stateFields as $key => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                        <select name="initial_state[{{ $key }}]"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                            <option value="">-- Pilih --</option>
                            <option value="0" @selected(($stateValues[$key] ?? '') === 0 || ($stateValues[$key] ?? '') === '0')>0 — Sangat Rendah</option>
                            <option value="10" @selected(($stateValues[$key] ?? '') === 10 || ($stateValues[$key] ?? '') === '10')>10</option>
                            <option value="20" @selected(($stateValues[$key] ?? '') === 20 || ($stateValues[$key] ?? '') === '20')>20</option>
                            <option value="30" @selected(($stateValues[$key] ?? '') === 30 || ($stateValues[$key] ?? '') === '30')>30</option>
                            <option value="40" @selected(($stateValues[$key] ?? '') === 40 || ($stateValues[$key] ?? '') === '40')>40</option>
                            <option value="50" @selected(($stateValues[$key] ?? '') === 50 || ($stateValues[$key] ?? '') === '50')>50 — Sedang</option>
                            <option value="60" @selected(($stateValues[$key] ?? '') === 60 || ($stateValues[$key] ?? '') === '60')>60</option>
                            <option value="70" @selected(($stateValues[$key] ?? '') === 70 || ($stateValues[$key] ?? '') === '70')>70</option>
                            <option value="80" @selected(($stateValues[$key] ?? '') === 80 || ($stateValues[$key] ?? '') === '80')>80</option>
                            <option value="90" @selected(($stateValues[$key] ?? '') === 90 || ($stateValues[$key] ?? '') === '90')>90</option>
                            <option value="100" @selected(($stateValues[$key] ?? '') === 100 || ($stateValues[$key] ?? '') === '100')>100 — Sangat Tinggi</option>
                        </select>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <h4 class="text-md font-medium text-gray-800 mb-3">Sensitivitas Transisi</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $sensitivityFields = [
                        'trust_gain_rate' => 'Kecepatan Trust Naik',
                        'trust_loss_rate' => 'Kecepatan Trust Turun',
                        'irritation_gain_rate' => 'Kecepatan Iritasi Naik',
                        'irritation_recovery_rate' => 'Kecepatan Iritasi Pulih',
                        'skepticism_multiplier' => 'Multiplier Skeptisisme',
                        'engagement_decay_rate' => 'Kecepatan Engagement Turun',
                    ];
                    $sensValues = old('state_sensitivity', $current?->state_sensitivity_json ?? []);
                @endphp
                @foreach ($sensitivityFields as $key => $label)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
                        <select name="state_sensitivity[{{ $key }}]"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                            <option value="">-- Pilih --</option>
                            <option value="0.25" @selected(($sensValues[$key] ?? '') === 0.25 || ($sensValues[$key] ?? '') === '0.25')>Sangat Lambat</option>
                            <option value="0.5" @selected(($sensValues[$key] ?? '') === 0.5 || ($sensValues[$key] ?? '') === '0.5')>Lambat</option>
                            <option value="1" @selected(($sensValues[$key] ?? '') === 1 || ($sensValues[$key] ?? '') === '1')>Normal</option>
                            <option value="1.5" @selected(($sensValues[$key] ?? '') === 1.5 || ($sensValues[$key] ?? '') === '1.5')>Cepat</option>
                            <option value="2" @selected(($sensValues[$key] ?? '') === 2 || ($sensValues[$key] ?? '') === '2')>Sangat Cepat</option>
                        </select>
                    </div>
                @endforeach

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Topik yang Memicu Kecemasan</label>
                    <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Contoh: cicilan, SLIK, DP</p>
                    <input type="text" name="state_sensitivity[anxiety_sensitivity_topics_text]" value="{{ old('state_sensitivity.anxiety_sensitivity_topics_text', isset($sensValues['anxiety_sensitivity_topics']) ? implode(', ', (array) $sensValues['anxiety_sensitivity_topics']) : '') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Sensitivitas</label>
                    <textarea name="state_sensitivity[notes]" rows="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('state_sensitivity.notes', $sensValues['notes'] ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 9: Salience Overrides --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">9. Salience Overrides</h3>
        <p class="text-sm text-gray-500 mb-4">Prioritas perilaku yang muncul dalam interaksi.</p>

        @php
            $salienceValues = old('salience_overrides', $current?->salience_overrides_json ?? []);
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Perilaku Utama (Primary)</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. 2-3 trait yang dominan.</p>
                <input type="text" name="salience_overrides[primary_traits_text]" value="{{ old('salience_overrides.primary_traits_text', isset($salienceValues['primary_traits']) ? implode(', ', (array) $salienceValues['primary_traits']) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Perilaku Sekunder (Secondary)</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. 2-3 trait yang muncul kontekstual.</p>
                <input type="text" name="salience_overrides[secondary_traits_text]" value="{{ old('salience_overrides.secondary_traits_text', isset($salienceValues['secondary_traits']) ? implode(', ', (array) $salienceValues['secondary_traits']) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Latar Belakang (Background)</label>
                <p class="text-xs text-gray-500 mb-2">Pisahkan dengan koma. Konteks tapi tidak konstan.</p>
                <input type="text" name="salience_overrides[background_traits_text]" value="{{ old('salience_overrides.background_traits_text', isset($salienceValues['background_traits']) ? implode(', ', (array) $salienceValues['background_traits']) : '') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">
            </div>

            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="salience_overrides[notes]" rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-sage-500 focus:ring-sage-500 text-sm">{{ old('salience_overrides.notes', $salienceValues['notes'] ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>
