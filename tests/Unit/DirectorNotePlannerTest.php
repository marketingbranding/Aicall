<?php

namespace Tests\Unit;

use App\Services\Director\BehaviorTranslationResult;
use App\Services\Director\BoundaryState;
use App\Services\Director\BoundaryTransition;
use App\Services\Director\ConversationPhase;
use App\Services\Director\ConversationPhaseTransition;
use App\Services\Director\DirectorNote;
use App\Services\Director\DirectorNotePlanner;
use App\Services\Director\DirectorState;
use App\Services\Director\HiddenInfoState;
use App\Services\Director\HiddenInfoTransition;
use App\Services\Director\ObjectionState;
use App\Services\Director\ObjectionTransition;
use App\Services\Director\RoleplayEventType;
use App\Services\Director\StateBand;
use App\Services\Director\StateToBehaviorTranslator;
use Tests\TestCase;

class DirectorNotePlannerTest extends TestCase
{
    private DirectorNotePlanner $planner;
    private StateToBehaviorTranslator $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planner = new DirectorNotePlanner;
        $this->translator = new StateToBehaviorTranslator;
    }

    private function translate(DirectorState $state): BehaviorTranslationResult
    {
        return $this->translator->translate($state);
    }

    public function test_note_generated_for_objection_transition(): void
    {
        $prev = DirectorState::default();
        $next = DirectorState::default();

        $objectionTransition = new ObjectionTransition(
            objectionKey: 'installment',
            fromState: ObjectionState::ACTIVE_HIDDEN,
            toState: ObjectionState::ACTIVE_VISIBLE,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
            directorNote: 'Salesperson asked relevant question. You may now reveal your concern: Installment.',
        );

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [$objectionTransition], [], [], [],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('objection', $notes[0]->category);
        $this->assertStringContainsString('Installment', $notes[0]->text);
    }

    public function test_note_generated_for_hidden_info_transition(): void
    {
        $prev = DirectorState::default();
        $next = DirectorState::default();

        $hiddenTransition = new HiddenInfoTransition(
            key: 'past_slik',
            fromState: HiddenInfoState::LOCKED,
            toState: HiddenInfoState::ELIGIBLE,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
            directorNote: 'You may now consider revealing: past SLIK issue.',
        );

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [$hiddenTransition], [], [],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('hidden_info', $notes[0]->category);
        $this->assertStringContainsString('SLIK', $notes[0]->text);
    }

    public function test_note_generated_for_boundary_transition(): void
    {
        $prev = DirectorState::default();
        $next = DirectorState::default();

        $boundaryTransition = new BoundaryTransition(
            fromState: BoundaryState::MILD_TEST_OCCURRED,
            toState: BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            triggeredBy: RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION,
            accepted: true,
            directorNote: 'A clear professional boundary has been set.',
        );

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [$boundaryTransition], [],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('boundary', $notes[0]->category);
        $this->assertStringContainsString('boundary', $notes[0]->text);
    }

    public function test_note_generated_for_phase_transition(): void
    {
        $prev = DirectorState::default();
        $next = DirectorState::default();

        $phaseTransition = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::DISCOVERY,
            toPhase: ConversationPhase::NEED_EXPLORATION,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
        );

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [$phaseTransition],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('phase_change', $notes[0]->category);
        $this->assertStringContainsString('Menggali kebutuhan', $notes[0]->text);
    }

    public function test_no_note_for_insignificant_event(): void
    {
        $prev = DirectorState::default();
        $next = DirectorState::default();

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(0, $notes);
    }

    public function test_note_does_not_expose_numeric_state(): void
    {
        $prev = DirectorState::default();
        $next = new DirectorState(trust: 25, irritation: 65, engagement: 25);

        $objectionTransition = new ObjectionTransition(
            objectionKey: 'price',
            fromState: ObjectionState::DORMANT,
            toState: ObjectionState::ACTIVE_VISIBLE,
            triggeredBy: RoleplayEventType::OBJECTION_TRIGGERED,
            accepted: true,
            directorNote: 'The topic of price has come up. You are concerned about this.',
        );

        $boundaryTransition = new BoundaryTransition(
            fromState: BoundaryState::NOT_TESTED,
            toState: BoundaryState::MILD_TEST_OCCURRED,
            triggeredBy: RoleplayEventType::CUSTOMER_BOUNDARY_TEST,
            accepted: true,
            directorNote: 'The customer tested a personal boundary.',
        );

        $phaseTransition = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::DISCOVERY,
            toPhase: ConversationPhase::EXPLANATION,
            triggeredBy: RoleplayEventType::CLEAR_EXPLANATION,
            accepted: true,
        );

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [$objectionTransition], [], [$boundaryTransition], [$phaseTransition],
        );

        foreach ($notes as $note) {
            $this->assertMatchesRegularExpression('/^\D+$/', $note->text, "Note contains numeric data: {$note->text}");
        }
    }

    public function test_deterministic_output(): void
    {
        $prev = new DirectorState(trust: 60);
        $next = new DirectorState(trust: 75, irritation: 65, engagement: 25);

        $ot = new ObjectionTransition(
            objectionKey: 'installment',
            fromState: ObjectionState::ACTIVE_HIDDEN,
            toState: ObjectionState::ACTIVE_VISIBLE,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
            directorNote: 'You may now reveal this concern.',
        );

        $ht = new HiddenInfoTransition(
            key: 'income',
            fromState: HiddenInfoState::LOCKED,
            toState: HiddenInfoState::ELIGIBLE,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
            directorNote: 'You may now consider revealing: income.',
        );

        $bt = new BoundaryTransition(
            fromState: BoundaryState::MILD_TEST_OCCURRED,
            toState: BoundaryState::CLEAR_BOUNDARY_ESTABLISHED,
            triggeredBy: RoleplayEventType::CLEAR_PROFESSIONAL_REDIRECTION,
            accepted: true,
            directorNote: 'Boundary established.',
        );

        $pt = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::DISCOVERY,
            toPhase: ConversationPhase::NEED_EXPLORATION,
            triggeredBy: RoleplayEventType::RELEVANT_FOLLOW_UP,
            accepted: true,
        );

        $translation = $this->translate($next);

        $notes1 = $this->planner->planNotes(
            $prev, $next, $translation,
            [$ot], [$ht], [$bt], [$pt],
        );

        $notes2 = $this->planner->planNotes(
            $prev, $next, $translation,
            [$ot], [$ht], [$bt], [$pt],
        );

        $this->assertCount(count($notes1), $notes2);

        foreach ($notes1 as $i => $note) {
            $this->assertSame($note->text, $notes2[$i]->text);
            $this->assertSame($note->category, $notes2[$i]->category);
            $this->assertSame($note->priority, $notes2[$i]->priority);
        }
    }

    public function test_rejected_objection_transition_does_not_generate_note(): void
    {
        $prev = DirectorState::default();
        $next = DirectorState::default();

        $rejected = new ObjectionTransition(
            objectionKey: 'price',
            fromState: ObjectionState::DORMANT,
            toState: ObjectionState::DORMANT,
            triggeredBy: RoleplayEventType::UNSUPPORTED_CLAIM,
            accepted: false,
            rejectionReason: 'Not a valid transition',
            directorNote: null,
        );

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [$rejected], [], [], [],
        );

        $this->assertCount(0, $notes);
    }

    public function test_premature_closing_generates_note(): void
    {
        $prev = DirectorState::default();
        $next = DirectorState::default();

        $pt = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::DISCOVERY,
            toPhase: ConversationPhase::DISCOVERY,
            triggeredBy: RoleplayEventType::AGGRESSIVE_CLOSING,
            accepted: true,
            prematureClosing: true,
        );

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [$pt],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('premature_closing', $notes[0]->category);
        $this->assertStringContainsString('terlalu awal', $notes[0]->text);
    }

    public function test_rejected_phase_transition_does_not_generate_note(): void
    {
        $prev = DirectorState::default();
        $next = DirectorState::default();

        $rejected = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::DISCOVERY,
            toPhase: ConversationPhase::DISCOVERY,
            triggeredBy: RoleplayEventType::AGGRESSIVE_CLOSING,
            accepted: false,
            prematureClosing: true,
        );

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [$rejected],
        );

        $this->assertCount(0, $notes);
    }

    public function test_trust_threshold_crossing_generates_note(): void
    {
        $prev = new DirectorState(trust: 40);
        $next = new DirectorState(trust: 25);

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('state_threshold', $notes[0]->category);
        $this->assertStringContainsString('menurun', $notes[0]->text);
    }

    public function test_irritation_threshold_crossing_generates_note(): void
    {
        $prev = new DirectorState(irritation: 40);
        $next = new DirectorState(irritation: 70);

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('state_threshold', $notes[0]->category);
        $this->assertStringContainsString('kesal', $notes[0]->text);
    }

    public function test_engagement_threshold_crossing_generates_note(): void
    {
        $prev = new DirectorState(engagement: 50);
        $next = new DirectorState(engagement: 25, trust: 50);

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('state_threshold', $notes[0]->category);
        $this->assertStringContainsString('menurun', $notes[0]->text);
    }

    public function test_no_threshold_note_for_steady_state(): void
    {
        $prev = new DirectorState(trust: 55, irritation: 45);
        $next = new DirectorState(trust: 58, irritation: 48);

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(0, $notes);
    }

    public function test_no_threshold_note_for_already_past_threshold(): void
    {
        $prev = new DirectorState(trust: 20);
        $next = new DirectorState(trust: 15);

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(0, $notes);
    }

    public function test_multiple_state_thresholds_in_one_event(): void
    {
        $prev = new DirectorState(trust: 50, engagement: 50, irritation: 40);
        $next = new DirectorState(trust: 25, engagement: 25, irritation: 65);

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(3, $notes);
        $categories = array_map(fn(DirectorNote $n) => $n->category, $notes);
        $this->assertSame(['state_threshold', 'state_threshold', 'state_threshold'], $categories);
    }

    public function test_confidence_threshold_crossing_generates_note(): void
    {
        $prev = new DirectorState(trust: 50);
        $next = new DirectorState(trust: 75);

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('state_threshold', $notes[0]->category);
        $this->assertStringContainsString('terbangun', $notes[0]->text);
    }

    public function test_pressure_threshold_crossing_generates_note(): void
    {
        $prev = new DirectorState(trust: 50, pressurePerception: 40);
        $next = new DirectorState(trust: 50, pressurePerception: 70);

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('state_threshold', $notes[0]->category);
        $this->assertStringContainsString('tertekan', $notes[0]->text);
    }

    public function test_anxiety_threshold_crossing_generates_note(): void
    {
        $prev = new DirectorState(trust: 50, anxiety: 50);
        $next = new DirectorState(trust: 50, anxiety: 80);

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('state_threshold', $notes[0]->category);
        $this->assertStringContainsString('meningkat', $notes[0]->text);
    }

    public function test_unknown_phase_transition_uses_fallback_note(): void
    {
        $prev = DirectorState::default();
        $next = DirectorState::default();

        $pt = new ConversationPhaseTransition(
            fromPhase: ConversationPhase::ENDING,
            toPhase: ConversationPhase::OPENING,
            triggeredBy: RoleplayEventType::APPROPRIATE_NEXT_STEP,
            accepted: true,
        );

        $notes = $this->planner->planNotes(
            $prev, $next, $this->translate($next),
            [], [], [], [$pt],
        );

        $this->assertCount(1, $notes);
        $this->assertSame('phase_change', $notes[0]->category);
        $this->assertStringContainsString('berubah', $notes[0]->text);
    }

    public function test_director_note_to_array(): void
    {
        $note = new DirectorNote('Test note', 'test_category', 5);

        $array = $note->toArray();

        $this->assertSame('Test note', $array['text']);
        $this->assertSame('test_category', $array['category']);
        $this->assertSame(5, $array['priority']);
    }
}
