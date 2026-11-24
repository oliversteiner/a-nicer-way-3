<?php

namespace App\Models;

use App\Enums\AssetLayer;
use App\Enums\BackdropAlign;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** An image that runs behind (or in front of) all boards of a journey, e.g. sky or mountains. */
class JourneyBackdrop extends Model
{
    /** @use HasFactory<\Database\Factories\JourneyBackdropFactory> */
    use HasFactory;

    /** Layers a backdrop may use: behind the boards or in front of them. */
    public const LAYERS = [AssetLayer::Back, AssetLayer::Front];

    protected $fillable = ['journey_id', 'layer', 'path', 'position', 'speed', 'align', 'height', 'repeat'];

    protected function casts(): array
    {
        return [
            'layer' => AssetLayer::class,
            'align' => BackdropAlign::class,
            'position' => 'integer',
            'speed' => 'float',
            'height' => 'integer',
            'repeat' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(fn (self $backdrop) => Storage::disk(BoardAsset::DISK)->delete($backdrop->path));
    }

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }
}
