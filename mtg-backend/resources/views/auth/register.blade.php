@extends('layouts.app')

@section('content')
  <h2>Register</h2>

  @if ($errors->any())
    <div class="alert error">
      <ul>
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ url('/register') }}" class="form-card">
    @csrf
    <label>
      Name (optional)
      <input type="text" name="name" value="{{ old('name') }}">
    </label>

    <label>
      Email
      <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
    </label>

    <label>
      Password
      <input type="password" name="password" required autocomplete="new-password">
    </label>

    <label>
      Confirm Password
      <input type="password" name="password_confirmation" required autocomplete="new-password">
    </label>

    <button type="submit">Create account</button>
  </form>

  <form method="GET" action="{{ route('login') }}" style="margin-top:1rem">
    <button type="submit">Log in</button>
  </form>
@endsection