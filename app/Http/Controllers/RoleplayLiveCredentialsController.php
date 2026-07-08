<?php

namespace App\Http\Controllers;

use App\Enums\RoleplaySessionStatus;
use App\Models\RoleplaySession;
use App\Services\LiveVoice\GeminiLiveRoleplayProvider;
use App\Services\LiveVoice\LiveCredentialProvisioningException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleplayLiveCredentialsController extends Controller
{
    public function store(
        Request $request,
        string $publicId,
        GeminiLiveRoleplayProvider $provider,
    ): JsonResponse {
        $session = RoleplaySession::query()
            ->with('snapshot')
            ->where('public_id', $publicId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless($this->canProvision($session), 409, 'Sesi belum siap untuk kredensial Live.');

        try {
            $credentials = $provider->createEphemeralToken($session);
        } catch (LiveCredentialProvisioningException) {
            return response()->json([
                'message' => 'Kredensial Gemini Live belum tersedia. Coba lagi nanti atau hubungi admin.',
                'error' => 'live_credentials_unavailable',
            ], 503);
        }

        $snapshot = $session->snapshot;
        $firstSpeaker = $snapshot?->scenario_snapshot_json['first_speaker'] ?? 'USER';

        return response()->json([
            'provider' => 'gemini',
            'api_version' => $credentials->apiVersion,
            'model' => $credentials->model,
            'ephemeral_token' => $credentials->token,
            'expires_at' => $credentials->expiresAt,
            'new_session_expires_at' => $credentials->newSessionExpiresAt,
            'session' => [
                'public_id' => $session->public_id,
                'status' => $session->status,
            ],
            'live_config' => $credentials->clientConfig,
            'first_speaker' => $firstSpeaker,
        ]);
    }

    private function canProvision(RoleplaySession $session): bool
    {
        return $session->snapshot !== null
            && in_array($session->status, [
                RoleplaySessionStatus::CREATED->value,
                RoleplaySessionStatus::PREPARING->value,
                RoleplaySessionStatus::REQUESTING_MICROPHONE->value,
                RoleplaySessionStatus::READY->value,
                RoleplaySessionStatus::ACTIVE->value,
                RoleplaySessionStatus::RECONNECTING->value,
            ], true);
    }
}
