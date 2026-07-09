# План: интерактивный инсталлятор bitrix-boilerplate

Задание для ИИ-агента. Репозиторий: https://github.com/DeemMoor/bitrix-boilerplate
Работай в ветке `feature/bootstrap-wizard`.

## 1. Контекст — что уже есть (НЕ переписывать, строить сверху)

- `scripts/install.sh` — качает репо и раскладывает файлы. Флаги `--repo-url`, `--ref`, `--dir`, `--public-dir`, `--force`. При `--public-dir` копирует файлы из `public/` в web-root и делает симлинки `bitrix`, `local`, `upload` из корня проекта в web-root (вариант для хостинга с фиксированным `public_html`).
- `scripts/init` (bash, ~700 строк) — читает `.env` и: генерит `local/.settings.php` (БД mysql/pgsql/mariadb; `CACHE` = redis/memcache/files/none; `SESSION` = redis/memcache/database/file; `CONNECTIONS`), копирует `dbconn.php` из example, ставит имя в `composer.json` из `PROJECT_NAME`, переименовывает модуль `vendor.engine` → `<vendor>.engine` (каталог, module ID, namespace, psr-4, DI). Идемпотентен, есть `--force`.
- `scripts/setup` — качает `bitrixsetup.php` в web-root (`--public-dir`).
- `scripts/strip-demo.php` — удаляет демо-срез (Example/Ping/Test). **Запускать строго ДО `init`** — завязан на имя `vendor.engine`.
- `.env.example` — все ключи: `PROJECT_NAME`, `MYSQL_*`/`POSTGRES_*`/`MARIADB_*`, `REDIS`, `MEMCACHED`, `CACHE`, `SESSION`, `CONNECTIONS` и др.
- `.gitignore` — уже исключает `.env`, `bitrix/`, `local/.settings.php`, `dbconn.php`.

Чего нет: интерактивного мастера, который склеивает всё в одну команду, и `git init` в конце.

## 2. Цель

Одна команда:

```bash
bash <(curl -fsSL https://github.com/DeemMoor/bitrix-boilerplate/raw/master/scripts/bootstrap)
```

Цветной интерактивный мастер задаёт вопросы → раскладывает файлы, пишет `.env`, запускает `strip-demo` (опц.) и `init`, ставит composer-зависимости (если возможно), делает `git init` + первый коммит. Тот же скрипт работает из уже склонированного репо: `./scripts/bootstrap`.

## 3. Deliverables

1. `scripts/bootstrap` — bash-мастер (основная работа).
2. Обновлённый `README.md` — раздел «Быстрый старт одной командой».
3. `scripts/tests/smoke.sh` — неинтерактивные smoke-тесты.
4. `.github/workflows/ci.yml` — shellcheck + smoke-тест.

## 4. Требования к scripts/bootstrap

### 4.1 Общие

- Bash ≥ 4, `set -euo pipefail`. Без внешних зависимостей (gum/whiptail/dialog нельзя — на shared-хостингах их нет).
- Два режима: интерактивный (вопросы с дефолтами, Enter = дефолт) и неинтерактивный (всё флагами, см. 4.5).
- Перед выполнением — цветное резюме выбранного и подтверждение `[Y/n]` (в неинтерактивном режиме пропускается).
- Проверка зависимостей на старте: `git` (обязателен), `php` ≥ 8.4 (нужен для strip-demo; если нет — предупредить и пропустить шаг с демо), `composer` (если нет — пропустить установку зависимостей с жёлтым предупреждением и подсказкой).
- Повторный запуск не должен ломать проект (init уже идемпотентен, bootstrap — тоже: существующий `.env` → спросить «перезаписать?»).
- `trap ERR`: красное сообщение с именем провалившегося шага.

### 4.2 Красивый UI — обязательное требование

- ANSI-цвета через esc-последовательности; автоотключение, если stdout не TTY или задан `NO_COLOR`.
- Палитра: заголовки/баннер — bold cyan; вопросы — cyan; дефолт в скобках — dim; успех `[OK]` — green; предупреждение — yellow; ошибка — red; выполняемая команда — dim.
- ASCII-баннер «Bitrix Boilerplate» при старте, прогресс шагов вида `[3/7] База данных`.
- Выбор из списка — нумерованное меню (ввод цифры), y/n-вопросы с подсветкой дефолта.
- Финальный экран: зелёная рамка с итогом и «что дальше» (для локалки: `dl up`, установка ядра; для хостинга: залить ядро / открыть bitrixsetup.php).

### 4.3 Вопросы мастера (порядок)

1. **Куда ставимся?** `1) Локалка или свой сервер` / `2) Shared-хостинг`.
   - Локалка/сервер: структура с папкой `public/` как есть (docroot = `public`). Спросить имя каталога проекта (`--dir`, default `project`).
   - Хостинг: спросить путь к docroot (default `public_html`) и вариант: `a)` код выше docroot — файлы из `public/` копируются в docroot, `bitrix/local/upload` симлинками (текущая механика install.sh с `--public-dir`); `b)` всё внутри docroot (`--dir . --public-dir . --force`). Использовать install.sh, не дублировать логику.
2. **Vendor и название проекта.** Два отдельных вопроса, валидация `^[a-z][a-z0-9_-]*$`. Из них собирается `PROJECT_NAME=vendor/name` для `.env` → дальше init сам обновит composer.json, переименует модуль и namespace.
3. **HOST_NAME** (default = name из п.2).
4. **База данных.** `1) MySQL` (спросить версию, def 8.4) / `2) PostgreSQL` / `3) MariaDB`. Для локалки под DL — креды по умолчанию (db/db/db). Для хостинга дополнительно спросить host, имя БД, пользователя, пароль (пароль — скрытый ввод `read -s`).
5. **Сервисы:** `Redis? [y/N]`, `Memcached? [y/N]`.
6. **Кэш Битрикса (CACHE):** files / redis / memcache / none — redis и memcache предлагать только если соответствующий сервис включён в п.5.
7. **Сессии (SESSION):** file / database / redis / memcache — та же логика.
8. **Удалить демо-данные?** `[y/N]` (Example-сущность, эндпоинты /api/example, /api/test, команда ping).
9. **git init + первый коммит?** `[Y/n]`.

### 4.4 Маппинг ответов в .env

Генерировать `.env` из `.env.example` sed-правками (сохранить комментарии):

| Ответ | Ключи .env |
|---|---|
| vendor+name | `PROJECT_NAME=vendor/name` |
| host name | `HOST_NAME=`, `DOCUMENT_ROOT` (для хостинга — реальный путь) |
| MySQL | `MYSQL_VERSION=<v>` + креды `MYSQL_*`; блоки POSTGRES/MARIADB остаются закомментированными |
| PostgreSQL | закомментировать `MYSQL_VERSION`, раскомментировать `POSTGRES_VERSION`, `POSTGRES_DB/USER/PASSWORD` |
| MariaDB | аналогично через `MARIADB_VERSION` |
| Redis | `REDIS=true` (+ `PHP_MODULES` содержит redis) |
| Memcached | `MEMCACHED=true` (+ memcached в `PHP_MODULES`) |
| CACHE/SESSION | `CACHE=`, `SESSION=` |
| Соединения | `CONNECTIONS=[<db>,redis?,memcache?]` — включать redis/memcache, если сервис включён |
| Хостинг | `APP_ENV=production`, `APP_DEBUG=false`; локалка: `local`/`true` |

### 4.5 Флаги неинтерактивного режима

`--target local|hosting`, `--dir`, `--public-dir`, `--vendor`, `--name`, `--host-name`, `--db mysql|pgsql|mariadb`, `--db-version`, `--db-host`, `--db-name`, `--db-user`, `--db-pass`, `--redis`, `--memcached`, `--cache`, `--session`, `--strip-demo` / `--keep-demo`, `--git` / `--no-git`, `--force`, `--yes` (принять дефолты для всего незаданного), `--ref` (ветка/тег болванки).

Правило: заданное флагом не спрашивается. Если stdin не TTY и нет `--yes` — упасть с понятной ошибкой, перечислив недостающие параметры.

### 4.6 Последовательность выполнения (после ответов)

1. Если запущен вне репо (curl-вариант) — вызвать `scripts/install.sh` с нужными `--dir/--public-dir/--force`, `cd` в проект. Если запущен из репо — пропустить.
2. Записать `.env` (п.4.4).
3. Если выбрано удаление демо — `php scripts/strip-demo.php` (**до init**).
4. `./scripts/init` (генерит `.settings.php`, `dbconn.php`, composer.json, переименовывает модуль).
5. `composer install` при наличии composer; иначе жёлтая подсказка (`dl exec composer install` для локалки). После переименования модуля — `composer dump-autoload`.
6. `git init -b main && git add -A && git commit -m "Initial commit from bitrix-boilerplate"`. Перед коммитом убедиться, что `.env` игнорируется (`git check-ignore .env`). Если git identity не настроен — коммитить с `-c user.name=... -c user.email=...` fallback «Bootstrap <bootstrap@localhost>» и предупредить.
7. Финальный экран-резюме (п.4.2).

## 4.7 Установка через composer create-project

Сделать вторым способом установки (после curl | bash):

```bash
composer create-project deemmoor/bitrix-boilerplate myproject
```

1. В composer.json задать реальное имя пакета `deemmoor/bitrix-boilerplate` (init при инициализации заменит его на имя проекта — это ок).
2. Добавить хук, запускающий мастера после создания проекта:

   ```json
   "scripts": {
       "post-create-project-cmd": "bash scripts/bootstrap --from-composer"
   }
   ```

3. Реализовать в bootstrap флаг `--from-composer`: пропустить скачивание файлов (composer уже разложил и удалил `.git`) и повторный `composer install`; остальные шаги (вопросы, `.env`, strip-demo, init, git init + коммит) — как обычно. Хук выполняется в TTY, интерактив и цвета работают.
4. Опубликовать пакет на Packagist; до публикации проверять через `--repository='{"type":"vcs","url":"https://github.com/DeemMoor/bitrix-boilerplate"}' --stability=dev`.
5. Создать тег релиза (например `v1.0.0`), чтобы create-project ставил stable, а не `dev-master`.
6. В README описать оба способа и ограничение: composer-путь требует локальных composer и PHP ≥ 8.4, для shared-хостингов основной способ — curl | bash.
7. Smoke-тест: `composer create-project` из локального vcs-репо с флагами неинтерактивного режима (переменная окружения `BOOTSTRAP_ARGS` или composer extra) → те же проверки, что в сценарии 1 п.5.

## 5. Тестирование (обязательный этап)

- `shellcheck scripts/bootstrap scripts/install.sh scripts/init scripts/setup` — 0 ошибок (существующие warning'и init можно занести в исключения).
- `bash -n` на все скрипты.
- `scripts/tests/smoke.sh` — сценарии во временном каталоге, без Битрикса/Docker (проверяем только файлы):
  1. local + mysql + redis + strip-demo + git: в результате `.env` корректен, `local/.settings.php` содержит `CacheEngineRedis` и `RedisConnection`, `composer.json` name = vendor/name, существует `local/modules/<vendor>.engine`, `vendor.engine` отсутствует, демо-файлы удалены, `git log --oneline | wc -l` = 1, `.env` не в индексе.
  2. hosting + pgsql + memcached + keep-demo: `.settings.php` содержит `PgsqlConnection` и memcache-блоки, симлинки в docroot на месте.
  3. Повторный запуск сценария 1 — не падает.
  4. Все ответы дефолтами через `--yes` — проходит без единого вопроса.
- Интерактив проверить пайпом ответов: `printf '1\nvendor\nmyproj\n...\n' | ./scripts/bootstrap`.

## 6. Критерии приёмки

- [ ] Одна команда (curl | bash) доводит от пустого каталога до готового проекта с git-историей из одного коммита.
- [ ] Все 5 пунктов ТЗ закрыты: цель установки (хостинг/сервер), vendor+название, выбор БД, Redis/Memcache → `.env` + `.settings.php`, удаление демо.
- [ ] Цветной UI, корректно деградирует без TTY/с NO_COLOR.
- [ ] Неинтерактивный режим полностью управляется флагами.
- [ ] shellcheck и smoke-тесты зелёные, README обновлён.
- [ ] Существующие сценарии из README (ручной путь через `dl env` + `./scripts/init`) продолжают работать без изменений.

## 7. Backlog идей (после основной задачи, по желанию)

1. Релизы: тегировать версии; `bootstrap`/`install.sh` по умолчанию ставят последний тег, `--ref master` — для смелых.
2. Автогенерация паролей БД (`openssl rand -base64 18`) вместо db/db.
4. Вопрос мастера «скачать bitrixsetup.php?» → вызов `scripts/setup`.
5. Автоопределение окружения: найден `dl` → дефолт «локалка»; найден `~/public_html` → дефолт «хостинг».
6. GitHub Actions на каждый PR: shellcheck + smoke.sh (закрывается этим же заданием, п.5).
7. `scripts/doctor` — самопроверка: `.settings.php` соответствует `.env`, БД/Redis доступны, версия PHP, права на каталоги.
8. Флаг `--remote <url>`: `git remote add origin` + push после первого коммита.
9. Выбор `PHP_VERSION` в мастере (7.3-apache … 8.4-fpm).
10. Pre-commit хуки: php-cs-fixer, phpstan, запрет коммита `.env` и дампов.
11. Makefile/Taskfile: `make init`, `make test`, `make deploy`.
12. Лог установки в `install.log` для разбора проблем на хостингах.
