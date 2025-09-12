
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'MTG App' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>
  <body>
    <header class="container">
      <nav class="nav">
        @auth
          <form method="GET" action="{{ route('dashboard') }}">
            <button type="submit">Dashboard</button>
          </form>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
          </form>
        @endauth

        @guest
          <form method="GET" action="{{ route('login') }}">
            <button type="submit">Login</button>
          </form>
          <form method="GET" action="{{ route('register') }}">
            <button type="submit">Register</button>
          </form>
        @endguest
      </nav>
      <h1 class="app-title">{{ $title ?? '' }}</h1>
    </header>

    <main class="container">
      @yield('content')
    </main>
  </body>
</html>