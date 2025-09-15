@extends('layouts.app')

@section('content')
  <div style="max-width: 520px; margin: 0 auto;">
  <h2 style="color:#fff; margin-bottom: .75rem;">Register</h2>

  @if ($errors->any())
    <div class="alert error">
      <ul>
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ url('/register') }}" class="form-card" style="display:grid; gap:.75rem;">
    @csrf
    <label>
      <span>Name (optional)</span>
      <input type="text" name="name" value="{{ old('name') }}">
    </label>

    <label>
      <span>Email</span>
      <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
    </label>

    <label>
      <span>Password</span>
      <input type="password" name="password" required autocomplete="new-password">
    </label>

    <label>
      <span>Confirm Password</span>
      <input type="password" name="password_confirmation" required autocomplete="new-password">
    </label>

    <button type="submit">Create account</button>
  </form>

  <div style="display:flex; justify-content: space-between; gap: .75rem; margin-top: .75rem;">
    <form method="GET" action="{{ route('login') }}">
      <button type="submit">Log in</button>
    </form>
    <div class="muted" style="align-self:center;">It’s quick and free.</div>
  </div>
  </div>
@endsection