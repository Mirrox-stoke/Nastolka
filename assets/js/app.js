/* assets/js/app.js */

'use strict';

// ── Config ─────────────────────────────────────────────────────────────────────
const API = {
  games: 'api/games.php',
  cart: 'api/cart.php',
  orders: 'api/orders.php',
  reviews: 'api/reviews.php',
};

// ── State ──────────────────────────────────────────────────────────────────────
const state = {
  filters: {
    search: '',
    cats: new Set(),         // пусто = все
    maxPrice: 8000,
    players: 0,
  },
  sort: 'name',
  page: 1,
  totalPages: 1,
  cartQty: 0,
};

// ── DOM shortcuts ──────────────────────────────────────────────────────────────
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

// ── API helpers ────────────────────────────────────────────────────────────────
async function apiFetch(url, opts = {}) {
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json' },
    ...opts,
  });
  const json = await res.json();
  if (!json.ok) throw new Error(json.error || 'Ошибка сервера');
  return json;
}

function get(url) { return apiFetch(url); }
function post(url, body) { return apiFetch(url, { method: 'POST', body: JSON.stringify(body) }); }

// ── Format helpers ─────────────────────────────────────────────────────────────
const fmt = n => n.toLocaleString('ru-RU') + ' ₽';
const stars = r => '★'.repeat(Math.round(r)) + '☆'.repeat(5 - Math.round(r));

// ── Toast ──────────────────────────────────────────────────────────────────────
let _toastTimer;
function toast(msg, type = '') {
  const el = $('#toast');
  el.textContent = msg;
  el.className = 'toast show ' + type;
  clearTimeout(_toastTimer);
  _toastTimer = setTimeout(() => { el.className = 'toast'; }, 2500);
}

// ── Catalog ────────────────────────────────────────────────────────────────────
async function loadGames() {
  const grid = $('#gamesGrid');
  grid.innerHTML = '<div class="spinner"></div>';

  const p = new URLSearchParams();
  if (state.filters.search) p.set('search', state.filters.search);
  if (state.filters.cats.size) p.set('category', [...state.filters.cats].join(','));
  if (state.filters.maxPrice) p.set('max_price', state.filters.maxPrice);
  if (state.filters.players) p.set('players', state.filters.players);
  p.set('sort', state.sort);
  p.set('page', state.page);
  p.set('per_page', 12);

  try {
    const { data: games, meta } = await get(`${API.games}?${p}`);
    state.totalPages = meta.pages;
    $('#catalogCount').textContent = `${meta.total} ${plural(meta.total, 'игра', 'игры', 'игр')} найдено`;
    renderGames(games);
    renderPagination(meta);
  } catch (e) {
    grid.innerHTML = `<div class="empty-state"><div class="icon">⚠️</div><p>${e.message}</p></div>`;
  }
}

function renderGames(games) {
  const grid = $('#gamesGrid');
  if (!games.length) {
    grid.innerHTML = `<div class="empty-state">
      <div class="icon">🔍</div>
      <p>Ничего не найдено.<br>Попробуйте изменить фильтры.</p>
    </div>`;
    return;
  }

  grid.innerHTML = games.map(g => {
    const badgeHtml = g.badge === 'new' ? `<span class="badge badge-new">Новинка</span>` :
      g.badge === 'sale' ? `<span class="badge badge-sale">Скидка</span>` : '';
    const oldHtml = g.old_price ? `<span class="old-price">${fmt(g.old_price)}</span>` : '';
    const playersStr = g.players_min === g.players_max
      ? `${g.players_min}`
      : `${g.players_min}–${g.players_max}`;
    const inCart = state.cartQty > 0; // упрощённо; для точности — проверять по id

    return `<article class="game-card" data-id="${g.id}">
      <div class="game-emoji">${g.emoji}</div>
      <div class="game-body">
        <div class="game-top">
          <span class="game-cat">${g.category_name}</span>
          ${badgeHtml}
        </div>
        <div class="game-name">${g.name}</div>
        <div class="game-meta">
          <span><i class="ti ti-users" aria-hidden="true"></i> ${playersStr}</span>
          <span><i class="ti ti-clock" aria-hidden="true"></i> ${g.play_time} мин</span>
        </div>
        <div class="game-rating">
          ${stars(g.rating)}
          <span class="score">${g.rating}</span>
        </div>
        <div class="game-footer">
          <div class="price-group">
            ${oldHtml}
            <span class="game-price">${fmt(g.price)}</span>
          </div>
          <button class="add-btn" data-id="${g.id}" data-name="${g.name}" aria-label="В корзину">
            <i class="ti ti-plus" aria-hidden="true"></i>
          </button>
        </div>
      </div>
    </article>`;
  }).join('');

  // Клики по кнопкам «В корзину»
  $$('.add-btn', grid).forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      addToCart(+btn.dataset.id, btn.dataset.name);
    });
  });
}

function renderPagination(meta) {
  const pg = $('#pagination');
  if (meta.pages <= 1) { pg.innerHTML = ''; return; }

  let html = `<button class="page-btn" data-page="${meta.page - 1}" ${meta.page <= 1 ? 'disabled' : ''}>
    <i class="ti ti-chevron-left"></i></button>`;

  for (let i = 1; i <= meta.pages; i++) {
    if (meta.pages > 7 && Math.abs(i - meta.page) > 2 && i !== 1 && i !== meta.pages) {
      if (i === meta.page - 3 || i === meta.page + 3) html += `<span style="padding:0 4px;color:var(--muted)">…</span>`;
      continue;
    }
    html += `<button class="page-btn ${i === meta.page ? 'active' : ''}" data-page="${i}">${i}</button>`;
  }

  html += `<button class="page-btn" data-page="${meta.page + 1}" ${meta.page >= meta.pages ? 'disabled' : ''}>
    <i class="ti ti-chevron-right"></i></button>`;

  pg.innerHTML = html;
  $$('.page-btn:not(:disabled)', pg).forEach(btn => {
    btn.addEventListener('click', () => {
      state.page = +btn.dataset.page;
      loadGames();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });
}

// ── Filters ────────────────────────────────────────────────────────────────────
function initFilters() {
  // Категории
  $$('.cat-filter').forEach(cb => {
    cb.addEventListener('change', () => {
      state.filters.cats = new Set($$('.cat-filter:checked').map(c => c.value));
      state.page = 1;
      loadGames();
    });
  });

  // Цена
  const priceRange = $('#priceRange');
  const priceVal = $('#priceVal');
  priceRange.addEventListener('input', () => {
    const v = +priceRange.value;
    state.filters.maxPrice = v;
    priceVal.textContent = v.toLocaleString('ru-RU');
    state.page = 1;
    loadGames();
  });

  // Количество игроков
  $$('.players-filter').forEach(cb => {
    cb.addEventListener('change', () => {
      const checked = $$('.players-filter:checked');
      state.filters.players = checked.length ? Math.min(...checked.map(c => +c.value)) : 0;
      state.page = 1;
      loadGames();
    });
  });

  // Сортировка
  $('#sortSelect').addEventListener('change', e => {
    state.sort = e.target.value;
    state.page = 1;
    loadGames();
  });

  // Поиск с debounce
  let searchTimer;
  $('#searchInput').addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.filters.search = e.target.value.trim();
      state.page = 1;
      loadGames();
    }, 280);
  });
}

// ── Cart ───────────────────────────────────────────────────────────────────────
async function loadCart() {
  try {
    const { data } = await get(API.cart);
    renderCart(data);
    state.cartQty = data.qty;
    $('#cartCount').textContent = data.qty;
  } catch (e) {
    console.error('Ошибка корзины:', e);
  }
}

async function addToCart(gameId, name) {
  try {
    const { data } = await post(API.cart, { action: 'add', game_id: gameId, qty: 1 });
    state.cartQty = data.qty;
    $('#cartCount').textContent = data.qty;
    renderCart(data);
    toast(`«${name}» добавлена в корзину`, 'success');
  } catch (e) {
    toast(e.message, 'error');
  }
}

async function updateCart(gameId, qty) {
  try {
    const { data } = await post(API.cart, { action: 'update', game_id: gameId, qty });
    state.cartQty = data.qty;
    $('#cartCount').textContent = data.qty;
    renderCart(data);
  } catch (e) {
    toast(e.message, 'error');
  }
}

async function removeFromCart(gameId) {
  try {
    const { data } = await post(API.cart, { action: 'remove', game_id: gameId });
    state.cartQty = data.qty;
    $('#cartCount').textContent = data.qty;
    renderCart(data);
  } catch (e) {
    toast(e.message, 'error');
  }
}

function renderCart(data) {
  const itemsEl = $('#cartItems');
  const footEl = $('#cartFoot');

  if (!data.items.length) {
    itemsEl.innerHTML = `<div class="cart-empty"><div class="icon">🛒</div><p>Корзина пуста</p></div>`;
    footEl.style.display = 'none';
    return;
  }

  itemsEl.innerHTML = data.items.map(item => `
    <div class="cart-item" data-id="${item.game_id}">
      <div class="cart-item-emoji">${item.emoji}</div>
      <div class="cart-item-info">
        <div class="cart-item-name">${item.name}</div>
        <div class="cart-item-price">${fmt(item.price)} / шт.</div>
        <div class="qty-row">
          <button class="qty-btn" data-action="dec" data-id="${item.game_id}" aria-label="Меньше">−</button>
          <span class="qty-num">${item.qty}</span>
          <button class="qty-btn" data-action="inc" data-id="${item.game_id}" aria-label="Больше">+</button>
          <button class="remove-btn" data-id="${item.game_id}" aria-label="Удалить">
            <i class="ti ti-trash" aria-hidden="true"></i>
          </button>
        </div>
      </div>
    </div>
  `).join('');

  $('#cartQtyTotal').textContent = data.qty + ' шт.';
  $('#cartTotal').textContent = fmt(data.total);
  footEl.style.display = 'block';

  // Обработчики
  $$('.qty-btn', itemsEl).forEach(btn => {
    btn.addEventListener('click', () => {
      const id = +btn.dataset.id;
      const row = btn.closest('.cart-item');
      const cur = +$('.qty-num', row).textContent;
      updateCart(id, btn.dataset.action === 'inc' ? cur + 1 : cur - 1);
    });
  });
  $$('.remove-btn', itemsEl).forEach(btn => {
    btn.addEventListener('click', () => removeFromCart(+btn.dataset.id));
  });
}

// ── Checkout ───────────────────────────────────────────────────────────────────
function initCheckout() {
  const modal = $('#checkoutModal');
  const form = $('#checkoutForm');
  const openBtn = $('#checkoutBtn');
  const closeBtn = $('#closeCheckout');

  openBtn.addEventListener('click', () => {
    modal.classList.add('open');
    closeCart();
  });
  closeBtn.addEventListener('click', () => modal.classList.remove('open'));
  modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const submitBtn = $('[type="submit"]', form);
    submitBtn.disabled = true;
    submitBtn.textContent = 'Оформляем…';

    try {
      const body = {
        name: $('#fieldName').value.trim(),
        email: $('#fieldEmail').value.trim(),
        phone: $('#fieldPhone').value.trim(),
        note: $('#fieldNote').value.trim(),
      };
      const { data } = await post(API.orders, body);

      // Показываем успех
      form.innerHTML = `<div class="order-success">
        <div class="check-icon"><i class="ti ti-check" aria-hidden="true"></i></div>
        <h3>Заказ #${data.order_id} оформлен!</h3>
        <p>Сумма: ${fmt(data.total_price)}<br>Мы свяжемся с вами для подтверждения.</p>
      </div>`;

      await loadCart();
      state.cartQty = 0;
      $('#cartCount').textContent = 0;

      setTimeout(() => modal.classList.remove('open'), 3500);
    } catch (err) {
      toast(err.message, 'error');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Подтвердить заказ';
    }
  });
}

// ── Cart panel open/close ──────────────────────────────────────────────────────
function openCart() {
  $('#cartBackdrop').classList.add('open');
  loadCart();
}
function closeCart() { $('#cartBackdrop').classList.remove('open'); }

function initCartUI() {
  $('#cartBtn').addEventListener('click', openCart);
  $('#closeCart').addEventListener('click', closeCart);
  $('#cartBackdrop').addEventListener('click', e => {
    if (e.target === $('#cartBackdrop')) closeCart();
  });
}

// ── Util ───────────────────────────────────────────────────────────────────────
function plural(n, one, few, many) {
  const mod10 = n % 10;
  const mod100 = n % 100;
  if (mod10 === 1 && mod100 !== 11) return `${n} ${one}`;
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return `${n} ${few}`;
  return `${n} ${many}`;
}

// ── Bootstrap ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initFilters();
  initCartUI();
  initCheckout();
  loadGames();
  loadCart();
});
