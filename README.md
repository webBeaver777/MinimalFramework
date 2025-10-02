# Minimal ToDo: PHP REST API + Frontend
Минимальное ToDo-приложение на чистом PHP (без фреймворков) + фронтенд на HTML/JS. Всё готово для запуска через Docker или ---
 ## Быстрый старт
 ### Docker (рекомендуется)
 docker compose up -d –pull always xdg-open http://localhost:8080 || open http://localhost:8080
локально.
 ### Локально (PHP 8.1+)
 composer install php -S 127.0.0.1:8000 router.php xdg-open http://127.0.0.1:8000 || open http://127.0.0.1:8000 
 > Данные хранятся в `storage/*.json`. Права на запись настроены для Docker и локально.
 ---
 ## Cтек и структура
 - PHP 8.1+ (Docker: php:8.4-fpm)
- FastRoute — маршрутизация
- JSON-файлы — хранилище
- Frontend: один `index.html` на Bootstrap 5 (CDN), чистый JS
- Docker: nginx + php-fpm + composer
 **Ключевые файлы:**
- `api.php` — точка входа API
- `src/` — весь код
- `storage/` — файлы данных
- `router.php` — роутер для PHP-сервера
- `index.html` — фронтенд
- `docker-compose.yml`, `nginx.conf` — Docker-конфиг 
 ---
 ## API
 **Базовые URL:**
- Docker: `http://localhost:8080`
- Локально: `http://127.0.0.1:8000`
 **Заголовки:**
- `Content-Type: application/json`
- `Authorization: Bearer <token>`
 **Схема задачи**
 { “id”: 1, “title”: “string (<=255)”, “completed”: false, “created_at”: “ISO8601”, “updated_at”: “ISO8601?” } 
 ### Эндпоинты
 **Аутентификация:**
- `POST /auth/register`
- `POST /auth/login`
- `GET /me` (требует токен)
 **Задачи:**
- `GET /tasks`
- `POST /tasks`
- `PATCH /tasks/{id}`
- `DELETE /tasks/{id}`
### Примеры curl

Регистрация
curl -X POST http://localhost:8080/auth/register -H ‘Content-Type: application/json’ -d ‘{“email”:“user@example.com”,“password”:“secret123”}’
Вход
curl -X POST http://localhost:8080/auth/login -H ‘Content-Type: application/json’ -d ‘{“email”:“user@example.com”,“password”:“secret123”}’
Список задач
curl ‘http://localhost:8080/tasks?page=1&per_page=10’ -H ‘Authorization: Bearer TOKEN_HERE’
Создание задачи
curl -X POST http://localhost:8080/tasks -H ‘Content-Type: application/json’ -H ‘Authorization: Bearer TOKEN_HERE’ -d ‘{“title”:“First task”}’
Обновление
curl -X PATCH http://localhost:8080/tasks/1 -H ‘Content-Type: application/json’ -H ‘Authorization: Bearer TOKEN_HERE’ -d ‘{“completed”: true}’ 
 ---
 ## Frontend
 - Один `index.html`
- Bootstrap 5 (CDN), JS — никаких сборщиков
- Возможности: регистрация/логин, хранение токена, задачи CRUD, уведомления
 **Запуск:**
  Открой базовый URL (см. выше)
 ---
 ## Отладка и сброс
  vendor/bin/phpstan analyze -c phpstan.neon.dist vendor/bin/pint -v rm -f storage/*.json # сброс всех данных 
 ---
 ## Траблшутинг
 - **Занят порт:** измените порт в `docker-compose.yml`
- **Нет прав на запись:** проверьте/измените права или перезапустите Docker
- **Кэш браузера:** Ctrl+Shift+R
 ---
 Клонируй → запускай → работает.
 В этом формате все секции короткие, читаемые, с лаконичными блоками и списками, легко копируются целиком для вставки в свой проек