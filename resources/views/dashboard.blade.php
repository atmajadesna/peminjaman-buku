@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <span>Beranda</span><span class="sep">/</span><span>Dashboard</span>
  </nav>

  <div class="page-header">
    <div>
      <h2 class="page-title">Dashboard</h2>
      <p class="page-subtitle">Ringkasan koleksi dan sirkulasi per {{ now()->translatedFormat('j F Y') }}</p>
    </div>
    <a href="{{ route('loans.index') }}" class="btn btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
      Pinjam Baru
    </a>
  </div>

  <div class="stats-grid">
    <div class="stat-card" style="--stripe-a:#92400E;--stripe-b:#D97706">
      <div class="stat-icon books" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
      </div>
      <div class="stat-info">
        <div class="stat-label">Koleksi</div>
        <div class="stat-value">{{ number_format($totalTitles, 0, ',', '.') }}</div>
        <div class="stat-note">{{ number_format($totalCopies, 0, ',', '.') }} eksemplar terdaftar</div>
      </div>
    </div>

    <div class="stat-card" style="--stripe-a:#D97706;--stripe-b:#F59E0B">
      <div class="stat-icon borrowed" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
      </div>
      <div class="stat-info">
        <div class="stat-label">Sedang Dipinjam</div>
        <div class="stat-value">{{ number_format($activeLoans, 0, ',', '.') }}</div>
        <div class="stat-note">{{ number_format($availableCopies, 0, ',', '.') }} eksemplar di rak</div>
      </div>
    </div>

    <div class="stat-card" style="--stripe-a:#0284C7;--stripe-b:#38BDF8">
      <div class="stat-icon members" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      </div>
      <div class="stat-info">
        <div class="stat-label">Anggota Aktif</div>
        <div class="stat-value">{{ number_format($activeMembers, 0, ',', '.') }}</div>
        <div class="stat-note">Terdaftar dan boleh meminjam</div>
      </div>
    </div>

    <div class="stat-card" style="--stripe-a:#DC2626;--stripe-b:#EF4444">
      <div class="stat-icon overdue" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      </div>
      <div class="stat-info">
        <div class="stat-label">Terlambat</div>
        <div class="stat-value">{{ number_format($overdueLoans, 0, ',', '.') }}</div>
        <div class="stat-note">
          @if($overdueLoans > 0)
            <a href="{{ route('loans.index', ['status' => 'overdue']) }}" style="color:var(--c-danger-ink);text-decoration:underline">Tinjau sekarang</a>
          @else
            Tidak ada keterlambatan
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Peminjaman Terbaru</h3>
        <a href="{{ route('loans.index') }}" class="btn btn-ghost btn-sm">Lihat semua</a>
      </div>

      @if($recentLoans->isEmpty())
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
          <p>Belum ada peminjaman tercatat.</p>
          <a href="{{ route('loans.index') }}" class="btn btn-primary btn-sm">Catat peminjaman</a>
        </div>
      @else
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th scope="col">Buku</th>
                <th scope="col">Peminjam</th>
                <th scope="col">Jatuh Tempo</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentLoans as $loan)
                @php $tint = $loan->book->coverTint(); @endphp
                <tr>
                  <td>
                    <div class="book-cell">
                      <div class="book-cover" style="background:linear-gradient(135deg,{{ $tint[0] }},{{ $tint[1] }})" aria-hidden="true">{{ Str::limit($loan->book->genre, 7, '') }}</div>
                      <div>
                        <div class="book-title-text">{{ $loan->book->title }}</div>
                        <div class="book-author">{{ $loan->book->author }}</div>
                      </div>
                    </div>
                  </td>
                  <td>{{ $loan->member->name }}</td>
                  <td>{{ $loan->due_at->translatedFormat('j M Y') }}</td>
                  <td><span class="badge {{ $loan->statusBadgeClass() }}">{{ $loan->statusLabel() }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Jatuh Tempo Terdekat</h3>
      </div>
      <div class="card-body">
        @if($dueSoon->isEmpty())
          <p style="font-size:.85rem;color:var(--c-text-muted)">Tidak ada peminjaman yang jatuh tempo dalam 3 hari ke depan.</p>
        @else
          <ul class="list">
            @foreach($dueSoon as $loan)
              <li class="list-item">
                <div class="list-dot borrow" aria-hidden="true"></div>
                <div>
                  <div class="list-text"><strong>{{ $loan->book->title }}</strong> — {{ $loan->member->name }}</div>
                  <div class="list-meta">{{ $loan->due_at->translatedFormat('j F Y') }} · {{ $loan->code }}</div>
                </div>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Paling Sering Dipinjam</h3>
      <a href="{{ route('books.index') }}" class="btn btn-ghost btn-sm">Buka katalog</a>
    </div>
    <div class="card-body">
      @if($mostBorrowed->isEmpty() || $mostBorrowed->sum('loans_count') === 0)
        <p style="font-size:.85rem;color:var(--c-text-muted)">Belum ada data peminjaman untuk diperingkat.</p>
      @else
        <ul class="list">
          @foreach($mostBorrowed as $book)
            @if($book->loans_count > 0)
              <li class="list-item">
                <div class="list-dot borrow" aria-hidden="true"></div>
                <div style="flex:1">
                  <div class="list-text"><strong>{{ $book->title }}</strong> — {{ $book->author }}</div>
                  <div class="list-meta">{{ $book->loans_count }} peminjaman · {{ $book->availableCopies() }} dari {{ $book->stock }} tersedia</div>
                </div>
                <span class="badge badge-genre">{{ $book->genre }}</span>
              </li>
            @endif
          @endforeach
        </ul>
      @endif
    </div>
  </div>
@endsection
