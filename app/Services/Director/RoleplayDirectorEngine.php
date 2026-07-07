<?php

namespace App\Services\Director;

class RoleplayDirectorEngine
{
    private const int RECENT_MEMORY_MAX = 20;

    private TransitionRuleProvider $ruleProvider;
    private DiminishingReturnCalculator $diminishingCalculator;
    private ?ObjectionStateMachine $objectionStateMachine;
    private ?HiddenInfoStateMachine $hiddenInfoStateMachine;
    private ?BoundaryStateMachine $boundaryStateMachine;
    private ?ConversationPhaseManager $phaseManager;
    private ?DifficultyModifier $difficultyModifier = null;

    /** @var array<string, true> */
    private array $recentFingerprints = [];

    /** @var string[] ordered list of recent fingerprints */
    private array $recentFingerprintOrder = [];

    public function __construct(
        ?TransitionRuleProvider $ruleProvider = null,
        ?DiminishingReturnCalculator $diminishingCalculator = null,
        ?ObjectionStateMachine $objectionStateMachine = null,
        ?HiddenInfoStateMachine $hiddenInfoStateMachine = null,
        ?BoundaryStateMachine $boundaryStateMachine = null,
        ?ConversationPhaseManager $phaseManager = null,
    ) {
        $this->ruleProvider = $ruleProvider ?? new TransitionRuleProvider();
        $this->diminishingCalculator = $diminishingCalculator ?? new DiminishingReturnCalculator();
        $this->objectionStateMachine = $objectionStateMachine;
        $this->hiddenInfoStateMachine = $hiddenInfoStateMachine;
        $this->boundaryStateMachine = $boundaryStateMachine;
        $this->phaseManager = $phaseManager;
    }

    public function setDifficultyModifier(?DifficultyModifier $modifier): void
    {
        $this->difficultyModifier = $modifier;
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

        $difficultyTransition = $this->difficultyModifier !== null
            ? $this->difficultyModifier->apply($baseTransition)
            : $baseTransition;

        $adjustedTransition = $this->diminishingCalculator->applyDiminishedTransition(
            $event->type,
            $difficultyTransition,
        );

        $newState = $currentState->apply($adjustedTransition);

        $objectionTransitions = [];
        if ($this->objectionStateMachine !== null) {
            $ot = $this->objectionStateMachine->processEvent($event);
            if ($ot !== null) {
                $objectionTransitions[] = $ot;
            }
        }

        $hiddenInfoTransitions = [];
        if ($this->hiddenInfoStateMachine !== null) {
            $ht = $this->hiddenInfoStateMachine->processEvent($event, $newState->getTrust());
            if ($ht !== null) {
                $hiddenInfoTransitions[] = $ht;
            }
        }

        $boundaryTransitions = [];
        if ($this->boundaryStateMachine !== null) {
            $bt = $this->boundaryStateMachine->processEvent($event, $newState->getTrust());
            if ($bt !== null) {
                $boundaryTransitions[] = $bt;
            }
        }

        $phaseTransitions = [];
        if ($this->phaseManager !== null) {
            $pt = $this->phaseManager->processEvent($event);
            if ($pt !== null) {
                $phaseTransitions[] = $pt;
            }
        }

        $this->rememberFingerprint($fingerprint);
        $this->diminishingCalculator->record($event->type);

        return new DirectorEngineResult(
            state: $newState,
            appliedTransition: $adjustedTransition,
            accepted: true,
            objectionTransitions: $objectionTransitions,
            hiddenInfoTransitions: $hiddenInfoTransitions,
            boundaryTransitions: $boundaryTransitions,
            phaseTransitions: $phaseTransitions,
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
        $this->objectionStateMachine?->reset();
        $this->hiddenInfoStateMachine?->reset();
        $this->boundaryStateMachine?->reset();
        $this->phaseManager?->reset();
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
