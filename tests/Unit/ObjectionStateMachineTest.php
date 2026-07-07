<?php

namespace Tests\Unit;

use App\Services\Director\DirectorState;
use App\Services\Director\ObjectionState;
use App\Services\Director\ObjectionStateMachine;
use App\Services\Director\ObjectionTransition;
use App\Services\Director\RoleplayDirectorEngine;
use App\Services\Director\RoleplayEvent;
use App\Services\Director\RoleplayEventType;
use Tests\TestCase;

class ObjectionStateMachineTest extends TestCase
{
    public function test_hidden_objection_starts_active_hidden(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('installment_heavy', 'HIDDEN', 50, 'Installment too heavy');

        $this->assertSame(ObjectionState::ACTIVE_HIDDEN, $osm->getState('installment_heavy'));
    }

    public function test_visible_objection_starts_dormant(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('location_far', 'VISIBLE', 50, 'Location too far');

        $this->assertSame(ObjectionState::DORMANT, $osm->getState('location_far'));
    }

    public function test_visible_objection_becomes_active_visible_on_trigger(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('location_far', 'VISIBLE', 50, 'Location too far');

        $event = new RoleplayEvent(
            RoleplayEventType::OBJECTION_TRIGGERED,
            topic: 'location',
            relatedObjectionKey: 'location_far',
        );

        $transition = $osm->processEvent($event);

        $this->assertNotNull($transition);
        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::DORMANT, $transition->fromState);
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $transition->toState);
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $osm->getState('location_far'));
    }

    public function test_hidden_objection_stays_hidden_on_trigger(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('installment_heavy', 'HIDDEN', 50, 'Installment too heavy');

        $event = new RoleplayEvent(
            RoleplayEventType::OBJECTION_TRIGGERED,
            topic: 'installment',
            relatedObjectionKey: 'installment_heavy',
        );

        $transition = $osm->processEvent($event);

        $this->assertNotNull($transition);
        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::ACTIVE_HIDDEN, $transition->fromState);
        $this->assertSame(ObjectionState::ACTIVE_HIDDEN, $transition->toState);
    }

    public function test_active_hidden_to_active_visible_on_relevant_follow_up(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('installment_heavy', 'HIDDEN', 50, 'Installment too heavy');

        $event = new RoleplayEvent(
            RoleplayEventType::RELEVANT_FOLLOW_UP,
            topic: 'installment',
            relatedObjectionKey: 'installment_heavy',
        );

        $transition = $osm->processEvent($event);

        $this->assertNotNull($transition);
        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::ACTIVE_HIDDEN, $transition->fromState);
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $transition->toState);
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $osm->getState('installment_heavy'));
        $this->assertNotNull($transition->directorNote);
        $this->assertStringContainsString('Installment too heavy', $transition->directorNote);
    }

    public function test_active_visible_to_acknowledged(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('location_far', 'VISIBLE', 50, 'Location too far');

        $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::OBJECTION_TRIGGERED,
            relatedObjectionKey: 'location_far',
        ));

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::OBJECTION_ACKNOWLEDGED,
            relatedObjectionKey: 'location_far',
        ));

        $this->assertNotNull($transition);
        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $transition->fromState);
        $this->assertSame(ObjectionState::ACKNOWLEDGED, $transition->toState);
        $this->assertSame(ObjectionState::ACKNOWLEDGED, $osm->getState('location_far'));
        $this->assertNotNull($transition->directorNote);
    }

    public function test_acknowledged_to_partially_resolved(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('location_far', 'VISIBLE', 50, 'Location too far');

        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_ACKNOWLEDGED, relatedObjectionKey: 'location_far'));

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::OBJECTION_PARTIALLY_RESOLVED,
            relatedObjectionKey: 'location_far',
        ));

        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::ACKNOWLEDGED, $transition->fromState);
        $this->assertSame(ObjectionState::PARTIALLY_RESOLVED, $transition->toState);
        $this->assertNotNull($transition->directorNote);
    }

    public function test_partially_resolved_to_resolved(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('location_far', 'VISIBLE', 50, 'Location too far');

        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_ACKNOWLEDGED, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_PARTIALLY_RESOLVED, relatedObjectionKey: 'location_far'));

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::OBJECTION_RESOLVED_CANDIDATE,
            relatedObjectionKey: 'location_far',
        ));

        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::PARTIALLY_RESOLVED, $transition->fromState);
        $this->assertSame(ObjectionState::RESOLVED, $transition->toState);
        $this->assertSame(ObjectionState::RESOLVED, $osm->getState('location_far'));
        $this->assertNotNull($transition->directorNote);
    }

    public function test_resolved_objection_reactivates_on_dismissed_concern(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('location_far', 'VISIBLE', 50, 'Location too far');

        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_ACKNOWLEDGED, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_PARTIALLY_RESOLVED, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_RESOLVED_CANDIDATE, relatedObjectionKey: 'location_far'));

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::DISMISSED_CONCERN,
            relatedObjectionKey: 'location_far',
        ));

        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::RESOLVED, $transition->fromState);
        $this->assertSame(ObjectionState::REACTIVATED, $transition->toState);
        $this->assertSame(ObjectionState::REACTIVATED, $osm->getState('location_far'));
        $this->assertNotNull($transition->directorNote);
    }

    public function test_reactivated_objection_becomes_active_visible_on_retrigger(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('location_far', 'VISIBLE', 50, 'Location too far');

        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_ACKNOWLEDGED, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_PARTIALLY_RESOLVED, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_RESOLVED_CANDIDATE, relatedObjectionKey: 'location_far'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::DISMISSED_CONCERN, relatedObjectionKey: 'location_far'));

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::OBJECTION_TRIGGERED,
            relatedObjectionKey: 'location_far',
        ));

        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::REACTIVATED, $transition->fromState);
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $transition->toState);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('location_far', 'VISIBLE', 50);

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::OBJECTION_RESOLVED_CANDIDATE,
            relatedObjectionKey: 'location_far',
        ));

        $this->assertNotNull($transition);
        $this->assertFalse($transition->accepted);
        $this->assertNotNull($transition->rejectionReason);
        $this->assertSame(ObjectionState::DORMANT, $osm->getState('location_far'));
    }

    public function test_unrelated_event_returns_null(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('location_far', 'VISIBLE', 50);

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::GOOD_OPENING,
        ));

        $this->assertNull($transition);
    }

    public function test_unknown_objection_key_is_ignored(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('known_key', 'VISIBLE', 50);

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::OBJECTION_TRIGGERED,
            relatedObjectionKey: 'unknown_key',
        ));

        $this->assertNull($transition);
    }

    public function test_multiple_objections_are_independent(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('installment', 'HIDDEN', 50, 'Installment heavy');
        $osm->register('location', 'VISIBLE', 50, 'Location far');

        $this->assertSame(ObjectionState::ACTIVE_HIDDEN, $osm->getState('installment'));
        $this->assertSame(ObjectionState::DORMANT, $osm->getState('location'));

        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'location'));
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $osm->getState('location'));
        $this->assertSame(ObjectionState::ACTIVE_HIDDEN, $osm->getState('installment'));

        $osm->processEvent(new RoleplayEvent(RoleplayEventType::RELEVANT_FOLLOW_UP, relatedObjectionKey: 'installment'));
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $osm->getState('installment'));
    }

    public function test_state_map_returns_all_states(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('obj_a', 'VISIBLE', 50);
        $osm->register('obj_b', 'HIDDEN', 50);

        $map = $osm->getStateMap();

        $this->assertSame('DORMANT', $map['obj_a']);
        $this->assertSame('ACTIVE_HIDDEN', $map['obj_b']);
    }

    public function test_deterministic_output(): void
    {
        $osm1 = new ObjectionStateMachine;
        $osm1->register('obj', 'VISIBLE', 50);
        $osm1->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'obj'));

        $osm2 = new ObjectionStateMachine;
        $osm2->register('obj', 'VISIBLE', 50);
        $osm2->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'obj'));

        $this->assertSame($osm1->getStateMap(), $osm2->getStateMap());
    }

    public function test_reset_clears_all_state(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('obj', 'VISIBLE', 50);
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'obj'));

        $osm->reset();

        $this->assertFalse($osm->has('obj'));
    }

    public function test_event_without_key_matches_first_applicable_objection(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('obj_a', 'VISIBLE', 50);
        $osm->register('obj_b', 'VISIBLE', 50);

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::OBJECTION_TRIGGERED,
        ));

        $this->assertNotNull($transition);
        $this->assertSame('obj_a', $transition->objectionKey);
    }

    public function test_concern_discovered_discloses_hidden_objection(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('hidden_obj', 'HIDDEN', 50, 'Hidden concern');

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::CONCERN_DISCOVERED,
            relatedObjectionKey: 'hidden_obj',
        ));

        $this->assertNotNull($transition);
        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::ACTIVE_HIDDEN, $transition->fromState);
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $transition->toState);
        $this->assertNotNull($transition->directorNote);
    }

    public function test_unsupported_claim_reactivates_resolved_objection(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('obj', 'VISIBLE', 50, 'Test objection');

        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'obj'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_ACKNOWLEDGED, relatedObjectionKey: 'obj'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_PARTIALLY_RESOLVED, relatedObjectionKey: 'obj'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_RESOLVED_CANDIDATE, relatedObjectionKey: 'obj'));

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::UNSUPPORTED_CLAIM,
            relatedObjectionKey: 'obj',
        ));

        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::REACTIVATED, $transition->toState);
    }

    public function test_contradictory_statement_reactivates_partially_resolved(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('obj', 'VISIBLE', 50, 'Test objection');

        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'obj'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_ACKNOWLEDGED, relatedObjectionKey: 'obj'));
        $osm->processEvent(new RoleplayEvent(RoleplayEventType::OBJECTION_PARTIALLY_RESOLVED, relatedObjectionKey: 'obj'));

        $transition = $osm->processEvent(new RoleplayEvent(
            RoleplayEventType::CONTRADICTORY_STATEMENT,
            relatedObjectionKey: 'obj',
        ));

        $this->assertTrue($transition->accepted);
        $this->assertSame(ObjectionState::REACTIVATED, $transition->toState);
    }

    public function test_engine_integration_updates_objection_state(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('installment', 'HIDDEN', 50, 'Installment heavy');

        $engine = new RoleplayDirectorEngine(
            objectionStateMachine: $osm,
        );

        $state = DirectorState::default();

        $result = $engine->applyEvent(
            new RoleplayEvent(
                RoleplayEventType::RELEVANT_FOLLOW_UP,
                topic: 'installment',
                relatedObjectionKey: 'installment',
            ),
            $state,
        );

        $this->assertTrue($result->accepted);
        $this->assertCount(1, $result->objectionTransitions);

        $transition = $result->objectionTransitions[0];
        $this->assertInstanceOf(ObjectionTransition::class, $transition);
        $this->assertSame('installment', $transition->objectionKey);
        $this->assertSame(ObjectionState::ACTIVE_HIDDEN, $transition->fromState);
        $this->assertSame(ObjectionState::ACTIVE_VISIBLE, $transition->toState);
    }

    public function test_engine_integration_without_osm_returns_empty_transitions(): void
    {
        $engine = new RoleplayDirectorEngine;
        $state = DirectorState::default();

        $result = $engine->applyEvent(
            new RoleplayEvent(RoleplayEventType::GOOD_OPENING),
            $state,
        );

        $this->assertTrue($result->accepted);
        $this->assertEmpty($result->objectionTransitions);
    }

    public function test_engine_memory_reset_also_resets_osm(): void
    {
        $osm = new ObjectionStateMachine;
        $osm->register('obj', 'VISIBLE', 50);

        $engine = new RoleplayDirectorEngine(
            objectionStateMachine: $osm,
        );

        $state = DirectorState::default();
        $engine->applyEvent(
            new RoleplayEvent(RoleplayEventType::OBJECTION_TRIGGERED, relatedObjectionKey: 'obj'),
            $state,
        );

        $this->assertTrue($osm->has('obj'));

        $engine->resetMemory();

        $this->assertFalse($osm->has('obj'));
    }

    public function test_objection_transition_to_array(): void
    {
        $transition = new ObjectionTransition(
            objectionKey: 'test_key',
            fromState: ObjectionState::ACTIVE_HIDDEN,
            toState: ObjectionState::ACTIVE_VISIBLE,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
            directorNote: 'Test note',
        );

        $array = $transition->toArray();

        $this->assertSame('test_key', $array['objection_key']);
        $this->assertSame('ACTIVE_HIDDEN', $array['from_state']);
        $this->assertSame('ACTIVE_VISIBLE', $array['to_state']);
        $this->assertSame('RELEVANT_FOLLOW_UP', $array['triggered_by']);
        $this->assertTrue($array['accepted']);
        $this->assertSame('Test note', $array['director_note']);
    }
}
