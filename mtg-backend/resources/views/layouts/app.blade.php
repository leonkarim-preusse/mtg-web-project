<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <script>window.isAuthed = {{ auth()->check() ? 'true' : 'false' }};</script>
    <title>{{ $title ?? 'MTG App' }}</title>
    @if (file_exists(public_path('build/manifest.json')))
      @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
      <link rel="stylesheet" href="{{ asset('app.css') }}">
    @endif
  </head>
  <body>
    <header class="topbar">
      <div class="topbar__inner container">
        <!-- Left: tabs with image background -->
        <div class="nav-banner">
          <nav class="tabs">
            <form method="GET" action="{{ route('cards.search') }}"><button type="submit">Card Search</button></form>
            <form method="GET" action="{{ route('decks.index') }}"><button type="submit">Decks</button></form>
            <form method="GET" action="{{ route('favorites.index') }}"><button type="submit">Favorites</button></form>
          </nav>
        </div>

        <!-- Middle: flexible gap (search can stay here) -->
        <form class="searchbar" method="GET" action="{{ route('search.global') }}">
          <input type="search" name="q" value="{{ request('q') }}" placeholder="Search cards or your decks…" />
          <input type="hidden" name="pageSize" value="{{ request('pageSize', 30) }}">
          <button type="submit">Search</button>
        </form>

        <!-- Right: logout (icon + text button) -->
        <div class="logout">
          @auth
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="icon-btn" aria-label="Logout">
              <!-- inline SVG icon -->
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
              </svg>
            </button>
            <button type="submit">Logout</button>
          </form>
          @endauth
          @guest
          <div class="auth-links">
            <form method="GET" action="{{ route('login') }}"><button type="submit">Login</button></form>
            <form method="GET" action="{{ route('register') }}"><button type="submit">Register</button></form>
          </div>
          @endguest
        </div>
      </div>
    </header>

    <main class="container">
      @yield('content')
    </main>
    @stack('scripts') <!-- ensure per-page scripts are injected -->
  </body>
</html>