<?php

namespace Database\Factories;

use App\Models\RoleplayEvent;
use App\Models\RoleplaySession;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleplayEventFactory extends Factory
{
    protected $model = RoleplayEvent::class;

    public function definition(): array
    {
        return [
            'roleplay_session_id' => RoleplaySession::factory(),
            'event_type' => 'ACTIVE_LISTENING',
            'severity' => 'MODERATE',
            'topic' => '',
            'related_objection_key' => null,
            'hidden_information_key' => null,
            'short_internal_reason' => null,
            'source_turn_sequence' => null,
            'fingerprint' => md5('test|'),
            'accepted' => true,
            'rejection_reason' => null,
            'previous_state_json' => ['trust' => 50, 'interest' => 50],
            'new_state_json' => ['trust' => 55, 'interest' => 55],
        ];
    }
}
