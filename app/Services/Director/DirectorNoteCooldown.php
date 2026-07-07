<?php

namespace App\Services\Director;

class DirectorNoteCooldown
{
    private const array CATEGORY_COOLDOWNS = [
        'state_threshold' => 5,
        'phase_change' => 5,
        'premature_closing' => 8,
        'objection' => 4,
        'hidden_info' => 4,
        'boundary' => 4,
    ];

    private const int DEFAULT_COOLDOWN = 5;
    private const int CRITICAL_PRIORITY = 3;
    private const int DUPLICATE_MEMORY = 3;

    private int $currentTurn = 0;

    /** @var array<string, int> category => last turn */
    private array $categoryLastTurn = [];

    /** @var string[] recent note texts for duplicate detection */
    private array $recentTexts = [];

    public function isAllowed(DirectorNote $note): bool
    {
        if ($note->priority >= self::CRITICAL_PRIORITY) {
            return true;
        }

        if ($this->isDuplicate($note)) {
            return false;
        }

        if ($this->isCategoryOnCooldown($note)) {
            return false;
        }

        return true;
    }

    public function record(DirectorNote $note): void
    {
        $this->categoryLastTurn[$note->category] = $this->currentTurn;

        $this->recentTexts[] = $note->text;
        if (count($this->recentTexts) > self::DUPLICATE_MEMORY) {
            array_shift($this->recentTexts);
        }
    }

    public function nextTurn(): void
    {
        $this->currentTurn++;
    }

    public function getCurrentTurn(): int
    {
        return $this->currentTurn;
    }

    public function reset(): void
    {
        $this->currentTurn = 0;
        $this->categoryLastTurn = [];
        $this->recentTexts = [];
    }

    private function isDuplicate(DirectorNote $note): bool
    {
        return in_array($note->text, $this->recentTexts, true);
    }

    private function isCategoryOnCooldown(DirectorNote $note): bool
    {
        $lastTurn = $this->categoryLastTurn[$note->category] ?? null;

        if ($lastTurn === null) {
            return false;
        }

        $cooldown = self::CATEGORY_COOLDOWNS[$note->category] ?? self::DEFAULT_COOLDOWN;

        return ($this->currentTurn - $lastTurn) < $cooldown;
    }
}
