<?php

namespace Tests\Unit;

use App\Services\Director\DirectorState;
use App\Services\Director\HiddenInfoState;
use App\Services\Director\HiddenInfoStateMachine;
use App\Services\Director\HiddenInfoTransition;
use App\Services\Director\RoleplayDirectorEngine;
use App\Services\Director\RoleplayEvent;
use App\Services\Director\RoleplayEventType;
use Tests\TestCase;

class HiddenInfoStateMachineTest extends TestCase
{
    public function test_hidden_information_starts_locked(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik_issue', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $this->assertSame(HiddenInfoState::LOCKED, $hism->getState('slik_issue'));
    }

    public function test_relevant_topic_with_sufficient_trust_unlocks_to_eligible(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik_issue', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $event = new RoleplayEvent(
            RoleplayEventType::RELEVANT_FOLLOW_UP,
            topic: 'income',
            relatedObjectionKey: 'slik_issue',
        );

        $transition = $hism->processEvent($event, currentTrust: 50);

        $this->assertNotNull($transition);
        $this->assertTrue($transition->accepted);
        $this->assertSame(HiddenInfoState::LOCKED, $transition->fromState);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $transition->toState);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $hism->getState('slik_issue'));
        $this->assertNotNull($transition->directorNote);
    }

    public function test_low_trust_does_not_unlock(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik_issue', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $event = new RoleplayEvent(
            RoleplayEventType::RELEVANT_FOLLOW_UP,
            topic: 'income',
            relatedObjectionKey: 'slik_issue',
        );

        $transition = $hism->processEvent($event, currentTrust: 10);

        $this->assertNotNull($transition);
        $this->assertFalse($transition->accepted);
        $this->assertSame('Trust requirement not met', $transition->rejectionReason);
        $this->assertSame(HiddenInfoState::LOCKED, $hism->getState('slik_issue'));
    }

    public function test_eligible_to_partial_disclosure(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik_issue', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income', relatedObjectionKey: 'slik_issue'),
            currentTrust: 50,
        );

        $transition = $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'income', relatedObjectionKey: 'slik_issue'),
            currentTrust: 55,
        );

        $this->assertNotNull($transition);
        $this->assertTrue($transition->accepted);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $transition->fromState);
        $this->assertSame(HiddenInfoState::DISCLOSED_PARTIAL, $transition->toState);
        $this->assertSame(HiddenInfoState::DISCLOSED_PARTIAL, $hism->getState('slik_issue'));
        $this->assertNotNull($transition->directorNote);
    }

    public function test_partial_to_full_disclosure(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik_issue', 'SLIK Issue', 30, 50, ['income'], 70, 50);

        $hism->processEvent(new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income', relatedObjectionKey: 'slik_issue'), currentTrust: 50);
        $hism->processEvent(new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'income', relatedObjectionKey: 'slik_issue'), currentTrust: 55);

        $transition = $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income', relatedObjectionKey: 'slik_issue'),
            currentTrust: 75,
        );

        $this->assertTrue($transition->accepted);
        $this->assertSame(HiddenInfoState::DISCLOSED_PARTIAL, $transition->fromState);
        $this->assertSame(HiddenInfoState::DISCLOSED_FULL, $transition->toState);
        $this->assertSame(HiddenInfoState::DISCLOSED_FULL, $hism->getState('slik_issue'));
        $this->assertNotNull($transition->directorNote);
    }

    public function test_unrelated_topic_does_not_unlock(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik_issue', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $event = new RoleplayEvent(
            RoleplayEventType::RELEVANT_FOLLOW_UP,
            topic: 'location',
            relatedObjectionKey: 'slik_issue',
        );

        $transition = $hism->processEvent($event, currentTrust: 80);

        $this->assertNotNull($transition);
        $this->assertFalse($transition->accepted);
        $this->assertSame(HiddenInfoState::LOCKED, $hism->getState('slik_issue'));
    }

    public function test_trust_signal_can_unlock_when_trust_is_high_enough(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik_issue', 'SLIK Issue', 30, 50, ['income'], 50, 50);

        $event = new RoleplayEvent(
            RoleplayEventType::TRUST_SIGNAL,
            relatedObjectionKey: 'slik_issue',
        );

        $transition = $hism->processEvent($event, currentTrust: 60);

        $this->assertTrue($transition->accepted);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $transition->toState);
    }

    public function test_low_trust_trust_signal_does_not_unlock(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik_issue', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $event = new RoleplayEvent(
            RoleplayEventType::TRUST_SIGNAL,
            relatedObjectionKey: 'slik_issue',
        );

        $transition = $hism->processEvent($event, currentTrust: 30);

        $this->assertFalse($transition->accepted);
        $this->assertSame(HiddenInfoState::LOCKED, $hism->getState('slik_issue'));
    }

    public function test_high_sensitivity_requires_more_trust(): void
    {
        $hismLow = new HiddenInfoStateMachine;
        $hismLow->register('low', 'Low Sensitivity', 10, 50, ['topic'], 50, 70);

        $hismHigh = new HiddenInfoStateMachine;
        $hismHigh->register('high', 'High Sensitivity', 90, 50, ['topic'], 50, 70);

        $event = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'topic', relatedObjectionKey: 'low');
        $resultLow = $hismLow->processEvent($event, currentTrust: 50);
        $this->assertTrue($resultLow->accepted);

        $event2 = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'topic', relatedObjectionKey: 'high');
        $resultHigh = $hismHigh->processEvent($event2, currentTrust: 50);
        $this->assertFalse($resultHigh->accepted);
    }

    public function test_multiple_hidden_items_are_independent(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 30, 50, ['income'], 50, 50);
        $hism->register('spouse', 'Spouse Disagreement', 50, 50, ['family'], 50, 70);

        $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income', relatedObjectionKey: 'slik'),
            currentTrust: 50,
        );

        $this->assertSame(HiddenInfoState::ELIGIBLE, $hism->getState('slik'));
        $this->assertSame(HiddenInfoState::LOCKED, $hism->getState('spouse'));
    }

    public function test_no_event_without_key_matches_first_applicable(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 30, 50, ['income'], 50, 50);
        $hism->register('spouse', 'Spouse Issue', 30, 50, ['income'], 50, 50);

        $transition = $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income'),
            currentTrust: 50,
        );

        $this->assertNotNull($transition);
        $this->assertTrue($transition->accepted);
        $this->assertSame('slik', $transition->key);
    }

    public function test_invalid_event_type_returns_rejected(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $transition = $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::GOOD_OPENING, relatedObjectionKey: 'slik'),
            currentTrust: 80,
        );

        $this->assertNotNull($transition);
        $this->assertFalse($transition->accepted);
    }

    public function test_unknown_key_is_ignored(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('known', 'Known', 50, 50, ['topic'], 50, 50);

        $transition = $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'topic', relatedObjectionKey: 'unknown'),
            currentTrust: 80,
        );

        $this->assertNull($transition);
    }

    public function test_state_map_returns_all_states(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('a', 'A', 50, 50, [], 50, 50);
        $hism->register('b', 'B', 50, 50, [], 50, 50);

        $map = $hism->getStateMap();

        $this->assertSame('LOCKED', $map['a']);
        $this->assertSame('LOCKED', $map['b']);
    }

    public function test_deterministic_output(): void
    {
        $hism1 = new HiddenInfoStateMachine;
        $hism1->register('x', 'X', 50, 50, ['topic'], 50, 50);

        $hism2 = new HiddenInfoStateMachine;
        $hism2->register('x', 'X', 50, 50, ['topic'], 50, 50);

        $event = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'topic', relatedObjectionKey: 'x');

        $hism1->processEvent($event, currentTrust: 60);
        $hism2->processEvent($event, currentTrust: 60);

        $this->assertSame($hism1->getStateMap(), $hism2->getStateMap());
    }

    public function test_reset_clears_all_state(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('x', 'X', 50, 50, ['topic'], 50, 50);
        $hism->processEvent(new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'topic', relatedObjectionKey: 'x'), currentTrust: 60);

        $hism->reset();

        $this->assertFalse($hism->has('x'));
    }

    public function test_clear_explanation_with_topic_unlocks_with_sufficient_trust(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 30, 50, ['income'], 50, 50);

        $transition = $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::CLEAR_EXPLANATION, topic: 'income', relatedObjectionKey: 'slik'),
            currentTrust: 55,
        );

        $this->assertTrue($transition->accepted);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $transition->toState);
    }

    public function test_empathic_response_with_topic_unlocks(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 30, 50, ['income'], 50, 50);

        $transition = $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'income', relatedObjectionKey: 'slik'),
            currentTrust: 50,
        );

        $this->assertTrue($transition->accepted);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $transition->toState);
    }

    public function test_concern_discovered_with_topic_unlocks(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 30, 50, ['income'], 50, 50);

        $transition = $hism->processEvent(
            new RoleplayEvent(RoleplayEventType::CONCERN_DISCOVERED, topic: 'income', relatedObjectionKey: 'slik'),
            currentTrust: 50,
        );

        $this->assertTrue($transition->accepted);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $transition->toState);
    }

    public function test_engine_integration_returns_hidden_info_transitions(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 30, 50, ['income'], 50, 50);

        $engine = new RoleplayDirectorEngine(
            hiddenInfoStateMachine: $hism,
        );

        $state = DirectorState::default();
        $result = $engine->applyEvent(
            new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income', relatedObjectionKey: 'slik'),
            $state,
        );

        $this->assertTrue($result->accepted);
        $this->assertCount(1, $result->hiddenInfoTransitions);

        $transition = $result->hiddenInfoTransitions[0];
        $this->assertInstanceOf(HiddenInfoTransition::class, $transition);
        $this->assertSame('slik', $transition->key);
        $this->assertSame(HiddenInfoState::LOCKED, $transition->fromState);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $transition->toState);
    }

    public function test_engine_integration_without_hism_returns_empty(): void
    {
        $engine = new RoleplayDirectorEngine;
        $state = DirectorState::default();

        $result = $engine->applyEvent(
            new RoleplayEvent(RoleplayEventType::GOOD_OPENING),
            $state,
        );

        $this->assertTrue($result->accepted);
        $this->assertEmpty($result->hiddenInfoTransitions);
    }

    public function test_engine_reset_clears_hism(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 30, 50, ['income'], 50, 50);

        $engine = new RoleplayDirectorEngine(
            hiddenInfoStateMachine: $hism,
        );

        $state = DirectorState::default();
        $engine->applyEvent(
            new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income', relatedObjectionKey: 'slik'),
            $state,
        );

        $this->assertTrue($hism->has('slik'));

        $engine->resetMemory();

        $this->assertFalse($hism->has('slik'));
    }

    public function test_full_cycle_locked_to_fully_disclosed(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('secret', 'Secret Info', 20, 50, ['topic'], 70, 50);

        $this->assertSame(HiddenInfoState::LOCKED, $hism->getState('secret'));

        $t1 = $hism->processEvent(new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'topic', relatedObjectionKey: 'secret'), currentTrust: 50);
        $this->assertTrue($t1->accepted);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $hism->getState('secret'));

        $t2 = $hism->processEvent(new RoleplayEvent(RoleplayEventType::EMPATHIC_RESPONSE, topic: 'topic', relatedObjectionKey: 'secret'), currentTrust: 55);
        $this->assertTrue($t2->accepted);
        $this->assertSame(HiddenInfoState::DISCLOSED_PARTIAL, $hism->getState('secret'));

        $t3 = $hism->processEvent(new RoleplayEvent(RoleplayEventType::TRUST_SIGNAL, relatedObjectionKey: 'secret'), currentTrust: 90);
        $this->assertTrue($t3->accepted);
        $this->assertSame(HiddenInfoState::DISCLOSED_FULL, $hism->getState('secret'));
    }

    public function test_direct_question_effectiveness_affects_unlocking(): void
    {
        $hismLow = new HiddenInfoStateMachine;
        $hismLow->register('low_dqe', 'Low DQE', 30, 50, ['topic'], 10, 50);

        $hismHigh = new HiddenInfoStateMachine;
        $hismHigh->register('high_dqe', 'High DQE', 30, 50, ['topic'], 90, 50);

        $event = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'topic', relatedObjectionKey: 'low_dqe');
        $resultLow = $hismLow->processEvent($event, currentTrust: 50);
        $this->assertTrue($resultLow->accepted);

        $event2 = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'topic', relatedObjectionKey: 'high_dqe');
        $resultHigh = $hismHigh->processEvent($event2, currentTrust: 39);
        $this->assertTrue($resultHigh->accepted);
    }

    public function test_normal_disclosure_resistance_uses_default_trust(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $event = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income', relatedObjectionKey: 'slik');

        $needs = $hism->processEvent($event, currentTrust: 34, disclosureResistance: 50);
        $this->assertFalse($needs->accepted);

        $sufficient = $hism->processEvent($event, currentTrust: 35, disclosureResistance: 50);
        $this->assertTrue($sufficient->accepted);
    }

    public function test_high_disclosure_resistance_requires_more_trust(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $event = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income', relatedObjectionKey: 'slik');

        $insufficient = $hism->processEvent($event, currentTrust: 41, disclosureResistance: 85);
        $this->assertFalse($insufficient->accepted);
        $this->assertSame('Trust requirement not met', $insufficient->rejectionReason);

        $sufficient = $hism->processEvent($event, currentTrust: 42, disclosureResistance: 85);
        $this->assertTrue($sufficient->accepted);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $sufficient->toState);
    }

    public function test_low_disclosure_resistance_makes_disclosure_easier(): void
    {
        $hism = new HiddenInfoStateMachine;
        $hism->register('slik', 'SLIK Issue', 50, 50, ['income'], 50, 50);

        $event = new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, topic: 'income', relatedObjectionKey: 'slik');

        $borderline = $hism->processEvent($event, currentTrust: 29, disclosureResistance: 20);
        $this->assertFalse($borderline->accepted);

        $sufficient = $hism->processEvent($event, currentTrust: 30, disclosureResistance: 20);
        $this->assertTrue($sufficient->accepted);
        $this->assertSame(HiddenInfoState::ELIGIBLE, $sufficient->toState);
    }

    public function test_hidden_info_transition_to_array(): void
    {
        $transition = new HiddenInfoTransition(
            key: 'test_key',
            fromState: HiddenInfoState::LOCKED,
            toState: HiddenInfoState::ELIGIBLE,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
            directorNote: 'Test note',
        );

        $array = $transition->toArray();

        $this->assertSame('test_key', $array['key']);
        $this->assertSame('LOCKED', $array['from_state']);
        $this->assertSame('ELIGIBLE', $array['to_state']);
        $this->assertSame('RELEVANT_FOLLOW_UP', $array['triggered_by']);
        $this->assertTrue($array['accepted']);
        $this->assertSame('Test note', $array['director_note']);
    }
}
