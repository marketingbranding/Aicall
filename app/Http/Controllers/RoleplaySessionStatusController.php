<?php

namespace App\Http\Controllers;

use App\Enums\EndingType;
use App\Enums\RoleplaySessionStatus;
use App\Models\RoleplaySession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleplaySessionStatusController extends Controller
{
    private const array TRANSITIONS = [
        'CREATED' => ['PREPARING', 'FAILED'],
        'PREPARING' => ['READY', 'FAILED', 'PREPARING'],
        'READY' => ['ACTIVE', 'ENDING', 'FAILED', 'READY'],
        'ACTIVE' => ['ENDING', 'FAILED', 'ACTIVE'],
        'ENDING' => ['COMPLETED', 'FAILED', 'ENDING'],
        'COMPLETED' => [],
        'FAILED' => [],
    ];

    public function update(Request $request, string $publicId): JsonResponse
    {
        $session = RoleplaySession::query()
            ->where('public_id', $publicId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_column(RoleplaySessionStatus::cases(), 'value'))],
            'ending_type' => ['nullable', 'string', 'in:' . implode(',', array_column(EndingType::cases(), 'value'))],
            'ending_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = strtoupper($validated['status']);
        $currentStatus = strtoupper($session->status);

        if (!in_array($newStatus, self::TRANSITIONS[$currentStatus] ?? [], true)) {
            return response()->json([
                'message' => 'Transisi status tidak diizinkan dari ' . $currentStatus . ' ke ' . $newStatus . '.',
                'error' => 'invalid_transition',
            ], 422);
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'ENDING') {
            $endingType = isset($validated['ending_type'])
                ? strtoupper($validated['ending_type'])
                : EndingType::USER_END->value;

            if (!in_array($endingType, array_column(EndingType::cases(), 'value'), true)) {
                return response()->json([
                    'message' => 'Tipe akhir sesi tidak valid.',
                    'error' => 'invalid_ending_type',
                ], 422);
            }

            $updateData['ending_type'] = $endingType;
            $updateData['ended_at'] = now();
            if (!empty($validated['ending_reason'])) {
                $updateData['ending_reason'] = $validated['ending_reason'];
            }
        }

        if ($newStatus === 'FAILED') {
            $updateData['ending_type'] = EndingType::FAILURE->value;
            $updateData['ended_at'] = now();
            if (!empty($validated['ending_reason'])) {
                $updateData['ending_reason'] = $validated['ending_reason'];
            }
        }

        if ($newStatus === 'ACTIVE' && $session->started_at === null) {
            $updateData['started_at'] = now();
        }

        if ($newStatus === 'COMPLETED' && $session->ended_at === null) {
            $updateData['ended_at'] = now();
        }

        $session->update($updateData);
        $session->refresh();

        return response()->json([
            'session' => [
                'public_id' => $session->public_id,
                'status' => $session->status,
                'started_at' => $session->started_at,
                'ended_at' => $session->ended_at,
                'ending_type' => $session->ending_type,
                'ending_reason' => $session->ending_reason,
            ],
        ]);
    }
}
