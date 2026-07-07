<?php

namespace Database\Factories;

use App\Enums\RoleplaySessionStatus;
use App\Models\RoleplaySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleplaySessionFactory extends Factory
{
    protected $model = RoleplaySession::class;

    public function definition(): array
    {
        return [
            'public_id' => Str::lower(Str::random(12)),
            'correlation_id' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'branch_id' => null,
            'scenario_id' => 'scenario-' . Str::random(6),
            'persona_id' => null,
            'persona_mode' => null,
            'difficulty_level' => 'NORMAL',
            'status' => RoleplaySessionStatus::CREATED->value,
            'started_at' => null,
            'ended_at' => null,
            'duration_seconds' => null,
            'ending_type' => null,
            'ending_reason' => null,
            'transcript_integrity' => null,
            'evaluation_status' => null,
            'director_version' => 1,
            'idempotency_key' => null,
            'idempotency_fingerprint' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => RoleplaySessionStatus::ACTIVE->value,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => RoleplaySessionStatus::COMPLETED->value,
            'started_at' => now()->subMinutes(10),
            'ended_at' => now(),
            'duration_seconds' => 600,
        ]);
    }

    public function status(RoleplaySessionStatus $status): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => $status->value,
        ]);
    }
}
