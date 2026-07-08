<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('director_state_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roleplay_session_id')
                ->unique()
                ->constrained('roleplay_sessions')
                ->cascadeOnDelete();
            $table->json('state_json');
            $table->json('machine_states_json');
            $table->unsignedInteger('event_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('director_state_snapshots');
    }
};
