<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Services\LendingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private readonly LendingService $lending) {}

    public function index(Request $request): View
    {
        $filter = $request->string('status')->trim()->toString();

        $loans = Loan::query()
            ->with(['book', 'member'])
            ->when($filter === 'active', fn ($q) => $q->onLoan())
            ->when($filter === 'overdue', fn ($q) => $q->overdue())
            ->when($filter === 'returned', fn ($q) => $q->returned())
            ->latest('borrowed_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('loans.index', [
            'loans' => $loans,
            'filter' => $filter,
            'availableBooks' => Book::query()
                ->withCount(['activeLoans'])
                ->orderBy('title')
                ->get()
                ->filter(fn (Book $book) => $book->availableCopies() > 0),
            'members' => Member::query()->where('is_active', true)->orderBy('name')->get(),
            'counts' => [
                'all' => Loan::query()->count(),
                'active' => Loan::query()->onLoan()->count(),
                'overdue' => Loan::query()->overdue()->count(),
                'returned' => Loan::query()->returned()->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'borrowed_at' => ['nullable', 'date'],
        ]);

        $loan = $this->lending->borrow(
            Book::query()->findOrFail($data['book_id']),
            Member::query()->findOrFail($data['member_id']),
            isset($data['borrowed_at']) ? \Illuminate\Support\Carbon::parse($data['borrowed_at']) : null,
        );

        return redirect()
            ->route('loans.index')
            ->with('status', sprintf(
                'Peminjaman %s dibuat. Jatuh tempo %s.',
                $loan->code,
                $loan->due_at->translatedFormat('j F Y')
            ));
    }

    public function giveBack(Loan $loan): RedirectResponse
    {
        $returned = $this->lending->giveBack($loan);

        $message = sprintf('Peminjaman %s ditandai dikembalikan.', $returned->code);

        if ($returned->fine > 0) {
            $message .= sprintf(
                ' Denda keterlambatan Rp%s.',
                number_format($returned->fine, 0, ',', '.')
            );
        }

        return redirect()->route('loans.index')->with('status', $message);
    }
}
