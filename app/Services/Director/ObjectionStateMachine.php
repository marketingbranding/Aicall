<?php

namespace App\Services\Director;

class ObjectionStateMachine
{
    private const array EVENT_TRANSITIONS = [
        'OBJECTION_TRIGGERED' => [
            ObjectionState::DORMANT->value => [self::KEY_VISIBLE => ObjectionState::ACTIVE_VISIBLE, self::KEY_HIDDEN => ObjectionState::ACTIVE_HIDDEN],
            ObjectionState::ACTIVE_HIDDEN->value => [self::KEY_ANY => ObjectionState::ACTIVE_HIDDEN],
            ObjectionState::REACTIVATED->value => [self::KEY_VISIBLE => ObjectionState::ACTIVE_VISIBLE, self::KEY_HIDDEN => ObjectionState::ACTIVE_HIDDEN],
        ],
        'RELEVANT_FOLLOW_UP' => [
            ObjectionState::ACTIVE_HIDDEN->value => [self::KEY_ANY => ObjectionState::ACTIVE_VISIBLE],
        ],
        'CONCERN_DISCOVERED' => [
            ObjectionState::ACTIVE_HIDDEN->value => [self::KEY_ANY => ObjectionState::ACTIVE_VISIBLE],
        ],
        'OBJECTION_ACKNOWLEDGED' => [
            ObjectionState::ACTIVE_VISIBLE->value => [self::KEY_ANY => ObjectionState::ACKNOWLEDGED],
        ],
        'OBJECTION_PARTIALLY_RESOLVED' => [
            ObjectionState::ACKNOWLEDGED->value => [self::KEY_ANY => ObjectionState::PARTIALLY_RESOLVED],
        ],
        'OBJECTION_RESOLVED_CANDIDATE' => [
            ObjectionState::PARTIALLY_RESOLVED->value => [self::KEY_ANY => ObjectionState::RESOLVED],
        ],
        'DISMISSED_CONCERN' => [
            ObjectionState::RESOLVED->value => [self::KEY_ANY => ObjectionState::REACTIVATED],
            ObjectionState::PARTIALLY_RESOLVED->value => [self::KEY_ANY => ObjectionState::REACTIVATED],
            ObjectionState::ACKNOWLEDGED->value => [self::KEY_ANY => ObjectionState::ACTIVE_VISIBLE],
        ],
        'UNSUPPORTED_CLAIM' => [
            ObjectionState::RESOLVED->value => [self::KEY_ANY => ObjectionState::REACTIVATED],
            ObjectionState::PARTIALLY_RESOLVED->value => [self::KEY_ANY => ObjectionState::REACTIVATED],
        ],
        'CONTRADICTORY_STATEMENT' => [
            ObjectionState::RESOLVED->value => [self::KEY_ANY => ObjectionState::REACTIVATED],
            ObjectionState::PARTIALLY_RESOLVED->value => [self::KEY_ANY => ObjectionState::REACTIVATED],
        ],
    ];

    private const string KEY_VISIBLE = '__VISIBLE__';
    private const string KEY_HIDDEN = '__HIDDEN__';
    private const string KEY_ANY = '__ANY__';

    /** @var array<string, ObjectionState> objectionKey => state */
    private array $states = [];

    /** @var array<string, array{visibility: string, persistence: int, title: string}> */
    private array $configs = [];

    private int $transitionCount = 0;
    private const int MAX_TRANSITIONS = 50;

    public function register(string $key, string $visibility = 'VISIBLE', int $persistence = 50, string $title = ''): void
    {
        $this->configs[$key] = [
            'visibility' => $visibility,
            'persistence' => $persistence,
            'title' => $title,
        ];

        $initialState = $visibility === 'HIDDEN'
            ? ObjectionState::ACTIVE_HIDDEN
            : ObjectionState::DORMANT;

        $this->states[$key] ??= $initialState;
    }

    public function has(string $key): bool
    {
        return isset($this->states[$key]);
    }

    public function getState(string $key): ObjectionState
    {
        return $this->states[$key] ?? ObjectionState::DORMANT;
    }

    /** @return array<string, string> */
    public function getStateMap(): array
    {
        $map = [];
        foreach ($this->states as $key => $state) {
            $map[$key] = $state->value;
        }
        return $map;
    }

    public function processEvent(RoleplayEvent $event, int $objectionPersistence = 50): ?ObjectionTransition
    {
        if ($this->transitionCount >= self::MAX_TRANSITIONS) {
            return null;
        }

        $eventType = $event->type->value;
        $objectionKey = $event->relatedObjectionKey;

        $transitionDef = self::EVENT_TRANSITIONS[$eventType] ?? null;

        if ($transitionDef === null) {
            return null;
        }

        if ($objectionKey !== null && isset($this->states[$objectionKey])) {
            return $this->applyTransition($objectionKey, $eventType, $transitionDef, $event, $objectionPersistence);
        }

        if ($objectionKey === null) {
            $matched = $this->findFirstMatchingObjection($eventType, $transitionDef, $event, $objectionPersistence);
            if ($matched !== null) {
                return $matched;
            }
        }

        return null;
    }

    private function applyTransition(string $key, string $eventType, array $transitionDef, RoleplayEvent $event, int $objectionPersistence = 50): ?ObjectionTransition
    {
        $currentState = $this->states[$key];
        $fromValue = $currentState->value;
        $visibility = $this->configs[$key]['visibility'] ?? 'VISIBLE';

        $stateRules = $transitionDef[$fromValue] ?? null;

        if ($stateRules === null) {
            return new ObjectionTransition(
                objectionKey: $key,
                fromState: $currentState,
                toState: $currentState,
                triggeredBy: $event->type,
                accepted: false,
                rejectionReason: "No transition from {$currentState->value} for event {$eventType}",
            );
        }

        $targetState = $stateRules[self::KEY_ANY]
            ?? $stateRules[$visibility === 'HIDDEN' ? self::KEY_HIDDEN : self::KEY_VISIBLE]
            ?? null;

        if ($targetState === null) {
            return new ObjectionTransition(
                objectionKey: $key,
                fromState: $currentState,
                toState: $currentState,
                triggeredBy: $event->type,
                accepted: false,
                rejectionReason: "No matching visibility rule for {$visibility}",
            );
        }

        if ($this->isForwardResolution($currentState, $targetState) && !$this->isPersistenceSufficient($objectionPersistence, $currentState)) {
            return new ObjectionTransition(
                objectionKey: $key,
                fromState: $currentState,
                toState: $currentState,
                triggeredBy: $event->type,
                accepted: false,
                rejectionReason: 'Objection persistence too high for transition',
            );
        }

        $this->states[$key] = $targetState;
        $this->transitionCount++;

        return new ObjectionTransition(
            objectionKey: $key,
            fromState: $currentState,
            toState: $targetState,
            triggeredBy: $event->type,
            accepted: true,
            directorNote: $this->buildDirectorNote($currentState, $targetState, $event->type, $key),
        );
    }

    private function findFirstMatchingObjection(string $eventType, array $transitionDef, RoleplayEvent $event, int $objectionPersistence = 50): ?ObjectionTransition
    {
        foreach ($this->states as $key => $currentState) {
            $fromValue = $currentState->value;
            $stateRules = $transitionDef[$fromValue] ?? null;

            if ($stateRules === null) {
                continue;
            }

            $visibility = $this->configs[$key]['visibility'] ?? 'VISIBLE';

            $targetState = $stateRules[self::KEY_ANY]
                ?? $stateRules[$visibility === 'HIDDEN' ? self::KEY_HIDDEN : self::KEY_VISIBLE]
                ?? null;

            if ($targetState === null) {
                continue;
            }

            if ($this->isForwardResolution($currentState, $targetState) && !$this->isPersistenceSufficient($objectionPersistence, $currentState)) {
                continue;
            }

            $this->states[$key] = $targetState;
            $this->transitionCount++;

            return new ObjectionTransition(
                objectionKey: $key,
                fromState: $currentState,
                toState: $targetState,
                triggeredBy: $event->type,
                accepted: true,
                directorNote: $this->buildDirectorNote($currentState, $targetState, $event->type, $key),
            );
        }

        return null;
    }

    public function reset(): void
    {
        $this->states = [];
        $this->configs = [];
        $this->transitionCount = 0;
    }

    private function isForwardResolution(ObjectionState $from, ObjectionState $to): bool
    {
        return match (true) {
            $from === ObjectionState::ACKNOWLEDGED && $to === ObjectionState::PARTIALLY_RESOLVED => true,
            $from === ObjectionState::PARTIALLY_RESOLVED && $to === ObjectionState::RESOLVED => true,
            default => false,
        };
    }

    private function isPersistenceSufficient(int $persistence, ObjectionState $from): bool
    {
        return match ($from) {
            ObjectionState::ACKNOWLEDGED => $persistence < 85,
            ObjectionState::PARTIALLY_RESOLVED => $persistence < 65,
            default => true,
        };
    }

    private function buildDirectorNote(ObjectionState $from, ObjectionState $to, RoleplayEventType $event, string $key): ?string
    {
        $title = $this->configs[$key]['title'] ?? $key;

        return match (true) {
            $from === ObjectionState::ACTIVE_HIDDEN && $to === ObjectionState::ACTIVE_VISIBLE =>
                "Salesperson asked relevant question. You may now reveal your concern: {$title}. Reveal naturally, do not explain all details yet.",

            $from === ObjectionState::DORMANT && $to === ObjectionState::ACTIVE_VISIBLE =>
                "The topic of {$title} has come up. You are concerned about this.",

            $from === ObjectionState::DORMANT && $to === ObjectionState::ACTIVE_HIDDEN =>
                "The topic of {$title} has come up. You are concerned but do not reveal it yet.",

            $from === ObjectionState::ACTIVE_VISIBLE && $to === ObjectionState::ACKNOWLEDGED =>
                "Salesperson acknowledged your concern about {$title}.",

            $from === ObjectionState::ACKNOWLEDGED && $to === ObjectionState::PARTIALLY_RESOLVED =>
                "Salesperson provided relevant information about {$title}. It helps somewhat but the concern persists.",

            $from === ObjectionState::PARTIALLY_RESOLVED && $to === ObjectionState::RESOLVED =>
                "Salesperson addressed {$title} well. You can consider it resolved for now.",

            $from === ObjectionState::RESOLVED && $to === ObjectionState::REACTIVATED =>
                "Your concern about {$title} returns after the salesperson's recent behavior.",

            $from === ObjectionState::PARTIALLY_RESOLVED && $to === ObjectionState::REACTIVATED =>
                "Your concern about {$title} intensifies again after the salesperson's recent behavior.",

            $from === ObjectionState::ACKNOWLEDGED && $to === ObjectionState::ACTIVE_VISIBLE =>
                "Your concern about {$title} is active again after the salesperson dismissed it.",

            default => null,
        };
    }
}
