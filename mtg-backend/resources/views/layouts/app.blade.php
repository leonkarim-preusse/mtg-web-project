<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="robots" content="index,follow">
    <meta name="description" content="{{ $metaDescription ?? 'MTG Card Search and Deck Builder.' }}">
    <link rel="canonical" href="{{ $canonical ?? request()->fullUrl() }}">
    <meta name="theme-color" content="#0b1220">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name', 'MTG App') }}">
    <meta property="og:title" content="{{ $title ?? 'MTG App' }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'MTG Card Search and Deck Builder.' }}">
    <meta property="og:url" content="{{ $canonical ?? request()->fullUrl() }}">
    <meta property="og:image" content="{{ asset('images/placeholder-card.png') }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'MTG App' }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? 'MTG Card Search and Deck Builder.' }}">
    <meta name="twitter:image" content="{{ asset('images/placeholder-card.png') }}">
    <script>window.isAuthed = {{ auth()->check() ? 'true' : 'false' }};</script>
    <title>{{ $title ?? 'MTG App' }}</title>
    @if (file_exists(public_path('build/manifest.json')))
      @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
      <link rel="stylesheet" href="{{ asset('app.css') }}">
    @endif
  </head>
  <body class="{{ request()->is('decks*') ? 'page-decks' : '' }}">
    <a class="skip-link" href="#main-content">Zum Inhalt springen</a>
    <header class="topbar" role="banner">
      <div class="topbar__inner container">
        <!-- Left: tabs with image background -->
        <div class="nav-banner">
          <nav class="tabs" aria-label="Hauptnavigation">
            <form method="GET" action="{{ route('cards.search') }}"><button type="submit">Card Search</button></form>
            @auth
              <a href="{{ route('decks.index') }}" class="button">Decks</a>
              <form method="GET" action="{{ route('favorites.index') }}"><button type="submit">Favorites</button></form>
            @endauth
          </nav>
        </div>

        <!-- Middle: flexible gap (search can stay here) -->
        <form class="searchbar" method="GET" action="{{ route('search.global') }}" role="search" aria-label="Seitensuche">
          <input type="search" name="q" value="{{ request('q') }}" placeholder="Search cards or your decks…" />
          <input type="hidden" name="pageSize" value="{{ request('pageSize', 30) }}">
          <button type="submit">Search</button>
        </form>

        <!-- Right: auth actions (login/logout) -->
        <div class="logout" aria-label="Benutzeraktionen">
          @guest
            <a href="{{ route('login') }}" class="button" style="display:inline-flex; align-items:center; gap:.4rem;" aria-label="Login">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="8" r="4"/>
                <path d="M6 20a6 6 0 0 1 12 0"/>
              </svg>
              <span>Login</span>
            </a>
          @endguest
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
          </form>
          @endauth
        </div>
      </div>
    </header>

    <main id="main-content" class="container" role="main">
      @yield('content')
    </main>
    <footer class="site-footer" role="contentinfo">
      <div class="container" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center; justify-content:space-between;">
        <p class="muted" style="margin:0;">&copy; {{ now()->year }} {{ config('app.name', 'MTG App') }}</p>
        <nav aria-label="Rechtliches">
          <a href="{{ route('privacy') }}">Datenschutzerklärung</a>
          <span aria-hidden="true"> · </span>
          <a href="{{ route('accessibility') }}">Erklärung zur Barrierefreiheit</a>
          <span aria-hidden="true"> · </span>
          <a href="https://www.uni-goettingen.de/de/439238.html" target="_blank" rel="noopener">Impressum</a>
        </nav>
      </div>
    </footer>
    @stack('scripts') <!-- ensure per-page scripts are injected -->
  </body>
</html>