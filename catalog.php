<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

session_init();
$user = current_user();

// Получаем игры с фильтрацией
try {
    $page = (int)($_GET['page'] ?? 1);
    $page = max(1, $page);
    $per_page = 12;
    $offset = ($page - 1) * $per_page;

    $query = 'SELECT * FROM games';
    $params = [];

    // Фильтр по категориям
    $categories = explode(',', $_GET['category'] ?? '');
    $categories = array_filter($categories);
    
    if (!empty($categories)) {
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $query .= " WHERE category_id IN (SELECT id FROM categories WHERE slug IN ($placeholders))";
        $params = $categories;
    }

    // Сортировка
    $sort = $_GET['sort'] ?? 'name';
    $order_by = match($sort) {
        'price_asc' => 'price ASC',
        'price_desc' => 'price DESC',
        'rating' => 'rating DESC',
        'new' => 'created_at DESC',
        default => 'name ASC'
    };

    $query .= " ORDER BY $order_by LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;

    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $games = $stmt->fetchAll();

    // Получаем количество всего
    $count_query = 'SELECT COUNT(*) as count FROM games';
    $count_params = [];
    if (!empty($categories)) {
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $count_query .= " WHERE category_id IN (SELECT id FROM categories WHERE slug IN ($placeholders))";
        $count_params = $categories;
    }

    $stmt = db()->prepare($count_query);
    $stmt->execute($count_params);
    $total = $stmt->fetch()['count'];
    $total_pages = ceil($total / $per_page);

    // Получаем категории
    $stmt = db()->query('SELECT slug, name FROM categories ORDER BY id');
    $all_categories = $stmt->fetchAll();

} catch (Exception $e) {
    $games = [];
    $total_pages = 1;
    $page = 1;
    $all_categories = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Каталог - Настолка</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ══ Header ══════════════════════════════════════════════════════════════════ -->
<header class="site-header">
  <div class="container">
    <div class="header-inner">
      <a href="home.php" class="logo">Настолка</a>
      
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Поиск игры...">
      </div>

      <div class="header-actions">
        <button class="btn-icon" id="cartBtn" title="Корзина">
          🛒
          <span class="badge-count" id="cartCount">0</span>
        </button>

        <?php if ($user): ?>
          <div class="user-menu">
            <button class="user-btn" id="userMenuBtn">
              <div class="user-avatar"><?php echo substr($user['username'], 0, 1); ?></div>
              <span><?php echo htmlspecialchars($user['username']); ?></span>
            </button>
            <div class="dropdown-menu" id="userDropdown">
              <a href="profile.php" class="dropdown-item">👤 Мой профиль</a>
              <a href="#" class="dropdown-item">📦 Мои заказы</a>
              <hr class="dropdown-divider">
              <button id="logoutBtn" class="dropdown-item" style="background: none; border: none; width: 100%; text-align: left;">🚪 Выход</button>
            </div>
          </div>
        <?php else: ?>
          <button class="btn-icon" id="loginBtn" title="Вход">👤</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<!-- ══ Main ════════════════════════════════════════════════════════════════════ -->
<div class="main-content">
  <div class="container">
    <section class="section">
      <h2 class="section-title">Каталог игр</h2>

      <!-- Фильтры -->
      <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 30px;">
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
          <div style="flex: 1; min-width: 150px;">
            <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px; font-weight: 500;">Категория</label>
            <select id="categoryFilter" style="width: 100%; padding: 10px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-size: 14px; cursor: pointer;">
              <option value="">Все категории</option>
              <?php foreach ($all_categories as $cat): ?>
              <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo ($cat['slug'] == (explode(',', $_GET['category'] ?? '')[0] ?? '')) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="flex: 1; min-width: 150px;">
            <label style="display: block; margin-bottom: 8px; color: var(--text-secondary); font-size: 14px; font-weight: 500;">Сортировка</label>
            <select id="sortFilter" style="width: 100%; padding: 10px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-size: 14px; cursor: pointer;">
              <option value="name" <?php echo $_GET['sort'] == 'name' ? 'selected' : ''; ?>>По названию</option>
              <option value="price_asc" <?php echo $_GET['sort'] == 'price_asc' ? 'selected' : ''; ?>>Цена: дешевле</option>
              <option value="price_desc" <?php echo $_GET['sort'] == 'price_desc' ? 'selected' : ''; ?>>Цена: дороже</option>
              <option value="rating" <?php echo $_GET['sort'] == 'rating' ? 'selected' : ''; ?>>По рейтингу</option>
              <option value="new" <?php echo $_GET['sort'] == 'new' ? 'selected' : ''; ?>>Новинки</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Игры -->
      <div class="grid">
        <?php if (!empty($games)): ?>
          <?php foreach ($games as $game): ?>
          <div class="card">
            <div class="card-header">
              <div class="card-emoji"><?php echo htmlspecialchars($game['emoji']); ?></div>
              <h3 class="card-title"><?php echo htmlspecialchars($game['name']); ?></h3>
              <?php if ($game['badge'] === 'new'): ?>
                <span class="badge new">Новинка</span>
              <?php elseif ($game['badge'] === 'sale'): ?>
                <span class="badge sale">Скидка</span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <p class="card-text" style="margin-bottom: 15px;"><?php echo substr($game['description'], 0, 80) . '...'; ?></p>
              <div style="color: var(--text-secondary); font-size: 13px; margin: 12px 0;">
                <div>⭐ <?php echo number_format($game['rating'], 1); ?></div>
                <div>👥 <?php echo $game['players_min']; ?>-<?php echo $game['players_max']; ?> игроков</div>
                <div>⏱️ <?php echo $game['play_time']; ?> мин</div>
              </div>
              <div style="font-size: 20px; font-weight: 700; color: var(--primary); margin-top: 12px;">
                <?php echo number_format($game['price']); ?> ₽
              </div>
              <button class="btn-primary" style="width: 100%; margin-top: 12px; padding: 10px; font-size: 14px;" onclick="addToCart(<?php echo $game['id']; ?>, '<?php echo htmlspecialchars($game['name']); ?>', <?php echo $game['price']; ?>)">
                🛒 Добавить в корзину
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px; color: var(--text-secondary);">
            <div style="font-size: 56px; margin-bottom: 20px;">🔍</div>
            <h3 style="font-size: 20px; margin-bottom: 8px; color: var(--text);">Игры не найдены</h3>
            <p style="font-size: 14px;">Попробуйте изменить фильтры или поиск</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Пагинация -->
      <?php if ($total_pages > 1): ?>
      <div style="display: flex; justify-content: center; gap: 8px; margin-top: 40px; flex-wrap: wrap;">
        <?php if ($page > 1): ?>
          <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $_GET['sort'] ?? 'name'; ?>&category=<?php echo $_GET['category'] ?? ''; ?>" 
             style="padding: 10px 14px; border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-weight: 500; transition: all 0.3s;">← Назад</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?php echo $i; ?>&sort=<?php echo $_GET['sort'] ?? 'name'; ?>&category=<?php echo $_GET['category'] ?? ''; ?>"
             style="padding: 10px 14px; border: 1px solid <?php echo $i === $page ? 'var(--primary)' : 'var(--border)'; ?>; border-radius: 6px; background: <?php echo $i === $page ? 'var(--primary)' : 'transparent'; ?>; color: var(--text); font-weight: 500; transition: all 0.3s;">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
          <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $_GET['sort'] ?? 'name'; ?>&category=<?php echo $_GET['category'] ?? ''; ?>"
             style="padding: 10px 14px; border: 1px solid var(--border); border-radius: 6px; color: var(--text); font-weight: 500; transition: all 0.3s;">Вперед →</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </section>
  </div>
</div>

<!-- ══ Footer ══════════════════════════════════════════════════════════════════ -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-content">
      <p>© 2024 Настолка - Магазин настольных игр</p>
    </div>
  </div>
</footer>

<!-- ══ Auth Modal ═══════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="authModal">
  <div class="modal">
    <div class="modal-header">
      <h2>Вход или Регистрация</h2>
      <button class="modal-close" id="closeAuthModal">✕</button>
    </div>

    <div class="modal-tabs">
      <button class="modal-tab active" data-tab="login">Вход</button>
      <button class="modal-tab" data-tab="register">Регистрация</button>
    </div>

    <!-- Вход -->
    <div class="modal-content active" id="loginContent">
      <form id="loginForm">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" id="loginEmail" class="form-input" placeholder="your@email.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Пароль</label>
          <input type="password" id="loginPassword" class="form-input" placeholder="••••••" required>
        </div>
        <div class="form-error" id="loginError"></div>
        <button type="submit" class="form-submit">Войти</button>
      </form>
    </div>

    <!-- Регистрация -->
    <div class="modal-content" id="registerContent">
      <form id="registerForm">
        <div class="form-group">
          <label class="form-label">Имя пользователя</label>
          <input type="text" id="regUsername" class="form-input" placeholder="Иван" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" id="regEmail" class="form-input" placeholder="your@email.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Пароль</label>
          <input type="password" id="regPassword" class="form-input" placeholder="••••••" required>
        </div>
        <div class="form-group">
          <label class="form-label">Подтвердить пароль</label>
          <input type="password" id="regPassword2" class="form-input" placeholder="••••••" required>
        </div>
        <div class="form-error" id="registerError"></div>
        <button type="submit" class="form-submit">Зарегистрироваться</button>
      </form>
    </div>
  </div>
</div>

<!-- ══ Toast ═══════════════════════════════════════════════════════════════════ -->
<div class="toast" id="toast"></div>

<!-- ══ Scripts ═════════════════════════════════════════════════════════════════ -->
<script>
// Фильтры
document.getElementById('categoryFilter').addEventListener('change', (e) => {
  const sort = new URLSearchParams(window.location.search).get('sort') || 'name';
  window.location.href = `?category=${e.target.value}&sort=${sort}`;
});

document.getElementById('sortFilter').addEventListener('change', (e) => {
  const category = new URLSearchParams(window.location.search).get('category') || '';
  window.location.href = `?category=${category}&sort=${e.target.value}`;
});

// Меню пользователя
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
const logoutBtn = document.getElementById('logoutBtn');
const loginBtn = document.getElementById('loginBtn');
const authModal = document.getElementById('authModal');
const closeAuthModal = document.getElementById('closeAuthModal');

if (userMenuBtn) {
  userMenuBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    userDropdown.classList.toggle('active');
  });
}

document.addEventListener('click', () => {
  if (userDropdown) userDropdown.classList.remove('active');
});

if (userDropdown) {
  userDropdown.addEventListener('click', (e) => {
    e.stopPropagation();
  });
}

if (logoutBtn) {
  logoutBtn.addEventListener('click', async (e) => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', 'logout');
    await fetch('api/auth.php', { method: 'POST', body: formData });
    location.reload();
  });
}

if (loginBtn) {
  loginBtn.addEventListener('click', () => {
    authModal.classList.add('active');
  });
}

if (closeAuthModal) {
  closeAuthModal.addEventListener('click', () => {
    authModal.classList.remove('active');
  });
}

authModal.addEventListener('click', (e) => {
  if (e.target === authModal) {
    authModal.classList.remove('active');
  }
});

// Табы
document.querySelectorAll('.modal-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    const tabName = tab.dataset.tab;
    document.querySelectorAll('.modal-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.querySelectorAll('.modal-content').forEach(c => c.classList.remove('active'));
    document.getElementById(tabName + 'Content').classList.add('active');
  });
});
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
