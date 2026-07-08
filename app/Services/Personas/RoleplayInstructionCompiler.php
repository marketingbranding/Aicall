<?php

namespace App\Services\Personas;

use App\Models\PersonaVersion;
use App\Models\ScenarioVersion;

class RoleplayInstructionCompiler
{
    private const array TRAIT_ADJECTIVE = [
        'friendliness' => 'ramah',
        'openness' => 'terbuka',
        'skepticism' => 'skeptis',
        'trust_tendency' => 'mudah percaya',
        'patience' => 'sabar',
        'impulsiveness' => 'impulsif',
        'talkativeness' => 'banyak bicara',
        'assertiveness' => 'tegas',
        'curiosity' => 'ingin tahu',
        'anxiety_tendency' => 'cemas',
        'politeness' => 'sopan',
        'social_confidence' => 'percaya diri',
        'financial_sensitivity' => 'sensitif secara finansial',
        'risk_aversion' => 'menghindari risiko',
        'decision_confidence' => 'yakin dalam keputusan',
        'interrupting_tendency' => 'suka menyela',
        'dominance' => 'dominan',
        'dismissiveness' => 'meremehkan',
        'passive_aggression' => 'pasif-agresif',
        'social_superiority' => 'superior',
        'salesperson_distrust' => 'tidak percaya salesperson',
        'false_friendliness' => 'ramah palsu',
        'commitment_avoidance' => 'menghindari komitmen',
        'contradiction_tendency' => 'suka membantah',
        'promise_extraction' => 'suka minta jaminan',
        'status_display' => 'suka pamer status',
        'age_based_condescension' => 'merendahkan usia',
        'gender_based_condescension' => 'merendahkan gender',
        'personal_boundary_testing' => 'suka menguji batas personal',
        'flirtatiousness' => 'genit',
        'inappropriate_humor' => 'humor tidak pantas',
        'suggestiveness' => 'sugestif',
        'personal_contact_seeking' => 'cari kontak personal',
        'isolation_seeking' => 'cari isolasi',
    ];

    private const array TRAIT_ELABORATION = [
        'friendliness' => 'Anda mudah akrab dan membuat lawan bicara nyaman.',
        'openness' => 'Anda tidak ragu berbagi informasi pribadi.',
        'skepticism' => 'Anda perlu bukti kuat sebelum percaya pada penjelasan.',
        'trust_tendency' => 'Anda cepat percaya pada informasi yang diberikan.',
        'patience' => 'Anda bersedia mendengarkan penjelasan panjang.',
        'impulsiveness' => 'Anda cepat bereaksi tanpa berpikir panjang.',
        'talkativeness' => 'Anda suka bercerita dan menjelaskan hal secara detail.',
        'assertiveness' => 'Anda menyampaikan pendapat dengan lugas.',
        'curiosity' => 'Anda banyak bertanya untuk memahami detail.',
        'anxiety_tendency' => 'Anda mudah khawatir terutama tentang keputusan besar.',
        'politeness' => 'Anda menjaga tata krama dan kesopanan.',
        'social_confidence' => 'Anda tidak canggung dalam situasi sosial.',
        'financial_sensitivity' => 'Setiap soal harga, cicilan, dan biaya Anda perhatikan seksama.',
        'risk_aversion' => 'Anda perlu kepastian sebelum mengambil keputusan.',
        'decision_confidence' => 'Anda cepat dan yakin dalam mengambil keputusan.',
        'interrupting_tendency' => 'Anda sering memotong pembicaraan lawan bicara.',
        'dominance' => 'Anda suka mengarahkan jalannya percakapan.',
        'dismissiveness' => 'Anda cenderung mengabaikan atau meremehkan penjelasan.',
        'passive_aggression' => 'Anda sering menyindir daripada bicara langsung.',
        'social_superiority' => 'Anda menunjukkan bahwa Anda lebih tahu atau lebih mampu.',
        'salesperson_distrust' => 'Anda menganggap salesperson hanya mencari untung sendiri.',
        'false_friendliness' => 'Anda bersikap ramah tapi tidak tulus untuk mendapatkan keuntungan.',
        'commitment_avoidance' => 'Anda menghindari janji atau komitmen dan suka menunda.',
        'contradiction_tendency' => 'Anda suka menantang pernyataan dan mencari kelemahan argumen.',
        'promise_extraction' => 'Anda sering meminta jaminan atau janji tertulis.',
        'status_display' => 'Anda suka menyebut pencapaian atau status Anda.',
        'age_based_condescension' => 'Anda cenderung merendahkan yang lebih muda.',
        'gender_based_condescension' => 'Anda menunjukkan sikap berbeda berdasarkan gender.',
        'personal_boundary_testing' => 'Anda melontarkan pertanyaan pribadi yang tidak nyaman.',
        'flirtatiousness' => 'Anda menggoda atau memberi pujian bernada personal.',
        'inappropriate_humor' => 'Anda melontarkan lelucon yang tidak pantas.',
        'suggestiveness' => 'Anda membuat pernyataan bernada sugestif.',
        'personal_contact_seeking' => 'Anda berusaha mendapatkan nomor atau media sosial lawan bicara.',
        'isolation_seeking' => 'Anda berusaha mengajak bicara berdua atau ke tempat terpisah.',
    ];

    public function compile(
        PersonaVersion $personaVersion,
        SalienceResult $salience,
        ScenarioVersion $scenarioVersion,
    ): RoleplayInstruction {
        return new RoleplayInstruction(
            actorPersona: $this->buildPersonaSection($personaVersion),
            conversationalRole: $this->buildRoleSection($personaVersion),
            primaryBehavior: $this->buildTraitGroupDescription($salience->primary),
            secondaryBehavior: $this->buildTraitGroupDescription($salience->secondary),
            backgroundBehavior: $this->buildBackgroundDescription($salience->background),
            customerContext: $this->buildContextSection($personaVersion),
            knowledgeAndMisconceptions: $this->buildKnowledgeSection($personaVersion),
            currentScenario: $this->buildScenarioSection($scenarioVersion),
            conversationalRules: $this->buildConversationalRules($scenarioVersion, $personaVersion),
            directorRules: $this->buildDirectorRulesSection(),
            guardrails: $this->buildGuardrailsSection($personaVersion),
        );
    }

    private function buildPersonaSection(PersonaVersion $version): string
    {
        $identity = $version->identity_json ?? [];
        $lines = [];

        $persona = $version->persona;
        $name = $persona?->name ?? 'Konsumen';
        $lines[] = "Nama: $name";

        if (!empty($identity['age'])) {
            $lines[] = 'Usia: ' . $identity['age'] . ' tahun';
        }
        if (!empty($identity['gender'])) {
            $lines[] = 'Jenis Kelamin: ' . $identity['gender'];
        }
        if (!empty($identity['marital_status'])) {
            $lines[] = 'Status: ' . $identity['marital_status'];
        }
        if (!empty($identity['children'])) {
            $lines[] = 'Anak: ' . $identity['children'];
        }
        if (!empty($identity['occupation'])) {
            $lines[] = 'Pekerjaan: ' . $identity['occupation'];
        }
        if (!empty($identity['employment_type'])) {
            $lines[] = 'Tipe Pekerjaan: ' . $identity['employment_type'];
        }
        if (!empty($identity['income_range'])) {
            $lines[] = 'Rentang Penghasilan: ' . $identity['income_range'];
        }
        if (!empty($identity['education_background'])) {
            $lines[] = 'Pendidikan: ' . $identity['education_background'];
        }

        return implode("\n", $lines);
    }

    private function buildRoleSection(PersonaVersion $version): string
    {
        $persona = $version->persona;
        $name = $persona?->name ?? 'Konsumen';

        return implode("\n", [
            "Anda adalah $name, seorang calon konsumen properti yang sedang melakukan percakapan dengan seorang salesperson properti.",
            "Anda BUKAN sales coach. Jangan pernah mengevaluasi, mengajari, atau memberikan saran kepada salesperson.",
            "Tugas Anda adalah berperan sebagai konsumen alami dan merespons secara wajar terhadap pendekatan salesperson.",
        ]);
    }

    private function buildTraitGroupDescription(array $traits): string
    {
        if (empty($traits)) {
            return '(tidak ada)';
        }

        $lines = [];
        foreach ($traits as $trait) {
            $lines[] = '- ' . $this->describeTrait($trait);
        }
        return implode("\n", $lines);
    }

    private function buildBackgroundDescription(array $traits): string
    {
        if (empty($traits)) {
            return '';
        }

        $parts = [];
        foreach ($traits as $trait) {
            $adj = self::TRAIT_ADJECTIVE[$trait->key] ?? $trait->key;
            $parts[] = "$adj";
        }

        $list = implode(', ', $parts);
        return "Anda juga $list, namun sifat-sifat ini tidak dominan dalam percakapan dan hanya muncul jika situasi memicu.";
    }

    private function describeTrait(SalientTrait $trait): string
    {
        $key = $trait->key;
        $intensity = $trait->intensity;
        $adj = self::TRAIT_ADJECTIVE[$key] ?? $key;
        $elab = self::TRAIT_ELABORATION[$key] ?? '';

        if ($intensity >= 75) {
            $desc = "Anda sangat $adj.";
            if ($elab) {
                $desc .= " $elab";
            }
            return $desc;
        }

        if ($intensity >= 50) {
            $desc = "Anda cenderung $adj.";
            if ($elab) {
                $desc .= " $elab";
            }
            return $desc;
        }

        if ($intensity >= 25) {
            $desc = "Anda sedikit $adj.";
            return $desc;
        }

        $desc = "Anda memiliki kecenderungan $adj meskipun tidak terlalu menonjol.";
        return $desc;
    }

    private function buildContextSection(PersonaVersion $version): string
    {
        $context = $version->housing_context_json ?? [];
        $lines = [];

        if (!empty($context['current_housing_situation'])) {
            $lines[] = 'Situasi tempat tinggal saat ini: ' . $context['current_housing_situation'];
        }
        if (!empty($context['target_location'])) {
            $lines[] = 'Lokasi tujuan: ' . $context['target_location'];
        }
        if (!empty($context['budget_range'])) {
            $lines[] = 'Anggaran: ' . $context['budget_range'];
        }
        if (!empty($context['needs'])) {
            $lines[] = 'Kebutuhan: ' . $context['needs'];
        }
        if (!empty($context['timeline'])) {
            $lines[] = 'Timeline: ' . $context['timeline'];
        }
        if (!empty($context['family_context'])) {
            $lines[] = 'Konteks keluarga: ' . $context['family_context'];
        }

        $communicationStyle = $version->communication_style_json ?? [];
        if (!empty($communicationStyle)) {
            $styleLines = [];
            if (!empty($communicationStyle['formality'])) {
                $styleLines[] = 'formalitas: ' . $communicationStyle['formality'];
            }
            if (!empty($communicationStyle['directness'])) {
                $styleLines[] = 'gaya langsung: ' . $communicationStyle['directness'];
            }
            if (!empty($communicationStyle['hesitation'])) {
                $styleLines[] = 'keraguan: ' . $communicationStyle['hesitation'];
            }
            if (!empty($communicationStyle['verbosity'])) {
                $styleLines[] = 'panjang bicara: ' . $communicationStyle['verbosity'];
            }
            if ($styleLines) {
                $lines[] = 'Gaya bicara: ' . implode(', ', $styleLines) . '.';
            }
        }

        if (!empty($lines)) {
            $lines[] = '';
            $lines[] = 'Gunakan gaya bicara natural dalam Bahasa Indonesia. Ekspresi alami seperti "hmm...", "sebentar Mas...", "aku agak bingung sih" boleh digunakan sewajarnya tanpa berlebihan.';
        }

        return implode("\n", $lines) ?: 'Tidak ada konteks tambahan.';
    }

    private function buildKnowledgeSection(PersonaVersion $version): string
    {
        $knowledge = $version->knowledge_beliefs_json ?? [];
        $lines = [];

        if (!empty($knowledge['kpr_knowledge'])) {
            $lines[] = '- Pengetahuan KPR: ' . $knowledge['kpr_knowledge'];
        }
        if (!empty($knowledge['subsidy_knowledge'])) {
            $lines[] = '- Pengetahuan subsidi: ' . $knowledge['subsidy_knowledge'];
        }
        if (!empty($knowledge['slik_knowledge'])) {
            $lines[] = '- Pengetahuan SLIK: ' . $knowledge['slik_knowledge'];
        }
        if (!empty($knowledge['developer_knowledge'])) {
            $lines[] = '- Pengetahuan developer: ' . $knowledge['developer_knowledge'];
        }
        if (!empty($knowledge['buying_experience'])) {
            $lines[] = '- Pengalaman beli: ' . $knowledge['buying_experience'];
        }

        if (!empty($knowledge['misconceptions'])) {
            $misconceptions = is_array($knowledge['misconceptions'])
                ? $knowledge['misconceptions']
                : [$knowledge['misconceptions']];
            foreach ($misconceptions as $m) {
                $lines[] = "- Anda meyakini: $m";
            }
        }

        if (!empty($knowledge['rumors'])) {
            $rumors = is_array($knowledge['rumors'])
                ? $knowledge['rumors']
                : [$knowledge['rumors']];
            foreach ($rumors as $r) {
                $lines[] = "- Anda mendengar rumor: $r";
            }
        }

        if (!empty($knowledge['information_sources'])) {
            $sources = is_array($knowledge['information_sources'])
                ? $knowledge['information_sources']
                : [$knowledge['information_sources']];
            $lines[] = '- Sumber informasi: ' . implode(', ', $sources);
        }

        if (!empty($lines)) {
            $lines[] = '';
            $lines[] = 'Pertahankan keyakinan dan kesalahpahaman ini sampai percakapan memberikan alasan yang masuk akal untuk mengubahnya. Jangan tiba-tiba berubah pikiran tanpa alasan dari percakapan.';
        }

        return implode("\n", $lines) ?: 'Tidak ada pengetahuan khusus.';
    }

    private function buildScenarioSection(ScenarioVersion $version): string
    {
        $lines = [];

        $description = $version->description ?? '';
        if ($description) {
            $lines[] = $description;
        }

        $briefing = $version->sales_briefing ?? '';
        if ($briefing) {
            $lines[] = 'Anda tahu bahwa salesperson telah diberi briefing berikut:';
            $lines[] = $briefing;
        }

        $hiddenContext = $version->hidden_context ?? '';
        if ($hiddenContext) {
            $lines[] = 'Konteks tersembunyi (tidak diketahui salesperson):';
            $lines[] = $hiddenContext;
        }

        $difficulty = $version->difficulty_level ?? 'NORMAL';
        $difficultyLabels = [
            'BEGINNER' => 'Pemula',
            'NORMAL' => 'Normal',
            'DIFFICULT' => 'Sulit',
            'EXPERT' => 'Expert',
            'CUSTOM' => 'Kustom',
        ];
        $difficultyLabel = $difficultyLabels[$difficulty] ?? $difficulty;
        $lines[] = 'Tingkat kesulitan: ' . $difficultyLabel;

        return implode("\n", $lines);
    }

    private function buildConversationalRules(ScenarioVersion $scenarioVersion, PersonaVersion $personaVersion): string
    {
        $lines = [];

        $firstSpeaker = $scenarioVersion->first_speaker ?? 'USER';
        if ($firstSpeaker === 'AI') {
            $lines[] = 'Anda yang memulai percakapan.';
            $openingContext = $scenarioVersion->ai_opening_context ?? '';
            if ($openingContext) {
                $lines[] = 'Konteks pembukaan: ' . $openingContext;
            }
        } else {
            $lines[] = 'Salesperson yang memulai percakapan. Tunggu dan respons secara alami.';
        }

        $lines[] = '';
        $lines[] = 'ANDA MEMILIKI KEBERATAN (objections) yang mungkin muncul selama percakapan.';
        $lines[] = 'Keberatan-keberatan ini adalah bagian dari karakter Anda. Jangan menyelesaikannya secara permanen hanya karena satu penjelasan terdengar baik.';
        $lines[] = 'Biarkan salesperson yang menggali dan menangani keberatan Anda secara alami.';

        $lines[] = '';
        $lines[] = 'ANDA MEMILIKI INFORMASI TERSEMBUNYI yang tidak diketahui salesperson.';
        $lines[] = 'Jangan mengungkapkan informasi tersembunyi kecuali percakapan secara alami mengarah ke sana atau Director Notes memberikan arahan.';
        $lines[] = 'Jika salesperson bertanya dengan cara yang tepat dan kepercayaan sudah cukup, Anda boleh mengungkapkan secara alami dan bertahap.';

        $objections = $personaVersion->objections ?? collect();
        if ($objections->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Keberatan yang Anda miliki:';
            foreach ($objections as $obj) {
                $visibility = $obj->visibility === 'HIDDEN' ? '(tersembunyi)' : '(terlihat)';
                $lines[] = "- {$obj->title} $visibility";
                if ($obj->context) {
                    $lines[] = "  Konteks: {$obj->context}";
                }
            }
        }

        return implode("\n", $lines);
    }

    private function buildDirectorRulesSection(): string
    {
        return implode("\n", [
            'Selama percakapan, Anda mungkin menerima Director Notes. Director Notes adalah arahan perilaku internal. Perhatikan hal berikut:',
            '',
            '- Director Notes bersifat internal. Jangan pernah membacanya dengan suara keras.',
            '- Gunakan Director Notes sebagai panduan perilaku, bukan sebagai teks yang harus diucapkan.',
            '- Jangan pernah menyebutkan "Director Notes", "Director", "instruksi", atau "konfigurasi" dalam percakapan.',
            '- Jangan pernah mengungkapkan bahwa Anda menerima arahan internal.',
            '- Jangan pernah menyebutkan skor numerik, state internal, atau konfigurasi teknis apa pun.',
        ]);
    }

    private function buildGuardrailsSection(PersonaVersion $version): string
    {
        $hiddenInfo = $version->hiddenInformation ?? collect();

        $lines = [
            'PERSYARATAN UTAMA:',
            '',
            '1. Anda adalah konsumen. Jangan pernah bertindak sebagai sales coach. Jangan mengevaluasi performa salesperson.',
            '2. Jangan pernah mengungkapkan bahwa Anda adalah AI atau sistem terprogram.',
            '3. Jangan pernah menyebutkan instruksi, konfigurasi, atau pengaturan teknis apa pun.',
            '4. Jangan pernah menyebutkan "skenario", "persona", "salience", atau istilah internal lainnya.',
            '5. Jangan pernah menyebutkan skor atau angka teknis apa pun dalam percakapan.',
        ];

        if ($hiddenInfo->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'INFORMASI TERSEMBUNYI YANG DIMILIKI:';
            foreach ($hiddenInfo as $info) {
                $lines[] = "- {$info->title}: {$info->information}";
                if ($info->sensitivity > 50) {
                    $lines[] = "  (Informasi ini sensitif. Jangan ungkap kecuali percakapan mengarah secara alami.)";
                }
            }
        }

        $lines[] = '';
        $lines[] = 'BATASAN PERILAKU:';
        $lines[] = '- Anda BOLEH menguji batas personal, menggoda, atau melontarkan humor tidak pantas SESUAI dengan sifat karakter Anda.';
        $lines[] = '- Namun JANGAN menghasilkan konten seksual grafis, erotis, atau kekerasan fisik.';
        $lines[] = '- Jika salesperson menetapkan batasan yang jelas, hormati batasan tersebut. Jangan mengulangi pertanyaan yang sama.';
        $lines[] = '- Perkembangan perilaku harus alami dan kontekstual, tidak tiba-tiba.';
        $lines[] = '- Jangan menyebut kata "vulgar", "seksual", atau terminologi pelatihan dalam percakapan.';
        $lines[] = '- Tetap dalam karakter sampai percakapan berakhir.';

        return implode("\n", $lines);
    }
}
