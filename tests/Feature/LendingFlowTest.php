<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LendingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsLibrarian(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/books')->assertRedirect('/login');
        $this->get('/loans')->assertRedirect('/login');
        $this->get('/members')->assertRedirect('/login');
    }

    public function test_librarian_can_sign_in_and_out(): void
    {
        $user = User::factory()->create(['password' => bcrypt('rahasia123')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('rahasia123')]);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_every_page_renders_for_a_signed_in_librarian(): void
    {
        $this->actingAsLibrarian();

        $book = Book::factory()->create(['title' => 'Laskar Pelangi', 'stock' => 2]);
        $member = Member::factory()->create(['name' => 'Rina Susanti']);
        Loan::factory()->create([
            'book_id' => $book->id,
            'member_id' => $member->id,
            'borrowed_at' => now()->subDays(2)->toDateString(),
            'due_at' => now()->addDays(12)->toDateString(),
        ]);

        $this->get('/dashboard')->assertOk()->assertSee('Dashboard')->assertSee('Laskar Pelangi');
        $this->get('/books')->assertOk()->assertSee('Laskar Pelangi')->assertSee('Katalog Buku');
        $this->get('/loans')->assertOk()->assertSee('Laskar Pelangi')->assertSee('Rina Susanti');
        $this->get('/members')->assertOk()->assertSee('Rina Susanti');
    }

    public function test_borrowing_through_the_http_endpoint_creates_a_loan(): void
    {
        $this->actingAsLibrarian();

        $book = Book::factory()->create(['stock' => 1]);
        $member = Member::factory()->create();

        $this->post('/loans', [
            'book_id' => $book->id,
            'member_id' => $member->id,
            'borrowed_at' => '2026-02-01',
        ])->assertRedirect('/loans');

        $this->assertDatabaseHas('loans', [
            'book_id' => $book->id,
            'member_id' => $member->id,
            'borrowed_at' => '2026-02-01 00:00:00',
            'due_at' => '2026-02-15 00:00:00',
            'returned_at' => null,
        ]);
    }

    public function test_borrowing_the_last_copy_twice_is_rejected_without_a_row(): void
    {
        $this->actingAsLibrarian();

        $book = Book::factory()->create(['stock' => 1]);
        $first = Member::factory()->create();
        $second = Member::factory()->create();

        $this->post('/loans', ['book_id' => $book->id, 'member_id' => $first->id])
            ->assertRedirect('/loans');

        $this->from('/loans')
            ->post('/loans', ['book_id' => $book->id, 'member_id' => $second->id])
            ->assertRedirect('/loans')
            ->assertSessionHasErrors('book_id');

        $this->assertSame(1, Loan::query()->count());
    }

    public function test_returning_through_the_http_endpoint_records_the_fine(): void
    {
        $this->actingAsLibrarian();

        $book = Book::factory()->create(['stock' => 1]);
        $member = Member::factory()->create();

        $loan = Loan::factory()->create([
            'book_id' => $book->id,
            'member_id' => $member->id,
            'borrowed_at' => now()->subDays(30)->toDateString(),
            'due_at' => now()->subDays(16)->toDateString(),
            'returned_at' => null,
        ]);

        $this->patch("/loans/{$loan->id}/return")->assertRedirect('/loans');

        $fresh = $loan->fresh();
        $this->assertNotNull($fresh->returned_at);
        $this->assertSame(16 * Loan::FINE_PER_DAY, $fresh->fine);
    }

    public function test_book_creation_validates_the_isbn_is_unique(): void
    {
        $this->actingAsLibrarian();

        $payload = [
            'title' => 'Buku Uji',
            'author' => 'Penulis Uji',
            'isbn' => '978-000-000-000-1',
            'genre' => 'Fiksi',
            'published_year' => 2020,
            'stock' => 2,
        ];

        $this->post('/books', $payload)->assertRedirect('/books');
        $this->assertDatabaseHas('books', ['isbn' => '978-000-000-000-1']);

        $this->from('/books')
            ->post('/books', array_merge($payload, ['title' => 'Buku Lain']))
            ->assertRedirect('/books')
            ->assertSessionHasErrors('isbn');

        $this->assertSame(1, Book::query()->count());
    }

    public function test_member_registration_generates_a_sequential_code(): void
    {
        $this->actingAsLibrarian();

        $this->post('/members', [
            'name' => 'Anggota Satu',
            'email' => 'satu@univ.ac.id',
            'role' => 'Mahasiswa',
        ])->assertRedirect('/members');

        $this->post('/members', [
            'name' => 'Anggota Dua',
            'email' => 'dua@univ.ac.id',
            'role' => 'Dosen',
        ])->assertRedirect('/members');

        $this->assertDatabaseHas('members', ['email' => 'satu@univ.ac.id', 'code' => 'AGT-0001']);
        $this->assertDatabaseHas('members', ['email' => 'dua@univ.ac.id', 'code' => 'AGT-0002']);
    }

    public function test_catalog_search_filters_by_title_and_author(): void
    {
        $this->actingAsLibrarian();

        Book::factory()->create(['title' => 'Laskar Pelangi', 'author' => 'Andrea Hirata']);
        Book::factory()->create(['title' => 'Clean Code', 'author' => 'Robert Martin']);

        $this->get('/books?q=Laskar')
            ->assertOk()
            ->assertSee('Laskar Pelangi')
            ->assertDontSee('Clean Code');
    }

    public function test_an_empty_catalog_shows_the_empty_state(): void
    {
        $this->actingAsLibrarian();

        $this->get('/books')
            ->assertOk()
            ->assertSee('Koleksi masih kosong');
    }

    public function test_loan_filter_tabs_partition_every_loan(): void
    {
        $this->actingAsLibrarian();

        $member = Member::factory()->create();
        $lending = app(\App\Services\LendingService::class);

        // One on time, one late, one already back — separate titles, because a
        // member may not hold two copies of the same book at once.
        $lending->borrow(Book::factory()->create(), $member, now()->subDays(2));
        $lending->borrow(Book::factory()->create(), $member, now()->subDays(30));
        $returned = $lending->borrow(Book::factory()->create(), $member, now()->subDays(20));
        $lending->giveBack($returned, now()->subDays(19));

        $response = $this->get('/loans')->assertOk();
        $counts = $response->viewData('counts');

        $this->assertSame(3, $counts['all']);
        $this->assertSame(1, $counts['active']);
        $this->assertSame(1, $counts['overdue']);
        $this->assertSame(1, $counts['returned']);
        $this->assertSame(
            $counts['all'],
            $counts['active'] + $counts['overdue'] + $counts['returned'],
            'The filter tabs must partition every loan, never overlap.'
        );
    }
}
