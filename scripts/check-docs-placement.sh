#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ERRORS=()
md_files=()

while IFS= read -r path; do
  md_files+=("$path")
done < <(find "$ROOT_DIR" \( -path "$ROOT_DIR/node_modules" -o -path "$ROOT_DIR/vendor" -o -path "$ROOT_DIR/storage" -o -path "$ROOT_DIR/public" -o -path "$ROOT_DIR/bootstrap" -o -path "$ROOT_DIR/.git" \) -prune -o -type f -name '*.md' -print)

for path in "${md_files[@]}"; do
  rel="${path#$ROOT_DIR/}"
  case "$rel" in
    README.md) continue ;;
    docs/*) ;; # additional docs root check follows below
    tests/*) continue ;;
    resources/markdown/*) continue ;;
    .github/*) continue ;;
    .kiro/*) continue ;;
    *)
      ERRORS+=("$rel")
      ;;
  esac
done

# Enforce function-based folders inside docs/ (allow docs/README.md for index)
while IFS= read -r path; do
  rel="${path#$ROOT_DIR/}"
  if [[ "$rel" != "docs/README.md" ]]; then
    ERRORS+=("$rel (expected under a function folder in docs/) ")
  fi
done < <(find "$ROOT_DIR/docs" -maxdepth 1 -mindepth 1 -type f -name '*.md')

if (( ${#ERRORS[@]} )); then
  echo "Found Markdown files outside the approved documentation layout:"
  for err in "${ERRORS[@]}"; do
    echo " - $err"
  done
  echo ""
  echo "Keep documentation Markdown inside function-specific folders under docs/. "
  echo "Exceptions: README.md at the repo root, tests/**, resources/markdown/**, .github/**, and .kiro/**."
  exit 1
fi

echo "Docs placement check passed."
