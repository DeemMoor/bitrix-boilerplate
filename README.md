# Bitrix Boilerplate

Boilerplate для проектов на 1C-Битрикс.

## Установка на сервер

Битрикс ставим начисто **до** boilerplate — наблюдалось, что при подкладке кода поверх свежей установки база может зависать на первых процентах. На чистом сервере всё проходит штатно.

1. Установите Битрикс начисто (своим способом или через `./scripts/setup`, см. ниже).
2. Положите boilerplate поверх. Если Apache хостинга смотрит в `public_html`:

   ```bash
   bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/install.sh) --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir public_html --public-dir . --force
   ```

   Если хостинг позволяет держать код выше document root:

   ```bash
   bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/install.sh) --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir app --public-dir ../public_html
   ```

   `install.sh` только раскладывает файлы и больше не качает `bitrixsetup.php`.

3. Скопируйте `.env.example` в `.env` и отредактируйте:

   ```bash
   cp .env.example .env
   ```

   На сервере выставьте `APP_ENV=production` (любое значение кроме `local`). Креды БД задавать в `.env` не нужно — они подтянутся из `bitrix/.settings.php`.

4. Запустите инициализацию:

   ```bash
   ./scripts/init
   ```

   На сервере (`APP_ENV != local`) скрипт читает `bitrix/.settings.php` и берёт оттуда `host`, `database`, `login`, `password` и тип соединения. Остальное (cache, session, connections) — из `.env`.

5. Поставьте Composer-зависимости:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

## Локальная разработка

DL нужен только локально. Инструкция по установке: https://local-deploy.github.io/ru/getting-started/install

1. Создайте `.env`:

   ```bash
   dl env
   ```

   Отредактируйте опции:

   - `PROJECT_NAME=vendor/name` — имя проекта для `composer.json` и модуля `vendor.engine`.
   - `PHP_MODULES="opcache redis memcached"` — PHP-модули.
   - `MYSQL_VERSION=8.4` — база по умолчанию, MySQL 8.4 LTS.
   - `POSTGRES_VERSION` или `MARIADB_VERSION` — если нужна другая база.
   - `REDIS=true`, `MEMCACHED=true` — дополнительные сервисы.
   - `CACHE=redis` — кэш: `redis`, `memcache`, `files`, `none`.
   - `SESSION=database` — сессии: `redis`, `memcache`, `database`, `file`.
   - `CONNECTIONS=[mysql,redis]` — соединения: `mysql`, `mariadb`, `pgsql`, `redis`, `memcache`.

   Если `CONNECTIONS` не указан, будет создано только соединение с базой данных из `.env`.

2. Поднимите контейнеры:

   ```bash
   dl up
   ```

3. Установите Битрикс. Скачать `bitrixsetup.php` в `public/`:

   ```bash
   ./scripts/setup
   ```

   Откройте `https://<host>/bitrixsetup.php` в браузере и пройдите установку. Креды БД берите из `.env`.

4. Сгенерируйте `local/.settings.php` и зависимости:

   ```bash
   ./scripts/init
   dl exec composer install
   ```

   На локалке (`APP_ENV=local`) `init` берёт креды из `.env`. Скрипт также создаёт `local/php_interface/dbconn.php`, обновляет `composer.json` и создаёт модуль `vendor.engine`. Существующие файлы и модуль не перезаписываются.

Document root веб-сервера: `public`.

## Установщик Битрикс

Скрипт `./scripts/setup` опционально качает `bitrixsetup.php` в web root:

```bash
./scripts/setup
```

Для нестандартного document root:

```bash
./scripts/setup --public-dir public_html
```
