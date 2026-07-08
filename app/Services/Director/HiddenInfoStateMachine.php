<?php

namespace App\Services\Director;

class HiddenInfoStateMachine
{
    private const array TRANSITION_RULES = [
        HiddenInfoState::LOCKED->value => [
            'to' => HiddenInfoState::ELIGIBLE,
            'events' => [
                ['type' => 'RELEVANT_FOLLOW_UP', 'needs_topic' => true, 'trust_mult' => 0.7],
                ['type' => 'EMPATHIC_RESPONSE', 'needs_topic' => true, 'trust_mult' => 0.8],
                ['type' => 'CLEAR_EXPLANATION', 'needs_topic' => true, 'trust_mult' => 0.9],
                ['type' => 'TRUST_SIGNAL', 'needs_topic' => false, 'trust_mult' => 1.0],
                ['type' => 'CONCERN_DISCOVERED', 'needs_topic' => true, 'trust_mult' => 0.8],
                ['type' => 'APPROPRIATE_NEXT_STEP', 'needs_topic' => true, 'trust_mult' => 0.9],
            ],
        ],
        HiddenInfoState::ELIGIBLE->value => [
            'to' => HiddenInfoState::DISCLOSED_PARTIAL,
            'events' => [
                ['type' => 'RELEVANT_FOLLOW_UP', 'needs_topic' => true, 'trust_mult' => 1.0],
                ['type' => 'EMPATHIC_RESPONSE', 'needs_topic' => true, 'trust_mult' => 1.0],
                ['type' => 'CLEAR_EXPLANATION', 'needs_topic' => true, 'trust_mult' => 1.1],
                ['type' => 'APPROPRIATE_NEXT_STEP', 'needs_topic' => true, 'trust_mult' => 1.1],
                ['type' => 'TRUST_SIGNAL', 'needs_topic' => false, 'trust_mult' => 1.2],
                ['type' => 'CONCERN_DISCOVERED', 'needs_topic' => true, 'trust_mult' => 1.0],
            ],
        ],
        HiddenInfoState::DISCLOSED_PARTIAL->value => [
            'to' => HiddenInfoState::DISCLOSED_FULL,
            'events' => [
                ['type' => 'EMPATHIC_RESPONSE', 'needs_topic' => true, 'trust_mult' => 1.1],
                ['type' => 'RELEVANT_FOLLOW_UP', 'needs_topic' => true, 'trust_mult' => 1.2],
                ['type' => 'CLEAR_EXPLANATION', 'needs_topic' => true, 'trust_mult' => 1.3],
                ['type' => 'TRUST_SIGNAL', 'needs_topic' => false, 'trust_mult' => 1.5],
                ['type' => 'APPROPRIATE_NEXT_STEP', 'needs_topic' => true, 'trust_mult' => 1.3],
            ],
        ],
    ];

    /** @var array<string, HiddenInfoState> key => state */
    private array $states = [];

    /** @var array<string, array> */
    private array $configs = [];

    private int $transitionCount = 0;
    private const int MAX_TRANSITIONS = 50;

    public function register(
        string $key,
        string $title = '',
        int $sensitivity = 50,
        int $disclosureDifficulty = 50,
        array $relevantTopics = [],
        int $directQuestionEffectiveness = 50,
        int $trustRequirement = 50,
    ): void {
        $this->configs[$key] = [
            'title' => $title,
            'sensitivity' => $sensitivity,
            'disclosure_difficulty' => $disclosureDifficulty,
            'relevant_topics' => $relevantTopics,
            'direct_question_effectiveness' => $directQuestionEffectiveness,
            'trust_requirement' => $trustRequirement,
        ];

        $this->states[$key] ??= HiddenInfoState::LOCKED;
    }

    public function has(string $key): bool
    {
        return isset($this->states[$key]);
    }

    public function getState(string $key): HiddenInfoState
    {
        return $this->states[$key] ?? HiddenInfoState::LOCKED;
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

    public function processEvent(RoleplayEvent $event, int $currentTrust, int $disclosureResistance = 50): ?HiddenInfoTransition
    {
        if ($this->transitionCount >= self::MAX_TRANSITIONS) {
            return null;
        }

        $eventType = $event->type->value;
        $key = $event->relatedObjectionKey;

        if ($key !== null && isset($this->states[$key])) {
            return $this->evaluateTransition($key, $eventType, $event, $currentTrust, $disclosureResistance);
        }

        if ($key === null) {
            return $this->findFirstMatching($eventType, $event, $currentTrust, $disclosureResistance);
        }

        return null;
    }

    private function evaluateTransition(string $key, string $eventType, RoleplayEvent $event, int $currentTrust, int $disclosureResistance = 50): ?HiddenInfoTransition
    {
        $currentState = $this->states[$key];
        $config = $this->configs[$key] ?? null;

        if ($config === null) {
            return null;
        }

        $stateRules = self::TRANSITION_RULES[$currentState->value] ?? null;

        if ($stateRules === null) {
            return new HiddenInfoTransition(
                key: $key,
                fromState: $currentState,
                toState: $currentState,
                triggeredBy: $event->type,
                accepted: false,
                rejectionReason: "No transition from {$currentState->value}",
            );
        }

        $matchingRule = $this->findMatchingEventRule($stateRules['events'], $eventType, $event, $config);

        if ($matchingRule === null) {
            return new HiddenInfoTransition(
                key: $key,
                fromState: $currentState,
                toState: $currentState,
                triggeredBy: $event->type,
                accepted: false,
                rejectionReason: "Event {$eventType} does not trigger transition from {$currentState->value}",
            );
        }

        if (!$this->checkTrustRequirement($config, $matchingRule['trust_mult'], $currentTrust, $disclosureResistance)) {
            return new HiddenInfoTransition(
                key: $key,
                fromState: $currentState,
                toState: $currentState,
                triggeredBy: $event->type,
                accepted: false,
                rejectionReason: 'Trust requirement not met',
            );
        }

        $targetState = $stateRules['to'];
        $this->states[$key] = $targetState;
        $this->transitionCount++;

        return new HiddenInfoTransition(
            key: $key,
            fromState: $currentState,
            toState: $targetState,
            triggeredBy: $event->type,
            accepted: true,
            directorNote: $this->buildDirectorNote($currentState, $targetState, $config['title']),
        );
    }

    private function findFirstMatching(string $eventType, RoleplayEvent $event, int $currentTrust, int $disclosureResistance = 50): ?HiddenInfoTransition
    {
        foreach ($this->states as $key => $currentState) {
            $config = $this->configs[$key] ?? null;
            if ($config === null) {
                continue;
            }

            $stateRules = self::TRANSITION_RULES[$currentState->value] ?? null;
            if ($stateRules === null) {
                continue;
            }

            $matchingRule = $this->findMatchingEventRule($stateRules['events'], $eventType, $event, $config);
            if ($matchingRule === null) {
                continue;
            }

            if (!$this->checkTrustRequirement($config, $matchingRule['trust_mult'], $currentTrust, $disclosureResistance)) {
                continue;
            }

            $targetState = $stateRules['to'];
            $this->states[$key] = $targetState;
            $this->transitionCount++;

            return new HiddenInfoTransition(
                key: $key,
                fromState: $currentState,
                toState: $targetState,
                triggeredBy: $event->type,
                accepted: true,
                directorNote: $this->buildDirectorNote($currentState, $targetState, $config['title']),
            );
        }

        return null;
    }

    private function findMatchingEventRule(array $eventRules, string $eventType, RoleplayEvent $event, array $config): ?array
    {
        foreach ($eventRules as $rule) {
            if ($rule['type'] !== $eventType) {
                continue;
            }

            $needsTopic = $rule['needs_topic'];

            $dqe = $config['direct_question_effectiveness'];
            $effectivelyNeedsTopic = $needsTopic || $dqe < 30;

            if ($effectivelyNeedsTopic && !$this->matchesTopic($event, $config)) {
                continue;
            }

            return $rule;
        }

        return null;
    }

    private function matchesTopic(RoleplayEvent $event, array $config): bool
    {
        if (empty($event->topic)) {
            return false;
        }

        $topics = $config['relevant_topics'] ?? [];

        $eventTopic = mb_strtolower(trim($event->topic));

        foreach ($topics as $topic) {
            if (mb_strtolower(trim($topic)) === $eventTopic) {
                return true;
            }
        }

        return false;
    }

    private function checkTrustRequirement(array $config, float $trustMult, int $currentTrust, int $disclosureResistance = 50): bool
    {
        $trustReq = $config['trust_requirement'];
        $sensitivity = $config['sensitivity'];
        $dqe = $config['direct_question_effectiveness'];

        $sensitivityFactor = 1.0 + (($sensitivity - 50) / 200.0);
        $dqeFactor = 1.0 + ((50 - $dqe) / 200.0);
        $resistanceFactor = 1.0 + (($disclosureResistance - 50) / 200.0);

        $requiredTrust = (int) ceil($trustReq * $trustMult * $sensitivityFactor * $dqeFactor * $resistanceFactor);

        return $currentTrust >= $requiredTrust;
    }

    public function restoreState(string $key, HiddenInfoState $state): void
    {
        if (isset($this->states[$key])) {
            $this->states[$key] = $state;
        }
    }

    public function reset(): void
    {
        $this->states = [];
        $this->configs = [];
        $this->transitionCount = 0;
    }

    private function buildDirectorNote(HiddenInfoState $from, HiddenInfoState $to, string $title): ?string
    {
        return match (true) {
            $from === HiddenInfoState::LOCKED && $to === HiddenInfoState::ELIGIBLE =>
                "You may now consider revealing: {$title}. The conversation creates an opportunity.",

            $from === HiddenInfoState::ELIGIBLE && $to === HiddenInfoState::DISCLOSED_PARTIAL =>
                "You may now partially reveal: {$title}. Do not provide every detail yet.",

            $from === HiddenInfoState::DISCLOSED_PARTIAL && $to === HiddenInfoState::DISCLOSED_FULL =>
                "Trust is sufficient. You may now fully disclose: {$title}. Reveal the complete information naturally.",

            default => null,
        };
    }
}
