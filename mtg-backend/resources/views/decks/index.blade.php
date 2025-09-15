
@extends('layouts.app')

@section('content')
  <div style="display:flex; align-items:center; justify-content:space-between; gap:.75rem;">
    <h2 style="color:#fff; margin:0;">Your Decks</h2>
    <div style="display:flex; gap:.5rem;">
      <button id="importNewBtn" class="button">Import New Deck</button>
      <form method="GET" action="{{ route('cards.search') }}" style="margin:0"><button type="submit">Build a deck</button></form>
    </div>
  </div>

  @if ($decks->isEmpty())
    <p class="muted" style="margin-top:.75rem;">No decks yet. Use "Build a deck" or import one.</p>
  @else
    <div class="results-grid" style="margin-top:1rem;">
      @foreach ($decks as $d)
        @php
          $img = $deckImages[(string)$d->id] ?? null;
          $count = $deckCounts[(string)$d->id] ?? 0;
        @endphp
        <a href="{{ route('decks.show', $d) }}" class="card" style="display:block; text-decoration:none; color:inherit;">
          @if ($img)
            <div class="card__media deck-cover">
              <img src="{{ $img }}" alt="Deck art">
            </div>
          @else
            <div class="card__media deck-cover">
              <img src="/images/placeholder-card.svg" alt="Deck placeholder">
            </div>
          @endif
          <div class="card__body">
            <h3 style="color:#fff; margin:.25rem 0 .35rem 0;">{{ $d->name }}</h3>
            <p class="muted">{{ $count }} cards</p>
            @if ($d->share_enabled)
              <p class="muted">Shared • <span style="opacity:.85;">{{ route('decks.share.show', ['token'=>$d->share_token]) }}</span></p>
            @endif
          </div>
        </a>
      @endforeach
    </div>
  @endif
@endsection

@push('scripts')
<script>
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  function openImportModal() {
    // Simple in-page modal
    const wrap = document.createElement('div');
    wrap.style.position='fixed'; wrap.style.inset='0'; wrap.style.background='rgba(0,0,0,.6)'; wrap.style.zIndex='2000';
    wrap.innerHTML = `
      <div style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:min(680px, 90vw); background:#0f172a; color:#fff; border:1px solid #ffffff22; border-radius:.6rem; box-shadow:0 20px 50px rgba(0,0,0,.5);">
        <div style="padding: .8rem 1rem; border-bottom:1px solid #ffffff1a; display:flex; justify-content:space-between; align-items:center;">
          <strong>Import New Deck</strong>
          <button class="button" data-close>Cancel</button>
        </div>
        <div style="padding: 1rem; display:grid; gap:.75rem;">
          <label style="display:grid; gap:.35rem;">
            <span>Deck name</span>
            <input type="text" id="imp-name" style="width:100%; padding:.6rem .7rem; border:1px solid #ffffff2a; border-radius:.45rem; background:#0b1220; color:#e5e7eb;" placeholder="My New Deck" />
          </label>
          <label style="display:grid; gap:.35rem;">
            <span>Paste MTG Arena list</span>
            <textarea id="imp-list" rows="10" style="width:100%; padding:.6rem .7rem; border:1px solid #ffffff2a; border-radius:.45rem; background:#0b1220; color:#e5e7eb;" placeholder="4 Lightning Bolt\n3 Llanowar Elves\n..."></textarea>
          </label>
          <div style="display:flex; gap:.5rem; justify-content:flex-end;">
            <button class="button" data-import>Import</button>
          </div>
          <div id="imp-error" class="muted" style="color:#fca5a5;"></div>
        </div>
      </div>`;
    document.body.appendChild(wrap);
    function close() { wrap.remove(); }
    wrap.addEventListener('click', (e) => { if (e.target === wrap || e.target.hasAttribute('data-close')) close(); });
    wrap.querySelector('[data-import]').addEventListener('click', async () => {
      const nameEl = wrap.querySelector('#imp-name');
      const listEl = wrap.querySelector('#imp-list');
      const errEl = wrap.querySelector('#imp-error');
      let name = nameEl.value.trim();
      const list = listEl.value.trim();
      if (!name || !list) { errEl.textContent = 'Please provide a name and a list.'; return; }
      errEl.textContent = '';
      try {
        const r = await fetch('{{ route('decks.importNew') }}', {
          method: 'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
          body: JSON.stringify({ name, list }), credentials: 'same-origin'
        });
        if (r.status === 409) {
          // Name conflict — prompt user to rename or cancel
          let newName = prompt('A deck with this name already exists. Please enter a different name, or press Cancel to stop.', name);
          if (!newName) { errEl.textContent = 'Import canceled.'; return; }
          newName = newName.trim(); if (!newName) { errEl.textContent = 'Invalid name. Try again via Import.'; return; }
          // retry once; if still conflict, loop
          let attempts = 0;
          while (attempts < 5) {
            attempts++;
            const r2 = await fetch('{{ route('decks.importNew') }}', {
              method: 'POST', headers: { 'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
              body: JSON.stringify({ name: newName, list }), credentials: 'same-origin'
            });
            if (r2.status === 201) { const j = await r2.json(); window.location.href = '/decks/' + encodeURIComponent(j.deck.id); return; }
            if (r2.status === 409) { newName = prompt('That name is still in use. Please enter a different name, or Cancel to stop.', newName) || ''; newName = newName.trim(); if (!newName) { errEl.textContent = 'Import canceled.'; return; } continue; }
            const jt = await r2.text(); errEl.textContent = 'Import failed: ' + jt; return;
          }
          errEl.textContent = 'Too many attempts. Import canceled.'; return;
        }
  if (r.status === 201) { const j = await r.json(); window.location.href = '/decks/' + encodeURIComponent(j.deck.id); return; }
        const txt = await r.text(); errEl.textContent = 'Import failed: ' + txt;
      } catch (e) {
        errEl.textContent = 'Import failed. Please try again.';
      }
    });
  }

  document.getElementById('importNewBtn')?.addEventListener('click', openImportModal);
</script>
@endpush