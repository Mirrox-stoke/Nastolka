<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

session_init();
$user = current_user();

// Получаем популярные игры
try {
    $stmt = db()->prepare('SELECT id, name, emoji, rating, price, badge, players_min, players_max FROM games ORDER BY rating DESC LIMIT 6');
    $stmt->execute();
    $featured_games = $stmt->fetchAll();
} catch (Exception) {
    $featured_games = [];
}

// Получаем категории
try {
    $stmt = db()->query('SELECT slug, name FROM categories ORDER BY id');
    $categories = $stmt->fetchAll();
} catch (Exception) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Настолка — магазин настольных игр</title>
  <meta name="description" content="Купить настольные игры с доставкой по России.">
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

<!-- ══ Hero ════════════════════════════════════════════════════════════════════ -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <h1>Добро пожаловать на Настолку!</h1>
      <p>Огромный выбор настольных игр для всей семьи и друзей</p>
      <a href="catalog.php" class="btn-primary">Перейти в каталог</a>
    </div>
  </div>
</section>

<!-- ══ Features ════════════════════════════════════════════════════════════════ -->
<div class="main-content">
  <div class="container">
    <section class="section">
      <h2 class="section-title">Почему выбирают Настолку?</h2>
      <div class="grid">
        <div class="card">
          <div class="card-header">
            <div class="card-emoji">🎲</div>
            <h3 class="card-title">Широкий выбор</h3>
          </div>
          <div class="card-body">
            <p class="card-text">Более 100 игр разных жанров: стратегии, карточные, кооперативы и многое другое.</p>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-emoji">📦</div>
            <h3 class="card-title">Быстрая доставка</h3>
          </div>
          <div class="card-body">
            <p class="card-text">Доставим заказ за 2-3 дня по всей России. Игры доезжают в идеальном состоянии.</p>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-emoji">💰</div>
            <h3 class="card-title">Справедливые цены</h3>
          </div>
          <div class="card-body">
            <p class="card-text">Конкурентные цены и регулярные скидки. Ищите выгодные предложения.</p>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-emoji">⭐</div>
            <h3 class="card-title">Честные отзывы</h3>
          </div>
          <div class="card-body">
            <p class="card-text">Читайте отзывы реальных игроков и выбирайте на основе рейтингов.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section class="section">
      <h2 class="section-title">Категории</h2>
      <div class="grid">
        <?php foreach ($categories as $cat): ?>
        <a href="catalog.php?category=<?php echo htmlspecialchars($cat['slug']); ?>" class="card" style="cursor: pointer; text-decoration: none;">
          <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars($cat['name']); ?></h3>
          </div>
          <div class="card-body">
            <p class="card-text">Перейти в категорию →</p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Popular Games -->
    <section class="section">
      <h2 class="section-title">Популярные игры</h2>
      <div class="grid">
        <?php foreach ($featured_games as $game): ?>
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
            <div style="margin: 12px 0; color: var(--text-secondary);">
              <div>⭐ <?php echo number_format($game['rating'], 1); ?></div>
              <div>👥 <?php echo $game['players_min']; ?>-<?php echo $game['players_max']; ?> игроков</div>
              <div style="font-size: 20px; font-weight: 700; color: var(--primary); margin-top: 8px;">
                <?php echo number_format($game['price']); ?> ₽
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>

<!-- ══ Footer ══════════════════════════════════════════════════════════════════ -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-content">
      <p>© 2024 Настолка - Магазин настольных игр</p>
      <p>Для веселья, развития и общения с друзьями и семьей</p>
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
<script src="assets/js/main.js"></script>
</body>
</html>
