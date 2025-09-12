
@extends('layouts.app')

@section('content')
  <h2>Dashboard</h2>
  <p>Welcome, {{ auth()->user()->name ?? auth()->user()->email }}.</p>
  <p>This is a placeholder. Next we’ll add Cards, Favorites, and Decks.</p>
@endsection