<?php

namespace Tests\Unit;

use App\Services\Director\DirectorNote;
use App\Services\Director\DirectorNoteCooldown;
use Tests\TestCase;

class DirectorNoteCooldownTest extends TestCase
{
    public function test_note_allowed_by_default(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note = new DirectorNote('Test note', 'objection', 2);

        $this->assertTrue($cooldown->isAllowed($note));
    }

    public function test_duplicate_note_is_suppressed(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note = new DirectorNote('Same text', 'objection', 2);

        $this->assertTrue($cooldown->isAllowed($note));
        $cooldown->record($note);

        $this->assertFalse($cooldown->isAllowed($note));
    }

    public function test_duplicate_note_across_categories_is_suppressed(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note1 = new DirectorNote('Same text', 'objection', 2);
        $note2 = new DirectorNote('Same text', 'phase_change', 2);

        $this->assertTrue($cooldown->isAllowed($note1));
        $cooldown->record($note1);

        $this->assertFalse($cooldown->isAllowed($note2));
    }

    public function test_duplicate_after_turns_is_still_suppressed(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note = new DirectorNote('Persistent text', 'objection', 2);

        $this->assertTrue($cooldown->isAllowed($note));
        $cooldown->record($note);

        $cooldown->nextTurn();
        $cooldown->nextTurn();
        $cooldown->nextTurn();
        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertFalse($cooldown->isAllowed($note));
    }

    public function test_near_duplicate_not_suppressed(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note1 = new DirectorNote('Text one', 'objection', 2);
        $note2 = new DirectorNote('Text two', 'objection', 2);

        $this->assertTrue($cooldown->isAllowed($note1));
        $cooldown->record($note1);

        for ($i = 0; $i < 4; $i++) {
            $cooldown->nextTurn();
        }

        $this->assertTrue($cooldown->isAllowed($note2));
    }

    public function test_same_category_note_is_cooled_down(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note1 = new DirectorNote('First objection', 'objection', 2);
        $note2 = new DirectorNote('Second objection', 'objection', 2);

        $this->assertTrue($cooldown->isAllowed($note1));
        $cooldown->record($note1);

        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertFalse($cooldown->isAllowed($note2));
    }

    public function test_same_category_after_cooldown_expires_is_allowed(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note1 = new DirectorNote('First', 'objection', 2);
        $note2 = new DirectorNote('Second', 'objection', 2);

        $cooldown->isAllowed($note1);
        $cooldown->record($note1);

        $cooldown->nextTurn();
        $cooldown->nextTurn();
        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertTrue($cooldown->isAllowed($note2));
    }

    public function test_different_category_note_can_pass(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $objectionNote = new DirectorNote('Objection note', 'objection', 2);
        $phaseNote = new DirectorNote('Phase change note', 'phase_change', 2);

        $this->assertTrue($cooldown->isAllowed($objectionNote));
        $cooldown->record($objectionNote);

        $cooldown->nextTurn();

        $this->assertTrue($cooldown->isAllowed($phaseNote));
    }

    public function test_critical_note_bypasses_duplicate_suppression(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note = new DirectorNote('Critical text', 'objection', 3);

        $this->assertTrue($cooldown->isAllowed($note));
        $cooldown->record($note);

        $this->assertTrue($cooldown->isAllowed($note));
    }

    public function test_critical_note_bypasses_category_cooldown(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $normal = new DirectorNote('First objection', 'objection', 2);
        $critical = new DirectorNote('Critical objection', 'objection', 3);

        $this->assertTrue($cooldown->isAllowed($normal));
        $cooldown->record($normal);

        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertTrue($cooldown->isAllowed($critical));
    }

    public function test_priority_2_note_does_not_bypass_cooldown(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note1 = new DirectorNote('First', 'objection', 2);
        $note2 = new DirectorNote('Second', 'objection', 2);

        $this->assertTrue($cooldown->isAllowed($note1));
        $cooldown->record($note1);

        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertFalse($cooldown->isAllowed($note2));
    }

    public function test_priority_4_note_bypasses_cooldown(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $normal = new DirectorNote('First', 'objection', 2);
        $highPriority = new DirectorNote('High priority', 'objection', 4);

        $this->assertTrue($cooldown->isAllowed($normal));
        $cooldown->record($normal);

        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertTrue($cooldown->isAllowed($highPriority));
    }

    public function test_premature_closing_has_longer_cooldown(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note1 = new DirectorNote('First', 'premature_closing', 2);
        $note2 = new DirectorNote('Second', 'premature_closing', 2);

        $this->assertTrue($cooldown->isAllowed($note1));
        $cooldown->record($note1);

        for ($i = 0; $i < 7; $i++) {
            $cooldown->nextTurn();
        }

        $this->assertFalse($cooldown->isAllowed($note2));

        $cooldown->nextTurn();

        $this->assertTrue($cooldown->isAllowed($note2));
    }

    public function test_unknown_category_uses_default_cooldown(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note1 = new DirectorNote('First', 'unknown_category', 2);
        $note2 = new DirectorNote('Second', 'unknown_category', 2);

        $this->assertTrue($cooldown->isAllowed($note1));
        $cooldown->record($note1);

        for ($i = 0; $i < 4; $i++) {
            $cooldown->nextTurn();
        }

        $this->assertFalse($cooldown->isAllowed($note2));

        $cooldown->nextTurn();

        $this->assertTrue($cooldown->isAllowed($note2));
    }

    public function test_reset_clears_cooldown_memory(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note = new DirectorNote('Reset test', 'objection', 2);

        $this->assertTrue($cooldown->isAllowed($note));
        $cooldown->record($note);

        $this->assertFalse($cooldown->isAllowed($note));

        $cooldown->reset();

        $this->assertTrue($cooldown->isAllowed($note));
    }

    public function test_reset_clears_category_cooldown(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note1 = new DirectorNote('First', 'phase_change', 2);
        $note2 = new DirectorNote('Second', 'phase_change', 2);

        $cooldown->record($note1);
        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertFalse($cooldown->isAllowed($note2));

        $cooldown->reset();

        $this->assertTrue($cooldown->isAllowed($note2));
    }

    public function test_reset_clears_turn_counter(): void
    {
        $cooldown = new DirectorNoteCooldown;

        $cooldown->nextTurn();
        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertSame(3, $cooldown->getCurrentTurn());

        $cooldown->reset();

        $this->assertSame(0, $cooldown->getCurrentTurn());
    }

    public function test_deterministic_output(): void
    {
        $c1 = new DirectorNoteCooldown;
        $c2 = new DirectorNoteCooldown;

        $notes = [
            new DirectorNote('First', 'objection', 2),
            new DirectorNote('Second', 'objection', 2),
            new DirectorNote('Third', 'phase_change', 2),
            new DirectorNote('Fourth', 'objection', 2),
            new DirectorNote('Fifth', 'objection', 3),
        ];

        $results1 = [];
        foreach ($notes as $note) {
            $results1[] = $c1->isAllowed($note);
            if ($c1->isAllowed($note)) {
                $c1->record($note);
            }
            $c1->nextTurn();
        }

        $results2 = [];
        foreach ($notes as $note) {
            $results2[] = $c2->isAllowed($note);
            if ($c2->isAllowed($note)) {
                $c2->record($note);
            }
            $c2->nextTurn();
        }

        $this->assertSame($results1, $results2);
    }

    public function test_non_critical_duplicate_after_reset_is_allowed(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $note = new DirectorNote('Note text', 'objection', 2);

        $cooldown->record($note);
        $cooldown->reset();

        $this->assertTrue($cooldown->isAllowed($note));
    }

    public function test_record_does_not_affect_different_category(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $objNote = new DirectorNote('Objection note', 'objection', 2);
        $boundaryNote = new DirectorNote('Boundary note', 'boundary', 2);

        $cooldown->record($objNote);
        $cooldown->nextTurn();

        $this->assertTrue($cooldown->isAllowed($boundaryNote));
    }

    public function test_critical_priority_3_bypasses_category_cooldown(): void
    {
        $cooldown = new DirectorNoteCooldown;
        $normal = new DirectorNote('Normal', 'phase_change', 2);
        $critical = new DirectorNote('Critical bypass', 'phase_change', 3);

        $cooldown->record($normal);
        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertTrue($cooldown->isAllowed($critical));
    }

    public function test_multiple_turns_advance_counter(): void
    {
        $cooldown = new DirectorNoteCooldown;

        $this->assertSame(0, $cooldown->getCurrentTurn());

        $cooldown->nextTurn();
        $cooldown->nextTurn();
        $cooldown->nextTurn();

        $this->assertSame(3, $cooldown->getCurrentTurn());
    }

    public function test_empty_cooldown_allows_all(): void
    {
        $cooldown = new DirectorNoteCooldown;

        $notes = [
            new DirectorNote('A', 'objection', 2),
            new DirectorNote('B', 'phase_change', 2),
            new DirectorNote('C', 'boundary', 2),
        ];

        foreach ($notes as $note) {
            $this->assertTrue($cooldown->isAllowed($note));
        }
    }
}
