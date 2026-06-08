
CREATE DATABASE IF NOT EXISTS nastolka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nastolka;

CREATE TABLE categories (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug    VARCHAR(60)  NOT NULL UNIQUE,
    name    VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (slug, name) VALUES
    ('strategy',  'Стратегия'),
    ('card',      'Карточные'),
    ('coop',      'Кооператив'),
    ('family',    'Семейные'),
    ('rpg',       'Ролевые'),
    ('party',     'Вечеринка');

CREATE TABLE games (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id  INT UNSIGNED NOT NULL,
    name         VARCHAR(200) NOT NULL,
    description  TEXT,
    emoji        VARCHAR(10)  NOT NULL DEFAULT '🎲',
    players_min  TINYINT UNSIGNED NOT NULL DEFAULT 2,
    players_max  TINYINT UNSIGNED NOT NULL DEFAULT 6,
    play_time    SMALLINT UNSIGNED NOT NULL DEFAULT 60  COMMENT 'минуты',
    rating       DECIMAL(3,1) NOT NULL DEFAULT 4.0,
    price        INT UNSIGNED NOT NULL               COMMENT 'копейки → нет; просто рубли',
    old_price    INT UNSIGNED NULL                   COMMENT 'NULL если скидки нет',
    badge        ENUM('','new','sale') NOT NULL DEFAULT '',
    stock        SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_games_cat FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO games (category_id, name, description, emoji, players_min, players_max, play_time, rating, price, old_price, badge, stock) VALUES
    (1, 'Колонизаторы',    'Классическая стратегия: стройте дороги, города и торгуйте ресурсами на острове Катан.', '🏝️', 3, 4, 90,  4.7, 2490, NULL,  '',     15),
    (1, 'Ticket to Ride',  'Прокладывайте железнодорожные маршруты по всей Европе раньше соперников.',             '🚂', 2, 5, 75,  4.6, 3290, NULL,  'new',  8),
    (2, 'Манчкин',         'Убивай монстров, бери сокровища, мешай друзьям — весёлая карточная ролевая пародия.',  '⚔️', 3, 6, 120, 4.4, 1490, NULL,  '',     20),
    (3, 'Pandemic',        'Команда учёных спасает мир от четырёх смертельных болезней. Кооператив.',               '🦠', 2, 4, 60,  4.8, 2790, NULL,  '',     12),
    (4, 'Диксит',          'Ассоциации и иллюстрации: угадай карту рассказчика среди похожих.',                    '🎨', 3, 6, 45,  4.5, 1890, 2490,  'sale', 18),
    (2, 'Dominion',        'Строй колоду из карт королевства и набирай победные очки быстрее соперника.',          '👑', 2, 4, 30,  4.5, 2290, NULL,  '',     10),
    (5, 'Ужас Аркхэма',   'Хаос, безумие и древние боги: детективное приключение по Лавкрафту.',                  '🐙', 2, 8, 180, 4.9, 4990, NULL,  'new',  5),
    (6, 'Кодовые имена',   'Два капитана подают слова-подсказки своим командам. Быстро, остро, весело.',           '🕵️', 4, 8, 20,  4.7, 990,  NULL,  '',     25),
    (1, 'Агрикола',        'Управляй фермерским хозяйством: сей, разводи скот, корми семью.',                      '🌾', 1, 5, 120, 4.8, 3890, NULL,  '',     7),
    (1, '7 Чудес',         'Возводи одно из семи чудес света, развивай науку и военную мощь.',                     '🏛️', 3, 7, 40,  4.6, 2690, 3290,  'sale', 9),
    (4, 'Имаджинариум',    'Русская версия Диксита — ассоциации по авторским иллюстрациям.',                       '💭', 3, 6, 40,  4.3, 1490, NULL,  '',     22),
    (5, 'Войны Богов',     'Эпические сражения в мире скандинавской мифологии.',                                   '⚡', 2, 4, 90,  4.5, 3490, NULL,  'new',  6);

CREATE TABLE customers (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    email      VARCHAR(200) NOT NULL UNIQUE,
    phone      VARCHAR(30)  NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    status      ENUM('new','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'new',
    total_price INT UNSIGNED NOT NULL DEFAULT 0,
    note        TEXT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_cust FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    game_id    INT UNSIGNED NOT NULL,
    qty        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price INT UNSIGNED NOT NULL,
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_game  FOREIGN KEY (game_id)  REFERENCES games(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    game_id     INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NULL,
    author_name VARCHAR(100) NOT NULL,
    stars       TINYINT UNSIGNED NOT NULL CHECK (stars BETWEEN 1 AND 5),
    body        TEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rev_game FOREIGN KEY (game_id) REFERENCES games(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(200) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    username        VARCHAR(100) NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_games_cat      ON games (category_id);
CREATE INDEX idx_games_price    ON games (price);
CREATE INDEX idx_games_rating   ON games (rating);
CREATE INDEX idx_oi_order       ON order_items (order_id);
CREATE INDEX idx_orders_cust    ON orders (customer_id);
CREATE INDEX idx_reviews_game   ON reviews (game_id);
