<?php

namespace App\Services\Director;

class RoleplayDirectorEngine
{
    private const int RECENT_MEMORY_MAX = 20;

    private TransitionRuleProvider $ruleProvider;
    private DiminishingReturnCalculator $diminishingCalculator;

    /** @var array<string, true> */
    private array $recentFingerprints = [];

    /** @var string[] ordered list of recent fingerprints */
    private array $recentFingerprintOrder = [];

    public function __construct(
        ?TransitionRuleProvider $ruleProvider = null,
        ?DiminishingReturnCalculator $diminishingCalculator = null,
    ) {
        $this->ruleProvider = $ruleProvider ?? new TransitionRuleProvider();
        $this->diminishingCalculator = $diminishingCalculator ?? new DiminishingReturnCalculator();
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

        $adjustedTransition = $this->diminishingCalculator->applyDiminishedTransition(
            $event->type,
            $baseTransition,
        );

        $newState = $currentState->apply($adjustedTransition);

        $this->rememberFingerprint($fingerprint);
        $this->diminishingCalculator->record($event->type);

        return new DirectorEngineResult(
            state: $newState,
            appliedTransition: $adjustedTransition,
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
        $this->diminishingCalculator->reset();
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
