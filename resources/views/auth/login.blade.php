<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk · Pustaka</title>
<meta name="description" content="Masuk ke Pustaka — sistem peminjaman buku.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,300;0,500;0,600;0,700&family=Crimson+Pro:ital,wght@0,300;0,400;0,500;0,600&display=swap" rel="stylesheet">
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
<div class="auth-shell">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="brand-icon" aria-hidden="true">P</div>
      <div>
        <div class="auth-title">Pustaka</div>
        <div class="auth-sub">Sistem Peminjaman Buku</div>
      </div>
    </div>

    @if(config('app.demo'))
      <div class="demo-banner" role="note" style="margin-bottom:var(--space-md)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span><strong>Demo publik.</strong> Datanya buatan, bukan catatan perpustakaan sungguhan. Jangan masukkan data pribadi.</span>
      </div>
    @endif

    <h1 style="font-family:var(--f-heading);font-size:1.15rem;font-weight:600;margin-bottom:var(--space-xs)">Masuk ke meja petugas</h1>
    <p style="font-size:.85rem;color:var(--c-text-muted);margin-bottom:var(--space-lg)">Kelola koleksi, peminjaman, dan pengembalian buku.</p>

    @if($errors->any())
      <div class="alert alert-danger" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <span>{{ $errors->first() }}</span>
      </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" novalidate>
      @csrf

      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-input @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="petugas@perpus.test">
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Kata Sandi</label>
        <input class="form-input @error('password') is-invalid @enderror" id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••">
      </div>

      <div class="form-group" style="display:flex;align-items:center;gap:var(--space-sm)">
        <input id="remember" name="remember" type="checkbox" value="1" style="width:16px;height:16px;accent-color:var(--c-accent)">
        <label for="remember" style="font-size:.85rem;color:var(--c-text-muted)">Ingat saya di perangkat ini</label>
      </div>

      <button class="btn btn-primary" type="submit" style="width:100%">Masuk</button>
    </form>

    <div class="auth-footer">
      Prototipe lokal. Akun demo dibuat oleh <code style="font-family:var(--f-mono);font-size:.75rem">php artisan db:seed</code>.
    </div>
  </div>
</div>
</body>
</html>
