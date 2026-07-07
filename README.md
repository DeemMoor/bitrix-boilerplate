# Bitrix Boilerplate

Болванка для проектов на 1C-Битрикс.

## Установка

Для локальной разработки потребуется библиотека DL. Инструкция по установке: https://local-deploy.github.io/ru/getting-started/install

Процедура одна, развилка только на шаге 4 — откуда взять ядро и базу.

1. Установите болванку — одна команда создаст каталог `project` и скопирует в него весь код:

   ```bash
   bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/install.sh) \
     --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir project
   cd project
   ```

   Вместо `project` подставьте имя своего проекта. Установка сразу в веб-рут хостинга описана в разделе [Развёртывание на сервере](#развёртывание-на-сервере).

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

   **Сервера ещё нет** — установите Битрикс: положите `bitrixsetup.php` в `public/`, откройте `https://<host>/bitrixsetup.php` в браузере и пройдите установку. Креды БД берите из `.env`.

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

Первый разворот:

1. Установите на сервере ядро Битрикса.
2. Установите код болванки поверх ядра.

   `--dir` — путь относительно **текущего каталога**, поэтому запускайте скрипт из каталога сайта (родителя docroot), например из `~/site.ru/`. Если docroot хостинга — `public_html`:

   ```bash
   cd ~/site.ru
   bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/install.sh) \
     --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir public_html --public-dir . --force
   ```

   Если вы уже **внутри** docroot — укажите `--dir .`, иначе скрипт создаст вложенную `public_html/public_html`:

   ```bash
   cd ~/site.ru/public_html
   bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/install.sh) \
     --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir . --public-dir . --force
   ```

   `--public-dir .` означает «веб-рут — это сам каталог проекта»: файлы из `public/` копируются в корень проекта (вариант для shared-хостинга, где docroot фиксирован).

   Если код можно держать выше document root:

   ```bash
   cd ~/site.ru
   bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/install.sh) \
     --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir app --public-dir ../public_html
   ```

3. Скопируйте `.env.example` в `.env` и отредактируйте. Выставьте `APP_ENV=production` (любое значение кроме `local`). Креды БД дублировать в `.env` не нужно — на сервере `init` читает их из `bitrix/.settings.php`.
4. Запустите инициализацию:

   ```bash
   ./scripts/init
   ```

   Скрипту нужен PHP >= 8.4 в CLI. На shared-хостингах системный `php` часто старый (на Beget, например, `php -v` — это PHP 5.6), укажите бинарник явно:

   ```bash
   PHP_BIN=php8.4 ./scripts/init
   ```

   (или добавьте `PHP_BIN=php8.4` в `.env`).

5. Поставьте зависимости:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

6. Прогоните миграции:

   ```bash
   vendor/bin/phinx migrate -c phinx.php
   ```

7. Подключите git. `install.sh` удаляет `.git` после копирования, поэтому каталог на сервере — ещё не репозиторий, и без этого шага `git pull` при следующих выкатках работать не будет.

   **Проект новый** — создайте пустой репозиторий проекта и запушьте в него текущее состояние:

   ```bash
   git init
   git remote add origin <url-репозитория-проекта>
   git add .
   git status   # проверьте, что не попало лишнее: bitrix/, upload/, .env отсекает .gitignore
   git commit -m "Initial project state"
   git push -u origin master
   ```

   **Репозиторий проекта уже существует** (код разрабатывали локально) — привяжите каталог к нему:

   ```bash
   git init
   git remote add origin <url-репозитория-проекта>
   git fetch origin
   git checkout -f -t origin/master
   ```

Если системный `php` на хостинге старый, composer и phinx тоже запускайте через нужный бинарник (либо переключите версию CLI в панели хостинга):

```bash
php8.4 "$(command -v composer)" install --no-dev --optimize-autoloader
php8.4 vendor/bin/phinx migrate -c phinx.php
```

Последующие выкатки (git подключён на шаге 7):

```bash
git pull
composer install --no-dev --optimize-autoloader
vendor/bin/phinx migrate -c phinx.php
```
