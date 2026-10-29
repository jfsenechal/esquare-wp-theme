#!/usr/bin/env bash
# Compiles assets/css/tailwind.input.css to assets/css/tailwind.css.
#
# Tailwind only generates the classes it finds in its sources, and most page
# markup lives in the database, so the content is exported first. Re-run this
# after adding new Tailwind classes in the editor.
#
# Uses the standalone Tailwind binary (downloaded once into build/), so no
# Node.js or package.json is needed.
#
# Usage: bin/build-css.sh [--watch]

set -euo pipefail

TAILWIND_VERSION="4.3.3"

THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WP_PATH="$(cd "$THEME_DIR/../../.." && pwd)"
BUILD_DIR="$THEME_DIR/build"
TAILWIND_BIN="$BUILD_DIR/tailwindcss-$TAILWIND_VERSION"

mkdir -p "$BUILD_DIR"

if [[ ! -x "$TAILWIND_BIN" ]]; then
    case "$(uname -s)-$(uname -m)" in
        Linux-x86_64) platform="linux-x64" ;;
        Linux-aarch64) platform="linux-arm64" ;;
        Darwin-x86_64) platform="macos-x64" ;;
        Darwin-arm64) platform="macos-arm64" ;;
        *) echo "Unsupported platform: $(uname -s)-$(uname -m)" >&2; exit 1 ;;
    esac
    curl -fsSL -o "$TAILWIND_BIN" \
        "https://github.com/tailwindlabs/tailwindcss/releases/download/v$TAILWIND_VERSION/tailwindcss-$platform"
    chmod +x "$TAILWIND_BIN"
fi

wp --path="$WP_PATH" post list \
    --post_type=post,page,wp_block,wp_template,wp_template_part \
    --post_status=any \
    --posts_per_page=-1 \
    --field=post_content \
    > "$BUILD_DIR/content.html"

"$TAILWIND_BIN" \
    --input "$THEME_DIR/assets/css/tailwind.input.css" \
    --output "$THEME_DIR/assets/css/tailwind.css" \
    --minify \
    "$@"
