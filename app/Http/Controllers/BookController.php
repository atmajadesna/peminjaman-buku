<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $genre = $request->string('genre')->trim()->toString();

        $books = Book::query()
            ->withCount(['activeLoans'])
            ->search($search)
            ->genre($genre)
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('books.index', [
            'books' => $books,
            'search' => $search,
            'genre' => $genre,
            'genres' => Book::query()->distinct()->orderBy('genre')->pluck('genre'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'author' => ['required', 'string', 'max:120'],
            'isbn' => ['required', 'string', 'max:32', 'unique:books,isbn'],
            'genre' => ['required', 'string', 'max:40'],
            'published_year' => ['required', 'integer', 'min:1000', 'max:'.(int) date('Y')],
            'stock' => ['required', 'integer', 'min:1', 'max:999'],
            'shelf_code' => ['nullable', 'string', 'max:20'],
        ], [
            'isbn.unique' => 'ISBN ini sudah terdaftar pada koleksi.',
        ]);

        $book = Book::create($data);

        return redirect()
            ->route('books.index')
            ->with('status', sprintf('Buku "%s" berhasil ditambahkan.', $book->title));
    }
}
