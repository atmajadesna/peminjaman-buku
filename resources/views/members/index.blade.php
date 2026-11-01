@extends('layouts.app')

@section('title', 'Anggota')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('dashboard') }}">Beranda</a><span class="sep">/</span><span>Anggota</span>
  </nav>

  <div class="page-header">
    <div>
      <h2 class="page-title">Anggota Perpustakaan</h2>
      <p class="page-subtitle">{{ number_format($members->total(), 0, ',', '.') }} anggota terdaftar</p>
    </div>
    <button class="btn btn-primary" type="button" onclick="openModal('add-member')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
      Anggota Baru
    </button>
  </div>

  @if($search !== '')
    <p style="font-size:.85rem;color:var(--c-text-muted);margin-bottom:var(--space-md)">
      Hasil pencarian untuk &ldquo;<strong>{{ $search }}</strong>&rdquo; ·
      <a href="{{ route('members.index') }}" style="color:var(--c-accent);text-decoration:underline">Bersihkan</a>
    </p>
  @endif

  <div class="card">
    @if($members->isEmpty())
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        <p>
          @if($search !== '')
            Tidak ada anggota yang cocok dengan pencarian.
          @else
            Belum ada anggota terdaftar.
          @endif
        </p>
        <button class="btn btn-primary btn-sm" type="button" onclick="openModal('add-member')">Daftarkan anggota</button>
      </div>
    @else
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th scope="col">Anggota</th>
              <th scope="col">Kode</th>
              <th scope="col">Email</th>
              <th scope="col">Pinjaman Aktif</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($members as $member)
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:var(--space-md)">
                    <div class="avatar" aria-hidden="true">{{ $member->initials() }}</div>
                    <div>
                      <div style="font-weight:500">{{ $member->name }}</div>
                      <div class="book-author">{{ $member->role }}</div>
                    </div>
                  </div>
                </td>
                <td class="mono">{{ $member->code }}</td>
                <td style="font-size:.85rem">{{ $member->email }}</td>
                <td>
                  @if($member->active_loans_count > 0)
                    <span class="badge badge-borrowed">{{ $member->active_loans_count }} buku</span>
                  @else
                    <span style="color:var(--c-text-muted)">—</span>
                  @endif
                </td>
                <td>
                  @if($member->is_active)
                    <span class="badge badge-available">Aktif</span>
                  @else
                    <span class="badge badge-overdue">Nonaktif</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if($members->hasPages())
        <div class="card-body" style="padding-top:0">
          <nav class="pagination" aria-label="Navigasi halaman anggota">
            @if($members->onFirstPage())
              <span class="disabled" aria-hidden="true">‹</span>
            @else
              <a href="{{ $members->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
            @endif

            @foreach($members->getUrlRange(max(1, $members->currentPage() - 2), min($members->lastPage(), $members->currentPage() + 2)) as $page => $url)
              @if($page === $members->currentPage())
                <span class="active" aria-current="page">{{ $page }}</span>
              @else
                <a href="{{ $url }}">{{ $page }}</a>
              @endif
            @endforeach

            @if($members->hasMorePages())
              <a href="{{ $members->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
            @else
              <span class="disabled" aria-hidden="true">›</span>
            @endif
          </nav>
        </div>
      @endif
    @endif
  </div>

  <div class="modal-overlay" id="modal-add-member">
    <div role="dialog" aria-modal="true" aria-labelledby="modal-add-member-title" style="background:var(--c-surface);border-radius:var(--r-xl);width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-xl)">
      <form method="POST" action="{{ route('members.store') }}">
        @csrf
        <div class="card-header">
          <h3 class="card-title" id="modal-add-member-title">Tambah Anggota Baru</h3>
          <button class="icon-btn" type="button" onclick="closeModal('add-member')" aria-label="Tutup dialog">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="name">Nama Lengkap</label>
            <input class="form-input @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="120" placeholder="Nama lengkap anggota">
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="member_email">Email</label>
            <input class="form-input @error('email') is-invalid @enderror" id="member_email" name="email" type="email" value="{{ old('email') }}" required maxlength="160" placeholder="nama@univ.ac.id">
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="role">Peran</label>
              <select class="form-input form-select" id="role" name="role" required>
                @foreach(['Mahasiswa', 'Dosen', 'Staff', 'Umum'] as $option)
                  <option value="{{ $option }}" @selected(old('role') === $option)>{{ $option }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="phone">No. Telepon</label>
              <input class="form-input" id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="24" placeholder="08xx-xxxx-xxxx">
            </div>
          </div>
        </div>

        <div class="card-header" style="border-top:1px solid var(--c-border-light);border-bottom:none;justify-content:flex-end">
          <button class="btn btn-outline" type="button" onclick="closeModal('add-member')">Batal</button>
          <button class="btn btn-primary" type="submit">Daftarkan</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  @if($errors->any())
    document.addEventListener('DOMContentLoaded', function () { openModal('add-member'); });
  @endif
</script>
@endpush
