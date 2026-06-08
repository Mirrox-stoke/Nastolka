<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

session_init();
$user = current_user();

// Если не авторизован - переводим на home
if (!$user) {
    header('Location: home.php');
    exit;
}

// Получаем статистику
try {
    // Количество заказов
    $stmt = db()->prepare('
        SELECT COUNT(*) as count FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE c.email = ?
    ');
    $stmt->execute([$user['email']]);
    $stats = $stmt->fetch();
    $orders_count = $stats['count'] ?? 0;
} catch (Exception) {
    $orders_count = 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Мой профиль - Настолка</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ══ Header ══════════════════════════════════════════════════════════════════ -->
<header class="site-header">
  <div class="container">
    <div class="header-inner">
      <a href="home.php" class="logo">Настолка</a>
      
      <div class="search-box">
        <input type="text" placeholder="Поиск игры...">
      </div>

      <div class="header-actions">
        <button class="btn-icon" title="Корзина">
          🛒
          <span class="badge-count">0</span>
        </button>

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
      </div>
    </div>
  </div>
</header>

<!-- ══ Main ════════════════════════════════════════════════════════════════════ -->
<div class="main-content">
  <div class="container">
    <!-- Profile Header -->
    <div class="profile-header">
      <div class="profile-avatar"><?php echo substr($user['username'], 0, 1); ?></div>
      <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
      <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
      
      <div class="profile-stats">
        <div class="stat">
          <div class="stat-value"><?php echo $orders_count; ?></div>
          <div class="stat-label">Заказов</div>
        </div>
        <div class="stat">
          <div class="stat-value">⭐</div>
          <div class="stat-label">Постоянный клиент</div>
        </div>
        <div class="stat">
          <div class="stat-value">🎮</div>
          <div class="stat-label">Любитель игр</div>
        </div>
      </div>
    </div>

    <!-- Profile Content -->
    <div class="profile-content">
      <!-- Информация об аккаунте -->
      <div class="info-box">
        <h3>📋 Информация об аккаунте</h3>
        <div class="info-item">
          <span class="info-label">Имя пользователя</span>
          <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Email</span>
          <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Статус</span>
          <span class="info-value">
            <span class="badge new">Активный</span>
          </span>
        </div>
        <div class="info-item">
          <span class="info-label">Дата регистрации</span>
          <span class="info-value"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
        </div>
      </div>

      <!-- Статистика -->
      <div class="info-box">
        <h3>📊 Статистика</h3>
        <div class="info-item">
          <span class="info-label">Всего заказов</span>
          <span class="info-value"><?php echo $orders_count; ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Последний заказ</span>
          <span class="info-value">—</span>
        </div>
        <div class="info-item">
          <span class="info-label">Премиум статус</span>
          <span class="info-value">Нет</span>
        </div>
        <div class="info-item">
          <span class="info-label">Скидка по программе</span>
          <span class="info-value">0%</span>
        </div>
      </div>

      <!-- Быстрые действия -->
      <div class="info-box">
        <h3>⚙️ Действия</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
          <a href="index.php" class="btn-primary" style="text-align: center;">🛍️ Продолжить покупки</a>
          <button class="btn-secondary" onclick="alert('Функция в разработке')" style="width: 100%; text-align: center;">🔑 Изменить пароль</button>
          <button id="logoutBtn2" class="btn-logout">🚪 Выйти из аккаунта</button>
        </div>
      </div>
    </div>
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

<!-- ══ Toast ═══════════════════════════════════════════════════════════════════ -->
<div class="toast" id="toast"></div>

<!-- ══ Scripts ═════════════════════════════════════════════════════════════════ -->
<script>
// Меню пользователя
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
const logoutBtn = document.getElementById('logoutBtn');
const logoutBtn2 = document.getElementById('logoutBtn2');

userMenuBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  userDropdown.classList.toggle('active');
});

document.addEventListener('click', () => {
  userDropdown.classList.remove('active');
});

userDropdown.addEventListener('click', (e) => {
  e.stopPropagation();
});

function logout() {
  const formData = new FormData();
  formData.append('action', 'logout');
  
  fetch('api/auth.php', {
    method: 'POST',
    body: formData
  }).then(() => {
    location.href = 'home.php';
  });
}

if (logoutBtn) {
  logoutBtn.addEventListener('click', logout);
}

if (logoutBtn2) {
  logoutBtn2.addEventListener('click', logout);
}
</script>
</body>
</html>
