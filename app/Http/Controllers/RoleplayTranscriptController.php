<?php

namespace App\Http\Controllers;

use App\Models\RoleplaySession;
use App\Models\RoleplayTranscriptTurn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleplayTranscriptController extends Controller
{
    public function store(Request $request, string $publicId): JsonResponse
    {
        $session = RoleplaySession::query()
            ->where('public_id', $publicId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($session->status, ['ACTIVE', 'ENDING'], true)) {
            return response()->json([
                'message' => 'Sesi tidak dalam status yang menerima transkrip.',
                'error' => 'invalid_session_status',
            ], 409);
        }

        $validated = $request->validate([
            'sequence' => ['required', 'integer', 'min:0'],
            'speaker' => ['required', 'string', 'in:USER,AI'],
            'text' => ['required', 'string', 'max:10000'],
            'status' => ['required', 'string', 'in:PARTIAL,FINAL'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'source_metadata' => ['nullable', 'array'],
        ]);

        $existing = RoleplayTranscriptTurn::where('roleplay_session_id', $session->id)
            ->where('sequence', $validated['sequence'])
            ->first();

        if ($existing && $existing->status === 'FINAL') {
            return response()->json([
                'message' => 'Giliran transkrip sudah final.',
                'error' => 'turn_already_final',
            ], 409);
        }

        $turn = RoleplayTranscriptTurn::updateOrCreate(
            [
                'roleplay_session_id' => $session->id,
                'sequence' => $validated['sequence'],
            ],
            [
                'speaker' => $validated['speaker'],
                'text' => $validated['text'],
                'status' => $validated['status'],
                'started_at' => $validated['started_at'],
                'ended_at' => $validated['ended_at'] ?? null,
                'source_metadata' => $validated['source_metadata'] ?? null,
            ],
        );

        return response()->json([
            'turn' => [
                'id' => $turn->id,
                'sequence' => $turn->sequence,
                'speaker' => $turn->speaker,
                'status' => $turn->status,
                'created_at' => $turn->created_at,
            ],
        ]);
    }
}
