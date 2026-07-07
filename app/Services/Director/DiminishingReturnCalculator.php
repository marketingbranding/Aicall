<?php

namespace App\Services\Director;

class DiminishingReturnCalculator
{
    private const int RECENT_MAX = 20;

    private const array POSITIVE_EVENT_VALUES = [
        'GOOD_OPENING' => true,
        'ACTIVE_LISTENING' => true,
        'CLEAR_EXPLANATION' => true,
        'RELEVANT_FOLLOW_UP' => true,
        'EMPATHIC_RESPONSE' => true,
        'APPROPRIATE_NEXT_STEP' => true,
        'CONCERN_DISCOVERED' => true,
        'OBJECTION_ACKNOWLEDGED' => true,
        'OBJECTION_PARTIALLY_RESOLVED' => true,
        'OBJECTION_RESOLVED_CANDIDATE' => true,
        'MISCONCEPTION_CHALLENGED' => true,
        'MISCONCEPTION_CLARIFIED_CANDIDATE' => true,
        'CLEAR_PROFESSIONAL_REDIRECTION' => true,
        'EXPLICIT_BOUNDARY_SET' => true,
        'CUSTOMER_RESPECTED_BOUNDARY' => true,
        'CUSTOMER_BECAME_MORE_ENGAGED' => true,
        'TRUST_SIGNAL' => true,
    ];

    /** @var array<string, int> event type value => count */
    private array $counts = [];

    /** @var string[] ordered event type values for ring-buffer eviction */
    private array $order = [];

    public static function isPositiveEvent(RoleplayEventType $type): bool
    {
        return isset(self::POSITIVE_EVENT_VALUES[$type->value]);
    }

    public function getMultiplier(RoleplayEventType $type): float
    {
        if (!self::isPositiveEvent($type)) {
            return 1.0;
        }

        $count = $this->counts[$type->value] ?? 0;

        return match (true) {
            $count <= 0 => 1.0,
            $count === 1 => 0.5,
            $count === 2 => 0.25,
            default => 0.0,
        };
    }

    public function record(RoleplayEventType $type): void
    {
        $val = $type->value;
        $this->counts[$val] = ($this->counts[$val] ?? 0) + 1;
        $this->order[] = $val;

        if (count($this->order) > self::RECENT_MAX) {
            $oldest = array_shift($this->order);
            $this->counts[$oldest] = ($this->counts[$oldest] ?? 1) - 1;
            if (($this->counts[$oldest] ?? 0) <= 0) {
                unset($this->counts[$oldest]);
            }
        }
    }

    public function applyDiminishedTransition(RoleplayEventType $type, StateTransition $base): StateTransition
    {
        $multiplier = $this->getMultiplier($type);

        if ($multiplier >= 1.0) {
            return $base;
        }

        return $base->withPositiveDiminishing($multiplier);
    }

    public function reset(): void
    {
        $this->counts = [];
        $this->order = [];
    }
}
