
@extends('layouts.app')

@section('content')
  <div style="display:flex; align-items:center; justify-content:space-between; gap: .75rem;">
    <h2 style="color:#fff; margin:0;">{{ $deck->name }}</h2>
    <div style="display:flex; gap:.5rem; align-items:center;">
      <label class="checkbox" title="Share this deck via public link">
        <input type="checkbox" id="share-toggle" {{ $deck->share_enabled ? 'checked' : '' }}> <span style="color:#fff;">Share</span>
      </label>
      <a href="{{ route('decks.export', $deck) }}" class="button" title="Export deck">Export</a>
      <input type="text" id="share-url" readonly style="width:320px; background:#0b1220; color:#e5e7eb; border:1px solid #ffffff22; border-radius:.4rem; padding:.45rem .6rem;" value="{{ $deck->share_enabled ? route('decks.share.show', ['token' => $deck->share_token]) : '' }}" placeholder="Share link (enable to generate)">
      <button class="button" id="copy-share" {{ $deck->share_enabled ? '' : 'disabled' }}>Copy</button>
    </div>
  </div>
  <div id="deck-cards" class="results-grid">
    @forelse ($items as $it)
      @php $c = $it['card']; $q = $it['quantity']; @endphp
      <article class="card" data-id="{{ $c['id'] }}">
        <button class="fav-btn {{ '' }}" data-id="{{ $c['id'] }}" aria-pressed="false" title="Favorite">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
            <path class="heart-fill" d="M12 21s-6.716-4.35-9.428-7.06C.86 12.228 1 9.5 3.2 7.8 5.4 6.1 8 7 9.2 8.6L12 11.5l2.8-2.9C16 7 18.6 6.1 20.8 7.8 23 9.5 23.14 12.228 21.428 13.94 18.716 16.65 12 21 12 21z"/>
            <path class="heart-stroke" d="M12 21s-6.716-4.35-9.428-7.06C.86 12.228 1 9.5 3.2 7.8 5.4 6.1 8 7 9.2 8.6L12 11.5l2.8-2.9C16 7 18.6 6.1 20.8 7.8 23 9.5 23.14 12.228 21.428 13.94 18.716 16.65 12 21 12 21z"/>
          </svg>
        </button>
        @auth
          <button class="add-btn" data-id="{{ $c['id'] }}" title="Add to deck" aria-haspopup="true" aria-expanded="false">+</button>
          <div class="deck-popover" data-for="{{ $c['id'] }}" hidden>
            <div class="deck-popover__inner">
              <div class="row">
                <label>Existing deck</label>
                <select class="deck-select"><option value="">Loading…</option></select>
                <button class="deck-add-existing" data-id="{{ $c['id'] }}">Add</button>
              </div>
              <div class="row">
                <label>New deck</label>
                <input type="text" class="deck-new-name" placeholder="Deck name">
                <button class="deck-create-add" data-id="{{ $c['id'] }}">Create + Add</button>
              </div>
            </div>
          </div>
        @endauth

        @if (!empty($c['imageUrl']))
          <div class="card__media"><img src="{{ $c['imageUrl'] }}" alt="{{ $c['name'] }}"></div>
        @endif
        <div class="card__body">
          <h3 style="color:#fff;">{{ $c['name'] }}</h3>
          <div style="display:flex;align-items:center;gap:.5rem;">
            <button class="qty-btn" data-op="minus">−</button>
            <span class="qty" data-qty="{{ $q }}" style="color:#fff;">{{ $q }}</span>
            <button class="qty-btn" data-op="plus">+</button>
          </div>
        </div>
      </article>
    @empty
      <p class="muted">No cards in this deck.</p>
    @endforelse
  </div>

  <section style="margin-top:1rem;">
    <h3 style="color:#fff;">Import</h3>
    @if (session('status'))
      <div class="alert" style="background:#0b3a2a; color:#fff; border:1px solid #22c55e44;">{{ session('status') }}</div>
    @endif
    <form method="POST" action="{{ route('decks.import', $deck) }}" class="form-card" style="display:grid; gap:.5rem;">
      @csrf
      <label>
        <span>Paste MTG Arena list (e.g., "4 Lightning Bolt")</span>
        <textarea name="list" rows="6" style="width:100%; background:#0f172a; color:#e5e7eb; border:1px solid #ffffff2a; border-radius:.45rem; padding:.6rem .7rem;"></textarea>
      </label>
      <div class="controls">
        <button type="submit">Import</button>
      </div>
    </form>
  </section>

  @push('scripts')
  <script>
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const shareToggle = document.getElementById('share-toggle');
    const shareInput = document.getElementById('share-url');
    const copyBtn = document.getElementById('copy-share');
    let favSet = new Set();

    async function updateShare(enabled, regenerate = false) {
      try {
        const r = await fetch('{{ route('decks.share.update', $deck) }}', {
          method: 'POST', headers: { 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
          body: JSON.stringify({ enabled: !!enabled, regenerate }), credentials: 'same-origin',
        });
        if (!r.ok) throw new Error('HTTP '+r.status);
        const j = await r.json();
        shareInput.value = j.share_enabled ? '{{ route('decks.share.show', ['token' => 'TOKEN']) }}'.replace('TOKEN', j.share_token) : '';
        copyBtn.disabled = !j.share_enabled;
      } catch (e) {
        alert('Failed to update sharing.');
        shareToggle.checked = !enabled; // revert
      }
    }

    shareToggle?.addEventListener('change', (e) => updateShare(e.target.checked));
    copyBtn?.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(shareInput.value);
        copyBtn.textContent = 'Copied!';
        setTimeout(() => copyBtn.textContent = 'Copy', 1200);
      } catch {}
    });

    // Favorites + Add-to-deck behaviors
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
        alert('Could not save favorite. Please try again.');
      }
    }

    async function fetchDecks() {
      const r = await fetch('/api/decks', { headers: { 'Accept':'application/json' }, credentials: 'same-origin' });
      if (!r.ok) throw new Error('HTTP '+r.status);
      const j = await r.json();
      return j.decks || [];
    }

    document.addEventListener('click', async (e) => {
      const fav = e.target.closest('.fav-btn');
      if (fav) { toggleFav(fav); return; }

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
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('.qty-btn');
      if (!btn) return;
      const cardEl = btn.closest('.card'); if (!cardEl) return;
      const id = String(cardEl.dataset.id || '');
      if (!id) return;

      const op = btn.dataset.op;
      const url = op === 'plus'
        ? '{{ route('api.decks.add', $deck) }}'
        : '{{ route('api.decks.remove', $deck) }}';

      try {
        const r = await fetch(url, {
          method: 'POST',
          headers: { 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
          body: JSON.stringify({ card_id: id, qty: 1 }),
          credentials: 'same-origin',
        });
        if (!r.ok) throw new Error('HTTP '+r.status);
        const qtyEl = cardEl.querySelector('.qty');
        let n = parseInt(qtyEl.getAttribute('data-qty')||'0',10);
        n = op === 'plus' ? n+1 : n-1;
        if (n <= 0) { cardEl.remove(); return; }
        qtyEl.setAttribute('data-qty', String(n));
        qtyEl.textContent = String(n);
      } catch(err) {
        alert('Failed to update deck.');
      }
    });
  </script>
  @endpush
@endsection