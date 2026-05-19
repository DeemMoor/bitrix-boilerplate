# Bitrix Boilerplate

Boilerplate для проектов на 1C-Битрикс. Репозиторий хранит только код проекта — ядро Битрикса, база и параметры окружения у каждого окружения свои.

## Из чего состоит проект

В git хранится:

- `local/` — модули, компоненты, шаблоны, `php_interface`, роуты;
- `composer.json`, `composer.lock`;
- `scripts/`, `phinx.php`, `database/`, шаблоны конфигов.

Не хранится в git (своё на каждом окружении):

- `bitrix/` — ядро Битрикса;
- `upload/` — загруженные файлы;
- `.env` — параметры окружения;
- `local/.settings.php`, `local/php_interface/dbconn.php`;
- база данных.

Болванка доставляет на сервер только код из `local/`. Ядро и база живут отдельно.

## Установка

DL нужен только локально. Инструкция по установке: https://local-deploy.github.io/ru/getting-started/install

Процедура одна, развилка только на шаге 4 — откуда взять ядро и базу.

1. Получите код:

   ```bash
   git clone <repo-url> project && cd project
   ```

   Разворот в готовый веб-рут хостинга — см. [Развёртывание на сервере](#развёртывание-на-сервере).

2. Создайте `.env`:

   ```bash
   dl env
   ```

   Основные параметры:

   - `PROJECT_NAME=vendor/name` — имя проекта для `composer.json` и модуля `vendor.engine`.
   - `CATALOG_SRV`, `USER_SRV`, `PORT_SRV`, `SERVER` — доступ к прод-серверу для `dl deploy`.
   - `MYSQL_VERSION`, `PHP_MODULES`, `CACHE`, `SESSION`, `CONNECTIONS`, `REDIS`, `MEMCACHED` — окружение и сервисы.
   - `EXCLUDED_TABLES`, `EXCLUDED_FILES` — что не тянуть при `dl deploy`.

3. Поднимите контейнеры:

   ```bash
   dl up
   ```

4. Получите ядро Битрикса и базу — один из вариантов:

   **Есть прод-сервер** — скачайте ядро и дамп базы с сервера из `.env`:

   ```bash
   dl deploy
   ```

   **Сервера ещё нет** — установите Битрикс начисто: положите `bitrixsetup.php` в `public/`, откройте `https://<host>/bitrixsetup.php` в браузере и пройдите установку. Креды БД берите из `.env`.

5. Инициализируйте проект:

   ```bash
   ./scripts/init
   ```

   Скрипт генерирует `local/.settings.php` и `local/php_interface/dbconn.php`, обновляет имя в `composer.json` и создаёт модуль `vendor.engine`. Существующие файлы и модуль не перезаписываются. На локалке (`APP_ENV=local`) креды БД берутся из `.env`.

6. Поставьте Composer-зависимости:

   ```bash
   dl exec composer install
   ```

Document root веб-сервера: `public`.

## Разработка и доставка

Каждый тип данных едет на сервер своим каналом:

- **Код `local/`** — через git (`git push` локально, `git pull` на сервере).
- **Структура БД** — phinx-миграции (`phinx.php`, каталог `database/`). Любое изменение схемы оформляйте миграцией, а не правкой базы вручную.
- **Демо-контент и тестовые данные** — phinx-сидеры.
- **Ядро `bitrix/`** — обновляется штатным апдейтером Битрикса на каждом окружении отдельно, в git не попадает.

## Развёртывание на сервере

Первый разворот (вручную, один раз):

1. Заведите на сервере ядро Битрикса — установка начисто или перенос ядра.
2. Разложите код поверх. Если docroot хостинга — `public_html`:

   ```bash
   bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/install.sh) \
     --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir public_html --public-dir . --force
   ```

   Если код можно держать выше document root:

   ```bash
   bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/install.sh) \
     --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir app --public-dir ../public_html
   ```

3. Скопируйте `.env.example` в `.env` и отредактируйте. Выставьте `APP_ENV=production` (любое значение кроме `local`). Креды БД дублировать в `.env` не нужно — на сервере `init` читает их из `bitrix/.settings.php`.
4. Запустите инициализацию:

   ```bash
   ./scripts/init
   ```

5. Поставьте зависимости:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

6. Прогоните миграции:

   ```bash
   vendor/bin/phinx migrate -c phinx.php
   ```

Последующие выкатки:

```bash
git pull
composer install --no-dev --optimize-autoloader
vendor/bin/phinx migrate -c phinx.php
```
