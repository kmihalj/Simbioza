#!/usr/bin/env python3
"""EN: Build eight optional native themes from local, original artwork.

HR: Izrađuje osam opcionalnih izvornih tema s lokalnim, izvornim ilustracijama.
"""

from __future__ import annotations

import argparse
import hashlib
import json
from pathlib import Path
import shutil

from generate_theme_collection import ROOT, MODULE, build, contrast, readable, write_json


# EN: Each hero uses five independent colour stops; existing themes are never edited.
# HR: Svaki hero koristi pet neovisnih boja prijelaza; postojeće se teme ne mijenjaju.
PROFILES = [
    dict(id="stadion", name="Stadion", style="sports", font="system", menu="system",
         size="medium", radius="small", wave=False, nav="hero", width="xxl", accent="top", shadow=True,
         hr="Atletska staza u zalasku sunca, plava, koraljna i tirkizna. Za sportske klubove i natjecanja.",
         en="A running track at sunset with blue, coral and teal. For sports clubs and competitions.",
         light="F0F4FA FFFFFF 172C43 53667C B2C2D4 1856B4 A63745 143575 146471",
         dark="0B1423 14243A E5EFFB ADC1DA 466482 8BB9FF FFABA8 14274F 164A55",
         light_stops="143575 34449B 7C3B7D A13E4D 146471",
         dark_stops="0D204A 2E285D 512D63 6C3046 10454C", title="hero", inner=True, height=296, rotation=-2),
    dict(id="ucionica", name="Učionica", style="education", font="humanist", menu="humanist",
         size="medium", radius="small", wave=False, nav="standalone", width="xxl", accent="start", shadow=False,
         hr="Knjige, geometrija i svemir u kolažu od papira. Vedra tema za škole, nastavnike i učenje.",
         en="Books, geometry and space in a paper collage. An optimistic theme for schools, teachers and learning.",
         light="F3F5FA FFFFFD 19344D 526A80 A9BDCA 245BA8 9C4E24 FFF0D1 A4DADD",
         dark="101B2B 1A2B3F EAF2FA AFC5D9 54728C A0C9FF F0C28D 1A2A49 335C66",
         light_stops="FFF0D1 FFE0BE F5C7CF C7C8ED A4DADD",
         dark_stops="1A2A49 2B3557 493957 494A62 335C66", title="content-card", inner=False, height=296, rotation=1),
    dict(id="mali-svijet", name="Mali svijet", style="preschool", font="rounded", menu="rounded",
         size="medium", radius="medium", wave=True, nav="standalone", width="xxl", accent="top", shadow=True,
         hr="Sunce, duga i kućice od papira, nježne boje i zaobljene kartice. Za vrtiće i obiteljske zajednice.",
         en="A paper sun, rainbow and little houses, gentle colours and rounded cards. For preschools and family communities.",
         light="FFF7E8 FFFDF8 41382D 756252 BDAA8D 376D64 A04C48 FFF0CB C5E5CD",
         dark="221D28 342C3A F8EEE5 CDBBAC 806E82 B8DFCF F8BDB5 35304F 355A53",
         light_stops="FFF0CB FFE1C6 F4CDDF DFD3F3 C5E5CD",
         dark_stops="35304F 533C58 5B3F56 4B4E68 355A53", title="content-card", inner=False, height=296, rotation=-2),
    dict(id="kinoteka", name="Kinoteka", style="cinema", font="serif", menu="humanist",
         size="medium", radius="small", wave=False, nav="hero", width="xxl", accent="start", shadow=True,
         hr="Filmska vrpca, zlatna svjetlost projektora i ljubičasto noćno kino. Za film, kulturu i priče.",
         en="A film ribbon, a golden projector beam and violet cinema nights. For film, culture and stories.",
         light="F5F0E8 FFFCF7 322A37 6F606E BCA9B9 6B3B80 90601D 251D49 723F40",
         dark="14111E 241C30 F6E9EF CBB6CD 765E83 CBB3F5 F0CC86 1C1938 59343E",
         light_stops="251D49 3B285D 5A3268 793F56 723F40",
         dark_stops="1C1938 2E204B 42284F 573047 59343E", title="hero", inner=True, height=296, rotation=-3),
    dict(id="manga", name="Manga", style="creative", font="system", menu="system",
         size="medium", radius="none", wave=False, nav="standalone", width="wide", accent="start", shadow=True,
         hr="Izvorni anime junak, stripovski kadrovi, raster i odvažne boje. Za strip, igre i kreativne klubove.",
         en="An original anime adventurer, comic panels, halftones and bold colour. For comics, games and creative clubs.",
         light="F5F1E6 FFFFFA 202A45 62647B A5A7B7 224DAE A83769 FFF3A4 BFBCFF",
         dark="131527 24253C F7F0E2 C8C3DB 646583 A6C4FF FFADD2 201552 5A4592",
         light_stops="FFF3A4 FFD98C FFBAAD F3ABD9 BFBCFF",
         dark_stops="201552 392067 6B275E 82404A 5A4592", title="hero", inner=True, height=296, rotation=2),
    dict(id="atelje", name="Atelje", style="art", font="serif", menu="humanist",
         size="medium", radius="none", wave=False, nav="standalone", width="xl", accent="start", shadow=False,
         hr="Akvarel i gvaš, topli papir i potezi kobalta, koralja i magente. Za likovne radionice i umjetničke portale.",
         en="Watercolour and gouache, warm paper and cobalt, coral and magenta strokes. For art workshops and creative journals.",
         light="FAF3E8 FFFDF7 3B3042 786372 C7ACBA 6C3C98 A34640 FFE9CC AFCFEC",
         dark="221C2A 342A3B F9ECDF CBB4C8 826789 D4B5F4 F4B999 342443 344E6A",
         light_stops="FFE9CC FFCCB5 F4B7D0 D2B8EB AFCFEC",
         dark_stops="342443 56334D 6B355D 513E75 344E6A", title="content-card", inner=False, height=296, rotation=-2),
    dict(id="planina", name="Planina", style="outdoors", font="humanist", menu="humanist",
         size="medium", radius="small", wave=True, nav="hero", width="xxl", accent="top", shadow=False,
         hr="Planinsko jezero u svitanje, šumska zelena i boje stijene. Za planinare, izlete i sport na otvorenom.",
         en="A mountain lake at dawn, forest greens and stone colours. For hiking, excursions and outdoor sport.",
         light="EFF4EE FDFEF9 283C34 5B7066 A3B8AD 2F6656 915335 113E49 795047",
         dark="0E1C1B 1C2D2A E8F2E7 B6CCBF 4E7368 A7D9C4 F2BF99 102F3B 573C3B",
         light_stops="113E49 135E64 326052 655A43 795047",
         dark_stops="102F3B 174444 264D42 494735 573C3B", title="hero", inner=True, height=296, rotation=1),
    dict(id="festival", name="Festival", style="creative", font="rounded", menu="system",
         size="medium", radius="medium", wave=True, nav="hero", width="wide", accent="top", shadow=True,
         hr="Vrpce u pet boja, konfeti i živahan prijelaz od ljubičaste do tirkizne. Za događanja, glazbu i zajednice.",
         en="Five-colour ribbons, confetti and a lively violet-to-teal gradient. For events, music and communities.",
         light="F5EFFB FFFFFF 322340 6D587C BFA4CE 7441A1 A13B59 49309C 12656D",
         dark="181022 2B1B38 F6EAFF CEB4E0 7F5897 D9ABFF FFB2CB 30205F 104C56",
         light_stops="49309C 803A9A A43E72 9F4C43 12656D",
         dark_stops="30205F 562866 6F2D59 713B3A 104C56", title="content-card", inner=True, height=296, rotation=-4),
]

LABELS = {
    "stadion": ["Stadion", "Stadium", "Stadion", "Stade", "Stadio", "Estadio"],
    "ucionica": ["Učionica", "Classroom", "Klassenzimmer", "Salle de classe", "Aula", "Aula"],
    "mali-svijet": ["Mali svijet", "Little World", "Kleine Welt", "Petit Monde", "Piccolo Mondo", "Pequeño Mundo"],
    "kinoteka": ["Kinoteka", "Cinema Club", "Filmklub", "Cinémathèque", "Cineteca", "Cineclub"],
    "manga": ["Manga"] * 6,
    "atelje": ["Atelje", "Atelier", "Atelier", "Atelier", "Atelier", "Taller"],
    "planina": ["Planina", "Mountain", "Bergwelt", "Montagne", "Montagna", "Montaña"],
    "festival": ["Festival"] * 6,
}


def festival_art(mode: str) -> str:
    """EN: Draw original static celebration ribbons without scripts or text.

    HR: Crta izvorne statične svečane vrpce bez skripti i teksta.
    """
    colors = ["#FFE68E", "#FF9FAD", "#B8A7FF", "#8FE3E2", "#FFC3EC"]
    elements = []
    for index, color in enumerate(colors):
        y = 85 + index * 65
        elements.append(f'<path d="M90 {y+80}C240 {y-100} 380 {y+270} 540 {y+65}S750 {y-70} 840 {y+40}" fill="none" stroke="{color}" stroke-width="34" stroke-linecap="round"/>')
    for x, y, radius in [(160, 75, 11), (750, 420, 14), (805, 115, 9), (250, 470, 8)]:
        elements.append(f'<circle cx="{x}" cy="{y}" r="{radius}" fill="{colors[(x+y)%5]}"/>')
    for x, y in [(95, 350), (680, 65), (530, 480)]:
        elements.append(f'<path d="M{x-10} {y}h20M{x} {y-10}v20" fill="none" stroke="#FFF1C2" stroke-width="5"/>')
    return '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600" viewBox="0 0 900 600">' + "".join(elements) + '</svg>\n'


def main() -> None:
    """EN: Build source themes and manifests without touching active HFC settings.

    HR: Izrađuje izvore tema i manifeste bez izmjene aktivnih postavki HFC-a.
    """
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=ROOT / "output/playwright/themes-20261105")
    args = parser.parse_args()
    art = ROOT / "resources/theme-collection/creative/artwork"
    base = next(t for t in json.loads((MODULE / "resources/theme/themes.json").read_text()) if t["id"] == "standard")
    prompts = json.loads((art / "prompts.json").read_text())
    catalog = []
    for profile in PROFILES:
        theme = build(profile, base)
        theme["label"] = dict(zip(("hr", "en", "de", "fr", "it", "es"), LABELS[profile["id"]]))
        hero = theme["components"]["hero"]
        hero.update(home_size="medium", inner_enabled=profile["inner"], content_min_height_px=profile["height"],
                    content_padding_top_px=16, content_padding_bottom_px=24,
                    visual_width_px=320 if profile["wave"] else 350,
                    visual_max_height_px=190 if profile["wave"] else 210, visual_top_px=0,
                    visual_rotation_deg=profile["rotation"])
        theme["components"]["content"].update(inner_title_placement=profile["title"],
                                             application_title_placement=profile["title"],
                                             inner_hero_overlap="small" if profile["inner"] else "none")
        assets = []
        root = args.output / "source" / theme["id"]
        destination = root / "assets"
        destination.mkdir(parents=True, exist_ok=True)
        if profile["id"] == "festival":
            for mode in ("light", "dark"):
                file = destination / f"hero-{mode}.svg"
                file.write_text(festival_art(mode))
                hero[f"visual_src_{mode}"] = f"@theme-assets/{theme['id']}/{file.name}"
        else:
            shutil.copyfile(art / (profile["id"] + ".png"), destination / "hero.png")
            hero.update(visual_src_light=f"@theme-assets/{theme['id']}/hero.png",
                        visual_src_dark=f"@theme-assets/{theme['id']}/hero.png")
        for mode in ("light", "dark"):
            shutil.copyfile(ROOT / f"data/themes/simbioza/assets/icon-natural-{mode}.png", destination / f"icon-{mode}.png")
            colors = theme[mode]["colors"]
            stops = ["#" + color for color in profile[mode + "_stops"].split()]
            foreground = readable(colors["hero_title"], stops, 4.8)
            for role in ("hero_text", "hero_title", "hero_subtitle", "hero_muted"):
                colors[role] = foreground
            for index, color in enumerate(stops, 1):
                colors[f"hero_gradient_{index}"] = color
            # EN: Embedded menu text must contrast with every stop, including wrapped menus.
            # HR: Tekst ugrađenog menija mora imati kontrast sa svakom bojom, i u više redaka.
            if profile["nav"] == "hero":
                for role in ("navbar_text", "navbar_link", "navbar_active"):
                    colors[role] = foreground
            assert all(contrast(foreground, color) >= 4.8 for color in stops)
        for file in sorted(destination.iterdir()):
            is_svg = file.suffix == ".svg"
            role = "hero" if file.name.startswith("hero") else "icon"
            assets.append(dict(file=file.name, role=role,
                               label={"hr":profile["name"] + " — " + ("zaglavlje" if role == "hero" else "znak Simbioze"),
                                      "en":profile["name"] + " — " + ("hero" if role == "hero" else "Simbioza mark")},
                               mime="image/svg+xml" if is_svg else "image/png",
                               width=900 if is_svg else 1536 if role == "hero" else 512,
                               height=600 if is_svg else 1024 if role == "hero" else 512,
                               bytes=file.stat().st_size, sha256=hashlib.sha256(file.read_bytes()).hexdigest()))
        write_json(root / "theme-assets.json", {"version":1, "assets":assets})
        write_json(root / "source/artwork.json", {"generator":"image_gen built-in tool" if profile["id"] != "festival" else "native SVG",
                                                 "images":[image for image in prompts["images"] if image["id"] == profile["id"]]})
        write_json(args.output / "definitions" / f"{theme['id']}.json", theme)
        catalog.append(dict(id=theme["id"], name=profile["name"], style=profile["style"],
                            description={"hr":profile["hr"], "en":profile["en"]}, theme=theme))
    write_json(args.output / "catalog.json", {"format":"simbioza-theme-collection", "version":1, "themes":catalog})
    print(f"Built {len(catalog)} themes with original local artwork and two native palettes each.")


if __name__ == "__main__":
    main()
