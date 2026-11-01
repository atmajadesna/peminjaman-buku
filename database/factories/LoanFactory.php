<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $borrowed = now()->subDays(fake()->numberBetween(0, 20))->startOfDay();

        return [
            'code' => 'PJ-'.fake()->unique()->numerify('####'),
            'book_id' => Book::factory(),
            'member_id' => Member::factory(),
            'borrowed_at' => $borrowed->toDateString(),
            'due_at' => $borrowed->copy()->addDays(Loan::LOAN_DAYS)->toDateString(),
            'returned_at' => null,
            'fine' => 0,
        ];
    }

    /** Already handed back, on time. */
    public function returned(): static
    {
        return $this->state(function () {
            $borrowed = now()->subDays(20)->startOfDay();

            return [
                'borrowed_at' => $borrowed->toDateString(),
                'due_at' => $borrowed->copy()->addDays(Loan::LOAN_DAYS)->toDateString(),
                'returned_at' => $borrowed->copy()->addDays(10)->toDateString(),
                'fine' => 0,
            ];
        });
    }

    /** Still out, past the due date. */
    public function overdue(): static
    {
        return $this->state(function () {
            $borrowed = now()->subDays(30)->startOfDay();

            return [
                'borrowed_at' => $borrowed->toDateString(),
                'due_at' => $borrowed->copy()->addDays(Loan::LOAN_DAYS)->toDateString(),
                'returned_at' => null,
                'fine' => 0,
            ];
        });
    }
}
