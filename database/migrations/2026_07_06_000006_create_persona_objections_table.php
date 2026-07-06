<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persona_objections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_version_id')->constrained('persona_versions')->cascadeOnDelete();
            $table->string('key');
            $table->string('title');
            $table->text('context')->nullable();
            $table->string('visibility')->default('VISIBLE');
            $table->unsignedInteger('severity')->default(50);
            $table->unsignedInteger('emotional_importance')->default(50);
            $table->json('trigger_conditions_json')->nullable();
            $table->json('disclosure_conditions_json')->nullable();
            $table->json('resolution_conditions_json')->nullable();
            $table->unsignedInteger('persistence')->default(50);
            $table->boolean('is_resolvable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['persona_version_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persona_objections');
    }
};
