@extends('layouts.app')

@section('content')
  <h2>Card Search</h2>
  <form id="search-form" class="form-card">
    @csrf
    <div class="field-grid grid-3">
      <label><span>Name</span><input type="text" name="name" placeholder="e.g. Lightning Bolt"></label>
      <label><span>Types</span><input type="text" name="types" placeholder="e.g. Creature, Instant, Land"></label>
      <label><span>Subtypes</span><input type="text" name="subtypes" placeholder="e.g. Dragon, Goblin, Aura"></label>

      <label><span>Colors (AND)</span><input type="text" name="colors" placeholder="e.g. R or R,G"></label>
      <label><span>Rarity</span><input type="text" name="rarity" placeholder="e.g. Common, Rare"></label>
      <label><span>Set (code or name)</span><input type="text" name="setOrName" placeholder="e.g. MH2 or Modern Horizons 2"></label>

      <label class="span-2"><span>Text contains</span><input type="text" name="text" placeholder="rules text contains…"></label>
      <label><span>Per page</span><input type="number" name="pageSize" min="1" max="100" value="100"></label>
    </div>

    <div class="controls">
      <button type="submit">Search</button>
      <button type="button" id="reset-btn" class="button">Reset</button>
      <button type="button" id="toggle-advanced" class="button">Advanced</button>
      <label class="toggle" style="margin-left:auto;" title="Show only one card per name">
        <input type="checkbox" id="unique-names" name="unique" value="1" checked>
        <span>Unique names</span>
      </label>
    </div>

    <div id="advanced-fields" class="advanced" hidden>
      <div class="field-grid grid-3">
        <label><span>CMC</span><input type="text" name="cmc" placeholder="e.g. 3, >=5, 2..4"></label>
        <label><span>Power</span><input type="text" name="power" placeholder="e.g. >=4"></label>
        <label><span>Toughness</span><input type="text" name="toughness" placeholder="e.g. >=4"></label>
        <label><span>Loyalty</span><input type="text" name="loyalty" placeholder="e.g. >=3"></label>
        <label><span>Color identity</span><input type="text" name="colorIdentity" placeholder="e.g. R,G"></label>
        <label><span>Order by</span><input type="text" name="orderBy" placeholder="e.g. name, set, cmc"></label>
        <label><span>Direction</span><input type="text" name="dir" placeholder="asc or desc"></label>
      </div>
    </div>
  </form>

  <div id="results" class="results">Enter criteria and press Search.</div>

  <script>
    let page = 1;
    let favSet = new Set();
    let lastCards = [];

    const form = document.getElementById('search-form');
    const resetBtn = document.getElementById('reset-btn');
    const results = document.getElementById('results');
    const toggleBtn = document.getElementById('toggle-advanced');
    const advanced = document.getElementById('advanced-fields');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const uniqueToggle = document.getElementById('unique-names');

    toggleBtn.addEventListener('click', () => {
      advanced.hidden = !advanced.hidden;
      toggleBtn.textContent = advanced.hidden ? 'Advanced' : 'Hide advanced';
    });

    uniqueToggle.addEventListener('change', () => {
      if (uniqueToggle.checked) uniqueToggle.name = 'unique';
      else uniqueToggle.removeAttribute('name'); // omit when OFF
      page = 1;
      fetchAndRender();
    });

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      page = 1;
      fetchAndRender();
    });

    resetBtn.addEventListener('click', () => {
      form.reset();
      uniqueToggle.checked = true; uniqueToggle.name = 'unique';
      page = 1;
      advanced.hidden = true;
      toggleBtn.textContent = 'Advanced';
      results.innerHTML = 'Enter criteria and press Search.';
    });

    async function loadFavorites() {
      if (!window.isAuthed) return;
      try {
        const res = await fetch('/api/favorites', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        const json = await res.json();
        favSet = new Set(json.ids || []);
      } catch {}
    }

    function favButton(id) {
      const on = favSet.has(String(id));
      return `
        <button class="fav-btn ${on ? 'is-on' : ''}" data-id="${String(id)}" aria-pressed="${on}" title="${on ? 'Remove from favorites' : 'Add to favorites'}">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
            <path class="heart-fill" d="M12 21s-6.716-4.35-9.428-7.06C.86 12.228 1 9.5 3.2 7.8 5.4 6.1 8 7 9.2 8.6L12 11.5l2.8-2.9C16 7 18.6 6.1 20.8 7.8 23 9.5 23.14 12.228 21.428 13.94 18.716 16.65 12 21 12 21z"/>
            <path class="heart-stroke" d="M12 21s-6.716-4.35-9.428-7.06C.86 12.228 1 9.5 3.2 7.8 5.4 6.1 8 7 9.2 8.6L12 11.5l2.8-2.9C16 7 18.6 6.1 20.8 7.8 23 9.5 23.14 12.228 21.428 13.94 18.716 16.65 12 21 12 21z"/>
          </svg>
        </button>
      `;
    }

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.fav-btn');
      if (!btn) return;
      const id = btn.dataset.id;
      if (!id) return;
      toggleFavorite(id, btn);
    });

    async function toggleFavorite(id, btn) {
      if (!window.isAuthed) { alert('Please log in to use favorites.'); return; }
      const targetOn = !btn.classList.contains('is-on');
      btn.classList.toggle('is-on', targetOn);
      btn.setAttribute('aria-pressed', targetOn ? 'true' : 'false');

      try {
        const res = await fetch('/api/favorites/toggle', {
          method: 'POST',
          headers: {
            'Accept':'application/json',
            'Content-Type':'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ id: String(id) }),
          credentials: 'same-origin',
        });
        const ct = res.headers.get('content-type') || '';
        if (!res.ok || !ct.includes('application/json')) throw new Error('toggle failed');
        const j = await res.json();
        const on = !!j.favorited;
        btn.classList.toggle('is-on', on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        if (on) favSet.add(String(id)); else favSet.delete(String(id));
      } catch (e) {
        btn.classList.toggle('is-on', !targetOn);
        btn.setAttribute('aria-pressed', (!targetOn) ? 'true' : 'false');
        alert('Could not save favorite. Please try again.');
      }
    }

    function renderCard(c) {
      const img = c.imageUrl ? `<img src="${c.imageUrl}" alt="${c.name}">` : '';
      const types = Array.isArray(c.types) ? c.types.join(' ') : (c.types || '');
      const colors = Array.isArray(c.colors) ? c.colors.join('') : (c.colors || '');
      return `
        <article class="card" data-name="${String(c.name || '')}">
          ${favButton(c.id)}
          <div class="card__media">${img}</div>
          <div class="card__body">
            <h3>${c.name}</h3>
            <p>${types}</p>
            <p class="muted">${c.manaCost ?? ''} ${c.cmc != null ? '(CMC ' + c.cmc + ')' : ''}</p>
            <p class="muted">${c.rarity ?? ''} ${c.setName ? '• ' + c.setName : (c.set ? '• ' + c.set : '')}</p>
            <p class="muted">${colors}</p>
          </div>
        </article>
      `;
    }

    function getPageSize() {
      const n = parseInt((form.querySelector('input[name="pageSize"]')?.value || '30'), 10);
      return isNaN(n) ? 30 : Math.min(Math.max(n, 1), 100);
    }

    async function fetchAndRender() {
      await loadFavorites();

      const data = new FormData(form);
      const params = new URLSearchParams();
      for (const [k, v] of data.entries()) {
        const s = String(v).trim();
        if (s !== '') params.append(k, s);
      }
      params.set('page', String(page));

      results.innerHTML = '<div class="loading">Searching…</div>';
      try {
        const res = await fetch('/api/cards?' + params.toString(), {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const json = await res.json();
        lastCards = json.cards || [];

        const pageSize = getPageSize();
        results.innerHTML = `
          <div class="results-grid">
            ${lastCards.map(renderCard).join('')}
          </div>
          <div class="pager">
            <form onsubmit="return false;"><button type="button" id="prev">Previous</button></form>
            <form onsubmit="return false;"><button type="button" id="next">Next</button></form>
            <span class="muted">Page ${page}</span>
          </div>
        `;

        const prevBtn = document.getElementById('prev');
        const nextBtn = document.getElementById('next');
        prevBtn.disabled = (page <= 1);
        nextBtn.disabled = (lastCards.length < pageSize);
        prevBtn.onclick = () => { if (page > 1) { page -= 1; fetchAndRender(); } };
        nextBtn.onclick = () => { page += 1; fetchAndRender(); };
      } catch (err) {
        results.innerHTML = `<p class="error">Error: ${String(err)}</p>`;
      }
    }
  </script>
@endsection