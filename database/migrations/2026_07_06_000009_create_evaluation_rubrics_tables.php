<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('GLOBAL');
            $table->foreignId('scenario_version_id')->nullable()->constrained('scenario_versions')->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('evaluation_rubric_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_rubric_id')->constrained('evaluation_rubrics')->cascadeOnDelete();
            $table->string('key');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('weight')->default(100);
            $table->boolean('is_enabled')->default(true);
            $table->text('evaluation_guidance')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_rubric_id', 'key']);
        });

        Schema::create('scenario_rubric_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_version_id')->constrained('scenario_versions')->cascadeOnDelete();
            $table->string('global_rubric_item_key');
            $table->unsignedInteger('weight_override')->nullable();
            $table->boolean('is_enabled_override')->nullable();
            $table->timestamps();

            $table->unique(['scenario_version_id', 'global_rubric_item_key'], 'scenario_rubric_overrides_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenario_rubric_overrides');
        Schema::dropIfExists('evaluation_rubric_items');
        Schema::dropIfExists('evaluation_rubrics');
    }
};
