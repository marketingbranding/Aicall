<?php

namespace App\Services\Director;

class RoleplayDirectorEngine
{
    private const int RECENT_MEMORY_MAX = 20;

    private TransitionRuleProvider $ruleProvider;

    /** @var array<string, true> */
    private array $recentFingerprints = [];

    /** @var string[] ordered list of recent fingerprints */
    private array $recentFingerprintOrder = [];

    public function __construct(?TransitionRuleProvider $ruleProvider = null)
    {
        $this->ruleProvider = $ruleProvider ?? new TransitionRuleProvider();
    }

    public function applyEvent(RoleplayEvent $event, DirectorState $currentState): DirectorEngineResult
    {
        $fingerprint = $event->fingerprint();

        if (isset($this->recentFingerprints[$fingerprint])) {
            return new DirectorEngineResult(
                state: $currentState,
                appliedTransition: new StateTransition(),
                accepted: false,
                rejectionReason: 'Duplicate event fingerprint',
            );
        }

        $baseTransition = $this->ruleProvider->getBaseTransition($event->type);

        $newState = $currentState->apply($baseTransition);

        $this->rememberFingerprint($fingerprint);

        return new DirectorEngineResult(
            state: $newState,
            appliedTransition: $baseTransition,
            accepted: true,
        );
    }

    public function validateEvent(RoleplayEvent $event): bool
    {
        return true;
    }

    public function resetMemory(): void
    {
        $this->recentFingerprints = [];
        $this->recentFingerprintOrder = [];
    }

    private function rememberFingerprint(string $fingerprint): void
    {
        $this->recentFingerprints[$fingerprint] = true;
        $this->recentFingerprintOrder[] = $fingerprint;

        if (count($this->recentFingerprintOrder) > self::RECENT_MEMORY_MAX) {
            $oldest = array_shift($this->recentFingerprintOrder);
            unset($this->recentFingerprints[$oldest]);
        }
    }
}
