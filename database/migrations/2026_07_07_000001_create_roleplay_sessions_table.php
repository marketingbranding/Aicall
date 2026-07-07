<?php

use App\Enums\RoleplaySessionStatus;
use App\Enums\EndingType;
use App\Enums\TranscriptIntegrity;
use App\Enums\EvaluationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roleplay_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 12)->unique();
            $table->uuid('correlation_id')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('scenario_id'); // scenario code or FK — denormalized
            $table->string('persona_id')->nullable(); // persona code or FK — denormalized
            $table->string('persona_mode')->nullable();
            $table->string('difficulty_level');
            $table->string('status', 30)->default(RoleplaySessionStatus::CREATED->value)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('ending_type', 20)->nullable();
            $table->text('ending_reason')->nullable();
            $table->string('transcript_integrity', 20)->nullable();
            $table->string('evaluation_status', 20)->nullable()->index();
            $table->unsignedInteger('director_version')->default(1);
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roleplay_sessions');
    }
};
