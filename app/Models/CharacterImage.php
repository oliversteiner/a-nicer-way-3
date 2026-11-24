<?php

namespace App\Models;

use App\Enums\CharacterPose;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CharacterImage extends Model
{
    /** @use HasFactory<\Database\Factories\CharacterImageFactory> */
    use HasFactory;

    protected $fillable = ['character_id', 'pose', 'path'];

    protected function casts(): array
    {
        return ['pose' => CharacterPose::class];
    }

    protected static function booted(): void
    {
        static::deleted(fn (self $image) => Storage::disk(BoardAsset::DISK)->delete($image->path));
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
