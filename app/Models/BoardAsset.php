<?php

namespace App\Models;

use App\Enums\AssetLayer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BoardAsset extends Model
{
    /** @use HasFactory<\Database\Factories\BoardAssetFactory> */
    use HasFactory;

    protected $fillable = ['board_id', 'layer', 'path', 'position'];

    protected function casts(): array
    {
        return ['layer' => AssetLayer::class, 'position' => 'integer'];
    }

    /** Disk that holds the uploaded image files (private; served through a route). */
    public const DISK = 'local';

    protected static function booted(): void
    {
        static::deleted(fn (self $asset) => Storage::disk(self::DISK)->delete($asset->path));
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
