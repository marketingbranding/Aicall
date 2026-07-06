<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status')->default('ACTIVE')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('persona_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->text('public_profile_text')->nullable();
            $table->json('identity_json');
            $table->json('housing_context_json')->nullable();
            $table->json('knowledge_beliefs_json')->nullable();
            $table->json('personality_profile_json')->nullable();
            $table->json('human_behavior_traits_json')->nullable();
            $table->json('communication_style_json')->nullable();
            $table->json('initial_dynamic_state_json')->nullable();
            $table->json('state_sensitivity_json')->nullable();
            $table->json('salience_overrides_json')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->unique(['persona_id', 'version_number']);
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->foreignId('current_version_id')
                ->nullable()
                ->after('status')
                ->constrained('persona_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_version_id');
        });

        Schema::dropIfExists('persona_versions');
        Schema::dropIfExists('personas');
    }
};
