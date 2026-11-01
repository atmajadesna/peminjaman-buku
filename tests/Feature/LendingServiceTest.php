<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Services\LendingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LendingServiceTest extends TestCase
{
    use RefreshDatabase;

    private LendingService $lending;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lending = app(LendingService::class);
    }

    public function test_borrowing_sets_a_fourteen_day_due_date(): void
    {
        $book = Book::factory()->create(['stock' => 2]);
        $member = Member::factory()->create();

        $loan = $this->lending->borrow($book, $member, Carbon::parse('2026-01-10'));

        $this->assertSame('2026-01-10', $loan->borrowed_at->toDateString());
        $this->assertSame('2026-01-24', $loan->due_at->toDateString());
        $this->assertNull($loan->returned_at);
        $this->assertSame(0, $loan->fine);
        $this->assertStringStartsWith('PJ-2026-', $loan->code);
    }

    public function test_availability_shrinks_with_each_active_loan(): void
    {
        $book = Book::factory()->create(['stock' => 3]);
        $this->assertSame(3, $book->availableCopies());

        $this->lending->borrow($book, Member::factory()->create());
        $this->assertSame(2, $book->fresh()->availableCopies());

        $this->lending->borrow($book, Member::factory()->create());
        $this->assertSame(1, $book->fresh()->availableCopies());
    }

    public function test_it_refuses_to_lend_beyond_stock(): void
    {
        $book = Book::factory()->create(['stock' => 1]);
        $this->lending->borrow($book, Member::factory()->create());

        try {
            $this->lending->borrow($book, Member::factory()->create());
            $this->fail('Expected a validation exception when stock is exhausted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('book_id', $e->errors());
        }

        $this->assertSame(1, Loan::query()->count(), 'A rejected borrow must not create a loan row.');
    }

    public function test_returning_frees_the_copy_back_up(): void
    {
        $book = Book::factory()->create(['stock' => 1]);
        $loan = $this->lending->borrow($book, Member::factory()->create(), Carbon::parse('2026-01-10'));

        $this->assertSame(0, $book->fresh()->availableCopies());

        $this->lending->giveBack($loan, Carbon::parse('2026-01-20'));

        $this->assertSame(1, $book->fresh()->availableCopies());
    }

    public function test_on_time_return_charges_no_fine(): void
    {
        $book = Book::factory()->create(['stock' => 1]);
        $member = Member::factory()->create();

        $loan = $this->lending->borrow($book, $member, Carbon::parse('2026-01-01'));
        // Due 2026-01-15; returning exactly on the due date is still on time.
        $returned = $this->lending->giveBack($loan, Carbon::parse('2026-01-15'));

        $this->assertSame(0, $returned->fine);
        $this->assertTrue($returned->isReturned());
    }

    public function test_late_return_charges_one_thousand_rupiah_per_day(): void
    {
        $book = Book::factory()->create(['stock' => 1]);
        $member = Member::factory()->create();

        $loan = $this->lending->borrow($book, $member, Carbon::parse('2026-01-01'));
        // Due 2026-01-15, returned 2026-01-20 => 5 days late.
        $returned = $this->lending->giveBack($loan, Carbon::parse('2026-01-20'));

        $this->assertSame(5, $returned->daysLate());
        $this->assertSame(5000, $returned->fine);
    }

    public function test_a_loan_cannot_be_returned_twice(): void
    {
        $book = Book::factory()->create(['stock' => 1]);
        $loan = $this->lending->borrow($book, Member::factory()->create());

        $this->lending->giveBack($loan);

        $this->expectException(ValidationException::class);
        $this->lending->giveBack($loan->fresh());
    }

    public function test_inactive_members_cannot_borrow(): void
    {
        $book = Book::factory()->create(['stock' => 1]);
        $member = Member::factory()->inactive()->create();

        $this->expectException(ValidationException::class);
        $this->lending->borrow($book, $member);
    }

    public function test_a_member_cannot_hold_two_copies_of_the_same_title(): void
    {
        $book = Book::factory()->create(['stock' => 5]);
        $member = Member::factory()->create();

        $this->lending->borrow($book, $member);

        try {
            $this->lending->borrow($book, $member);
            $this->fail('Expected a duplicate-hold validation exception.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('book_id', $e->errors());
        }

        $this->assertSame(1, Loan::query()->count());
    }

    public function test_overdue_is_strictly_after_the_due_date(): void
    {
        $book = Book::factory()->create(['stock' => 1]);
        $loan = $this->lending->borrow($book, Member::factory()->create(), Carbon::parse('2026-01-01'));

        Carbon::setTestNow(Carbon::parse('2026-01-15 23:00:00'));
        $this->assertFalse($loan->fresh()->isOverdue(), 'Due today is not overdue yet.');

        Carbon::setTestNow(Carbon::parse('2026-01-16 08:00:00'));
        $this->assertTrue($loan->fresh()->isOverdue());
        $this->assertSame(1, $loan->fresh()->daysLate());

        Carbon::setTestNow();
    }

    public function test_projected_fine_grows_while_the_loan_stays_out(): void
    {
        $book = Book::factory()->create(['stock' => 1]);
        $loan = $this->lending->borrow($book, Member::factory()->create(), Carbon::parse('2026-01-01'));

        Carbon::setTestNow(Carbon::parse('2026-01-18'));
        $this->assertSame(3000, $loan->fresh()->projectedFine());

        Carbon::setTestNow(Carbon::parse('2026-01-25'));
        $this->assertSame(10000, $loan->fresh()->projectedFine());

        Carbon::setTestNow();
    }
}
