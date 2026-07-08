<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('director_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roleplay_session_id')
                ->constrained('roleplay_sessions')
                ->cascadeOnDelete();
            $table->foreignId('roleplay_event_id')
                ->nullable()
                ->constrained('roleplay_events')
                ->cascadeOnDelete();
            $table->text('text');
            $table->string('category', 50);
            $table->integer('priority')->default(0);
            $table->unsignedInteger('source_turn')->nullable();
            $table->timestamps();

            $table->index('roleplay_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('director_notes');
    }
};
