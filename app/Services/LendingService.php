<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LendingService
{
    /**
     * Create a loan for a member.
     *
     * The stock check runs inside a transaction against a row-locked book row
     * so two concurrent requests cannot both claim the last copy.
     *
     * @throws ValidationException
     */
    public function borrow(Book $book, Member $member, ?CarbonInterface $borrowedAt = null): Loan
    {
        $borrowedAt = ($borrowedAt ?? now())->copy()->startOfDay();

        return DB::transaction(function () use ($book, $member, $borrowedAt) {
            /** @var Book $locked */
            $locked = Book::query()->whereKey($book->getKey())->lockForUpdate()->firstOrFail();

            if (! $member->is_active) {
                throw ValidationException::withMessages([
                    'member_id' => 'Anggota ini sedang tidak aktif dan tidak dapat meminjam buku.',
                ]);
            }

            $activeForBook = $locked->activeLoans()->count();

            if ($activeForBook >= $locked->stock) {
                throw ValidationException::withMessages([
                    'book_id' => sprintf(
                        'Stok habis: "%s" hanya punya %d eksemplar dan semuanya sedang dipinjam.',
                        $locked->title,
                        $locked->stock
                    ),
                ]);
            }

            $alreadyHolding = Loan::query()
                ->where('book_id', $locked->getKey())
                ->where('member_id', $member->getKey())
                ->whereNull('returned_at')
                ->exists();

            if ($alreadyHolding) {
                throw ValidationException::withMessages([
                    'book_id' => sprintf(
                        '%s masih memegang eksemplar "%s" yang belum dikembalikan.',
                        $member->name,
                        $locked->title
                    ),
                ]);
            }

            return Loan::create([
                'code' => $this->nextCode($borrowedAt),
                'book_id' => $locked->getKey(),
                'member_id' => $member->getKey(),
                'borrowed_at' => $borrowedAt->toDateString(),
                'due_at' => $borrowedAt->copy()->addDays(Loan::LOAN_DAYS)->toDateString(),
                'returned_at' => null,
                'fine' => 0,
            ]);
        });
    }

    /**
     * Mark a loan as returned and record the late fee.
     *
     * @throws ValidationException
     */
    public function giveBack(Loan $loan, ?CarbonInterface $returnedAt = null): Loan
    {
        $returnedAt = ($returnedAt ?? now())->copy()->startOfDay();

        return DB::transaction(function () use ($loan, $returnedAt) {
            /** @var Loan $locked */
            $locked = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isReturned()) {
                throw ValidationException::withMessages([
                    'loan' => 'Peminjaman ini sudah dikembalikan sebelumnya.',
                ]);
            }

            if ($returnedAt->lt($locked->borrowed_at->copy()->startOfDay())) {
                throw ValidationException::withMessages([
                    'loan' => 'Tanggal pengembalian tidak boleh sebelum tanggal pinjam.',
                ]);
            }

            $lateDays = 0;
            if ($returnedAt->copy()->startOfDay()->gt($locked->due_at->copy()->startOfDay())) {
                $lateDays = (int) $locked->due_at->copy()->startOfDay()
                    ->diffInDays($returnedAt->copy()->startOfDay());
            }

            $locked->forceFill([
                'returned_at' => $returnedAt->toDateString(),
                'fine' => $lateDays * Loan::FINE_PER_DAY,
            ])->save();

            return $locked;
        });
    }

    /** Sequential, human-readable code such as PJ-2026-0007. */
    private function nextCode(CarbonInterface $date): string
    {
        $prefix = 'PJ-'.$date->format('Y').'-';
        $last = Loan::query()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
