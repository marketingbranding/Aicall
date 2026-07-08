<?php

namespace App\Services\Transcript;

use App\Enums\TranscriptIntegrity;
use App\Models\RoleplaySession;
use App\Models\RoleplayTranscriptTurn;
use Illuminate\Database\Eloquent\Collection;

class TranscriptAssembler
{
    private const array VALID_SPEAKERS = ['USER', 'AI'];
    private const array VALID_STATUSES = ['PARTIAL', 'FINAL'];

    public function assemble(RoleplaySession $session): TranscriptAssemblyResult
    {
        $turns = $session->transcriptTurns()
            ->orderBy('sequence')
            ->get();

        if ($turns->isEmpty()) {
            return new TranscriptAssemblyResult(
                integrity: TranscriptIntegrity::FAILED,
                turns: [],
                issues: ['Tidak ada giliran transkrip yang ditemukan.'],
                interruptedTurns: [],
            );
        }

        $issues = [];
        $interruptedTurns = [];
        $allFinal = true;
        $seenSequences = [];
        $expectedSequence = 0;
        $hasGap = false;

        foreach ($turns as $turn) {
            $sequence = $turn->sequence;

            if (in_array($sequence, $seenSequences, true)) {
                $issues[] = "Sequen duplikat terdeteksi: $sequence.";
            }
            $seenSequences[] = $sequence;

            if ($sequence > $expectedSequence) {
                for ($missing = $expectedSequence; $missing < $sequence; $missing++) {
                    $issues[] = "Kesenjangan sekuen terdeteksi: sekuen $missing hilang.";
                }
                $hasGap = true;
            }
            $expectedSequence = max($expectedSequence, $sequence + 1);

            if (!in_array($turn->speaker, self::VALID_SPEAKERS, true)) {
                $issues[] = "Giliran sekuen $sequence memiliki pembicara tidak valid: '{$turn->speaker}'.";
            }

            $text = is_string($turn->text) ? trim($turn->text) : '';
            if ($text === '') {
                $issues[] = "Giliran sekuen $sequence memiliki teks kosong.";
            }

            if (!in_array($turn->status, self::VALID_STATUSES, true)) {
                $issues[] = "Giliran sekuen $sequence memiliki status tidak valid: '{$turn->status}'.";
            }

            if ($turn->status !== 'FINAL') {
                $allFinal = false;

                if ($turn->speaker === 'AI') {
                    $interruptedTurns[] = [
                        'sequence' => $turn->sequence,
                        'speaker' => $turn->speaker,
                        'text' => $turn->text,
                        'started_at' => $turn->started_at?->toIso8601String(),
                    ];
                }
            }
        }

        if ($hasGap || !$allFinal || !empty($issues)) {
            $integrity = TranscriptIntegrity::PARTIAL;
        } else {
            $integrity = TranscriptIntegrity::COMPLETE;
        }

        if ($session->transcript_integrity !== $integrity->value) {
            $session->updateQuietly(['transcript_integrity' => $integrity->value]);
        }

        return new TranscriptAssemblyResult(
            integrity: $integrity,
            turns: $turns->all(),
            issues: $issues,
            interruptedTurns: $interruptedTurns,
        );
    }
}
