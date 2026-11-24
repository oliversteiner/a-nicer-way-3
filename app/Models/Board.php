<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    /** @use HasFactory<\Database\Factories\BoardFactory> */
    use HasFactory;

    protected $fillable = [
        'journey_id', 'position', 'title', 'description', 'notes',
        'day', 'time', 'location', 'is_night', 'mood', 'simulator_state',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'day' => 'integer',
            'is_night' => 'boolean',
            'simulator_state' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // The database cascade does not fire model events, so remove the files here.
        static::deleting(fn (self $board) => $board->assets->each->delete());
    }

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(BoardAsset::class)->orderBy('position');
    }
}
