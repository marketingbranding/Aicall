<?php

namespace App\Services\LiveVoice;

use App\Domain\LiveVoice\LiveModelCapabilityRegistry;
use App\Models\RoleplaySession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class GeminiLiveRoleplayProvider
{
    public function __construct(
        private readonly LiveModelCapabilityRegistry $capabilities,
    ) {}

    public function createEphemeralToken(RoleplaySession $session): GeminiLiveCredentials
    {
        $apiKey = (string) config('gemini.api_key', '');
        $endpoint = (string) config('gemini.live.auth_token_endpoint', '');
        $apiVersion = (string) config('gemini.live.api_version', 'v1alpha');

        if ($apiKey === '' || $endpoint === '') {
            throw LiveCredentialProvisioningException::missingConfiguration();
        }

        $snapshot = $session->snapshot;
        if (! $snapshot || ! is_string($snapshot->actor_instructions) || $snapshot->actor_instructions === '') {
            throw LiveCredentialProvisioningException::missingConfiguration();
        }

        $model = (string) config('gemini.live.model');
        $modelCapabilities = $this->capabilities->forModel($model);
        $now = Carbon::now('UTC');
        $newSessionExpiresAt = $now->copy()->addSeconds((int) config('gemini.live.new_session_ttl_seconds', 60));
        $expiresAt = $now->copy()->addSeconds((int) config('gemini.live.message_ttl_seconds', 1800));

        $liveConfig = $this->liveConfig($snapshot->actor_instructions, $modelCapabilities);

        $response = Http::timeout(10)->post($endpoint . '?key=' . urlencode($apiKey), [
            'authToken' => [
                'uses' => (int) config('gemini.live.token_uses', 1),
                'newSessionExpireTime' => $newSessionExpiresAt->toJSON(),
                'expireTime' => $expiresAt->toJSON(),
                'liveConnectConstraints' => [
                    'model' => $model,
                    'config' => $liveConfig,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw LiveCredentialProvisioningException::providerRejectedRequest();
        }

        $token = (string) data_get($response->json(), 'name');
        if ($token === '') {
            throw LiveCredentialProvisioningException::providerRejectedRequest();
        }

        return new GeminiLiveCredentials(
            token: $token,
            model: $model,
            apiVersion: $apiVersion,
            expiresAt: $expiresAt->toJSON(),
            newSessionExpiresAt: $newSessionExpiresAt->toJSON(),
            clientConfig: $this->clientConfig($modelCapabilities),
        );
    }

    private function liveConfig(string $actorInstructions, $modelCapabilities): array
    {
        $config = [
            'responseModalities' => ['AUDIO'],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $actorInstructions],
                ],
            ],
        ];

        if ($modelCapabilities->supports('supports_input_transcription')) {
            $config['inputAudioTranscription'] = new \stdClass();
        }

        if ($modelCapabilities->supports('supports_output_transcription')) {
            $config['outputAudioTranscription'] = new \stdClass();
        }

        if ($modelCapabilities->supports('supports_session_resumption')) {
            $config['sessionResumption'] = new \stdClass();
        }

        return $config;
    }

    private function clientConfig($modelCapabilities): array
    {
        return [
            'response_modalities' => ['AUDIO'],
            'input_audio_mime_type' => $modelCapabilities->inputAudioMimeType(),
            'output_audio_mime_type' => $modelCapabilities->outputAudioMimeType(),
            'input_transcription' => $modelCapabilities->supports('supports_input_transcription'),
            'output_transcription' => $modelCapabilities->supports('supports_output_transcription'),
            'session_resumption' => $modelCapabilities->supports('supports_session_resumption'),
            'realtime_text_input' => $modelCapabilities->supports('supports_realtime_text_input'),
        ];
    }
}
