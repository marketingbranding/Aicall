<?php

namespace App\Models;

use Database\Factories\PersonaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'status', 'current_version_id', 'created_by'])]
class Persona extends Model
{
    /** @use HasFactory<PersonaFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_ARCHIVED = 'ARCHIVED';

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(PersonaVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PersonaVersion::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    public function duplicate(User $user): self
    {
        $version = $this->currentVersion;

        $clone = self::create([
            'code' => $this->code . '_' . strtolower(\Illuminate\Support\Str::random(4)),
            'name' => $this->name . ' (Salinan)',
            'status' => self::STATUS_ACTIVE,
            'created_by' => $user->id,
        ]);

        if ($version) {
            $newVersion = $version->replicateForPersona($clone, $user);
            $clone->update(['current_version_id' => $newVersion->id]);
        }

        return $clone;
    }
}
