<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->isbn13(),
            'genre' => fake()->randomElement(['Fiksi', 'Sains', 'Teknologi', 'Sejarah', 'Sastra', 'Filsafat']),
            'published_year' => fake()->numberBetween(1990, (int) date('Y')),
            'stock' => fake()->numberBetween(1, 4),
            'shelf_code' => strtoupper(fake()->bothify('?#')),
        ];
    }
}
