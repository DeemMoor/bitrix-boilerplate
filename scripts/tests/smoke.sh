#!/usr/bin/env bash
#
# Smoke-тесты установщика scripts/bootstrap.
#
# Запуск из любого места:
#   bash scripts/tests/smoke.sh
#
# Без Битрикса и Docker: dl и composer подменяются заглушками (реальный
# composer — SMOKE_REAL_COMPOSER=1), Битрикс на «хостинге» — фейковым ядром.
# Проверяются только файлы, которые генерирует мастер.

set -Eeuo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"
BOOTSTRAP="$REPO_ROOT/scripts/bootstrap"
SMOKE_TMP="$(mktemp -d)"
trap 'rm -rf "$SMOKE_TMP"' EXIT

FAIL=0
note()  { printf '\n=== %s ===\n' "$1"; }
check() { # check "описание" команда с аргументами...
    local desc="$1"
    shift
    if "$@" >/dev/null 2>&1; then
        printf '  ok:   %s\n' "$desc"
    else
        printf '  FAIL: %s\n' "$desc" >&2
        FAIL=1
    fi
}

# --- Заглушки dl и composer -------------------------------------------------
FAKE_BIN="$SMOKE_TMP/bin"
mkdir -p "$FAKE_BIN"

printf '#!/bin/sh\nexit 0\n' > "$FAKE_BIN/dl"
chmod +x "$FAKE_BIN/dl"

if [[ "${SMOKE_REAL_COMPOSER:-0}" != "1" ]]; then
    cat > "$FAKE_BIN/composer" <<'SH'
#!/bin/sh
case "$1" in
    install) mkdir -p vendor && : > vendor/autoload.php ;;
esac
exit 0
SH
    chmod +x "$FAKE_BIN/composer"
fi

export PATH="$FAKE_BIN:$PATH"

make_fake_bitrix() { # make_fake_bitrix <docroot> <db: mysql|pgsql>
    local docroot="$1" db="${2:-mysql}" class="Bitrix\\\\Main\\\\DB\\\\MysqliConnection"
    if [[ "$db" == "pgsql" ]]; then
        class="Bitrix\\\\Main\\\\DB\\\\PgsqlConnection"
    fi
    mkdir -p "$docroot/bitrix/modules/main"
    printf '<?php\n' > "$docroot/bitrix/modules/main/include.php"
    cat > "$docroot/bitrix/.settings.php" <<PHP
<?php
return [
    'connections' => [
        'value' => [
            'default' => [
                'className' => '${class}',
                'host' => '127.0.0.1',
                'database' => 'sitedb',
                'login' => 'siteuser',
                'password' => 'secret',
            ],
        ],
    ],
];
PHP
}

# =============================================================================
note "Сценарий 1: локалка + mysql + redis + strip-demo + git"
S1="$SMOKE_TMP/s1"
mkdir -p "$S1"
(cd "$S1" && bash "$BOOTSTRAP" --source-dir "$REPO_ROOT" --target local --dir project \
    --vendor acme --name shop --db mysql --redis --cache redis --session redis \
    --strip-demo --git --yes)
P="$S1/project"
check ".env: PROJECT_NAME=acme/shop"        grep -qx 'PROJECT_NAME=acme/shop' "$P/.env"
check ".env: CACHE=redis"                   grep -qx 'CACHE=redis' "$P/.env"
check ".env: REDIS=true"                    grep -qx 'REDIS=true' "$P/.env"
check ".env: CONNECTIONS=[mysql,redis]"     grep -qx 'CONNECTIONS=\[mysql,redis\]' "$P/.env"
check ".settings.php: CacheEngineRedis"     grep -q 'CacheEngineRedis' "$P/local/.settings.php"
check ".settings.php: RedisConnection"      grep -q 'RedisConnection' "$P/local/.settings.php"
check "composer.json: name acme/shop"       grep -q '"name": "acme/shop"' "$P/composer.json"
check "модуль acme.engine существует"       test -d "$P/local/modules/acme.engine"
check "модуля vendor.engine больше нет"     test ! -e "$P/local/modules/vendor.engine"
check "демо удалено (ExampleController)"    test ! -e "$P/local/modules/acme.engine/lib/Controller/ExampleController.php"
check "демо удалено (ExampleTable)"         test ! -e "$P/local/modules/acme.engine/lib/Entity/ExampleTable.php"
check "dbconn.php создан"                   test -f "$P/local/php_interface/dbconn.php"
check "git: ровно один коммит"              test "$(git -C "$P" rev-list --count HEAD)" = "1"
check "git: .env игнорируется"              git -C "$P" check-ignore -q .env
check "git: .env не в индексе"              test -z "$(git -C "$P" ls-files .env)"

# =============================================================================
note "Сценарий 2: хостинг + pgsql + memcached + принудительный strip-demo"
S2="$SMOKE_TMP/s2/site.ru"
mkdir -p "$S2/public_html"
make_fake_bitrix "$S2/public_html" pgsql
(cd "$S2" && bash "$BOOTSTRAP" --source-dir "$REPO_ROOT" --target hosting --public-dir public_html \
    --vendor acme --name site --memcached --session database --cache memcache --git --yes)
D="$S2/public_html"
check ".env: APP_ENV=production"            grep -qx 'APP_ENV=production' "$D/.env"
check ".env: APP_DEBUG=false"               grep -qx 'APP_DEBUG=false' "$D/.env"
check ".env: CONNECTIONS=[pgsql,memcache]"  grep -qx 'CONNECTIONS=\[pgsql,memcache\]' "$D/.env"
check ".settings.php: PgsqlConnection"      grep -q 'PgsqlConnection' "$D/local/.settings.php"
check ".settings.php: memcache-блок"        grep -q "'memcache' =>" "$D/local/.settings.php"
check ".settings.php: креды из Битрикса"    grep -q "'database' => 'sitedb'" "$D/local/.settings.php"
check "index.php скопирован в docroot"      test -f "$D/index.php"
check "каталог public/ убран из docroot"    test ! -d "$D/public"
check "ядро Битрикса не тронуто"            grep -q 'sitedb' "$D/bitrix/.settings.php"
check "демо удалено принудительно"          test ! -e "$D/local/modules/acme.engine/lib/Controller/ExampleController.php"
check "git: ровно один коммит"              test "$(git -C "$D" rev-list --count HEAD)" = "1"

# =============================================================================
note "Сценарий 3: повторный запуск сценария 1 (--force) не ломает проект"
(cd "$S1" && bash "$BOOTSTRAP" --source-dir "$REPO_ROOT" --target local --dir project \
    --vendor acme --name shop --db mysql --redis --cache redis --session redis \
    --strip-demo --git --yes --force)
check "модуль acme.engine на месте"         test -d "$P/local/modules/acme.engine"
check ".settings.php перегенерирован"       grep -q 'CacheEngineRedis' "$P/local/.settings.php"
check "git: по-прежнему один коммит"        test "$(git -C "$P" rev-list --count HEAD)" = "1"

# =============================================================================
note "Сценарий 4: все дефолты через --yes"
S4="$SMOKE_TMP/s4"
mkdir -p "$S4"
(cd "$S4" && bash "$BOOTSTRAP" --source-dir "$REPO_ROOT" --yes)
P4="$S4/project"
check ".env: PROJECT_NAME=vendor/project"   grep -qx 'PROJECT_NAME=vendor/project' "$P4/.env"
check ".env: CONNECTIONS=[mysql]"           grep -qx 'CONNECTIONS=\[mysql\]' "$P4/.env"
check ".settings.php создан"                test -f "$P4/local/.settings.php"
check "демо оставлено (по умолчанию)"       test -f "$P4/local/modules/vendor.engine/lib/Controller/ExampleController.php"
check "git: ровно один коммит"              test "$(git -C "$P4" rev-list --count HEAD)" = "1"

# =============================================================================
note "Сценарий 5: режим composer create-project (--from-composer + BOOTSTRAP_ARGS)"
S5="$SMOKE_TMP/s5/proj"
mkdir -p "$S5"
(cd "$REPO_ROOT" && git ls-files -coz --exclude-standard | tar --null --ignore-failed-read -cf - -T -) \
    | tar -xpf - -C "$S5"
mkdir -p "$S5/vendor"
: > "$S5/vendor/autoload.php"
(cd "$S5" && BOOTSTRAP_ARGS="--vendor acme --name compo --db mysql --yes" \
    bash scripts/bootstrap --from-composer)
check "composer.json: name acme/compo"      grep -q '"name": "acme/compo"' "$S5/composer.json"
check "модуль acme.engine существует"       test -d "$S5/local/modules/acme.engine"
check ".env создан"                         test -f "$S5/.env"
check "git: ровно один коммит"              test "$(git -C "$S5" rev-list --count HEAD)" = "1"

# =============================================================================
note "Сценарий 6: авария на шаге composer — каталог проекта подчищен"
S6="$SMOKE_TMP/s6"
mkdir -p "$S6/badbin"
printf '#!/bin/sh\nexit 1\n' > "$S6/badbin/composer"
chmod +x "$S6/badbin/composer"
rc=0
(cd "$S6" && PATH="$S6/badbin:$PATH" bash "$BOOTSTRAP" --source-dir "$REPO_ROOT" --yes) \
    >"$S6/out.log" 2>&1 || rc=$?
check "установка упала (composer сломан)"   test "$rc" -ne 0
check "ошибка названа явно"                 grep -q 'завершился с ошибкой' "$S6/out.log"
check "хвостов не осталось (project удалён)" test ! -e "$S6/project"

# =============================================================================
note "Сценарий 7: хостинг без установленного Битрикса — ошибка, без изменений"
S7="$SMOKE_TMP/s7"
mkdir -p "$S7/public_html"
rc=0
(cd "$S7" && bash "$BOOTSTRAP" --source-dir "$REPO_ROOT" --target hosting --public-dir public_html --yes) \
    >"$S7/out.log" 2>&1 || rc=$?
check "установка упала (Битрикса нет)"      test "$rc" -ne 0
check "ошибка говорит про Битрикс"          grep -q 'не найден установленный Битрикс' "$S7/out.log"
check "docroot остался пустым"              test -z "$(ls -A "$S7/public_html")"

# =============================================================================
printf '\n'
if [[ "$FAIL" -ne 0 ]]; then
    printf 'SMOKE: есть проваленные проверки.\n' >&2
    exit 1
fi
printf 'SMOKE: все проверки прошли.\n'
