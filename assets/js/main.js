// ════════════════════════════════════════════════════════════════════════════
// SIMPLE CART (localStorage based)
// ════════════════════════════════════════════════════════════════════════════

function getCart() {
  try {
    return JSON.parse(localStorage.getItem('cart')) || [];
  } catch {
    return [];
  }
}

function saveCart(cart) {
  localStorage.setItem('cart', JSON.stringify(cart));
  updateCartCount();
}

function addToCart(gameId, gameName, gamePrice) {
  const cart = getCart();
  const item = cart.find(i => i.id === gameId);
  
  if (item) {
    item.quantity += 1;
  } else {
    cart.push({ id: gameId, name: gameName, price: gamePrice, quantity: 1 });
  }
  
  saveCart(cart);
  showToast(`"${gameName}" добавлена в корзину! ✅`, 'success');
}

function removeFromCart(gameId) {
  const cart = getCart().filter(i => i.id !== gameId);
  saveCart(cart);
}

function updateCartCount() {
  const cart = getCart();
  const total = cart.reduce((sum, item) => sum + item.quantity, 0);
  const countEl = document.getElementById('cartCount');
  if (countEl) {
    countEl.textContent = total;
    countEl.style.display = total > 0 ? 'flex' : 'none';
  }
}

// Инициализировать корзину при загрузке
window.addEventListener('DOMContentLoaded', () => {
  updateCartCount();
});

// ════════════════════════════════════════════════════════════════════════════
// AUTH MODAL & FORMS
// ════════════════════════════════════════════════════════════════════════════

const authModal = document.getElementById('authModal');
const loginBtn = document.getElementById('loginBtn');
const closeAuthModal = document.getElementById('closeAuthModal');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const modalTabs = document.querySelectorAll('.modal-tab');

// Открыть модаль
if (loginBtn) {
  loginBtn.addEventListener('click', () => {
    authModal.classList.add('active');
  });
}

// Закрыть модаль
if (closeAuthModal) {
  closeAuthModal.addEventListener('click', () => {
    authModal.classList.remove('active');
  });
}

// Закрыть при клике на фон
if (authModal) {
  authModal.addEventListener('click', (e) => {
    if (e.target === authModal) {
      authModal.classList.remove('active');
    }
  });
}

// Переключение табов
modalTabs.forEach(tab => {
  tab.addEventListener('click', () => {
    const tabName = tab.dataset.tab;
    
    // Обновляем табы
    document.querySelectorAll('.modal-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    
    // Обновляем контент
    document.querySelectorAll('.modal-content').forEach(c => c.classList.remove('active'));
    document.getElementById(tabName + 'Content').classList.add('active');
    
    // Очищаем ошибки
    const errorDiv = document.getElementById(tabName + 'Error');
    if (errorDiv) {
      errorDiv.textContent = '';
      errorDiv.classList.remove('show');
    }
  });
});

// Вход
if (loginForm) {
  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const errorDiv = document.getElementById('loginError');
    const submitBtn = loginForm.querySelector('[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Вход...';
    
    try {
      const formData = new FormData();
      formData.append('action', 'login');
      formData.append('email', email);
      formData.append('password', password);
      
      const response = await fetch('login.php', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.ok) {
        showToast('Успешный вход!', 'success');
        authModal.classList.remove('active');
        setTimeout(() => location.reload(), 1000);
      } else {
        errorDiv.textContent = data.error || 'Ошибка при входе';
        errorDiv.classList.add('show');
      }
    } catch (err) {
      errorDiv.textContent = 'Ошибка подключения';
      errorDiv.classList.add('show');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Войти';
    }
  });
}

// Регистрация
if (registerForm) {
  registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const username = document.getElementById('regUsername').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPassword').value;
    const password2 = document.getElementById('regPassword2').value;
    const errorDiv = document.getElementById('registerError');
    const submitBtn = registerForm.querySelector('[type="submit"]');
    
    if (password !== password2) {
      errorDiv.textContent = 'Пароли не совпадают';
      errorDiv.classList.add('show');
      return;
    }
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Регистрация...';
    
    try {
      const formData = new FormData();
      formData.append('action', 'register');
      formData.append('email', email);
      formData.append('password', password);
      formData.append('username', username);
      
      const response = await fetch('register.php', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.ok) {
        showToast('Регистрация успешна! Теперь авторизуйтесь', 'success');
        registerForm.reset();
        document.querySelector('[data-tab="login"]').click();
      } else {
        errorDiv.textContent = data.error || 'Ошибка при регистрации';
        errorDiv.classList.add('show');
      }
    } catch (err) {
      errorDiv.textContent = 'Ошибка подключения';
      errorDiv.classList.add('show');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Зарегистрироваться';
    }
  });
}

// ════════════════════════════════════════════════════════════════════════════
// USER MENU
// ════════════════════════════════════════════════════════════════════════════

const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
const logoutBtn = document.getElementById('logoutBtn');

if (userMenuBtn && userDropdown) {
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
}

if (logoutBtn) {
  logoutBtn.addEventListener('click', async (e) => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', 'logout');
    await fetch('logout.php', { method: 'POST', body: formData });
    location.reload();
  });
}

// ════════════════════════════════════════════════════════════════════════════
// FILTERS
// ════════════════════════════════════════════════════════════════════════════

const categoryFilter = document.getElementById('categoryFilter');
const sortFilter = document.getElementById('sortFilter');

if (categoryFilter) {
  categoryFilter.addEventListener('change', (e) => {
    const sort = new URLSearchParams(window.location.search).get('sort') || 'name';
    window.location.href = `?category=${e.target.value}&sort=${sort}`;
  });
}

if (sortFilter) {
  sortFilter.addEventListener('change', (e) => {
    const category = new URLSearchParams(window.location.search).get('category') || '';
    window.location.href = `?category=${category}&sort=${e.target.value}`;
  });
}

// ════════════════════════════════════════════════════════════════════════════
// TOAST NOTIFICATION
// ════════════════════════════════════════════════════════════════════════════

function showToast(message, type = 'info') {
  const toast = document.getElementById('toast');
  if (!toast) return;
  
  toast.textContent = message;
  toast.className = `toast ${type} show`;
  
  setTimeout(() => {
    toast.classList.remove('show');
  }, 3000);
}
