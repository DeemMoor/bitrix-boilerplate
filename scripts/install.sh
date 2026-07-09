#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  ./scripts/install.sh --repo-url <git_repo_url> --dir <project_dir> [--public-dir <web_root_dir>]

Drop the boilerplate files into the target directory. Bitrix must already be
installed (or be installed afterwards) — this script no longer downloads
bitrixsetup.php. Use scripts/setup if you need to fetch the installer.

After bootstrap: copy .env.example to .env, edit it, then run ./scripts/init.

Options:
  --repo-url URL   Git repository URL of the boilerplate repository.
  --from-dir DIR   Copy the boilerplate from a local directory instead of
                   cloning. Only git-visible files are copied (tracked and
                   untracked, ignored files are skipped). Overrides --repo-url.
  --ref REF        Optional branch, tag, or commit to checkout. Default: master
  --dir DIR        Target project directory, resolved relative to the current
                   directory. Run the script from the parent of the target dir
                   (e.g. the site root). If you are already inside the hosting
                   docroot, pass --dir .
  --public-dir DIR Web root directory relative to project root or absolute path.
                   Use --public-dir . when the docroot is the project dir itself.
                   Default: public
  --force          Allow installing into an existing directory. Existing matching files may be overwritten.
  -h, --help       Show this help.
EOF
}

REPO_URL=""
FROM_DIR=""
REF="master"
TARGET_DIR=""
PUBLIC_DIR="public"
FORCE=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --repo-url)
            REPO_URL="${2:-}"
            shift 2
            ;;
        --from-dir)
            FROM_DIR="${2:-}"
            shift 2
            ;;
        --ref)
            REF="${2:-}"
            shift 2
            ;;
        --dir)
            TARGET_DIR="${2:-}"
            shift 2
            ;;
        --public-dir)
            PUBLIC_DIR="${2:-}"
            shift 2
            ;;
        --force)
            FORCE=1
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            if [[ -z "$TARGET_DIR" ]]; then
                TARGET_DIR="$1"
                shift
            else
                echo "Unknown argument: $1" >&2
                usage >&2
                exit 1
            fi
            ;;
    esac
done

if [[ -z "$REPO_URL" && -z "$FROM_DIR" ]]; then
    echo "--repo-url or --from-dir is required." >&2
    usage >&2
    exit 1
fi

if [[ -n "$FROM_DIR" && ! -d "$FROM_DIR" ]]; then
    echo "--from-dir: directory not found: $FROM_DIR" >&2
    exit 1
fi

if [[ -z "$TARGET_DIR" ]]; then
    echo "--dir is required." >&2
    usage >&2
    exit 1
fi

if [[ -z "$PUBLIC_DIR" ]]; then
    echo "--public-dir cannot be empty." >&2
    usage >&2
    exit 1
fi

if ! command -v git >/dev/null 2>&1; then
    echo "git is required to install the project from the repository." >&2
    exit 1
fi

TARGET_DIR="${TARGET_DIR%/}"

if [[ -e "$TARGET_DIR" ]]; then
    if [[ ! -d "$TARGET_DIR" ]]; then
        echo "Target exists and is not a directory: $TARGET_DIR" >&2
        exit 1
    fi

    if [[ "$FORCE" -ne 1 ]]; then
        echo "Target directory already exists: $TARGET_DIR" >&2
        echo "Use --force to install into an existing hosting directory." >&2
        exit 1
    fi
else
    if [[ "$(basename "$PWD")" == "$TARGET_DIR" ]]; then
        echo "[WARN] Current directory is already named '$TARGET_DIR' — this will create nested $PWD/$TARGET_DIR." >&2
        echo "[WARN] If you are already inside the docroot, use --dir . instead." >&2
        answer=""
        if [[ -t 0 ]]; then
            read -r -p "Continue anyway? [y/N] " answer
        fi
        if [[ ! "$answer" =~ ^[yY] ]]; then
            echo "Aborted." >&2
            exit 1
        fi
    fi
    mkdir -p "$TARGET_DIR"
fi

TARGET_DIR="$(cd "$TARGET_DIR" && pwd -P)"

resolve_public_dir() {
    if [[ "$PUBLIC_DIR" = /* ]]; then
        printf '%s\n' "${PUBLIC_DIR%/}"
    else
        printf '%s\n' "${TARGET_DIR}/${PUBLIC_DIR%/}"
    fi
}

prepare_public_dir() {
    local public_source public_target project_root web_root item link

    if [[ "$PUBLIC_DIR" == "public" ]]; then
        return
    fi

    public_source="$TARGET_DIR/public"
    public_target="$(resolve_public_dir)"
    mkdir -p "$public_target"

    project_root="$(cd "$TARGET_DIR" && pwd -P)"
    web_root="$(cd "$public_target" && pwd -P)"

    find "$public_source" -mindepth 1 -maxdepth 1 -type f -exec cp {} "$web_root/" \;

    if [[ "$web_root" == "$project_root" ]]; then
        # Web root совпадает с корнем проекта (--public-dir .): файлы из public/
        # уже скопированы в корень, а bitrix/local/upload лежат там же реально —
        # исходный каталог public/ больше не нужен, убираем хвост.
        rm -rf "$public_source"
        return
    fi

    for item in bitrix local upload; do
        link="$web_root/$item"
        if [[ -e "$link" || -L "$link" ]]; then
            echo "Web root path already exists: $link" >&2
            echo "Remove it manually or use another --public-dir." >&2
            exit 1
        fi

        ln -s "$project_root/$item" "$link"
    done
}

TMP_DIR="$(mktemp -d)"
cleanup() {
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

if [[ -n "$FROM_DIR" ]]; then
    SRC_DIR="$(cd "$FROM_DIR" && pwd -P)"
else
    echo "Cloning boilerplate repository..."
    git clone --depth 1 --branch "$REF" "$REPO_URL" "$TMP_DIR/repo"
    SRC_DIR="$TMP_DIR/repo"
fi

echo "Installing project to $TARGET_DIR..."
if [[ -d "$SRC_DIR/.git" ]]; then
    # Копируем только видимые git'у файлы (tracked + untracked без ignored):
    # это отсекает vendor/, bitrix/, .env и прочий локальный мусор источника.
    (cd "$SRC_DIR" && git ls-files -coz --exclude-standard \
        | tar --null --ignore-failed-read -cf - -T -) | tar -xpf - -C "$TARGET_DIR"
else
    cp -R "$SRC_DIR/." "$TARGET_DIR/"
fi
rm -rf "$TARGET_DIR/.git"
prepare_public_dir

echo "Done. Project installed to $TARGET_DIR."
echo "Next: copy .env.example to .env, edit it, then run ./scripts/init."
