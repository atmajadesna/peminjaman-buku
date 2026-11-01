<?php

namespace Tests\Unit;

use App\Models\Loan;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The late-fee maths, exercised on a bare model instance.
 *
 * Kept free of the database on purpose: this is the rule that decides how much
 * money a member owes, so it should be verifiable without any fixtures.
 */
class LoanFineTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function loanDue(string $due): Loan
    {
        $loan = new Loan();
        $loan->due_at = Carbon::parse($due);

        return $loan;
    }

    public function test_a_loan_due_today_is_not_late(): void
    {
        Carbon::setTestNow('2026-03-10 16:00:00');

        $loan = $this->loanDue('2026-03-10');

        $this->assertFalse($loan->isOverdue());
        $this->assertSame(0, $loan->daysLate());
        $this->assertSame(0, $loan->projectedFine());
    }

    public function test_a_loan_is_late_once_the_due_date_has_passed(): void
    {
        Carbon::setTestNow('2026-03-11 00:05:00');

        $loan = $this->loanDue('2026-03-10');

        $this->assertTrue($loan->isOverdue());
        $this->assertSame(1, $loan->daysLate());
        $this->assertSame(1000, $loan->projectedFine());
    }

    public function test_the_fine_grows_by_one_day_at_a_time(): void
    {
        Carbon::setTestNow('2026-03-17 23:59:00');

        $loan = $this->loanDue('2026-03-10');

        // Seven calendar days late, regardless of the time of day.
        $this->assertSame(7, $loan->daysLate());
        $this->assertSame(7 * Loan::FINE_PER_DAY, $loan->projectedFine());
    }

    public function test_a_returned_loan_stops_accruing_a_fine(): void
    {
        Carbon::setTestNow('2026-03-20 09:00:00');

        $loan = $this->loanDue('2026-03-10');
        $loan->returned_at = Carbon::parse('2026-03-13');

        $this->assertTrue($loan->isReturned());
        $this->assertFalse($loan->isOverdue());
        // Three days late at the moment it came back — the week since then
        // must not keep adding to the bill.
        $this->assertSame(3, $loan->daysLate());
        $this->assertSame(3000, $loan->projectedFine());
    }

    public function test_a_loan_returned_before_its_due_date_owes_nothing(): void
    {
        Carbon::setTestNow('2026-03-12 09:00:00');

        $loan = $this->loanDue('2026-03-20');
        $loan->returned_at = Carbon::parse('2026-03-11');

        $this->assertSame(0, $loan->daysLate());
        $this->assertSame(0, $loan->projectedFine());
    }

    public function test_the_default_lending_window_is_fourteen_days(): void
    {
        $this->assertSame(14, Loan::LOAN_DAYS);
    }
}
