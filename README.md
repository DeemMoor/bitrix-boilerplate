# Bitrix Boilerplate

Production-ready болерплейт для проектов на 1C-Битрикс.

## Структура

```text
bitrix/          ядро Битрикс, не хранится в git
upload/          пользовательские файлы, не хранятся в git
local/           проектный код, модули, шаблоны и настройки
public/          document root веб-сервера
public/bitrix -> ../bitrix
public/local  -> ../local
public/upload -> ../upload
```

## Установка

```bash
composer install
cp .env.example .env
```

Document root веб-сервера должен смотреть в `public`.

Реальные файлы `/local/.settings.php`, `/local/.settings_extra.php` и `/local/php_interface/dbconn.php` не хранятся в git. Для них есть example-файлы без production-секретов.

## Локальный запуск через DL

Проект рассчитан на запуск через [Deploy Local](https://local-deploy.github.io/).

```bash
dl service up
dl env
dl up
```

В `.env.example` зафиксированы переменные, которые понимает DL:

- `DOCUMENT_ROOT=/var/www/html/public`
- `PHP_VERSION=8.4-fpm`
- `MYSQL_VERSION=8.0`
- `REDIS=true`
- `MEMCACHED=true`

Для установленного локально `DL v1.1.3` доступны PHP-образы `7.3`, `7.4`, `8.0`, `8.1`, `8.2`, `8.3`, `8.4` в вариантах `fpm` и `apache`. MySQL в локальном шаблоне DL явно перечислен как `5.7`, `8.0`, `9.0`. PostgreSQL задается переменной `POSTGRES_VERSION` как тег официального образа `postgres`; по умолчанию шаблон DL использует `15`.
