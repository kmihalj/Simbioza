#!/usr/bin/env python3
"""EN: Compose the current gallery from existing native Theme preview screenshots.

HR: Složi aktualnu galeriju iz postojećih snimki izvornog pregleda modula Tema.
"""
from __future__ import annotations

import argparse
import html
import json
from pathlib import Path
import shutil

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]


def publish(module_docs: Path | None) -> None:
    """EN: Preserve individual renders; rebuild contact sheets and the offline index.

    HR: Čuva pojedinačne snimke; obnavlja pregledne slike i indeks za rad bez mreže.
    """
    public = ROOT / "public/assets/theme-gallery"
    catalog = json.loads((public / "catalog.json").read_text())
    collection = json.loads((ROOT / "resources/installation/theme/collection.json").read_text())
    ids = {entry["id"] for entry in collection["themes"]}
    if not ids or ids != {entry["id"] for entry in catalog} or len(ids) != len(catalog):
        raise ValueError("Gallery catalog must exactly match the theme collection.")
    font_candidates = ["/System/Library/Fonts/Supplemental/Arial.ttf", "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"]
    font_path = next((p for p in font_candidates if Path(p).is_file()), None)
    if font_path is None:
        raise ValueError("A local Arial or DejaVu Sans font is required to compose contact sheets.")
    font = ImageFont.truetype(font_path, 30)
    hr_noun = "teme" if len(catalog) % 100 not in range(11, 15) and len(catalog) % 10 in [2, 3, 4] else "tema"
    for language in ["hr", "en"]:
        for mode in ["light", "dark"]:
            contact = Image.new("RGB", (1480, ((len(catalog) + 1) // 2) * 550 + 100), "#f4f5f7" if mode == "light" else "#101724")
            draw = ImageDraw.Draw(contact)
            ink = "#17243b" if mode == "light" else "#f1f5ff"
            title = f'Simbioza · {len(catalog)} {hr_noun} · {"svijetli" if mode == "light" else "tamni"} izgled' if language == "hr" else f"Simbioza · {len(catalog)} themes · {mode} appearance"
            draw.text((24, 24), title, font=font, fill=ink)
            for index, entry in enumerate(catalog):
                source = public / f'{entry["id"]}-{mode}-{language}.webp'
                with Image.open(source) as image:
                    thumb = image.convert("RGB").resize((704, 489), Image.Resampling.LANCZOS)
                x = 24 + (index % 2) * 736
                y = 100 + (index // 2) * 550
                draw.text((x, y), entry["label"].get(language, entry["label"].get("en", entry["id"])), font=font, fill=ink)
                contact.paste(thumb, (x, y + 40))
            contact.save(public / f"overview-{mode}-{language}.webp", "WEBP", quality=85, method=6)
    css = "body{font:16px/1.5 system-ui,sans-serif;background:#f2f5f8;color:#142b40;margin:0}main{max-width:1500px;margin:auto;padding:2rem}header{display:flex;gap:1rem;align-items:center;flex-wrap:wrap}select{font:inherit;padding:.4rem}article{background:white;border:1px solid #bccbd6;border-radius:12px;padding:1rem;margin:2rem 0}.pair{display:grid;grid-template-columns:1fr 1fr;gap:1rem}img{width:100%;height:auto;border:1px solid #d0d9df}figure{margin:0}a:focus-visible,select:focus-visible{outline:3px solid #265dcc;outline-offset:3px}@media(max-width:700px){.pair{grid-template-columns:1fr}}[hidden]{display:none!important}"
    parts = []
    for entry in catalog:
        parts.append('<article><h2>' + html.escape(entry["label"].get("en", entry["id"])) + '</h2><div class="pair">')
        for mode in ["light", "dark"]:
            parts.append("<figure>")
            for language in ["en", "hr"]:
                file = f'{entry["id"]}-{mode}-{language}.webp'
                parts.append(f'<a data-lang="{language}" href="{file}"' + (' hidden' if language == "hr" else '') + f'><img loading="lazy" width="1440" height="1000" alt="{html.escape(entry["label"].get(language, entry["id"]))} · {mode}" src="{file}"></a>')
            parts.append('<figcaption>' + ('☀ Light / Svijetla' if mode == "light" else '☾ Dark / Tamna') + '</figcaption></figure>')
        parts.append("</div></article>")
    index = '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Simbioza · Theme gallery / Galerija tema</title><style>' + css + '</style><main><header><h1>Simbioza · Theme gallery / Galerija tema</h1><label>Language / Jezik <select id="language"><option value="en">English</option><option value="hr">Hrvatski</option></select></label></header>'
    index += f'<p>{len(catalog)} themes · {len(catalog) * 2} light/dark previews per language. / {len(catalog)} {hr_noun} · {len(catalog) * 2} svijetla/tamna pregleda po jeziku.</p>'
    index += ''.join(parts) + '</main><script>const select=document.querySelector("select");function language(){document.documentElement.lang=select.value;document.querySelectorAll("[data-lang]").forEach(e=>e.hidden=e.dataset.lang!==select.value)}select.value=new URLSearchParams(location.search).get("lang")==="hr"?"hr":"en";select.addEventListener("change",language);language();</script></html>'
    (public / "index.html").write_text(index)
    destinations = [ROOT / "docs/images/theme-gallery"]
    if module_docs is not None:
        destinations.append(module_docs)
    for destination in destinations:
        destination.mkdir(parents=True, exist_ok=True)
        for source in public.glob("*.webp"):
            shutil.copy2(source, destination / source.name)
        shutil.copy2(public / "catalog.json", destination / "catalog.json")
    print(f"Composed {len(catalog)} themes, {len(catalog) * 4} unchanged previews and four contact sheets.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--theme-module-docs", type=Path)
    publish(parser.parse_args().theme_module_docs)
