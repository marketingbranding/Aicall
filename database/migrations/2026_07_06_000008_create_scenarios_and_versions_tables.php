<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status')->default('ACTIVE')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('scenario_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained('scenarios')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->text('description')->nullable();
            $table->text('sales_briefing')->nullable();
            $table->text('hidden_context')->nullable();
            $table->text('training_objective')->nullable();
            $table->string('starting_phase')->nullable();
            $table->string('first_speaker')->default('AI');
            $table->text('ai_opening_context')->nullable();
            $table->text('initial_customer_intent')->nullable();
            $table->json('target_behaviors_json')->nullable();
            $table->json('important_discovery_points_json')->nullable();
            $table->json('mandatory_topics_json')->nullable();
            $table->json('prohibited_claims_json')->nullable();
            $table->json('success_conditions_json')->nullable();
            $table->json('failure_conditions_json')->nullable();
            $table->string('difficulty_level')->default('NORMAL');
            $table->json('difficulty_config_json')->nullable();
            $table->unsignedInteger('max_duration_seconds')->nullable();
            $table->boolean('allow_ai_end_call')->default(false);
            $table->json('allowed_persona_modes_json')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->unique(['scenario_id', 'version_number']);
        });

        Schema::table('scenarios', function (Blueprint $table) {
            $table->foreignId('current_version_id')
                ->nullable()
                ->after('status')
                ->constrained('scenario_versions')
                ->nullOnDelete();
        });

        Schema::create('scenario_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_version_id')->constrained('scenario_versions')->cascadeOnDelete();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('weight')->nullable();
            $table->timestamps();

            $table->unique(['scenario_version_id', 'persona_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_personas');

        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_version_id');
        });

        Schema::dropIfExists('scenario_versions');
        Schema::dropIfExists('scenarios');
    }
};
