<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roleplay_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roleplay_session_id')
                ->constrained('roleplay_sessions')
                ->cascadeOnDelete();
            $table->string('event_type', 50);
            $table->string('severity', 20)->default('MODERATE');
            $table->string('topic', 255)->default('');
            $table->string('related_objection_key', 100)->nullable();
            $table->string('hidden_information_key', 100)->nullable();
            $table->text('short_internal_reason')->nullable();
            $table->unsignedInteger('source_turn_sequence')->nullable();
            $table->string('fingerprint', 32);
            $table->boolean('accepted');
            $table->string('rejection_reason', 255)->nullable();
            $table->json('previous_state_json');
            $table->json('new_state_json');
            $table->timestamps();

            $table->unique(['roleplay_session_id', 'fingerprint'], 'roleplay_events_fingerprint_unique');
            $table->index('roleplay_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roleplay_events');
    }
};
