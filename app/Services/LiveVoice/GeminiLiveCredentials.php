<?php

namespace App\Services\LiveVoice;

final readonly class GeminiLiveCredentials
{
    /**
     * @param  array<string, mixed>  $clientConfig
     */
    public function __construct(
        public string $token,
        public string $model,
        public string $apiVersion,
        public string $expiresAt,
        public string $newSessionExpiresAt,
        public array $clientConfig,
    ) {}
}
