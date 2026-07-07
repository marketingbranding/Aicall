<?php

namespace App\Services\Director;

readonly class RoleplayEvent
{
    public function __construct(
        public RoleplayEventType $type,
        public string $topic = '',
        public string $severity = 'MODERATE',
        public ?string $relatedObjectionKey = null,
        public string $shortInternalReason = '',
    ) {}

    public function fingerprint(): string
    {
        $parts = [$this->type->value, $this->topic];

        if ($this->relatedObjectionKey !== null) {
            $parts[] = $this->relatedObjectionKey;
        }

        return md5(implode('|', $parts));
    }
}
