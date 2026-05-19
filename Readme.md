# Porno Blog

Простенький блог на PHP 8.4. Под капотом: Docker, Smarty, SCSS и Doctrine (ORM + Миграции).

---

## 🚀 Быстрый старт

**1. Поднимаем контейнеры (PHP, Nginx, MySQL, Redis):**
```bash
docker compose up --build -d
```

**2. Накатываем зависимости:**
```bash
docker exec -it porno-php-1 composer install
```

---

## 🗄️ База данных и фикстуры

Команды можно запускать прямо с хоста — они сами прокинутся в контейнер.

* **Сгенерировать миграцию (diff):**
  ```bash
  vendor/bin/doctrine-migrations diff
  ```
* **Применить миграции:**
  ```bash
  vendor/bin/doctrine-migrations migrate
  ```
* **Залить тестовые данные (фикстуры):**
  ```bash
  composer db:fixtures
  ```

---

## 🎨 Фронтенд (SCSS)

Стили пишем в `style.scss`, а сборка летит в папку `build` (она в `.gitignore`).

**Запустить автосборку стилей (Watch):**
```bash
docker exec -it porno-php-1 sass --watch public/assets/css/style.scss:public/assets/build/style.css
```

---

## 🧪 Тесты

Пишем на **PHPUnit 9**, базу не трогаем — всё на моках.

**Как гонять тесты:**
1. Прыгаем в контейнер: `docker exec -it porno-php-1 bash`
2. Запускаем всё разом: `composer test`
3. Запустить один конкретный тест:
   ```bash
   ./vendor/bin/phpunit tests/Unit/UseCase/HomePage/HomePageIndexHandlerTest.php
   ```

> 🛠️ **Лайфхак:** Если при запуске тестов ругнётся Git (`fatal: detected dubious ownership`), просто выполни внутри контейнера:  
> `git config --global --add safe.directory /var/www`

---

## 🐳 Коротко про Docker

* **Потушить проект:** `docker compose down`
* **Что там по контейнерам:** `docker ps`
* **Посмотреть логи Nginx:** `docker compose logs nginx`
