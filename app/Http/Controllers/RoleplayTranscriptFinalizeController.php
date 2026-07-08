<?php

namespace App\Http\Controllers;

use App\Enums\RoleplaySessionStatus;
use App\Models\RoleplaySession;
use App\Services\Transcript\TranscriptAssembler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleplayTranscriptFinalizeController extends Controller
{
    public function __construct(
        private readonly TranscriptAssembler $assembler,
    ) {}

    public function store(Request $request, string $publicId): JsonResponse
    {
        $session = RoleplaySession::query()
            ->where('public_id', $publicId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $allowed = [
            RoleplaySessionStatus::ENDING->value,
            RoleplaySessionStatus::TRANSCRIPT_FINALIZING->value,
            RoleplaySessionStatus::EVALUATING->value,
            RoleplaySessionStatus::COMPLETED->value,
            RoleplaySessionStatus::FAILED->value,
        ];

        if (!in_array($session->status, $allowed, true)) {
            return response()->json([
                'message' => 'Sesi tidak dalam status yang dapat difinalisasi.',
                'error' => 'invalid_session_status',
            ], 409);
        }

        $result = $this->assembler->assemble($session);

        if ($session->status === RoleplaySessionStatus::ENDING->value) {
            $session->update(['status' => RoleplaySessionStatus::TRANSCRIPT_FINALIZING->value]);
        }

        return response()->json([
            'integrity' => $result->integrity->value,
            'turn_count' => count($result->turns),
            'issues' => $result->issues,
            'interrupted_turns' => $result->interruptedTurns,
            'session_status' => $session->fresh()->status,
        ]);
    }
}
