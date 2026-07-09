# Bitrix Boilerplate

Болванка для проектов на 1C-Битрикс: каркас проекта, основной модуль, API-роуты, контроллеры, use case, репозитории, DTO/presenter-слой, консольные команды, OpenAPI и миграции.

## Быстрый старт

### 1. Через curl

```bash
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap)
```

Мастер спросит сценарий установки:

- `local` — локальная разработка: нужен только `dl`, PHP/Composer выполняются внутри контейнеров.
- `server` — свой VPS/выделенный сервер: Битрикс уже стоит в `public/`, PHP/Composer работают на сервере.
- `hosting` — shared-хостинг: Битрикс уже стоит в `public_html/`, код кладётся в `app/`, `public_html` заменяется симлинком на `app/public`.

Если Битрикса нет в docroot, установщик остановится с явной ошибкой и откатит созданные файлы.

### 2. Через Composer

После публикации пакета на Packagist:

```bash
composer create-project deemmoor/bitrix-boilerplate myproject
```

До публикации используйте VCS-репозиторий напрямую:

```bash
composer create-project \
  --repository='{"type":"vcs","url":"https://github.com/DeemMoor/bitrix-boilerplate"}' \
  --stability=dev \
  deemmoor/bitrix-boilerplate myproject dev-master
```

После `create-project` запускается тот же мастер. Composer-способ требует host `PHP >= 8.4`, `composer`, `git`; для чистой локальной Docker/DL-установки используйте curl.

## Что внутри болванки

Болванка ставится поверх ядра Битрикса и даёт готовый каркас проекта по канонам DDD и чистой архитектуры:

- Основной модуль `local/modules/<vendor>.engine` со слоями: роуты (`routes.php`), контроллеры, use case'ы, репозитории, DTO и presenter'ы. В модуле лежит рабочий пример среза (сущность `Example`, эндпоинты `/api/example` и `/api/test`, консольная команда `ping`) — копируйте его код и делайте свой функционал по образцу.
- REST API с OpenAPI-документацией из PHP-атрибутов — доступна на `/api/doc`.
- Консольные команды на symfony/console — `php console`.
- Миграции и сидеры БД на phinx — каталог `database/`.
- Конфигурация через `.env`: БД (MySQL/PostgreSQL/MariaDB), Redis/Memcached, кэш и сессии Битрикса. `scripts/init` генерирует из него `local/.settings.php` и `dbconn.php`.
- Демо-срез удаляется одной командой (`php scripts/strip-demo.php`), при установке на прод мастер удаляет его сам — остаётся чистый каркас модуля.

## Сценарии установки

### Локальная разработка

```bash
mkdir myproject && cd myproject
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap) \
  --target local \
  --dir .
```

Установщик поднимет контейнеры через `dl up`, затем по выбору скачает `public/bitrixsetup.php`, выполнит `dl deploy` или пропустит установку ядра.

### Свой сервер/VPS

```bash
cd /var/www/site
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap) \
  --target server \
  --dir . \
  --public-dir public
```

Ожидается, что Битрикс уже установлен в `/var/www/site/public/bitrix`. Код и зависимости ставятся в `/var/www/site`, docroot остаётся `public/`.

### Shared-хостинг

```bash
cd ~/site.ru
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap) \
  --target hosting \
  --dir app \
  --public-dir public_html
```

Ожидается, что Битрикс уже установлен в `~/site.ru/public_html/bitrix`. Установщик перенесёт `bitrix/`, `local/`, `upload/` в `app/` и заменит `public_html` симлинком на `app/public`.

## Что проверяет установщик

- Для всех режимов: `bash >= 4`, `git`.
- Для `local`: наличие `dl`; PHP/Composer запускаются через `dl exec` внутри контейнера.
- Для `server` и `hosting`: `PHP >= 8.4`, `composer`, установленный Битрикс в docroot.
- При аварии выводит конкретную ошибку и откатывает созданные файлы/каталоги.
- Перед первым коммитом проверяет, что `.env` игнорируется git.

## Что делает мастер

- Копирует болванку из GitHub или запускается из уже скачанного проекта.
- Генерирует `.env` из `.env.example`, сохраняя комментарии.
- Локально запускает `dl up`, затем скачивает `bitrixsetup.php`, выполняет `dl deploy` или пропускает шаг по выбору.
- На `server`/`hosting` ставит `APP_ENV=production`, `APP_DEBUG=false` и всегда удаляет демо-срез.
- До `init` может удалить демо-код: Example-сущность, `/api/example`, `/api/test`, команду `ping`.
- Запускает `scripts/init`: локально через `dl exec bash`, на сервере/хостинге через выбранный `PHP_BIN`.
- Переименовывает `vendor.engine` в `<vendor>.engine`, обновляет `composer.json`, ставит Composer-зависимости и делает первый git-коммит по выбору.

## Неинтерактивный запуск

Локально:

```bash
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap) \
  --target local \
  --dir . \
  --vendor acme \
  --name shop \
  --db mysql \
  --redis \
  --cache redis \
  --session redis \
  --bitrix setup \
  --strip-demo \
  --git \
  --yes
```

Свой сервер:

```bash
cd /var/www/site
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap) \
  --target server \
  --dir . \
  --public-dir public \
  --vendor acme \
  --name site \
  --cache files \
  --session database \
  --git \
  --yes
```

Shared-хостинг:

```bash
cd ~/site.ru
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap) \
  --target hosting \
  --dir app \
  --public-dir public_html \
  --vendor acme \
  --name site \
  --cache files \
  --session database \
  --git \
  --yes
```

## После установки

Локально мастер уже поднял контейнеры и поставил Composer-зависимости внутри них. Если выбран `--bitrix setup`, откройте `/bitrixsetup.php` в браузере и установите ядро Битрикса.

После установки ядра:

```bash
dl exec "vendor/bin/phinx migrate -c phinx.php"
```

На сервере/хостинге:

```bash
vendor/bin/phinx migrate -c phinx.php
```

Если системный `php` старый, укажите нужный бинарник:

```bash
PHP_BIN=php8.4 bash scripts/bootstrap
php8.4 vendor/bin/phinx migrate -c phinx.php
```

## Ручные команды

Локально команды проекта выполняйте внутри контейнера:

```bash
cp .env.example .env
dl up
dl exec bash scripts/init
dl exec composer install
```

На сервере/хостинге без DL:

```bash
cp .env.example .env
PHP_BIN=php8.4 bash scripts/init
composer install --no-dev --optimize-autoloader
```

Удалить демо-срез вручную можно только до `scripts/init`:

```bash
dl exec php scripts/strip-demo.php
```

На сервере/хостинге без DL используйте `php scripts/strip-demo.php`.

## Доставка изменений

- Код `local/` доставляйте через git.
- Структуру БД оформляйте phinx-миграциями в `database/`.
- Демо-контент и тестовые данные оформляйте сидерами.
- Ядро `bitrix/`, `upload/`, `.env`, `local/.settings.php`, `dbconn.php` не коммитьте.

## API

После установки модуля документация доступна по адресу:

```text
/api/doc
```
