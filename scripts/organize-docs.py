#!/usr/bin/env python3
"""
Organize Markdown docs: move stray files into docs/ folders and update links.
"""

from __future__ import annotations

import argparse
import os
import pathlib
import shutil
import sys
from typing import Dict, Iterable, List, Sequence, Tuple

ROOT = pathlib.Path(__file__).resolve().parent.parent

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

ALLOWED_ROOT_FILES = {ROOT / "README.md"}
ALLOWED_PREFIXES = {
    ROOT / "docs",
    ROOT / "tests",
    ROOT / "resources" / "markdown",
    ROOT / ".github",
    ROOT / ".kiro",
}

TEXT_SUFFIXES = {
    ".md",
    ".mdx",
    ".php",
    ".blade.php",
    ".js",
    ".ts",
    ".tsx",
    ".json",
    ".yaml",
    ".yml",
    ".neon",
    ".xml",
    ".html",
    ".css",
    ".scss",
    ".txt",
}


def should_skip(path: pathlib.Path) -> bool:
    return any(part in SKIP_DIRS for part in path.parts)


def is_allowed(path: pathlib.Path) -> bool:
    if path in ALLOWED_ROOT_FILES:
        return True
    return any(prefix in path.parents or path == prefix for prefix in ALLOWED_PREFIXES)


def find_markdown() -> Iterable[pathlib.Path]:
    for path in ROOT.rglob("*.md"):
        if should_skip(path):
            continue
        yield path


def find_stray_markdown() -> List[pathlib.Path]:
    return [path for path in find_markdown() if not is_allowed(path)]


def guess_target_folder(filename: str) -> pathlib.Path:
    name = filename.upper()
    patterns: Sequence[Tuple[Sequence[str], str]] = (
        (("NEWS", "SECURITY"), "docs/news/security"),
        (("NEWS",), "docs/news"),
        (("ADMIN",), "docs/admin"),
        (("COMMENT",), "docs/comments"),
        (("DESIGN", "TOKEN"), "docs/design-tokens"),
        (("ACCESS",), "docs/accessibility"),
        (("INTERFACE",), "docs/interface"),
        (("OPTIMISTIC",), "docs/optimistic-ui"),
        (("SCOPE",), "docs/query-scopes"),
        (("SECURITY",), "docs/security"),
        (("VALIDATION",), "docs/validation"),
        (("REQUEST",), "docs/validation"),
        (("ROUTE",), "docs/routing"),
        (("UI",), "docs/ui-ux"),
        (("UX",), "docs/ui-ux"),
        (("TAILWIND",), "docs/ui-ux"),
        (("MODAL",), "docs/ui-ux"),
        (("PROJECT",), "docs/project"),
        (("PLAN",), "docs/planning"),
        (("TODO",), "docs/planning"),
        (("TASK",), "docs/planning"),
        (("DOCUMENTATION",), "docs/documentation"),
        (("I18N",), "docs/i18n"),
        (("LOCALE",), "docs/i18n"),
        (("LIVEWIRE",), "docs/livewire"),
        (("VOLT",), "docs/volt"),
        (("API",), "docs/api"),
    )
    for keywords, folder in patterns:
        if all(keyword in name for keyword in keywords):
            return ROOT / folder
    return ROOT / "docs/misc"


def move_stray_files(stray_files: Sequence[pathlib.Path]) -> List[Tuple[pathlib.Path, pathlib.Path]]:
    moves: List[Tuple[pathlib.Path, pathlib.Path]] = []
    for source in stray_files:
        target_dir = guess_target_folder(source.stem)
        target_dir.mkdir(parents=True, exist_ok=True)
        destination = target_dir / source.name
        if destination.exists():
            raise SystemExit(
                f"Refusing to overwrite existing file: {destination}. "
                f"Resolve the conflict and rerun the command."
            )
        shutil.move(str(source), str(destination))
        moves.append((source, destination))
    return moves


def iter_text_files() -> Iterable[pathlib.Path]:
    for path in ROOT.rglob("*"):
        if not path.is_file():
            continue
        if should_skip(path):
            continue
        if any(str(path).endswith(suffix) for suffix in TEXT_SUFFIXES):
            yield path


def update_links(moves: Sequence[Tuple[pathlib.Path, pathlib.Path]]) -> List[pathlib.Path]:
    if not moves:
        return []

    updated: List[pathlib.Path] = []
    mapping: Dict[str, pathlib.Path] = {
        move[0].relative_to(ROOT).as_posix(): move[1] for move in moves
    }

    for file_path in iter_text_files():
        try:
            text = file_path.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            continue

        original = text
        for old_rel, new_abs in mapping.items():
            new_rel = pathlib.Path(os.path.relpath(new_abs, file_path.parent)).as_posix()
            old_rel_from_file = pathlib.Path(
                os.path.relpath(ROOT / old_rel, file_path.parent)
            ).as_posix()

            candidates = {
                old_rel,
                f"./{old_rel}",
                old_rel_from_file,
                f"./{old_rel_from_file}",
            }

            for needle in candidates:
                if needle in text:
                    text = text.replace(needle, new_rel)

        if text != original:
            file_path.write_text(text, encoding="utf-8")
            updated.append(file_path)

    return updated


def ensure_docs_root_only_index() -> None:
    docs_root = ROOT / "docs"
    root_md = [p for p in docs_root.glob("*.md") if p.name != "README.md"]
    if root_md:
        paths = "\n".join(f" - {p.relative_to(ROOT)}" for p in root_md)
        raise SystemExit(
            "Docs root should only contain docs/README.md. Move these files into function folders:\n"
            f"{paths}"
        )


def main() -> None:
    parser = argparse.ArgumentParser(description="Organize Markdown documentation.")
    parser.add_argument("--fix", action="store_true", help="Move stray files and update links.")
    args = parser.parse_args()

    stray = find_stray_markdown()
    if args.fix and stray:
        print(f"Moving {len(stray)} Markdown file(s) into docs/... folders")
        moves = move_stray_files(stray)
        updated = update_links(moves)
        if updated:
            print(f"Updated links in {len(updated)} file(s)")
    elif stray:
        raise SystemExit(
            f"Found {len(stray)} Markdown file(s) outside docs/. "
            "Re-run with --fix to move them automatically."
        )
    else:
        print("No stray Markdown files found.")

    ensure_docs_root_only_index()

    if args.fix:
        stray_after = find_stray_markdown()
        if stray_after:
            remaining = "\n".join(f" - {p.relative_to(ROOT)}" for p in stray_after)
            raise SystemExit(
                "Some Markdown files are still outside docs/ after attempting to fix:\n"
                f"{remaining}"
            )

    print("Docs verification complete.")


if __name__ == "__main__":
    main()
