<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') · Pustaka</title>
<meta name="description" content="Pustaka — sistem peminjaman buku untuk mengelola koleksi, peminjaman, dan pengembalian.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,300;0,500;0,600;0,700;1,300;1,500&family=Crimson+Pro:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/pustaka.css') }}?v=1">
<script>
  (function () {
    try {
      var t = localStorage.getItem('pustaka-theme');
      if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t);
    } catch (e) {}
  })();
</script>
</head>
<body>
<a class="skip-link" href="#main">Lompat ke konten</a>

<button class="sidebar-toggle" type="button" onclick="toggleSidebar()" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
</button>
<div class="sidebar-overlay" onclick="toggleSidebar()" aria-hidden="true"></div>

<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon" aria-hidden="true">P</div>
      <div>
        <div class="brand-name">Pustaka</div>
        <div class="brand-sub">Sistem Peminjaman Buku</div>
      </div>
    </div>

    <nav class="sidebar-nav" aria-label="Navigasi utama">
      <div class="sidebar-section">
        <div class="sidebar-section-title">Menu Utama</div>

        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          Dashboard
        </a>

        <a href="{{ route('books.index') }}" class="nav-item {{ request()->routeIs('books.*') ? 'active' : '' }}" @if(request()->routeIs('books.*')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
          Katalog Buku
          <span class="nav-badge">{{ number_format($navBookCount ?? 0, 0, ',', '.') }}</span>
        </a>

        <a href="{{ route('loans.index') }}" class="nav-item {{ request()->routeIs('loans.*') ? 'active' : '' }}" @if(request()->routeIs('loans.*')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
          Peminjaman
          @if(($navActiveLoanCount ?? 0) > 0)
            <span class="nav-badge">{{ $navActiveLoanCount }}</span>
          @endif
        </a>

        <a href="{{ route('members.index') }}" class="nav-item {{ request()->routeIs('members.*') ? 'active' : '' }}" @if(request()->routeIs('members.*')) aria-current="page" @endif>
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          Anggota
        </a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="avatar" aria-hidden="true">{{ auth()->user()->initials() ?? 'AD' }}</div>
      <div class="user-info sidebar-user">
        <div class="user-name">{{ auth()->user()->name ?? 'Petugas' }}</div>
        <div class="user-role">Administrator</div>
      </div>
    </div>
    <div class="sidebar-actions">
      <button class="icon-btn" type="button" onclick="toggleTheme()" aria-label="Ganti tema terang atau gelap" title="Ganti tema">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
      </button>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="icon-btn" type="submit" aria-label="Keluar" title="Keluar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        </button>
      </form>
    </div>
  </aside>

  <main class="main" id="main">
    <header class="topbar">
      <form class="search-form" method="GET" action="{{ route('books.index') }}" role="search">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <label class="sr-only" for="global-search">Cari buku berdasarkan judul, penulis, atau ISBN</label>
        <input id="global-search" type="search" name="q" value="{{ request()->routeIs('books.*') ? request('q') : '' }}" placeholder="Cari judul, penulis, atau ISBN…">
      </form>
      <div class="topbar-actions">
        <a href="{{ route('loans.index', ['status' => 'overdue']) }}" class="icon-btn" aria-label="Lihat peminjaman terlambat" title="Peminjaman terlambat">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          @if(($navOverdueCount ?? 0) > 0)
            <span class="notif-dot" aria-hidden="true" style="position:absolute;top:9px;right:9px;width:8px;height:8px;border-radius:50%;background:var(--c-danger)"></span>
          @endif
        </a>
      </div>
    </header>

    <div class="page-content">
      @if(config('app.demo'))
        <div class="demo-banner" role="note">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          <span><strong>Demo publik.</strong> Semua data di sini buatan dan bisa diubah siapa saja yang masuk. Bukan catatan perpustakaan sungguhan.</span>
        </div>
      @endif

      @if(session('status'))
        <div class="alert alert-success" role="status">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex-shrink:0;margin-top:2px"><path d="M20 6L9 17l-5-5"/></svg>
          <span>{{ session('status') }}</span>
        </div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      @yield('content')
    </div>
  </main>
</div>

<script>
  function toggleSidebar() {
    var bar = document.getElementById('sidebar');
    var overlay = document.querySelector('.sidebar-overlay');
    var button = document.querySelector('.sidebar-toggle');
    var open = bar.classList.toggle('open');
    overlay.classList.toggle('open', open);
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    button.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
  }

  function toggleTheme() {
    var root = document.documentElement;
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('pustaka-theme', next); } catch (e) {}
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var bar = document.getElementById('sidebar');
    if (bar.classList.contains('open')) toggleSidebar();
    document.querySelectorAll('.modal-overlay.open').forEach(function (m) { m.classList.remove('open'); });
  });

  function openModal(id) {
    var m = document.getElementById('modal-' + id);
    if (!m) return;
    m.classList.add('open');
    var first = m.querySelector('input, select, button');
    if (first) first.focus();
  }

  function closeModal(id) {
    var m = document.getElementById('modal-' + id);
    if (m) m.classList.remove('open');
  }

  document.querySelectorAll('.modal-overlay').forEach(function (m) {
    m.addEventListener('click', function (e) { if (e.target === m) m.classList.remove('open'); });
  });
</script>
@stack('scripts')
</body>
</html>
