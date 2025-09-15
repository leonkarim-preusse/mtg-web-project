
@extends('layouts.app')

@section('content')
  <h2>Favorites</h2>
  <div id="fav-results" class="results">Loading…</div>

  <script>
    const out = document.getElementById('fav-results');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    let favSet = new Set();

    async function loadFavIds() {
      const r = await fetch('/api/favorites', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
      const j = await r.json();
      favSet = new Set(j.ids || []);
      return Array.from(favSet);
    }

    async function loadCards(ids) {
      if (!ids.length) return [];
      const r = await fetch('/api/cards/resolve?ids=' + encodeURIComponent(ids.join(',')), {
        headers: { 'Accept': 'application/json' },
      });
      const j = await r.json();
      return j.cards || [];
    }

    function favBtnHtml(id, on) {
      return `
        <button class="fav-btn ${on ? 'is-on' : ''}" data-id="${String(id)}" aria-pressed="${on ? 'true' : 'false'}" title="${on ? 'Remove from favorites' : 'Add to favorites'}">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
            <path class="heart-fill" d="M12 21s-6.716-4.35-9.428-7.06C.86 12.228 1 9.5 3.2 7.8 5.4 6.1 8 7 9.2 8.6L12 11.5l2.8-2.9C16 7 18.6 6.1 20.8 7.8 23 9.5 23.14 12.228 21.428 13.94 18.716 16.65 12 21 12 21z"/>
            <path class="heart-stroke" d="M12 21s-6.716-4.35-9.428-7.06C.86 12.228 1 9.5 3.2 7.8 5.4 6.1 8 7 9.2 8.6L12 11.5l2.8-2.9C16 7 18.6 6.1 20.8 7.8 23 9.5 23.14 12.228 21.428 13.94 18.716 16.65 12 21 12 21z"/>
          </svg>
        </button>
      `;
    }

    function render(cards) {
      if (!cards.length) {
        out.innerHTML = '<p class="muted">No favorites yet.</p>';
        return;
      }
      out.innerHTML = `
        <div class="results-grid">
          ${cards.map(c => `
            <article class="card">
              ${favBtnHtml(c.id, favSet.has(String(c.id)))}
              ${c.imageUrl ? `<div class="card__media"><img src="${c.imageUrl}" alt="${c.name}"></div>` : ''}
              <div class="card__body">
                <h3>${c.name}</h3>
                <p>${Array.isArray(c.types) ? c.types.join(' ') : (c.types || '')}</p>
                <p class="muted">${c.rarity ?? ''} ${c.setName ? '• ' + c.setName : (c.set ? '• ' + c.set : '')}</p>
              </div>
            </article>
          `).join('')}
        </div>
      `;
    }

    async function toggleFav(btn) {
      const id = String(btn.dataset.id || '');
      if (!id) return;
      // optimistic
      const targetOn = !btn.classList.contains('is-on');
      btn.classList.toggle('is-on', targetOn);
      btn.setAttribute('aria-pressed', targetOn ? 'true' : 'false');

      try {
        const r = await fetch('/api/favorites/toggle', {
          method: 'POST',
          headers: { 'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest' },
          body: JSON.stringify({ id }),
          credentials: 'same-origin',
        });
        const ct = r.headers.get('content-type') || '';
        if (!r.ok || !ct.includes('application/json')) throw new Error('toggle failed');
        const j = await r.json();
        const on = !!j.favorited;
        btn.classList.toggle('is-on', on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');

        // Update local set and remove card from list when unfavorited
        if (on) favSet.add(id); else favSet.delete(id);
        if (!on) {
          // remove the card element
          const card = btn.closest('.card');
          if (card) card.remove();
          // empty state if none left
          if (document.querySelectorAll('.results-grid .card').length === 0) {
            out.innerHTML = '<p class="muted">No favorites yet.</p>';
          }
        }
      } catch (e) {
        // revert
        btn.classList.toggle('is-on', !targetOn);
        btn.setAttribute('aria-pressed', (!targetOn) ? 'true' : 'false');
        alert('Could not save favorite. Please try again.');
      }
    }

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.fav-btn');
      if (btn) toggleFav(btn);
    });

    (async function init() {
      try {
        const ids = await loadFavIds();
        const cards = await loadCards(ids);
        render(cards);
      } catch (e) {
        out.innerHTML = '<p class="error">Failed to load favorites.</p>';
        console.error(e);
      }
    })();
  </script>
@endsection