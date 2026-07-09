# Bitrix Boilerplate

Болванка для проектов на 1C-Битрикс: каркас проекта, основной модуль, API-роуты, контроллеры, use case, репозитории, DTO/presenter-слой, консольные команды, OpenAPI и миграции.

## Быстрый старт

### 1. Через curl

```bash
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap)
```

Мастер спросит, куда ставить проект, vendor/name, БД, Redis/Memcached, кэш, сессии, удалять ли демо и делать ли первый git-коммит.

Для shared-хостинга сначала установите ядро Битрикса в docroot, затем запускайте команду выше из каталога сайта. Если Битрикса нет, установщик остановится с ошибкой и подчистит созданные файлы.

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

После `create-project` автоматически запустится тот же мастер. Этот способ требует локальные `PHP >= 8.4`, `composer`, `git`; для локальной установки также нужен `dl`.

## Что проверяет установщик

- `bash >= 4`, `git`, `PHP >= 8.4`, `composer`.
- Для локалки: наличие `dl`.
- Для shared-хостинга: установленный Битрикс в docroot (`bitrix/.settings.php` и `bitrix/modules/main`).
- На аварии выводит конкретную ошибку и откатывает созданные файлы/каталоги.
- Перед первым коммитом проверяет, что `.env` игнорируется git.

## Что делает мастер

- Копирует болванку из GitHub или запускается из уже скачанного проекта.
- Генерирует `.env` из `.env.example`, сохраняя комментарии.
- Для продовой установки ставит `APP_ENV=production`, `APP_DEBUG=false` и всегда удаляет демо-срез.
- До `init` может удалить демо-код: Example-сущность, `/api/example`, `/api/test`, команду `ping`.
- Запускает `scripts/init`: генерирует `local/.settings.php`, `dbconn.php`, меняет `composer.json`, переименовывает `vendor.engine` в `<vendor>.engine`.
- Ставит Composer-зависимости и делает `git init` + первый коммит, если вы это подтвердили.

## Неинтерактивный запуск

```bash
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap) \
  --target local \
  --dir myproject \
  --vendor acme \
  --name shop \
  --db mysql \
  --redis \
  --cache redis \
  --session redis \
  --strip-demo \
  --git \
  --yes
```

Для хостинга, где Битрикс уже установлен в `public_html`:

```bash
cd ~/site.ru
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap) \
  --target hosting \
  --public-dir public_html \
  --vendor acme \
  --name site \
  --cache files \
  --session database \
  --git \
  --yes
```

## После установки

Локально:

```bash
cd myproject
dl up
./scripts/setup
```

Откройте `bitrixsetup.php` в браузере и установите ядро Битрикса. Креды БД берите из `.env`.

На хостинге:

```bash
composer install --no-dev --optimize-autoloader
vendor/bin/phinx migrate -c phinx.php
```

Если системный `php` старый, используйте нужный бинарник:

```bash
PHP_BIN=php8.4 bash scripts/bootstrap
php8.4 "$(command -v composer)" install --no-dev --optimize-autoloader
php8.4 vendor/bin/phinx migrate -c phinx.php
```

## Ручные команды

Старый ручной путь остаётся рабочим:

```bash
cp .env.example .env
./scripts/init
composer install
```

Удалить демо-срез вручную можно только до `scripts/init`:

```bash
php scripts/strip-demo.php
```

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
