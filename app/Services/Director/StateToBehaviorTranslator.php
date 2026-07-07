<?php

namespace App\Services\Director;

class StateToBehaviorTranslator
{
    private const DESCRIPTIONS = [
        'trust_high' => 'cukup percaya',
        'trust_low' => 'kurang percaya',
        'trust_very_low' => 'tidak percaya',
        'trust_very_high' => 'sangat percaya',
        'interest_high' => 'tertarik',
        'interest_low' => 'kurang tertarik',
        'interest_very_low' => 'tidak tertarik',
        'interest_very_high' => 'sangat tertarik',
        'confusion_high' => 'cukup bingung',
        'confusion_low' => 'tidak bingung',
        'confusion_very_high' => 'sangat bingung',
        'anxiety_high' => 'cukup cemas',
        'anxiety_low' => 'tidak cemas',
        'anxiety_very_high' => 'sangat cemas',
        'irritation_high' => 'cukup kesal',
        'irritation_low' => 'tidak kesal',
        'irritation_very_high' => 'sangat kesal',
        'pressure_high' => 'cukup tertekan',
        'pressure_low' => 'tidak tertekan',
        'pressure_very_high' => 'sangat tertekan',
        'engagement_high' => 'cukap terlibat',
        'engagement_low' => 'kurang terlibat',
        'engagement_very_low' => 'tidak terlibat',
        'engagement_very_high' => 'sangat terlibat',
    ];

    public function translate(DirectorState $state): BehaviorTranslationResult
    {
        $bands = [
            'trust' => StateBand::fromValue($state->getTrust()),
            'interest' => StateBand::fromValue($state->getInterest()),
            'confusion' => StateBand::fromValue($state->getConfusion()),
            'anxiety' => StateBand::fromValue($state->getAnxiety()),
            'irritation' => StateBand::fromValue($state->getIrritation()),
            'pressure_perception' => StateBand::fromValue($state->getPressurePerception()),
            'engagement' => StateBand::fromValue($state->getEngagement()),
        ];

        $phrases = [];

        $trustPhrase = $this->describe($state->getTrust(), 'trust', 'percaya pada sales');
        $interestPhrase = $this->describe($state->getInterest(), 'interest', 'tertarik dengan penawaran');
        $confusionPhrase = $this->describe($state->getConfusion(), 'confusion', 'mengikuti penjelasan');
        $anxietyPhrase = $this->describe($state->getAnxiety(), 'anxiety', 'merasa cemas');
        $irritationPhrase = $this->describe($state->getIrritation(), 'irritation', 'merasa kesal');
        $pressurePhrase = $this->describe($state->getPressurePerception(), 'pressure', 'merasa tertekan');
        $engagementPhrase = $this->describe($state->getEngagement(), 'engagement', 'terlibat dalam percakapan');

        if ($trustPhrase) {
            $phrases[] = $trustPhrase;
        }
        if ($interestPhrase) {
            $phrases[] = $interestPhrase;
        }
        if ($confusionPhrase) {
            $phrases[] = $confusionPhrase;
        }
        if ($anxietyPhrase) {
            $phrases[] = $anxietyPhrase;
        }
        if ($irritationPhrase) {
            $phrases[] = $irritationPhrase;
        }
        if ($pressurePhrase) {
            $phrases[] = $pressurePhrase;
        }
        if ($engagementPhrase) {
            $phrases[] = $engagementPhrase;
        }

        $qualitativeText = implode('. ', $phrases) . '.';

        $noteSuggestion = $this->buildNoteSuggestion($bands, $state);

        return new BehaviorTranslationResult($bands, $qualitativeText, $noteSuggestion);
    }

    private function describe(int $value, string $key, string $neutral): ?string
    {
        $band = StateBand::fromValue($value);

        return match ($band) {
            StateBand::VERY_LOW => self::DESCRIPTIONS[$key . '_very_low'] ?? null,
            StateBand::LOW => self::DESCRIPTIONS[$key . '_low'] ?? null,
            StateBand::MODERATE => null,
            StateBand::HIGH => self::DESCRIPTIONS[$key . '_high'] ?? null,
            StateBand::VERY_HIGH => self::DESCRIPTIONS[$key . '_very_high'] ?? null,
        };
    }

    private function buildNoteSuggestion(array $bands, DirectorState $state): string
    {
        $notes = [];

        if ($state->getTrust() <= 30) {
            $notes[] = 'Kepercayaan masih rendah—jangan terlihat terlalu yakin.';
        } elseif ($state->getTrust() >= 70) {
            $notes[] = 'Kepercayaan sudah cukup baik—bisa sedikit lebih terbuka.';
        }

        if ($state->getIrritation() >= 60) {
            $notes[] = 'Kekesalan cukup tinggi—tunjukkan ketidaknyamanan secara wajar.';
        }

        if ($state->getEngagement() <= 30) {
            $notes[] = 'Keterlibatan menurun—bisa menunjukkan kurangnya minat.';
        } elseif ($state->getEngagement() >= 70) {
            $notes[] = 'Keterlibatan baik—pertahankan antusiasme wajar.';
        }

        if ($state->getConfusion() >= 60) {
            $notes[] = 'Kebingungan cukup tinggi—bisa minta klarifikasi.';
        }

        if ($state->getPressurePerception() >= 60) {
            $notes[] = 'Merasa tertekan—bisa menunjukkan keinginan untuk mundur.';
        }

        if ($state->getAnxiety() >= 70) {
            $notes[] = 'Kecemasan tinggi—bisa menunjukkan kekhawatiran.';
        }

        return implode(' ', $notes);
    }
}
