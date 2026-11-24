<?php

namespace App\Models;

use App\Enums\AssetLayer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardAsset extends Model
{
    /** @use HasFactory<\Database\Factories\BoardAssetFactory> */
    use HasFactory;

    protected $fillable = ['board_id', 'layer', 'path', 'position'];

    protected function casts(): array
    {
        return ['layer' => AssetLayer::class, 'position' => 'integer'];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
