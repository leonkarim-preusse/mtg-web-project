@extends('layouts.app')

@section('content')
  <h2>Search results @if($q) for “{{ $q }}” @endif</h2>

  @php
    // page and pageSize come from controller; keep them for Prev/Next
    $page = (int) ($page ?? 1);
    $pageSize = (int) ($pageSize ?? 30);
  @endphp

  <section style="margin-top:1rem;">
    <div style="display:flex; align-items:center; gap:.75rem;"><h3 style="margin:0;">Cards</h3></div>

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
            @auth
              <button class="add-btn" data-id="{{ $c['id'] ?? '' }}" title="Add to deck" aria-haspopup="true" aria-expanded="false">+</button>
              <div class="deck-popover" data-for="{{ $c['id'] ?? '' }}" hidden>
                <div class="deck-popover__inner">
                  <div class="row">
                    <label>Existing deck</label>
                    <select class="deck-select"><option value="">Loading…</option></select>
                    <button class="deck-add-existing" data-id="{{ $c['id'] ?? '' }}">Add</button>
                  </div>
                  <div class="row">
                    <label>New deck</label>
                    <input type="text" class="deck-new-name" placeholder="Deck name">
                    <button class="deck-create-add" data-id="{{ $c['id'] ?? '' }}">Create + Add</button>
                  </div>
                </div>
              </div>
            @endauth
            @if (!empty($c['imageUrl']))
              <div class="card__media"><img src="{{ $c['imageUrl'] }}" alt="{{ $c['name'] }}"></div>
            @endif
            <div class="card__body">
              <h4 style="margin:.5rem 0; color:#fff;">{{ $c['name'] ?? '' }}</h4>
              <p style="color:#fff;">{{ isset($c['types']) && is_array($c['types']) ? implode(' ', $c['types']) : ($c['types'] ?? '') }}</p>
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
  function closeAllPopovers() {
    document.querySelectorAll('.deck-popover').forEach(p => p.hidden = true);
    document.querySelectorAll('.add-btn[aria-expanded="true"]').forEach(b => b.setAttribute('aria-expanded','false'));
    document.querySelectorAll('.card.popover-open').forEach(c => c.classList.remove('popover-open'));
  }

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

  // Add-to-deck interactions
  async function fetchDecks() {
    const r = await fetch('/api/decks', { headers: { 'Accept':'application/json' }, credentials: 'same-origin' });
    if (!r.ok) throw new Error('HTTP '+r.status);
    const j = await r.json();
    return j.decks || [];
  }

  document.addEventListener('click', async (e) => {
    const addBtn = e.target.closest('.add-btn');
    if (addBtn) {
      if (!window.isAuthed) { alert('Please log in to manage decks.'); return; }
      const id = addBtn.dataset.id;
      const pop = document.querySelector(`.deck-popover[data-for="${CSS.escape(id)}"]`);
      const cardEl = addBtn.closest('.card');
      const sel = pop.querySelector('.deck-select');
      if (pop.hidden) {
        closeAllPopovers();
        addBtn.setAttribute('aria-expanded', 'true');
        pop.hidden = false;
        cardEl?.classList.add('popover-open');
        try {
          const decks = await fetchDecks();
          sel.innerHTML = decks.length ? decks.map(d => `<option value="${d.id}">${d.name}</option>`).join('') : '<option value="">No decks</option>';
        } catch { sel.innerHTML = '<option value="">Error</option>'; }
      } else {
        addBtn.setAttribute('aria-expanded', 'false');
        pop.hidden = true;
        cardEl?.classList.remove('popover-open');
      }
      return;
    }

    const addExisting = e.target.closest('.deck-add-existing');
    if (addExisting) {
      const id = addExisting.dataset.id;
      const pop = document.querySelector(`.deck-popover[data-for="${CSS.escape(id)}"]`);
      const deckId = pop.querySelector('.deck-select')?.value;
      if (!deckId) return alert('Choose a deck.');
      try {
        const r = await fetch(`/api/decks/${encodeURIComponent(deckId)}/add`, {
          method: 'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
          body: JSON.stringify({ card_id: String(id), qty: 1 }), credentials: 'same-origin',
        });
        if (!r.ok) throw new Error('HTTP '+r.status);
        closeAllPopovers();
      } catch { alert('Failed to add to deck.'); }
      return;
    }

    const createAdd = e.target.closest('.deck-create-add');
    if (createAdd) {
      const id = createAdd.dataset.id;
      const pop = document.querySelector(`.deck-popover[data-for="${CSS.escape(id)}"]`);
      const name = pop.querySelector('.deck-new-name')?.value.trim();
      if (!name) return alert('Enter a deck name.');
      try {
        const r1 = await fetch('/api/decks', {
          method: 'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
          body: JSON.stringify({ name }), credentials: 'same-origin',
        });
        if (!r1.ok) throw new Error('HTTP '+r1.status);
        const j1 = await r1.json();
        const deckId = j1.deck?.id; if (!deckId) throw new Error('No deck id');
        const r2 = await fetch(`/api/decks/${encodeURIComponent(deckId)}/add`, {
          method: 'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
          body: JSON.stringify({ card_id: String(id), qty: 1 }), credentials: 'same-origin',
        });
        if (!r2.ok) throw new Error('HTTP '+r2.status);
        closeAllPopovers();
      } catch { alert('Failed to create/add.'); }
      return;
    }

    if (!e.target.closest('.deck-popover') && !e.target.closest('.add-btn')) closeAllPopovers();
  });

  loadFavs();
</script>
@endpush