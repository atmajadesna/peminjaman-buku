<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\LendingService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a demo librarian account plus a small, clearly sample collection.
     *
     * This is a local prototype dataset, not real library records.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'petugas@perpus.test'],
            [
                'name' => 'Atmaja Desna',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $books = collect([
            ['Laskar Pelangi', 'Andrea Hirata', '978-602-241-054-5', 'Fiksi', 2005, 3, 'A-01'],
            ['A Brief History of Time', 'Stephen Hawking', '978-055-338-016-3', 'Sains', 1988, 2, 'B-02'],
            ['Clean Code', 'Robert C. Martin', '978-013-235-088-4', 'Teknologi', 2008, 2, 'C-01'],
            ['Bumi', 'Tere Liye', '978-602-024-407-1', 'Fiksi', 2014, 3, 'A-02'],
            ['Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', '978-006-231-609-7', 'Sejarah', 2011, 2, 'D-01'],
            ['Atomic Habits', 'James Clear', '978-073-521-129-2', 'Self-Help', 2018, 3, 'A-03'],
            ['The Alchemist', 'Paulo Coelho', '978-006-112-241-5', 'Fiksi', 1988, 2, 'A-04'],
            ['Filosofi Teras', 'Henry Manampiring', '978-602-063-377-4', 'Filsafat', 2018, 2, 'E-01'],
            ['Pulang', 'Leila S. Chudori', '978-602-424-456-7', 'Sastra', 2012, 2, 'F-01'],
            ['Gödel, Escher, Bach', 'Douglas Hofstadter', '978-046-502-656-2', 'Filsafat', 1979, 1, 'E-02'],
        ])->map(fn (array $row) => Book::query()->updateOrCreate(
            ['isbn' => $row[2]],
            [
                'title' => $row[0],
                'author' => $row[1],
                'genre' => $row[3],
                'published_year' => $row[4],
                'stock' => $row[5],
                'shelf_code' => $row[6],
            ]
        ));

        $members = collect([
            ['Rina Susanti', 'rina.s@univ.ac.id', 'Mahasiswa'],
            ['Budi Hartono', 'budi.h@univ.ac.id', 'Dosen'],
            ['Ahmad Fauzi', 'ahmad.f@univ.ac.id', 'Mahasiswa'],
            ['Siti Nurhaliza', 'siti.n@univ.ac.id', 'Mahasiswa'],
            ['Devi Anggraini', 'devi.a@univ.ac.id', 'Staff'],
            ['Maya Putri', 'maya.p@univ.ac.id', 'Umum'],
        ])->values()->map(fn (array $row, int $index) => Member::query()->updateOrCreate(
            ['email' => $row[1]],
            [
                'code' => 'AGT-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'name' => $row[0],
                'role' => $row[2],
                'phone' => '0812-0000-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]
        ));

        if (Loan::query()->exists()) {
            return;
        }

        $lending = app(LendingService::class);

        // Seeded in borrow-date order so the generated loan codes (PJ-…-0001,
        // 0002, …) ascend with time — otherwise the table looks mis-sorted.
        // Dates are relative to today so overdue and due-soon states are real.
        $history = [
            // [book, member, borrowed at, returned at (null = still out)]
            [$books[3], $members[3], Carbon::now()->subDays(30), Carbon::now()->subDays(12)],
            [$books[4], $members[4], Carbon::now()->subDays(25), Carbon::now()->subDays(5)],
            [$books[2], $members[2], Carbon::now()->subDays(21), null],   // overdue
            [$books[5], $members[5], Carbon::now()->subDays(12), null],   // due in 2 days
            [$books[1], $members[1], Carbon::now()->subDays(9), null],
            [$books[0], $members[0], Carbon::now()->subDays(3), null],
        ];

        foreach ($history as [$book, $member, $borrowedAt, $returnedAt]) {
            $loan = $lending->borrow($book, $member, $borrowedAt);

            if ($returnedAt !== null) {
                $lending->giveBack($loan, $returnedAt);
            }
        }
    }
}
