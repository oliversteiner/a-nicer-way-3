<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Journey extends Model
{
    /** @use HasFactory<\Database\Factories\JourneyFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'slug', 'description', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class)->orderBy('position');
    }

    /** Renumber the boards 1..n in their current order (e.g. after a delete). */
    public function resequenceBoards(): void
    {
        $ids = $this->boards()->pluck('id');

        // Park all positions outside the range first: (journey_id, position) is unique.
        $this->boards()->update(['position' => DB::raw('position + 1000000')]);

        foreach ($ids as $index => $id) {
            Board::query()->whereKey($id)->update(['position' => $index + 1]);
        }
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
