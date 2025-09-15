@extends('layouts.app')

@section('content')
  <h2>Search results @if($q) for “{{ $q }}” @endif</h2>

  @php
    // page and pageSize come from controller; keep them for Prev/Next
    $page = (int) ($page ?? 1);
    $pageSize = (int) ($pageSize ?? 30);
  @endphp

  <section style="margin-top:1rem;">
    <div style="display:flex; align-items:center; gap:.75rem;">
      <h3 style="margin:0;">Cards</h3>
      <label class="toggle" title="Show only one card per name (prefers first with image)">
        <input type="checkbox" id="unique-names-top" {{ request()->boolean('unique', true) ? 'checked' : '' }}>
        <span>Unique names</span>
      </label>
    </div>

    @if (empty($cards))
      <p class="muted">No card results right now. The MTG API may be busy; try again in a moment.</p>
    @else
      <div class="results-grid" id="top-results-grid">
        @foreach ($cards as $c)
          <article class="card" data-name="{{ $c['name'] ?? '' }}">
            <button class="fav-btn" data-id="{{ $c['id'] ?? '' }}" title="Add to favorites" aria-pressed="false">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                <path class="heart-fill" d="M12 21s-6.716-4.35-9.428-7.06C.86 12.228 1 9.5 3.2 7.8 5.4 6.1 8 7 9.2 8.6L12 11.5l2.8-2.9C16 7 18.6 6.1 20.8 7.8 23 9.5 23.14 12.228 21.428 13.94 18.716 16.65 12 21 12 21z"/>
                <path class="heart-stroke" d="M12 21s-6.716-4.35-9.428-7.06C.86 12.228 1 9.5 3.2 7.8 5.4 6.1 8 7 9.2 8.6L12 11.5l2.8-2.9C16 7 18.6 6.1 20.8 7.8 23 9.5 23.14 12.228 21.428 13.94 18.716 16.65 12 21 12 21z"/>
              </svg>
            </button>
            @if (!empty($c['imageUrl']))
              <div class="card__media"><img src="{{ $c['imageUrl'] }}" alt="{{ $c['name'] }}"></div>
            @endif
            <div class="card__body">
              <h4 style="margin:.5rem 0;">{{ $c['name'] ?? '' }}</h4>
              <p>{{ isset($c['types']) && is_array($c['types']) ? implode(' ', $c['types']) : ($c['types'] ?? '') }}</p>
              <p class="muted">{{ $c['rarity'] ?? '' }} {{ !empty($c['setName']) ? '• '.$c['setName'] : (!empty($c['set']) ? '• '.$c['set'] : '') }}</p>
            </div>
          </article>
        @endforeach
      </div>
    @endif

    <div style="margin-top:.75rem; display:flex; gap:.5rem; align-items:center;">
      <form method="GET" action="{{ route('search.global') }}">
        <input type="hidden" name="q" value="{{ $q }}">
        <input type="hidden" name="pageSize" value="{{ $pageSize }}">
        <input type="hidden" name="page" value="{{ max(1, $page - 1) }}">
        <button type="submit" @if($page <= 1) disabled @endif>Previous</button>
      </form>

      <form method="GET" action="{{ route('search.global') }}">
        <input type="hidden" name="q" value="{{ $q }}">
        <input type="hidden" name="pageSize" value="{{ $pageSize }}">
        <input type="hidden" name="page" value="{{ $page + 1 }}">
        <button type="submit" @if(count($cards) < $pageSize) disabled @endif>Next</button>
      </form>

      <span style="opacity:.7; margin-left:.5rem;">Page {{ $page }}</span>

      <form method="GET" action="{{ route('search.global') }}" style="margin-left:auto; display:flex; gap:.5rem; align-items:center;">
        <input type="hidden" name="q" value="{{ $q }}">
        <label style="display:flex; gap:.35rem; align-items:center;">
          Per page
          <input type="number" min="1" max="100" name="pageSize" value="{{ $pageSize }}" style="width:5rem;">
        </label>
        <button type="submit">Apply</button>
      </form>
    </div>
  </section>

  <section style="margin-top:2rem;">
    <h3>Your Decks</h3>
    @auth
      @if ($decks->isEmpty())
        <p>No matching decks.</p>
      @else
        <ul style="padding-left:1rem;">
          @foreach ($decks as $d)
            <li style="margin:.35rem 0;">{{ $d->name }}</li>
          @endforeach
        </ul>
        <form method="GET" action="{{ route('decks.index') }}" style="margin-top:.5rem;">
          <button type="submit">Open Decks</button>
        </form>
      @endif
    @else
      <p>Login to search your decks.</p>
      <form method="GET" action="{{ route('login') }}"><button type="submit">Login</button></form>
    @endauth
  </section>
@endsection

@push('scripts')
<script>
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
  let favSet = new Set();

  async function loadFavs() {
    if (!window.isAuthed) return;
    try {
      const r = await fetch('/api/favorites', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
      const j = await r.json();
      favSet = new Set(j.ids || []);
      document.querySelectorAll('.fav-btn').forEach(btn => {
        const on = favSet.has(String(btn.dataset.id));
        btn.classList.toggle('is-on', on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
    } catch {}
  }

  async function toggleFav(btn) {
    if (!window.isAuthed) { alert('Please log in to use favorites.'); return; }
    const id = String(btn.dataset.id || '');
    if (!id) return;

    const targetOn = !btn.classList.contains('is-on');
    btn.classList.toggle('is-on', targetOn);
    btn.setAttribute('aria-pressed', targetOn ? 'true' : 'false');

    try {
      const r = await fetch('/api/favorites/toggle', {
        method: 'POST',
        headers: { 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ id }),
        credentials: 'same-origin',
      });
      const ct = r.headers.get('content-type') || '';
      if (!r.ok || !ct.includes('application/json')) throw new Error('Toggle failed');
      const j = await r.json();
      const confirmed = !!j.favorited;
      btn.classList.toggle('is-on', confirmed);
      btn.setAttribute('aria-pressed', confirmed ? 'true' : 'false');
      if (confirmed) favSet.add(id); else favSet.delete(id);
    } catch (e) {
      btn.classList.toggle('is-on', !targetOn);
      btn.setAttribute('aria-pressed', (!targetOn) ? 'true' : 'false');
      console.error(e);
      alert('Could not save favorite. Please try again.');
    }
  }

  document.addEventListener('click', e => {
    const btn = e.target.closest('.fav-btn');
    if (btn) toggleFav(btn);
  });

  // Unique names toggle logic for top search page
  const uniqueTop = document.getElementById('unique-names-top');
  function applyUniqueDom() {
    const on = uniqueTop?.checked ?? true;
    const cards = Array.from(document.querySelectorAll('#top-results-grid .card'));
    if (!on) {
      cards.forEach(el => el.classList.remove('dupe-hidden'));
      return;
    }
    const seen = new Map(); // name -> {el, hasImg}
    for (const el of cards) {
      const name = String(el.dataset.name || '').toLowerCase().trim();
      if (!name) continue;
      const hasImg = !!el.querySelector('img');
      if (!seen.has(name)) {
        seen.set(name, { el, hasImg });
        el.classList.remove('dupe-hidden');
      } else {
        const rec = seen.get(name);
        if (!rec.hasImg && hasImg) {
          // Prefer this with image: show current, hide previous
          rec.el.classList.add('dupe-hidden');
          el.classList.remove('dupe-hidden');
          seen.set(name, { el, hasImg: true });
        } else {
          el.classList.add('dupe-hidden');
        }
      }
    }
  }
  uniqueTop?.addEventListener('change', applyUniqueDom);

  loadFavs();
  applyUniqueDom(); // default ON

  // Reload the page when toggled so results are fetched again
  const uniqueTop = document.getElementById('unique-names-top');
  uniqueTop?.addEventListener('change', () => {
    const url = new URL(window.location.href);
    if (uniqueTop.checked) url.searchParams.set('unique', '1');
    else url.searchParams.set('unique', '0');
    url.searchParams.set('page', '1');
    window.location.href = url.toString(); // full reload with new param
  });
</script>
@endpush