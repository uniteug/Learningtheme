#!/usr/bin/env python3
"""Génère les versions .min.css des feuilles du thème et du plugin.

Usage : python3 bin/minify-css.py
Minification prudente (commentaires, espaces) : aucune règle n'est réécrite.
"""
import pathlib
import re

ROOT = pathlib.Path(__file__).resolve().parent.parent
TARGETS = [
    ROOT / "learn-drive" / "assets" / "css",
    ROOT / "learn-drive" / "assets" / "css" / "blocks",
    ROOT / "learn-drive-core" / "assets" / "css",
]


def minify(css: str) -> str:
    css = re.sub(r"/\*.*?\*/", "", css, flags=re.S)
    # Protège le contenu des chaînes (url data:, content:) avant de compacter.
    strings = []

    def keep(match):
        strings.append(match.group(0))
        return f"\x00{len(strings) - 1}\x00"

    css = re.sub(r'"(?:\\.|[^"\\])*"|\'(?:\\.|[^\'\\])*\'', keep, css)
    css = re.sub(r"\s+", " ", css)
    css = re.sub(r"\s*([{};,>])\s*", r"\1", css)
    # Pas d'espace supprimé AVANT « : » : « .a :where(b) » ≠ « .a:where(b) ».
    css = re.sub(r":\s+", ":", css)
    css = re.sub(r";}", "}", css)
    css = re.sub(r"\x00(\d+)\x00", lambda m: strings[int(m.group(1))], css)
    return css.strip() + "\n"


for folder in TARGETS:
    for path in sorted(folder.glob("*.css")):
        if path.name.endswith(".min.css"):
            continue
        out = path.with_name(path.stem + ".min.css")
        out.write_text(minify(path.read_text(encoding="utf-8")), encoding="utf-8")
        print(f"{out.relative_to(ROOT)}  {path.stat().st_size} → {out.stat().st_size} octets")
