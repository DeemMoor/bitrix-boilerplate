# Bitrix Boilerplate

Boilerplate для проектов на 1C-Битрикс.

## Установка на сервер

Если Apache на хостинге смотрит в `public_html`, выполните:

```bash
bash <(curl -fsSL https://raw.githubusercontent.com/DeemMoor/bitrix-boilerplate/master/scripts/install.sh) --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir public_html --public-dir . --force
```

Замените `public_html` на директорию, в которую смотрит ваш сайт. Команда скачает проект и сохранит установщик в `public_html/bitrixsetup.php`. `--force` нужен для хостингов, где web root уже создан.

Если хостинг позволяет держать код выше document root:

```bash
bash <(curl -fsSL https://raw.githubusercontent.com/DeemMoor/bitrix-boilerplate/master/scripts/install.sh) --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir app --public-dir ../public_html
```

После установки откройте в браузере:

```text
https://example.com/bitrixsetup.php
```

После завершения установки Битрикс установите Composer-зависимости:

```bash
composer install --no-dev --optimize-autoloader
```

## Локальная разработка

DL нужен только локально. Инструкция по установке: https://local-deploy.github.io/ru/getting-started/install

Создайте `.env`:

```bash
dl env
```

После генерации `.env` отредактируйте нужные опции:

- `PROJECT_NAME=vendor/name` - имя проекта для `composer.json` и модуля `vendor.engine`.
- `PHP_MODULES="opcache redis memcached"` - PHP-модули.
- `MYSQL_VERSION=8.4` - база по умолчанию, MySQL 8.4 LTS.
- `POSTGRES_VERSION` или `MARIADB_VERSION` - если нужна другая база.
- `REDIS=true`, `MEMCACHED=true` - дополнительные сервисы.
- `CACHE=redis` - кэш: `redis`, `memcache`, `files`, `none`.
- `SESSION=database` - сессии: `redis`, `memcache`, `database`, `file`.
- `CONNECTIONS=[mysql,redis]` - соединения: `mysql`, `mariadb`, `pgsql`, `redis`, `memcache`.

Если `CONNECTIONS` не указан, будет создано только соединение с базой данных из `.env`.

```bash
./scripts/init
dl up
dl exec composer install
```

`./scripts/init` создает `local/.settings.php`, `local/php_interface/dbconn.php`, обновляет `composer.json` и создает модуль `vendor.engine`. Существующие файлы и модуль не перезаписываются.

Document root веб-сервера: `public`.

## Установщик Битрикс

```bash
./scripts/setup
```

Для нестандартного document root:

```bash
./scripts/setup --public-dir public_html
```
