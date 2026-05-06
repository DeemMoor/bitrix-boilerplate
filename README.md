# Bitrix Boilerplate

Boilerplate для проектов на 1C-Битрикс.

## Установка

Создать проект из репозитория и сразу скачать установщик Битрикс:

```bash
bash <(curl -fsSL https://raw.githubusercontent.com/DeemMoor/bitrix-boilerplate/master/scripts/install.sh) --repo-url https://github.com/DeemMoor/bitrix-boilerplate.git --dir my-project
```

После установки перейдите в проект:

```bash
cd my-project
```

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
