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
  --link-public-dir For hosting: replace --public-dir with a symlink to project public/.
                   For --public-dir public: convert public/{bitrix,local,upload}
                   into symlinks to project root. Existing Bitrix dirs are moved first.
  --force          Allow installing into an existing directory. Existing matching files may be overwritten.
  -h, --help       Show this help.
EOF
}

REPO_URL=""
FROM_DIR=""
REF="master"
TARGET_DIR=""
PUBLIC_DIR="public"
LINK_PUBLIC_DIR=0
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
        --link-public-dir)
            LINK_PUBLIC_DIR=1
            shift
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

merge_dir_contents() { # merge_dir_contents <src> <dest>
    local src="$1" dest="$2" item base
    [[ -e "$src" || -L "$src" ]] || return 0
    mkdir -p "$dest"
    shopt -s dotglob nullglob
    for item in "$src"/*; do
        base="$(basename "$item")"
        if [[ -e "$dest/$base" || -L "$dest/$base" ]]; then
            if [[ -d "$item" && -d "$dest/$base" && ! -L "$item" && ! -L "$dest/$base" ]]; then
                merge_dir_contents "$item" "$dest/$base"
            else
                echo "Cannot move hosting file, target exists: $dest/$base" >&2
                exit 1
            fi
        else
            mv "$item" "$dest/$base"
        fi
    done
    shopt -u dotglob nullglob
    rmdir "$src" 2>/dev/null || true
}

move_docroot_leftovers() { # move_docroot_leftovers <docroot> <project_public>
    # Остальные файлы docroot (robots.txt, .htaccess, свой index.php и т.п.)
    # переносим в project public/: файлы работающего сайта важнее болваночных
    # копий, поэтому при совпадении имён побеждает версия с хостинга.
    local src="$1" dest="$2" item base
    mkdir -p "$dest"
    shopt -s dotglob nullglob
    for item in "$src"/*; do
        base="$(basename "$item")"
        if [[ -d "$item" && -d "$dest/$base" && ! -L "$item" && ! -L "$dest/$base" ]]; then
            merge_dir_contents "$item" "$dest/$base"
        else
            rm -rf "${dest:?}/$base"
            mv "$item" "$dest/$base"
        fi
    done
    shopt -u dotglob nullglob
}

prepare_linked_public_dir() { # public_html -> project/public
    local public_source public_target project_root item

    public_source="$TARGET_DIR/public"
    public_target="$(resolve_public_dir)"
    project_root="$(cd "$TARGET_DIR" && pwd -P)"

    if [[ -L "$public_target" && "$(readlink "$public_target")" == "$public_source" ]]; then
        return
    fi

    if [[ -e "$public_target" && ! -d "$public_target" ]]; then
        echo "Web root exists and is not a directory: $public_target" >&2
        exit 1
    fi

    if [[ -d "$public_target" ]]; then
        for item in bitrix local upload; do
            merge_dir_contents "$public_target/$item" "$project_root/$item"
        done

        move_docroot_leftovers "$public_target" "$public_source"
        rmdir "$public_target"
    else
        mkdir -p "$(dirname "$public_target")"
    fi

    ln -s "$public_source" "$public_target"
}

prepare_project_public_links() { # project/public/{bitrix,local,upload} -> ../{bitrix,local,upload}
    local public_source project_root item link

    public_source="$TARGET_DIR/public"
    project_root="$(cd "$TARGET_DIR" && pwd -P)"
    mkdir -p "$public_source"

    for item in bitrix local upload; do
        link="$public_source/$item"
        if [[ -L "$link" ]]; then
            continue
        fi
        if [[ -e "$link" ]]; then
            merge_dir_contents "$link" "$project_root/$item"
        else
            mkdir -p "$project_root/$item"
        fi
        ln -s "../$item" "$link"
    done
}

prepare_public_dir() {
    local public_source public_target project_root web_root item link

    if [[ "$PUBLIC_DIR" == "public" ]]; then
        if [[ "$LINK_PUBLIC_DIR" -eq 1 ]]; then
            prepare_project_public_links
        fi
        return
    fi

    if [[ "$LINK_PUBLIC_DIR" -eq 1 ]]; then
        prepare_linked_public_dir
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
    # Если ставим поверх уже установленного Bitrix в public/, не пытаемся
    # распаковать tracked symlink public/{bitrix,local,upload} поверх каталогов.
    COPY_MANIFEST="$TMP_DIR/copy-manifest"
    (cd "$SRC_DIR" && git ls-files -coz --exclude-standard) > "$COPY_MANIFEST"
    for public_link in public/bitrix public/local public/upload; do
        if [[ -e "$TARGET_DIR/$public_link" || -L "$TARGET_DIR/$public_link" ]]; then
            FILTERED_MANIFEST="$TMP_DIR/copy-manifest.filtered"
            grep -zvxF "$public_link" "$COPY_MANIFEST" > "$FILTERED_MANIFEST" || true
            mv "$FILTERED_MANIFEST" "$COPY_MANIFEST"
        fi
    done
    (cd "$SRC_DIR" && tar --null --ignore-failed-read -cf - -T "$COPY_MANIFEST") | tar -xpf - -C "$TARGET_DIR"
else
    cp -R "$SRC_DIR/." "$TARGET_DIR/"
fi
# .git цели не трогаем: болванка не приносит свой .git (манифест его не содержит),
# а при повторной установке поверх живого проекта чужую git-историю удалять нельзя.
prepare_public_dir

echo "Done. Project installed to $TARGET_DIR."
echo "Next: copy .env.example to .env, edit it, then run ./scripts/init."
