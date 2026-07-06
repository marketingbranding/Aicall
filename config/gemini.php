<?php

return [
    'live' => [
        'model' => env('GEMINI_LIVE_MODEL', 'gemini-3.1-flash-live-preview'),

        'models' => [
            'gemini-3.1-flash-live-preview' => [
                'supports_native_audio' => true,
                'supports_input_transcription' => true,
                'supports_output_transcription' => true,
                'supports_function_calling' => true,
                'supports_async_function_calling' => false,
                'supports_affective_dialogue' => true,
                'supports_proactive_audio' => true,
                'supports_realtime_text_input' => true,
                'supports_session_resumption' => true,
                'supports_context_compression' => true,
                'input_audio_mime_type' => 'audio/pcm;rate=16000',
                'output_audio_mime_type' => 'audio/pcm;rate=24000',
                'max_audio_session_seconds' => 900,
                'connection_goaway_supported' => true,
                'ephemeral_tokens_supported' => true,
                'ephemeral_token_api_version' => 'v1alpha',
            ],
        ],
    ],
];
