<?php

namespace App\Domain\LiveVoice;

final readonly class LiveModelCapabilities
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $modelId,
        public array $raw,
    ) {}

    public function supports(string $capability): bool
    {
        return (bool) ($this->raw[$capability] ?? false);
    }

    public function inputAudioMimeType(): string
    {
        return (string) $this->raw['input_audio_mime_type'];
    }

    public function outputAudioMimeType(): string
    {
        return (string) $this->raw['output_audio_mime_type'];
    }

    public function maxAudioSessionSeconds(): int
    {
        return (int) $this->raw['max_audio_session_seconds'];
    }
}
