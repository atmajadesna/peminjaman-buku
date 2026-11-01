<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalCopies = (int) Book::query()->sum('stock');
        $activeLoans = Loan::query()->active()->count();
        $overdueLoans = Loan::query()->overdue()->count();
        $activeMembers = Member::query()->where('is_active', true)->count();

        $recentLoans = Loan::query()
            ->with(['book', 'member'])
            ->latest('borrowed_at')
            ->latest('id')
            ->limit(6)
            ->get();

        $dueSoon = Loan::query()
            ->active()
            ->with(['book', 'member'])
            ->whereDate('due_at', '>=', now()->toDateString())
            ->whereDate('due_at', '<=', now()->addDays(3)->toDateString())
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        $mostBorrowed = Book::query()
            ->withCount(['loans'])
            ->orderByDesc('loans_count')
            ->orderBy('title')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'totalTitles' => Book::query()->count(),
            'totalCopies' => $totalCopies,
            'activeLoans' => $activeLoans,
            'overdueLoans' => $overdueLoans,
            'activeMembers' => $activeMembers,
            'availableCopies' => max(0, $totalCopies - $activeLoans),
            'recentLoans' => $recentLoans,
            'dueSoon' => $dueSoon,
            'mostBorrowed' => $mostBorrowed,
        ]);
    }
}
