<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roleplay_session_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roleplay_session_id')->unique()->constrained('roleplay_sessions')->cascadeOnDelete();
            $table->foreignId('persona_version_id')->nullable()->constrained('persona_versions')->nullOnDelete();
            $table->foreignId('scenario_version_id')->nullable()->constrained('scenario_versions')->nullOnDelete();
            $table->json('persona_snapshot_json');
            $table->json('scenario_snapshot_json');
            $table->json('difficulty_snapshot_json');
            $table->json('salience_snapshot_json');
            $table->json('rubric_snapshot_json');
            $table->json('director_snapshot_json');
            $table->string('actor_instruction_hash', 64);
            $table->text('actor_instructions');
            $table->timestamp('created_at');

            $table->index('roleplay_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roleplay_session_snapshots');
    }
};
