<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roleplay_transcript_turns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('roleplay_session_id');
            $table->unsignedInteger('sequence');
            $table->string('speaker', 4);
            $table->text('text');
            $table->string('status', 10)->default('PARTIAL');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->json('source_metadata')->nullable();
            $table->timestamps();

            $table->unique(['roleplay_session_id', 'sequence'], 'transcript_turns_session_sequence_unique');
            $table->index(['roleplay_session_id', 'status'], 'transcript_turns_session_status_index');

            $table->foreign('roleplay_session_id', 'transcript_turns_session_fk')
                ->references('id')
                ->on('roleplay_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roleplay_transcript_turns');
    }
};
