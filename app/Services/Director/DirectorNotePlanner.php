<?php

namespace App\Services\Director;

class DirectorNotePlanner
{
    private const array THRESHOLDS = [
        'trust_low' => ['var' => 'trust', 'max' => 30, 'note' => 'Kepercayaan Anda kepada salesperson menurun.'],
        'trust_high' => ['var' => 'trust', 'min' => 70, 'note' => 'Kepercayaan Anda kepada salesperson mulai terbangun.'],
        'irritation_high' => ['var' => 'irritation', 'min' => 60, 'note' => 'Anda mulai merasa kesal.'],
        'engagement_low' => ['var' => 'engagement', 'max' => 30, 'note' => 'Keterlibatan Anda menurun.'],
        'engagement_high' => ['var' => 'engagement', 'min' => 70, 'note' => 'Anda semakin terlibat dalam percakapan.'],
        'confusion_high' => ['var' => 'confusion', 'min' => 60, 'note' => 'Anda merasa bingung dengan penjelasan yang diberikan.'],
        'pressure_high' => ['var' => 'pressurePerception', 'min' => 60, 'note' => 'Anda merasa tertekan dengan pendekatan salesperson.'],
        'anxiety_high' => ['var' => 'anxiety', 'min' => 70, 'note' => 'Kecemasan Anda meningkat.'],
    ];

    private const array PHASE_NOTE_MAP = [
        'OPENING->RAPPORT' => 'Percakapan mulai membangun rapport.',
        'OPENING->DISCOVERY' => 'Memasuki tahap discovery.',
        'RAPPORT->DISCOVERY' => 'Beralih ke tahap discovery.',
        'DISCOVERY->NEED_EXPLORATION' => 'Menggali kebutuhan lebih dalam.',
        'NEED_EXPLORATION->EXPLANATION' => 'Salesperson mulai memberikan penjelasan.',
        'EXPLANATION->COMMITMENT' => 'Menuju tahap komitmen.',
        'EXPLANATION->OBJECTION_HANDLING' => 'Keberatan muncul dan perlu ditangani.',
        'OBJECTION_HANDLING->COMMITMENT' => 'Keberatan telah ditangani, melanjutkan transaksi.',
        'COMMITMENT->CLOSING' => 'Memasuki tahap closing.',
        'CLOSING->ENDING' => 'Percakapan akan segera berakhir.',
        'EXPLANATION->DISCOVERY' => 'Kembali menggali informasi setelah penjelasan.',
        'OBJECTION_HANDLING->DISCOVERY' => 'Kembali ke tahap discovery untuk memahami kebutuhan.',
        'COMMITMENT->DISCOVERY' => 'Kembali ke tahap discovery dari komitmen.',
        'CLOSING->DISCOVERY' => 'Kembali ke tahap discovery dari closing.',
    ];

    public function planNotes(
        DirectorState $previousState,
        DirectorState $newState,
        BehaviorTranslationResult $translation,
        array $objectionTransitions,
        array $hiddenInfoTransitions,
        array $boundaryTransitions,
        array $phaseTransitions,
    ): array {
        $notes = [];

        foreach ($objectionTransitions as $ot) {
            if ($ot instanceof ObjectionTransition && $ot->directorNote !== null) {
                $notes[] = new DirectorNote(
                    text: $ot->directorNote,
                    category: 'objection',
                    priority: 2,
                );
            }
        }

        foreach ($hiddenInfoTransitions as $ht) {
            if ($ht instanceof HiddenInfoTransition && $ht->directorNote !== null) {
                $notes[] = new DirectorNote(
                    text: $ht->directorNote,
                    category: 'hidden_info',
                    priority: 2,
                );
            }
        }

        foreach ($boundaryTransitions as $bt) {
            if ($bt instanceof BoundaryTransition && $bt->directorNote !== null) {
                $notes[] = new DirectorNote(
                    text: $bt->directorNote,
                    category: 'boundary',
                    priority: 2,
                );
            }
        }

        foreach ($phaseTransitions as $pt) {
            if (!$pt instanceof ConversationPhaseTransition || !$pt->accepted) {
                continue;
            }

            if ($pt->prematureClosing) {
                $notes[] = new DirectorNote(
                    text: 'Salesperson mencoba melakukan closing terlalu awal. Anda merasa tertekan.',
                    category: 'premature_closing',
                    priority: 3,
                );
            }

            if ($pt->fromPhase !== $pt->toPhase) {
                $mapKey = $pt->fromPhase->value . '->' . $pt->toPhase->value;
                $phaseNote = self::PHASE_NOTE_MAP[$mapKey] ?? null;

                if ($phaseNote === null) {
                    $phaseNote = 'Tahap percakapan berubah: ' . $pt->fromPhase->value . ' ke ' . $pt->toPhase->value . '.';
                }

                $notes[] = new DirectorNote(
                    text: $phaseNote,
                    category: 'phase_change',
                    priority: 1,
                );
            }
        }

        $this->addStateThresholdNotes($previousState, $newState, $notes);

        return $notes;
    }

    private function addStateThresholdNotes(DirectorState $previous, DirectorState $new, array &$notes): void
    {
        $getter = fn(string $var, DirectorState $s): int => match ($var) {
            'trust' => $s->getTrust(),
            'interest' => $s->getInterest(),
            'confusion' => $s->getConfusion(),
            'anxiety' => $s->getAnxiety(),
            'irritation' => $s->getIrritation(),
            'pressurePerception' => $s->getPressurePerception(),
            'engagement' => $s->getEngagement(),
            default => 0,
        };

        foreach (self::THRESHOLDS as $key => $threshold) {
            $var = $threshold['var'];
            $prevVal = $getter($var, $previous);
            $newVal = $getter($var, $new);

            $crossed = false;

            if (isset($threshold['max'])) {
                $crossed = $prevVal > $threshold['max'] && $newVal <= $threshold['max'];
            } elseif (isset($threshold['min'])) {
                $crossed = $prevVal < $threshold['min'] && $newVal >= $threshold['min'];
            }

            if ($crossed) {
                $notes[] = new DirectorNote(
                    text: $threshold['note'],
                    category: 'state_threshold',
                    priority: 1,
                );
            }
        }
    }
}
