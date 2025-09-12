@extends('layouts.app')

@section('content')
  <h2>Login</h2>

  @if ($errors->any())
    <div class="alert error">
      <ul>
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ url('/login') }}" class="form-card">
    @csrf
    <label>
      Email
      <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
    </label>

    <label>
      Password
      <input type="password" name="password" required autocomplete="current-password">
    </label>

    <label class="checkbox">
      <input type="checkbox" name="remember"> Remember me
    </label>

    <button type="submit">Login</button>
  </form>

  <form method="GET" action="{{ route('register') }}" style="margin-top:1rem">
    <button type="submit">Create an account</button>
  </form>
@endsection