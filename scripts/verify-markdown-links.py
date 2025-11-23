#!/usr/bin/env python3
"""
Verify local Markdown links resolve to existing files.

Checks all Markdown files (excluding vendor/node_modules/etc.) for links that
point to other Markdown files within the repo and fails if any target is
missing.
"""

from __future__ import annotations

import re
import sys
import urllib.parse
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

SKIP_DIRS = {
    "node_modules",
    "vendor",
    "storage",
    "public",
    "bootstrap",
    ".git",
    ".idea",
    ".vscode",
    "vendor-bin",
}

LINK_RE = re.compile(r"\[[^\]]+\]\(([^)]+)\)")
SKIP_SCHEMES = {"http", "https", "mailto", "tel"}


def iter_markdown() -> list[Path]:
    files: list[Path] = []
    for path in ROOT.rglob("*.md"):
        if any(part in SKIP_DIRS for part in path.parts):
            continue
        files.append(path)
    return files


def should_check(href: str) -> bool:
    href = href.strip()
    if not href or href.startswith("#") or href.startswith("//"):
        return False

    parsed = urllib.parse.urlparse(href)
    if parsed.scheme in SKIP_SCHEMES:
        return False

    # We only validate links pointing to Markdown files.
    path = parsed.path
    return path.endswith(".md")


def resolve_target(current_file: Path, href: str) -> Path | None:
    parsed = urllib.parse.urlparse(href)
    path = urllib.parse.unquote(parsed.path)

    # Remove fragments and queries
    path = path.split("#", 1)[0].split("?", 1)[0]

    target = Path(path)
    if not target.is_absolute():
        target = (current_file.parent / target).resolve()
    else:
        target = (ROOT / target.relative_to("/")).resolve()

    try:
        target.relative_to(ROOT)
    except ValueError:
        return None

    return target


def main() -> int:
    errors: list[str] = []

    for md_file in iter_markdown():
        try:
            text = md_file.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            continue

        for match in LINK_RE.finditer(text):
            href = match.group(1)
            if not should_check(href):
                continue

            target = resolve_target(md_file, href)
            if target is None or not target.exists():
                rel = md_file.relative_to(ROOT)
                errors.append(f"{rel} -> {href}")

    if errors:
        print("Broken Markdown links found:")
        for err in errors:
            print(f" - {err}")
        print("\nFix or remove the broken links above.")
        return 1

    print("Markdown link check passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
