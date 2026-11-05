#!/usr/bin/env python3
"""EN: Package every bundled theme and the offline gallery without installed configuration.

HR: Pakira sve priložene teme i galeriju bez konfiguracije aktivne instalacije.
"""
import hashlib
import json
from pathlib import Path
import sys
import zipfile

root = Path(__file__).resolve().parents[1]
version = (root / "VERSION").read_text().strip()
output = Path(sys.argv[1]) if len(sys.argv) > 1 else root / "build" / f"Simbioza-{version}-themes.zip"
output.parent.mkdir(parents=True, exist_ok=True)
collection = json.loads((root / "resources/installation/theme/collection.json").read_text())
catalog = json.loads((root / "public/assets/theme-gallery/catalog.json").read_text())
ids = [entry["id"] for entry in collection["themes"]]
if len(ids) != 26 or set(ids) != {entry["id"] for entry in catalog}:
    raise ValueError("The collection and gallery must contain the same 26 themes.")
files = {}
for entry in collection["themes"]:
    source = root / "resources/installation/theme" / (entry["id"] + ".zip")
    if hashlib.sha256(source.read_bytes()).hexdigest() != entry["sha256"]:
        raise ValueError("Theme archive checksum mismatch: " + entry["id"])
    files["themes/" + source.name] = source
for source in (root / "public/assets/theme-gallery").iterdir():
    if source.is_file():
        files["gallery/" + source.name] = source
with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
    for name, source in sorted(files.items()):
        archive.write(source, name)
    archive.writestr("SHA256SUMS", "".join(hashlib.sha256(source.read_bytes()).hexdigest() + "  " + name + "\n" for name, source in sorted(files.items())))
    archive.writestr("README_en.md", "# Simbioza theme collection\n\n26 themes, each with light and dark appearances.\n\nOpen gallery/index.html to view all previews offline.\nExtract this collection, then import one themes/<id>.zip under Settings > Theme > Import complete theme. Do not upload the whole collection to the single-theme form. Upgrades add bundled themes once; deleted themes stay deleted until you explicitly import them.\n")
    archive.writestr("README_hr.md", "# Kolekcija tema Simbioze\n\n26 tema, svaka sa svijetlim i tamnim izgledom.\n\nOtvorite gallery/index.html za pregled svih slika bez interneta.\nRaspakirajte kolekciju pa u Postavke > Tema > Uvezi cijelu temu odaberite jedan themes/<id>.zip. Cijelu kolekciju nemojte učitati kao jednu temu. Nadogradnje dodaju teme jednom; izbrisana tema vraća se samo izričitim ručnim uvozom.\n")
with zipfile.ZipFile(output) as archive:
    if archive.testzip() is not None:
        raise ValueError("Corrupt collection ZIP.")
print(output)
