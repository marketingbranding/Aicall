<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persona_hidden_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_version_id')->constrained('persona_versions')->cascadeOnDelete();
            $table->string('key');
            $table->string('title');
            $table->text('information')->nullable();
            $table->unsignedInteger('sensitivity')->default(50);
            $table->unsignedInteger('disclosure_difficulty')->default(50);
            $table->json('relevant_topics_json')->nullable();
            $table->unsignedInteger('direct_question_effectiveness')->default(50);
            $table->unsignedInteger('trust_requirement')->default(50);
            $table->json('disclosure_conditions_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['persona_version_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persona_hidden_information');
    }
};
