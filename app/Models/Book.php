<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'author', 'isbn', 'genre', 'published_year', 'stock', 'shelf_code',
    ];

    protected function casts(): array
    {
        return [
            'published_year' => 'integer',
            'stock' => 'integer',
        ];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /** Loans that have not been returned yet. */
    public function activeLoans(): HasMany
    {
        return $this->loans()->whereNull('returned_at');
    }

    /**
     * Copies currently on the shelf.
     *
     * Availability is derived from stock minus active loans rather than stored
     * in a counter column, so it cannot drift out of sync with the loan table.
     */
    public function availableCopies(): int
    {
        if ($this->relationLoaded('activeLoans')) {
            $active = $this->activeLoans->count();
        } elseif (array_key_exists('active_loans_count', $this->attributes)) {
            $active = (int) $this->attributes['active_loans_count'];
        } else {
            $active = $this->activeLoans()->count();
        }

        return max(0, $this->stock - $active);
    }

    public function isAvailable(): bool
    {
        return $this->availableCopies() > 0;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('author', 'like', $like)
                ->orWhere('isbn', 'like', $like);
        });
    }

    public function scopeGenre(Builder $query, ?string $genre): Builder
    {
        return $genre ? $query->where('genre', $genre) : $query;
    }

    /**
     * Deterministic cover tint.
     *
     * Colour is keyed to the genre so the same shelf always looks the same —
     * a catalogue where "Fiksi" is red on one card and blue on the next reads
     * as a bug, not as variety. Unknown genres fall back to a stable hash.
     */
    public function coverTint(): array
    {
        // Tints are deliberately dark enough that the white cover lettering
        // clears 4.5:1 on every genre.
        $byGenre = [
            'fiksi' => ['#7C2D12', '#5C1F0C'],
            'sains' => ['#075985', '#0C4A6E'],
            'teknologi' => ['#065F46', '#064E3B'],
            'sejarah' => ['#92400E', '#78350F'],
            'sastra' => ['#5B21B6', '#4C1D95'],
            'filsafat' => ['#155E75', '#164E63'],
            'self-help' => ['#9D174D', '#831843'],
            'biografi' => ['#3730A3', '#312E81'],
            'agama' => ['#166534', '#14532D'],
        ];

        $key = mb_strtolower(trim((string) $this->genre));

        if (isset($byGenre[$key])) {
            return $byGenre[$key];
        }

        $fallback = array_values($byGenre);

        return $fallback[abs(crc32($key)) % count($fallback)];
    }
}
