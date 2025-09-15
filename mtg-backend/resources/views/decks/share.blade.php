@extends('layouts.app')

@section('content')
  <h2 style="color:#fff;">{{ $deck->name }}</h2>
  <p class="muted">Shared deck (read-only)</p>
  <div class="results-grid">
    @foreach ($items as $it)
      @php $c = $it['card']; $q = $it['quantity']; @endphp
      <article class="card">
        @if (!empty($c['imageUrl']))
          <div class="card__media"><img src="{{ $c['imageUrl'] }}" alt="{{ $c['name'] }}"></div>
        @endif
        <div class="card__body">
          <h3 style="color:#fff;">{{ $c['name'] }}</h3>
          <div style="display:flex;align-items:center;gap:.5rem;">
            <span class="muted">Quantity:</span>
            <span style="color:#fff; font-weight:700;">{{ $q }}</span>
          </div>
        </div>
      </article>
    @endforeach
  </div>
@endsection
