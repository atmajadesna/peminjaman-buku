@extends('layouts.app')

@section('title', 'Peminjaman')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}">Beranda</a><span class="sep">/</span><span>Peminjaman</span>
  </nav>

  <div class="page-header">
    <div>
      <h1 class="page-title">Peminjaman</h1>
      <p class="page-subtitle">Masa pinjam {{ \App\Models\Loan::LOAN_DAYS }} hari · denda Rp{{ number_format(\App\Models\Loan::FINE_PER_DAY, 0, ',', '.') }} per hari terlambat</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openModal('new-borrow')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
      Pinjam Baru
    </button>
  </div>

  <div class="card">
    <div class="card-header">
      <div style="display:flex;gap:var(--space-sm);flex-wrap:wrap" role="group" aria-label="Filter status peminjaman">
        @php
          $tabs = [
            '' => ['label' => 'Semua', 'count' => $counts['all']],
            'active' => ['label' => 'Dipinjam', 'count' => $counts['active']],
            'overdue' => ['label' => 'Terlambat', 'count' => $counts['overdue']],
            'returned' => ['label' => 'Dikembalikan', 'count' => $counts['returned']],
          ];
        @endphp
        @foreach($tabs as $key => $tab)
          <a href="{{ route('loans.index', $key === '' ? [] : ['status' => $key]) }}"
             class="btn btn-sm {{ $filter === $key ? 'btn-primary' : 'btn-outline' }}"
             @if($filter === $key) aria-current="page" @endif>
            {{ $tab['label'] }} <span style="opacity:.65">({{ $tab['count'] }})</span>
          </a>
        @endforeach
      </div>
    </div>

    @if($loans->isEmpty())
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
        <p>
          @if($filter === '')
            Belum ada peminjaman tercatat.
          @else
            Tidak ada peminjaman dengan status ini.
          @endif
        </p>
        <button class="btn btn-primary btn-sm" type="button" onclick="openModal('new-borrow')">Catat peminjaman</button>
      </div>
    @else
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th scope="col">Kode</th>
              <th scope="col">Buku</th>
              <th scope="col">Peminjam</th>
              <th scope="col">Pinjam</th>
              <th scope="col">Jatuh Tempo</th>
              <th scope="col">Status</th>
              <th scope="col">Denda</th>
              <th scope="col"><span class="sr-only">Aksi</span></th>
            </tr>
          </thead>
          <tbody>
            @foreach($loans as $loan)
              @php $tint = $loan->book->coverTint(); @endphp
              <tr>
                <td class="mono">{{ $loan->code }}</td>
                <td>
                  <div class="book-cell">
                    <div class="book-cover" style="background:linear-gradient(135deg,{{ $tint[0] }},{{ $tint[1] }})" aria-hidden="true">{{ Str::limit($loan->book->genre, 7, '') }}</div>
                    <div>
                      <div class="book-title-text">{{ $loan->book->title }}</div>
                      <div class="book-author">{{ $loan->book->author }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div>{{ $loan->member->name }}</div>
                  <div class="book-author">{{ $loan->member->code }}</div>
                </td>
                <td>{{ $loan->borrowed_at->translatedFormat('j M Y') }}</td>
                <td>
                  {{ $loan->due_at->translatedFormat('j M Y') }}
                  @if($loan->isOverdue())
                    <div class="book-author" style="color:var(--c-danger-ink)">{{ $loan->daysLate() }} hari lewat</div>
                  @endif
                </td>
                <td><span class="badge {{ $loan->statusBadgeClass() }}">{{ $loan->statusLabel() }}</span></td>
                <td>
                  @if($loan->isReturned())
                    {{ $loan->fine > 0 ? 'Rp'.number_format($loan->fine, 0, ',', '.') : '—' }}
                  @elseif($loan->isOverdue())
                    <span style="color:var(--c-danger-ink)">Rp{{ number_format($loan->projectedFine(), 0, ',', '.') }}</span>
                  @else
                    <span style="color:var(--c-text-muted)">—</span>
                  @endif
                </td>
                <td>
                  @if($loan->isReturned())
                    <span class="btn btn-sm btn-ghost" aria-disabled="true" style="pointer-events:none;opacity:.5">Selesai</span>
                  @else
                    <form method="POST" action="{{ route('loans.return', $loan) }}">
                      @csrf
                      @method('PATCH')
                      <button class="btn btn-sm {{ $loan->isOverdue() ? 'btn-danger' : 'btn-outline' }}" type="submit">
                        Kembalikan
                      </button>
                    </form>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if($loans->hasPages())
        <div class="card-body" style="padding-top:0">
          <nav class="pagination" aria-label="Navigasi halaman peminjaman">
            @if($loans->onFirstPage())
              <span class="disabled" aria-hidden="true">‹</span>
            @else
              <a href="{{ $loans->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
            @endif

            @foreach($loans->getUrlRange(max(1, $loans->currentPage() - 2), min($loans->lastPage(), $loans->currentPage() + 2)) as $page => $url)
              @if($page === $loans->currentPage())
                <span class="active" aria-current="page">{{ $page }}</span>
              @else
                <a href="{{ $url }}">{{ $page }}</a>
              @endif
            @endforeach

            @if($loans->hasMorePages())
              <a href="{{ $loans->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
            @else
              <span class="disabled" aria-hidden="true">›</span>
            @endif
          </nav>
        </div>
      @endif
    @endif
  </div>

  <div class="modal-overlay" id="modal-new-borrow">
    <div role="dialog" aria-modal="true" aria-labelledby="modal-new-borrow-title" style="background:var(--c-surface);border-radius:var(--r-xl);width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-xl)">
      <form method="POST" action="{{ route('loans.store') }}">
        @csrf
        <div class="card-header">
          <h2 class="card-title" id="modal-new-borrow-title">Peminjaman Baru</h2>
          <button class="icon-btn" type="button" onclick="closeModal('new-borrow')" aria-label="Tutup dialog">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="card-body">
          @if($availableBooks->isEmpty())
            <div class="alert alert-danger" role="alert">
              <span>Tidak ada buku dengan eksemplar tersedia. Tambah stok di katalog terlebih dahulu.</span>
            </div>
          @endif

          <div class="form-group">
            <label class="form-label" for="member_id">Anggota</label>
            <select class="form-input form-select" id="member_id" name="member_id" required>
              <option value="">Pilih anggota…</option>
              @foreach($members as $member)
                <option value="{{ $member->id }}" @selected((int) old('member_id') === $member->id)>
                  {{ $member->name }} ({{ $member->code }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="book_id">Buku</label>
            <select class="form-input form-select" id="book_id" name="book_id" required @disabled($availableBooks->isEmpty())>
              <option value="">Pilih buku…</option>
              @foreach($availableBooks as $book)
                <option value="{{ $book->id }}" @selected((int) old('book_id') === $book->id)>
                  {{ $book->title }} — {{ $book->author }} ({{ $book->availableCopies() }} tersedia)
                </option>
              @endforeach
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="borrowed_at">Tanggal Pinjam</label>
            <input class="form-input" id="borrowed_at" name="borrowed_at" type="date" value="{{ old('borrowed_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}">
            <p class="form-hint">Jatuh tempo dihitung otomatis {{ \App\Models\Loan::LOAN_DAYS }} hari setelah tanggal pinjam.</p>
          </div>
        </div>

        <div class="card-header" style="border-top:1px solid var(--c-border-light);border-bottom:none;justify-content:flex-end">
          <button class="btn btn-outline" type="button" onclick="closeModal('new-borrow')">Batal</button>
          <button class="btn btn-primary" type="submit" @disabled($availableBooks->isEmpty())>Proses Pinjam</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  @if($errors->any())
    document.addEventListener('DOMContentLoaded', function () { openModal('new-borrow'); });
  @endif
</script>
@endpush
