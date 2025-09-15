@extends('layouts.app')

@section('content')
  <div style="max-width: 520px; margin: 0 auto;">
    <h2 style="color:#fff; margin-bottom: .75rem;">Login</h2>

    @if ($errors->any())
      <div class="alert error">
        <ul style="margin:0; padding-left: 1rem;">
          @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ url('/login') }}" class="form-card" style="display:grid; gap:.75rem;">
      @csrf
      <label>
        <span>Email</span>
        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
      </label>

      <label>
        <span>Password</span>
        <input type="password" name="password" required autocomplete="current-password">
      </label>

      <label class="checkbox">
        <input type="checkbox" name="remember" value="1"> Remember me
      </label>

      <button type="submit">Login</button>
    </form>

    <div style="display:flex; justify-content: space-between; gap: .75rem; margin-top: .75rem;">
      <form method="GET" action="{{ route('register') }}">
        <button type="submit">Create an account</button>
      </form>
      <div class="muted" style="align-self:center;">Welcome back!</div>
    </div>
  </div>
@endsection