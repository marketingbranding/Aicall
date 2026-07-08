<?php

namespace Tests\Unit;

use App\Domain\LiveVoice\LiveModelCapabilityRegistry;
use InvalidArgumentException;
use Tests\TestCase;

class LiveModelCapabilityRegistryTest extends TestCase
{
    public function test_it_resolves_configured_live_model_capabilities(): void
    {
        config(['gemini.live.model' => 'gemini-3.1-flash-live-preview']);

        $capabilities = app(LiveModelCapabilityRegistry::class)->configured();

        $this->assertSame('gemini-3.1-flash-live-preview', $capabilities->modelId);
        $this->assertTrue($capabilities->supports('supports_native_audio'));
        $this->assertTrue($capabilities->supports('supports_input_transcription'));
        $this->assertTrue($capabilities->supports('supports_output_transcription'));
        $this->assertTrue($capabilities->supports('supports_function_calling'));
        $this->assertFalse($capabilities->supports('supports_async_function_calling'));
        $this->assertFalse($capabilities->supports('supports_affective_dialogue'));
        $this->assertFalse($capabilities->supports('supports_proactive_audio'));
        $this->assertTrue($capabilities->supports('supports_realtime_text_input'));
        $this->assertSame('audio/pcm;rate=16000', $capabilities->inputAudioMimeType());
        $this->assertSame('audio/pcm;rate=24000', $capabilities->outputAudioMimeType());
        $this->assertSame(900, $capabilities->maxAudioSessionSeconds());
    }

    public function test_it_rejects_unregistered_live_models(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(LiveModelCapabilityRegistry::class)->forModel('unregistered-model');
    }
}
