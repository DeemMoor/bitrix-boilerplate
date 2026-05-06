#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  ./scripts/install.sh --repo-url <git_repo_url> --dir <project_dir> [--public-dir <web_root_dir>]

Create a new project from the boilerplate repository and run scripts/setup.

Options:
  --repo-url URL   Git repository URL of the boilerplate repository.
  --ref REF        Optional branch, tag, or commit to checkout. Default: master
  --dir DIR        Target project directory.
  --public-dir DIR Web root directory relative to project root or absolute path. Default: public
  --force          Allow installing into an existing directory. Existing matching files may be overwritten.
  -h, --help       Show this help.
EOF
}

REPO_URL=""
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

if [[ -z "$REPO_URL" ]]; then
    echo "--repo-url is required." >&2
    usage >&2
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

echo "Cloning boilerplate repository..."
git clone --depth 1 --branch "$REF" "$REPO_URL" "$TMP_DIR/repo"

echo "Installing project to $TARGET_DIR..."
cp -R "$TMP_DIR/repo/." "$TARGET_DIR/"
rm -rf "$TARGET_DIR/.git"
prepare_public_dir

echo "Downloading Bitrix installer..."
(
    cd "$TARGET_DIR"
    ./scripts/setup --public-dir "$PUBLIC_DIR"
)

echo "Done. Project installed to $TARGET_DIR."
