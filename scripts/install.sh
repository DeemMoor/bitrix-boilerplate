#!/usr/bin/env bash

set -euo pipefail

usage() {
    cat <<'EOF'
Usage:
  ./scripts/install.sh --repo-url <git_repo_url> --dir <project_dir>

Create a new project from the boilerplate repository and run scripts/setup.

Options:
  --repo-url URL   Git repository URL of the boilerplate repository.
  --ref REF        Optional branch, tag, or commit to checkout. Default: master
  --dir DIR        Target project directory.
  --force          Allow installing into an existing empty directory.
  -h, --help       Show this help.
EOF
}

REPO_URL=""
REF="master"
TARGET_DIR=""
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
        echo "Use --force only for an existing empty directory." >&2
        exit 1
    fi

    if find "$TARGET_DIR" -mindepth 1 -print -quit | grep -q .; then
        echo "Target directory is not empty: $TARGET_DIR" >&2
        exit 1
    fi
else
    mkdir -p "$TARGET_DIR"
fi

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

echo "Downloading Bitrix installer..."
(
    cd "$TARGET_DIR"
    ./scripts/setup
)

echo "Done. Project installed to $TARGET_DIR."
