@extends('layouts.app')

@section('title', 'Katalog Buku')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}">Beranda</a><span class="sep">/</span><span>Katalog Buku</span>
  </nav>

  <div class="page-header">
    <div>
      <h2 class="page-title">Katalog Buku</h2>
      <p class="page-subtitle">{{ number_format($books->total(), 0, ',', '.') }} judul dalam koleksi</p>
    </div>
    <div style="display:flex;gap:var(--space-sm);align-items:center;flex-wrap:wrap">
      <form method="GET" action="{{ route('books.index') }}" style="display:flex;gap:var(--space-sm);align-items:center;flex-wrap:wrap">
        @if($search !== '')
          <input type="hidden" name="q" value="{{ $search }}">
        @endif
        <label class="sr-only" for="genre-filter">Filter genre</label>
        <select class="form-input form-select" id="genre-filter" name="genre" onchange="this.form.submit()" style="width:auto;height:40px;font-size:.85rem">
          <option value="">Semua genre</option>
          @foreach($genres as $option)
            <option value="{{ $option }}" @selected($genre === $option)>{{ $option }}</option>
          @endforeach
        </select>
        <noscript><button class="btn btn-outline" type="submit">Terapkan</button></noscript>
      </form>
      <button class="btn btn-primary" type="button" onclick="openModal('add-book')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Tambah Buku
      </button>
    </div>
  </div>

  @if($search !== '' || $genre !== '')
    <p style="font-size:.85rem;color:var(--c-text-muted);margin-bottom:var(--space-md)">
      Menampilkan hasil
      @if($search !== '') untuk &ldquo;<strong>{{ $search }}</strong>&rdquo; @endif
      @if($genre !== '') dalam genre <strong>{{ $genre }}</strong> @endif
      · <a href="{{ route('books.index') }}" style="color:var(--c-accent);text-decoration:underline">Bersihkan filter</a>
    </p>
  @endif

  @if($books->isEmpty())
    <div class="card">
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
        <p>
          @if($search !== '' || $genre !== '')
            Tidak ada buku yang cocok dengan filter ini.
          @else
            Koleksi masih kosong. Tambahkan buku pertama untuk memulai.
          @endif
        </p>
        <button class="btn btn-primary btn-sm" type="button" onclick="openModal('add-book')">Tambah Buku</button>
      </div>
    </div>
  @else
    <div class="book-grid">
      @foreach($books as $book)
        @php
          $tint = $book->coverTint();
          $available = max(0, $book->stock - $book->active_loans_count);
        @endphp
        <article class="book-card">
          <div class="book-card-cover" style="background:linear-gradient(145deg,{{ $tint[0] }},{{ $tint[1] }})">
            {{-- Decorative spine lettering: the real title is in the card body
                 below, so hide this from assistive tech. --}}
            <div class="cover-text" aria-hidden="true">{{ $book->title }}</div>
            <div class="cover-badge">
              @if($available > 0)
                <span class="badge badge-available">{{ $available }} tersedia</span>
              @else
                <span class="badge badge-borrowed">Habis dipinjam</span>
              @endif
            </div>
          </div>
          <div class="book-card-body">
            <h3 class="bc-title">{{ $book->title }}</h3>
            <p class="bc-author">{{ $book->author }} · {{ $book->published_year }}</p>
            <div class="bc-genre-row">
              <span class="badge badge-genre">{{ $book->genre }}</span>
            </div>
            <div class="bc-foot">
              <span class="bc-isbn">{{ $book->isbn }}</span>
              @if($book->shelf_code)
                <span>Rak {{ $book->shelf_code }}</span>
              @endif
            </div>
          </div>
        </article>
      @endforeach
    </div>

    @if($books->hasPages())
      <div class="pagination-wrap">
        <nav class="pagination" aria-label="Navigasi halaman katalog">
          @if($books->onFirstPage())
            <span class="disabled" aria-hidden="true">‹</span>
          @else
            <a href="{{ $books->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
          @endif

          @foreach($books->getUrlRange(max(1, $books->currentPage() - 2), min($books->lastPage(), $books->currentPage() + 2)) as $page => $url)
            @if($page === $books->currentPage())
              <span class="active" aria-current="page">{{ $page }}</span>
            @else
              <a href="{{ $url }}">{{ $page }}</a>
            @endif
          @endforeach

          @if($books->hasMorePages())
            <a href="{{ $books->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
          @else
            <span class="disabled" aria-hidden="true">›</span>
          @endif
        </nav>
      </div>
    @endif
  @endif

  <div class="modal-overlay" id="modal-add-book">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-add-book-title" style="background:var(--c-surface);border-radius:var(--r-xl);width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-xl)">
      <form method="POST" action="{{ route('books.store') }}">
        @csrf
        <div class="card-header">
          <h3 class="card-title" id="modal-add-book-title">Tambah Buku Baru</h3>
          <button class="icon-btn" type="button" onclick="closeModal('add-book')" aria-label="Tutup dialog">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="title">Judul Buku</label>
            <input class="form-input @error('title') is-invalid @enderror" id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="180" placeholder="Misal: Laskar Pelangi">
            @error('title')<p class="form-error">{{ $message }}</p>@enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="author">Penulis</label>
            <input class="form-input @error('author') is-invalid @enderror" id="author" name="author" type="text" value="{{ old('author') }}" required maxlength="120" placeholder="Nama penulis">
            @error('author')<p class="form-error">{{ $message }}</p>@enderror
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="isbn">ISBN</label>
              <input class="form-input @error('isbn') is-invalid @enderror" id="isbn" name="isbn" type="text" value="{{ old('isbn') }}" required maxlength="32" placeholder="978-602-241-054-5">
              @error('isbn')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
              <label class="form-label" for="published_year">Tahun Terbit</label>
              <input class="form-input @error('published_year') is-invalid @enderror" id="published_year" name="published_year" type="number" value="{{ old('published_year') }}" required min="1000" max="{{ date('Y') }}" placeholder="{{ date('Y') }}">
              @error('published_year')<p class="form-error">{{ $message }}</p>@enderror
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="genre">Genre</label>
              <input class="form-input @error('genre') is-invalid @enderror" id="genre" name="genre" type="text" value="{{ old('genre') }}" required maxlength="40" list="genre-options" placeholder="Fiksi">
              <datalist id="genre-options">
                @foreach($genres as $option)
                  <option value="{{ $option }}"></option>
                @endforeach
              </datalist>
              @error('genre')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
              <label class="form-label" for="stock">Jumlah Eksemplar</label>
              <input class="form-input @error('stock') is-invalid @enderror" id="stock" name="stock" type="number" value="{{ old('stock', 1) }}" required min="1" max="999">
              @error('stock')<p class="form-error">{{ $message }}</p>@enderror
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="shelf_code">Kode Rak <span style="text-transform:none;letter-spacing:0">(opsional)</span></label>
            <input class="form-input" id="shelf_code" name="shelf_code" type="text" value="{{ old('shelf_code') }}" maxlength="20" placeholder="A-03">
          </div>
        </div>

        <div class="card-header" style="border-top:1px solid var(--c-border-light);border-bottom:none;justify-content:flex-end">
          <button class="btn btn-outline" type="button" onclick="closeModal('add-book')">Batal</button>
          <button class="btn btn-primary" type="submit">Simpan Buku</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  @if($errors->any())
    document.addEventListener('DOMContentLoaded', function () { openModal('add-book'); });
  @endif
</script>
@endpush
