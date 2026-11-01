<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory;

    /** Late fee per started day, in whole rupiah. */
    public const FINE_PER_DAY = 1000;

    /** Default lending window in days. */
    public const LOAN_DAYS = 14;

    protected $fillable = [
        'code', 'book_id', 'member_id', 'borrowed_at', 'due_at', 'returned_at', 'fine',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
            'fine' => 'integer',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    /**
     * A loan is overdue when it is still out and the due date is strictly
     * before today. Due today is not overdue — the member has the whole day.
     */
    public function isOverdue(): bool
    {
        if ($this->isReturned()) {
            return false;
        }

        return $this->due_at->copy()->startOfDay()->lt(now()->startOfDay());
    }

    public function daysLate(): int
    {
        $end = $this->isReturned() ? $this->returned_at : now();

        if ($end->copy()->startOfDay()->lte($this->due_at->copy()->startOfDay())) {
            return 0;
        }

        return (int) $this->due_at->copy()->startOfDay()
            ->diffInDays($end->copy()->startOfDay());
    }

    /** Fee that would be charged if the loan were returned right now. */
    public function projectedFine(): int
    {
        return $this->daysLate() * self::FINE_PER_DAY;
    }

    public function status(): string
    {
        if ($this->isReturned()) {
            return 'returned';
        }

        return $this->isOverdue() ? 'overdue' : 'active';
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'returned' => 'Dikembalikan',
            'overdue' => 'Terlambat',
            default => 'Dipinjam',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status()) {
            'returned' => 'badge-available',
            'overdue' => 'badge-overdue',
            default => 'badge-borrowed',
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    /**
     * Still out and not yet late. Together with overdue() and returned() this
     * partitions every loan, so the filter tabs always add up to the total.
     */
    public function scopeOnLoan(Builder $query): Builder
    {
        return $query->whereNull('returned_at')
            ->whereDate('due_at', '>=', now()->toDateString());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('returned_at')
            ->whereDate('due_at', '<', now()->toDateString());
    }

    public function scopeReturned(Builder $query): Builder
    {
        return $query->whereNotNull('returned_at');
    }
}
