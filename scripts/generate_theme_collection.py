#!/usr/bin/env python3
"""EN: Build twelve optional, portable Simbioza themes using native Theme settings.
HR: Izrađuje dvanaest opcionalnih, prenosivih tema izvornim postavkama modula Theme.
"""

from __future__ import annotations

import argparse
import copy
import hashlib
import json
from pathlib import Path
import shutil

ROOT = Path(__file__).resolve().parents[1]
MODULE = ROOT / "vendor/aaieduhr/heartphrame-module-theme"
FONTS = {
    "system": 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
    "rounded": 'ui-rounded, "SF Pro Rounded", system-ui, -apple-system, BlinkMacSystemFont, sans-serif',
    "humanist": 'Aptos, "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif',
    "serif": 'Georgia, "Times New Roman", serif',
    "mono": '"SFMono-Regular", Consolas, "Liberation Mono", monospace',
}

# EN: Palette order: page, surface, ink, muted, border, primary, accent, hero start/end.
# HR: Redoslijed palete: stranica, ploha, tekst, pomoćni tekst, obrub, primarna, akcent, hero početak/kraj.
PROFILES = [
    dict(id="aurora", name="Aurora", style="modern", font="system", menu="system", art="aurora", size="large", radius="medium", wave=True, nav="hero", width="xxl", accent="none", shadow=True,
         hr="Sjeverno svjetlo, hladni plavi tonovi i prozračne kartice. Za moderne portale i timsko znanje.",
         en="Northern lights, cool blues and airy cards. For contemporary portals and team knowledge.",
         light="F1F6FB FFFFFF 152B43 52677C CBD8E5 245AC4 087A72 122553 086574",
         dark="0A1424 122239 E4EEFA A8BED5 365373 7DAAFF 60DAC7 0A1732 06454D", colors="84F4DA 7DAAFF C1BCFF E8FFFA"),
    dict(id="folio", name="Folio", style="editorial", font="serif", menu="humanist", art="folio", size="medium", radius="none", wave=False, nav="standalone", width="xl", accent="start", shadow=False,
         hr="Topao novinski papir, vinsko crveni naglasci i serifna tipografija. Za časopise, priče i osobne blogove.",
         en="Warm newsprint, wine-red accents and serif typography. For magazines, stories and personal blogs.",
         light="F3EEE5 FFFCF5 29251F 6C6055 C5B9A8 8D3040 725A31 F5E8D5 E7D2B7",
         dark="1B1917 28231F F4EADF BFB09E 6A5D50 EBB09F DBC08D 28211D 40312A", colors="8D3040 B69A6B 384D4A FFFCF5"),
    dict(id="neon-arcade", name="Neon Arcade", style="gaming", font="system", menu="mono", art="arcade", size="large", radius="none", wave=False, nav="hero", width="wide", accent="top", shadow=True,
         hr="Neonski cijan, ljubičasta i geometrijski gamepad. Za gaming zajednice, turnire i digitalne klubove.",
         en="Neon cyan, violet and a geometric gamepad. For gaming communities, tournaments and digital clubs.",
         light="F1EDFF FFFFFF 251B45 655581 B6A8D0 6034C4 087D78 21113E 39206A",
         dark="0D0919 19112D F2ECFF BDB0D8 594078 BC9CFF 52EBDD 160D2B 321258", colors="60F6DD B891FF FF8DC7 F8F1FF"),
    dict(id="terminal-84", name="Terminal 84", style="retro", font="mono", menu="mono", art="terminal", size="compact", radius="none", wave=False, nav="standalone", width="wide", accent="start", shadow=False, compact=True,
         hr="Monospace, fosforno zelena i jasni četvrtasti obrubi. Retro računalni stil za tehničke timove.",
         en="Monospace, phosphor green and crisp square borders. A retro computing style for technical teams.",
         light="EDF2E8 F9FCF6 153425 49624E 91A68C 246A39 775A18 0A2418 173E26",
         dark="07110B 101F15 D8F3D9 9ABA9D 41674A 9FE7A7 E4C775 08170E 122D1A", colors="A9F4A8 72BA83 EBD987 E3FFE1"),
    dict(id="cassette", name="Cassette Sunset", style="retro", font="rounded", menu="rounded", art="cassette", size="large", radius="medium", wave=True, nav="hero", width="xxl", accent="top", shadow=True,
         hr="Zalazak sunca, kazete i zaobljene linije sedamdesetih. Za glazbu, kreativne zajednice i nostalgične projekte.",
         en="Sunsets, cassette tapes and rounded seventies lines. For music, creative communities and nostalgic projects.",
         light="F9EDDD FFFAF1 422B26 795D4E D0B69C A4492D 5F7050 6A302B 9B4B2B",
         dark="211411 35221B F8E7CE CDB299 84614B F3B38E C7D39D 311A18 693326", colors="F2C66C F59565 EEC8A1 9CC1A1"),
    dict(id="botanika", name="Botanika", style="nature", font="humanist", menu="humanist", art="botanical", size="large", radius="medium", wave=True, nav="standalone", width="xxl", accent="start", shadow=False,
         hr="Kadulja, šumska zelena i nacrtani listovi. Miran prostor za obrazovanje, održivost i zajednice.",
         en="Sage, forest green and illustrated leaves. A calm space for education, sustainability and communities.",
         light="EFF3E9 FDFEF8 233B2B 5C7059 B0BDA5 315F43 8A5F20 214A37 41644B",
         dark="101B15 1C2B20 E7F0DE B3C2A8 526A51 A3D4A4 E3C18C 142D21 2C4D38", colors="C3DDA4 89B99C F0D49A E7EED2"),
    dict(id="jadran", name="Jadran", style="coastal", font="humanist", menu="serif", art="adriatic", size="large", radius="small", wave=True, nav="hero", width="xxl", accent="top", shadow=True,
         hr="Jadransko plavetnilo, kamen i jedra. Svježa tema za udruge, putovanja i otvorene portale.",
         en="Adriatic blue, limestone and sails. A fresh theme for associations, travel and public portals.",
         light="F2F6F8 FFFFFC 173F52 537080 BDCCD2 146292 A95332 123F71 116F80",
         dark="0A1C29 132E3C E3F0F2 ABC6CF 3F6474 8CCEF2 F5BB98 0B2A4D 105464", colors="BDECF1 F9EBD3 F6BB7E 84C8E0"),
    dict(id="blueprint", name="Blueprint", style="technical", font="humanist", menu="mono", art="blueprint", size="medium", radius="none", wave=False, nav="standalone", width="wide", accent="start", shadow=False, compact=True,
         hr="Tehnički nacrt, mreža i indigo. Precizna tema za dokumentaciju, inženjerstvo i istraživačke projekte.",
         en="Technical drawings, grid lines and indigo. A precise theme for documentation, engineering and research projects.",
         light="EDF3F8 FFFFFF 193851 516D83 B6C8D7 285C9C 80621C 163962 1D4D7C",
         dark="0C1929 16293D E1EDF8 ACC4DA 44637E 93C6F4 E9CD8A 10233E 183F64", colors="A9D4FA E9CC85 E1F3FF 76ADD5"),
    dict(id="sakura", name="Sakura", style="calm", font="rounded", menu="rounded", art="sakura", size="medium", radius="medium", wave=False, nav="standalone", width="xl", accent="none", shadow=True,
         hr="Ružičasti papir, šljiva i cvjetovi trešnje. Nježna tema za osobne bilješke, radionice i kreativne blogove.",
         en="Blush paper, plum and cherry blossoms. A gentle theme for personal notes, workshops and creative blogs.",
         light="FAF2F4 FFFBFC 4C2F45 7D6174 D8BDC9 91446D 67528D F4DDE8 E9D9EF",
         dark="251722 382334 F5E8F0 CDB1C4 856079 E7A3C5 C3B2EE 301B2B 493050", colors="A7547D D9A0BB 78688F FFF7FA"),
    dict(id="pop-studio", name="Pop Studio", style="creative", font="humanist", menu="humanist", art="pop", size="large", radius="none", wave=False, nav="standalone", width="wide", accent="start", shadow=False,
         hr="Kobalt, žuta i razigrani geometrijski oblici. Grafički snažna tema za dizajn, događanja i kreativne kolektive.",
         en="Cobalt, yellow and playful geometric shapes. A graphic theme for design, events and creative collectives.",
         light="F5F1E8 FFFFFB 20252F 62616B B8B7AF 2C49AA 9C3E37 F4DC67 F0BF63",
         dark="171B29 252B40 F1F0E9 B6BBCE 66718E A2B5FF F5C85D 29345E 4D3152", colors="2947AD E87962 242A38 FFFAE6"),
    dict(id="espresso", name="Espresso", style="blog", font="serif", menu="humanist", art="espresso", size="medium", radius="small", wave=False, nav="standalone", width="xl", accent="none", shadow=False,
         hr="Kava, bakar i dugi tekstovi u serifu. Urednička tema za dnevnike, eseje i stručne članke.",
         en="Coffee, copper and long-form serif text. An editorial theme for journals, essays and expert articles.",
         light="F3ECE2 FFFCF7 3C2C24 77604D C9B7A1 7F4931 6B6C3F EBD6BD D9BA98",
         dark="201A16 30261F F1E5D5 C4AE96 7D6651 E8B78F C9CE94 2C211A 4C3527", colors="7F4931 B58C60 7C8156 FFF8ED"),
    dict(id="nocturne", name="Nocturne", style="elegant", font="serif", menu="system", art="nocturne", size="large", radius="small", wave=False, nav="hero", width="xxl", accent="start", shadow=True,
         hr="Ponoćno plava, mjesečeve orbite i zlatni detalji. Elegantna tema za kulturne ustanove i posebne projekte.",
         en="Midnight blue, lunar orbits and gold details. An elegant theme for cultural institutions and special projects.",
         light="F0F1F4 FFFFFF 252D45 626B81 BEC4D1 475889 82652B 151E39 29334D",
         dark="0C111E 171F31 ECEEF5 AEB8D0 4E5D7A B3BFEA E9CE95 0C152A 202C46", colors="E8CD91 A6B6DF 7B92BD ECF0FF"),
]


def channels(color: str) -> list[float]:
    """EN: Decode an opaque RGB colour. HR: Dekodira neprozirnu RGB boju."""
    return [int(color.lstrip("#")[i:i + 2], 16) / 255 for i in (0, 2, 4)]


def mix(first: str, second: str, amount: float) -> str:
    """EN: Mix two opaque palette colours. HR: Miješa dvije neprozirne boje palete."""
    values = [round((a + (b - a) * amount) * 255) for a, b in zip(channels(first), channels(second))]
    return "#" + "".join(f"{x:02X}" for x in values)


def contrast(first: str, second: str) -> float:
    """EN: Calculate WCAG sRGB contrast. HR: Računa WCAG kontrast sRGB boja."""
    def luminance(color: str) -> float:
        values = [x / 12.92 if x <= .04045 else ((x + .055) / 1.055) ** 2.4 for x in channels(color)]
        return sum(x * y for x, y in zip(values, (.2126, .7152, .0722)))
    a, b = sorted((luminance(first), luminance(second)))
    return (b + .05) / (a + .05)


def readable(preferred: str, backgrounds: list[str], minimum: float = 4.5) -> str:
    """EN: Preserve a passing colour, otherwise increase its contrast. HR: Čuva dobru boju ili povećava kontrast."""
    if min(contrast(preferred, bg) for bg in backgrounds) >= minimum:
        return preferred
    target = max(("#111111", "#FFFFFF"), key=lambda c: min(contrast(c, bg) for bg in backgrounds))
    for step in range(1, 101):
        candidate = mix(preferred, target, step / 100)
        if min(contrast(candidate, bg) for bg in backgrounds) >= minimum:
            return candidate
    raise ValueError("Palette has incompatible backgrounds")


def palette(profile: dict, mode: str, defaults: dict) -> dict:
    """EN: Expand art direction to every native semantic colour. HR: Proširuje likovni smjer na sve izvorne semantičke boje."""
    bg, surface, text, muted, border, primary, accent, hero_a, hero_b = ["#" + x for x in profile[mode].split()]
    colors = copy.deepcopy(defaults)
    muted = readable(muted, [bg, surface], 4.6)
    link = readable(primary, [bg, surface], 4.8)
    hero = [mix(hero_a, hero_b, x / 4) for x in range(5)]
    hero_preferred = text if min(contrast(text, value) for value in hero) >= 5 else max(
        ("#F8FAFC", "#17212B"), key=lambda value: min(contrast(value, bg) for bg in hero))
    hero_text = readable(hero_preferred, hero, 5)
    # EN: Controls get strong boundaries; large decorative cards may remain quieter.
    # HR: Kontrole dobivaju jasne granice, a velike ukrasne kartice mirnije obrube.
    border = readable(border, [surface, bg], 3)
    alternate = mix(surface, bg, .65)
    tint = mix(surface, primary, .08 if mode == "light" else .13)
    action = readable(primary, [surface], 4.8)
    foreground = lambda value: max(("#111111", "#FFFFFF"), key=lambda fg: contrast(fg, value))
    colors.update(body_bg=bg, body_text=text, surface_bg=surface, surface_text=text, muted_text=muted,
                  border=border, primary=primary, secondary=accent, link=link,
                  card_bg=surface, card_text=text, card_border=border, card_accent=accent,
                  card_shadow="#000000" if mode == "dark" else mix(text, bg, .5),
                  title_supporting_text=muted, hero_text=hero_text, hero_title=hero_text,
                  hero_subtitle=hero_text, hero_muted=hero_text, focus_ring=link,
                  table_header_bg=tint, table_header_text=readable(text, [tint], 5),
                  table_row_bg=surface, table_row_text=text, table_row_alt_bg=alternate, table_border=border,
                  document_action_bg=surface, document_action_text=action, document_action_border=border,
                  document_action_hover_bg=primary, document_action_hover_text=foreground(primary),
                  document_action_active_bg=accent, document_action_active_text=foreground(accent),
                  badge_bg=primary, badge_text=foreground(primary), tag_bg=tint, tag_text=readable(primary, [tint]),
                  nav_default_bg=tint, nav_default_text=readable(text, [tint]), nav_border=border,
                  nav_active_bg=primary, nav_active_text=foreground(primary), nav_active_accent=accent,
                  nav_hover_bg=tint, left_menu_hover_bg=tint, left_menu_hover_text=readable(text, [tint]),
                  left_menu_active_bg=primary, left_menu_active_text=foreground(primary), special_left_menu_accent=accent,
                  pagination_bg=surface, pagination_text=link, pagination_active_bg=primary,
                  pagination_active_text=foreground(primary), breadcrumb_text=muted, breadcrumb_link=link,
                  editor_tab_bg=bg, editor_tab_text=muted, editor_tab_border=border,
                  editor_tab_hover_bg=tint, editor_tab_active_bg=surface, editor_tab_active_text=text,
                  editor_tab_active_accent=primary, editor_tab_panel_bg=surface, editor_tab_panel_text=text,
                  editor_accordion_header_bg=tint, editor_accordion_header_text=text,
                  editor_accordion_body_bg=surface, editor_accordion_body_text=text,
                  code_bg=tint, code_text=readable(accent, [tint]), blockquote_border=accent,
                  callout_bg=tint, callout_text=readable(text, [tint]), callout_border=border,
                  disabled_bg=alternate, disabled_text=readable(muted, [alternate]))
    for role in ("title", "content", "sidebar"):
        colors.update({f"{role}_card_bg": surface, f"{role}_card_text": text,
                       f"{role}_card_border": border, f"{role}_card_accent": primary if role != "sidebar" else accent})
    for state, value in dict(success="#277044" if mode == "light" else "#8AD0A6",
                             warning="#876000" if mode == "light" else "#F1CF7B",
                             danger="#B13948" if mode == "light" else "#F0A0AF",
                             info="#286A8A" if mode == "light" else "#9DD4EE").items():
        colors[state] = value
    colors.update(valid=colors["success"], invalid=colors["danger"])
    for state in ("primary", "secondary", "success", "warning", "danger", "info"):
        colors[state + "_text"] = foreground(colors[state])
    for state in ("primary", "success", "warning", "danger", "info"):
        alert_bg = mix(surface, colors[state], .10 if mode == "light" else .15)
        colors.update({f"alert_{state}_bg": alert_bg, f"alert_{state}_text": readable(colors[state], [alert_bg]),
                       f"alert_{state}_border": readable(colors[state], [alert_bg], 3)})
    nav_bg = hero_a if profile["nav"] == "hero" else surface
    nav_text = readable(hero_text if profile["nav"] == "hero" else text, [nav_bg])
    for i, value in enumerate(hero, 1):
        colors[f"hero_gradient_{i}"] = value
        colors[f"navbar_gradient_{i}"] = nav_bg
    colors.update(navbar_text=nav_text, navbar_link=nav_text, navbar_active=nav_text,
                  navbar_active_bg=mix(nav_bg, nav_text, .1))
    return colors


def artwork(profile: dict, mode: str) -> str:
    """EN: Draw original static vector artwork, with no scripts or remote assets. HR: Crta izvorne statične vektore bez skripti ili vanjskih resursa."""
    a, b, c, d = ["#" + x for x in profile["colors"].split()]
    if mode == "dark" and profile["id"] in ("folio", "sakura", "pop-studio", "espresso"):
        a, b, c, d = mix(a, "#FFFFFF", .55), mix(b, "#FFFFFF", .15), mix(c, "#FFFFFF", .5), "#F4EADF"
    style = profile["art"]
    elements = []
    if style == "aurora":
        for i, color in enumerate((b, c, a, d)):
            y = 75 + i * 70
            elements.append(f'<path d="M55 {y+155}C190 {y-100} 350 {y+290} 505 {y+70}S755 {y-35} 845 {y+10}" fill="none" stroke="{color}" stroke-width="{65-i*10}" opacity=".72"/>')
        elements += [f'<circle cx="710" cy="175" r="68" fill="none" stroke="{a}" stroke-width="2"/>', f'<circle cx="710" cy="175" r="9" fill="{d}"/>']
    elif style == "folio":
        elements += [f'<rect x="170" y="105" width="430" height="350" fill="{d}" stroke="{a}" stroke-width="3" transform="rotate(-8 385 280)"/>', f'<rect x="285" y="150" width="410" height="340" fill="{d}" stroke="{a}" stroke-width="3" transform="rotate(8 490 320)"/>']
        elements += [f'<path d="M340 225H635M340 249H590M340 300H465M340 324H465M340 348H465M340 372H465M340 396H465" stroke="{a}" stroke-width="7"/>', f'<rect x="500" y="285" width="126" height="125" fill="{b}"/>', f'<circle cx="620" cy="420" r="55" fill="{a}"/><path d="M595 420h50M620 395v50" stroke="{d}" stroke-width="3"/>']
    elif style == "arcade":
        for y in range(310, 551, 40): elements.append(f'<path d="M80 {y}H840" stroke="{b}" stroke-width="2" opacity=".3"/>')
        for x in range(80, 841, 95): elements.append(f'<path d="M450 250L{x} 550" stroke="{b}" stroke-width="2" opacity=".3"/>')
        elements += [f'<path d="M250 210Q270 160 330 165H580Q640 160 660 210L710 380Q720 425 670 425L565 360H345L240 425Q190 425 200 380Z" fill="#251538" stroke="{a}" stroke-width="9"/>', f'<path d="M305 245v85M263 287h85" stroke="{a}" stroke-width="22"/>', f'<circle cx="580" cy="254" r="19" fill="{c}"/><circle cx="628" cy="298" r="19" fill="{b}"/>', f'<path d="M390 305h30m45 0h30" stroke="{d}" stroke-width="7"/>', f'<path d="M665 75v35m-17-18h35M180 145v35m-17-18h35" stroke="{c}" stroke-width="4"/>']
    elif style == "terminal":
        elements += [f'<rect x="130" y="100" width="630" height="385" fill="#0B2114" stroke="{a}" stroke-width="5"/>', f'<path d="M130 150H760" stroke="{a}" stroke-width="2"/>']
        for x in (157, 180, 203): elements.append(f'<rect x="{x}" y="120" width="10" height="10" fill="{b}"/>')
        elements += [f'<path d="M180 205l28 24-28 24M230 253h30M285 215h210M285 240h150M180 300h285M180 330h400M180 360h340M180 390h200" fill="none" stroke="{a}" stroke-width="7"/>', f'<rect x="580" y="345" width="85" height="70" fill="none" stroke="{c}" stroke-width="3"/><path d="M600 380h45M623 357v45" stroke="{c}" stroke-width="3"/>']
    elif style == "cassette":
        for i, color in enumerate((a, b, c, d)):
            elements.append(f'<path d="M130 {360-i*45}V235a{270-i*45} {190-i*30} 0 0 1 {540-i*90} 0v{125-i*12}" fill="none" stroke="{color}" stroke-width="30" opacity=".8"/>')
        elements += [f'<rect x="220" y="260" width="440" height="250" rx="35" fill="{c}" stroke="#6A392D" stroke-width="6"/>', f'<rect x="255" y="296" width="370" height="112" rx="48" fill="#6A392D"/>', f'<circle cx="317" cy="352" r="37" fill="{c}"/><circle cx="563" cy="352" r="37" fill="{c}"/><path d="M350 435h180l35 55H315Z" fill="{b}"/>', f'<path d="M370 352h140" stroke="{a}" stroke-width="8"/>']
    elif style == "botanical":
        elements += [f'<circle cx="495" cy="300" r="210" fill="none" stroke="{c}" stroke-width="2" opacity=".65"/>', f'<path d="M440 510Q525 340 470 105M470 405Q350 330 305 210M488 290Q620 245 680 130" fill="none" stroke="{d}" stroke-width="5"/>']
        for x,y,angle in [(455,155,-20),(505,220,80),(450,290,-30),(400,350,-55),(338,255,-55),(565,250,45),(634,174,55)]:
            elements.append(f'<ellipse cx="{x}" cy="{y}" rx="35" ry="83" fill="{a if x<490 else b}" transform="rotate({angle} {x} {y})"/><path d="M{x} {y-63}V{y+63}" stroke="{d}" stroke-width="2" transform="rotate({angle} {x} {y})"/>')
        elements += [f'<circle cx="245" cy="410" r="24" fill="{c}"/>', f'<circle cx="690" cy="350" r="10" fill="{d}"/>']
    elif style == "adriatic":
        elements += [f'<circle cx="665" cy="160" r="75" fill="{c}"/>', f'<path d="M460 130V390H270Z" fill="{d}"/><path d="M485 200V390H640Z" fill="{a}"/><path d="M270 407H650L598 450H320Z" fill="{c}"/>']
        for i, color in enumerate((b, a, d)):
            elements.append(f'<path d="M70 {435+i*36}q80-35 160 0t160 0t160 0t160 0t160 0" fill="none" stroke="{color}" stroke-width="{12-i*3}"/>')
        elements += [f'<path d="M200 155q25-20 50 0q25-20 50 0M295 115q20-15 40 0q20-15 40 0" fill="none" stroke="{d}" stroke-width="3"/>']
    elif style == "blueprint":
        for x in range(90, 821, 45): elements.append(f'<path d="M{x} 80V530" stroke="{a}" stroke-width="1" opacity=".23"/>')
        for y in range(80, 531, 45): elements.append(f'<path d="M90 {y}H820" stroke="{a}" stroke-width="1" opacity=".23"/>')
        elements += [f'<path d="M280 220l190-100 190 100v220L470 535 280 440Zm0 0 190 100 190-100M470 320v215M470 120v200" fill="none" stroke="{d}" stroke-width="4"/>', f'<path d="M220 190V465M205 205l30-30m-30 305 30-30M275 70h385M275 55v30M660 55v30" stroke="{b}" stroke-width="3"/>', f'<circle cx="470" cy="320" r="56" fill="none" stroke="{b}" stroke-width="3" stroke-dasharray="10 8"/>']
    elif style == "sakura":
        elements += [f'<circle cx="555" cy="280" r="180" fill="{b}" opacity=".25"/><path d="M170 480Q420 320 715 120M370 350L390 170M510 260L705 300" fill="none" stroke="{c}" stroke-width="8"/>']
        for x,y in [(385,190),(460,300),(620,170),(680,290),(285,405),(555,255)]:
            for angle in range(0,360,72): elements.append(f'<ellipse cx="{x}" cy="{y-22}" rx="16" ry="30" fill="{d}" transform="rotate({angle} {x} {y})"/>')
            elements.append(f'<circle cx="{x}" cy="{y}" r="10" fill="{a}"/>')
    elif style == "pop":
        elements += [f'<rect x="135" y="115" width="230" height="315" fill="{a}" transform="rotate(-9 250 270)"/>', f'<circle cx="560" cy="245" r="140" fill="{b}" stroke="{c}" stroke-width="7"/>', f'<path d="M450 450l95-140 95 140Z" fill="{d}" stroke="{c}" stroke-width="6"/>', f'<path d="M170 465h190M210 135l55 55-55 55 55 55-55 55" fill="none" stroke="{d}" stroke-width="14"/>']
        for i in range(6):
            for j in range(3):
                if (i+j)%2==0: elements.append(f'<rect x="{610+i*22}" y="{390+j*22}" width="22" height="22" fill="{c}"/>')
        elements.append(f'<path d="M735 145v75m-37-37h75" stroke="{c}" stroke-width="9"/>')
    elif style == "espresso":
        elements += [f'<rect x="195" y="175" width="350" height="315" rx="6" fill="{d}" stroke="{a}" stroke-width="4" transform="rotate(-10 370 330)"/>', f'<path d="M250 240l210-35M260 280l210-35M270 320l140-24M285 380l190-32M295 420l180-30" stroke="{b}" stroke-width="5"/>', f'<ellipse cx="620" cy="420" rx="150" ry="47" fill="{b}" opacity=".5"/>', f'<path d="M520 245H690V365Q690 425 605 425T520 365ZM690 270h30q70 50 0 90h-30" fill="{d}" stroke="{a}" stroke-width="8"/>', f'<ellipse cx="605" cy="250" rx="85" ry="23" fill="{a}"/><path d="M575 195q-30-32 0-65m57 60q-30-32 0-65" fill="none" stroke="{c}" stroke-width="4"/>']
    elif style == "nocturne":
        elements += [f'<circle cx="495" cy="285" r="155" fill="none" stroke="{a}" stroke-width="2"/><circle cx="495" cy="285" r="210" fill="none" stroke="{b}" stroke-width="1" opacity=".65"/>', f'<ellipse cx="495" cy="285" rx="330" ry="80" fill="none" stroke="{a}" stroke-width="3" transform="rotate(-22 495 285)"/>', f'<path d="M540 150a140 140 0 1 0 0 270 110 110 0 0 1 0-270Z" fill="{a}"/>']
        for x,y,size in [(235,130,8),(720,370,10),(650,110,5),(190,360,4),(760,180,4),(360,510,5)]:
            elements.append(f'<path d="M{x-size} {y}h{size*2}M{x} {y-size}v{size*2}" stroke="{d}" stroke-width="2"/>')
    return '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600" viewBox="0 0 900 600">' + "\n".join(elements) + "</svg>\n"


def build(profile: dict, base: dict) -> dict:
    """EN: Build a fully editable native theme. HR: Izrađuje potpuno uredljivu izvornu temu."""
    theme = copy.deepcopy(base)
    theme_id = "simbioza-" + profile["id"]
    theme.update(id=theme_id, label={locale: profile["name"] for locale in ("hr", "en", "de", "fr", "it", "es")},
                 system=False, font_family=FONTS[profile["font"]], menu_font_family=FONTS[profile["menu"]],
                 menu_font_size="1rem", menu_font_weight="700" if profile["style"] in ("gaming", "creative", "retro") else "500", menu_font_style="normal")
    compact = profile.get("compact", False)
    theme["components"] = {
        "skip_link": True,
        "header": {"enabled": True, "sticky": True, "container": profile["width"], "height_px": 60 if compact else 72,
                   "items": [dict(id="brand-mark", type="logo", position="start", text={}, href="@app", src_light=f"@theme-assets/{theme_id}/icon-light.png", src_dark=f"@theme-assets/{theme_id}/icon-dark.png", alt={"hr":"Simbioza znak","en":"Simbioza mark"}, height=38, new_tab=False),
                             dict(id="brand-name", type="text", position="start", text={"hr":"Simbioza","en":"Simbioza"}, href="", height=38),
                             dict(id="language", type="language", position="end", height=38), dict(id="account", type="account", position="end", height=38)]},
        "navigation": {"placement": profile["nav"], "container": profile["width"], "height_px": 48 if compact else 56, "breakpoint":"lg", "tabs_style":"top-accent" if profile["accent"]=="top" else "standard", "tabs_accent_enabled":True, "tabs_radius":profile["radius"], "tabs_active_border_width_px":3, "brand_display":"none", "show_mobile_label":True, "mobile_label":{"hr":"Izbornik","en":"Menu"}, "sticky":profile["nav"]=="standalone", "show_icons":profile["style"] in ("gaming","technical"), "mark_external_links":True, "text_shadow":"none"},
        "hero": {"home_enabled":True, "inner_enabled": profile["id"] in ("aurora","neon-arcade","cassette","jadran","nocturne"), "container":profile["width"], "home_size":profile["size"], "inner_size":"compact", "alignment":"start", "content_min_height_px":300 if compact else 380 if profile["size"]=="large" else 330, "content_padding_top_px":24, "content_padding_bottom_px":32, "visual_enabled":True, "visual_src_light":f"@theme-assets/{theme_id}/hero-light.svg", "visual_src_dark":f"@theme-assets/{theme_id}/hero-dark.svg", "visual_alt":{}, "visual_decorative":True, "visual_allow_overflow":False, "visual_width_px":560 if profile["width"]!="xl" else 470, "visual_max_height_px":335, "visual_top_px":12, "visual_right_px":0, "visual_rotation_deg":0, "wave":profile["wave"], "title_text_shadow":"none", "supporting_text_shadow":"none"},
        "content": {"container":profile["width"], "inner_title_placement":"content-card", "application_title_placement":"content-card", "surface":"plain", "inner_hero_overlap":"small" if profile["wave"] else "none", "density":"compact" if compact else "comfortable", "title_accent":profile["accent"], "title_shadow":profile["shadow"], "content_accent":profile["accent"], "content_shadow":profile["shadow"], "sidebar_accent":"start" if profile["accent"]=="start" else "none", "sidebar_shadow":False},
        "cards": {"style":"raised" if profile["shadow"] else "flat", "density":"compact" if compact else "comfortable", "radius":profile["radius"]},
    }
    for mode in ("light", "dark"):
        theme[mode] = {"colors":palette(profile, mode, base[mode]["colors"])}
    return theme


def write_json(path: Path, payload: object) -> None:
    """EN: Write deterministic UTF-8 JSON. HR: Zapisuje ponovljivi UTF-8 JSON."""
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=4) + "\n")


def main() -> None:
    """EN: Build optional archives; installation is an explicit separate action. HR: Izrađuje opcionalne arhive; instalacija je izričita zasebna radnja."""
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=ROOT / "output/playwright/themes-20261104")
    args = parser.parse_args()
    base = next(t for t in json.loads((MODULE / "resources/theme/themes.json").read_text()) if t["id"] == "standard")
    catalog = []
    for profile in PROFILES:
        theme = build(profile, base)
        theme_root = args.output / "source" / theme["id"]
        asset_root = theme_root / "assets"
        asset_root.mkdir(parents=True, exist_ok=True)
        assets = []
        for mode in ("light", "dark"):
            (asset_root / f"hero-{mode}.svg").write_text(artwork(profile, mode))
            shutil.copyfile(ROOT / f"data/themes/simbioza/assets/icon-natural-{mode}.png", asset_root / f"icon-{mode}.png")
            for role, ext, dimensions in (("hero","svg",(900,600)), ("icon","png",(512,512))):
                file = asset_root / f"{role}-{mode}.{ext}"
                assets.append(dict(file=file.name, role=role, label={"hr":f"{profile['name']} — {'svijetlo' if mode=='light' else 'tamno'}", "en":f"{profile['name']} — {mode}"}, mime="image/svg+xml" if ext=="svg" else "image/png", width=dimensions[0], height=dimensions[1], bytes=file.stat().st_size, sha256=hashlib.sha256(file.read_bytes()).hexdigest()))
        write_json(theme_root / "theme-assets.json", {"version":1, "assets":assets})
        write_json(args.output / "definitions" / f"{theme['id']}.json", theme)
        catalog.append(dict(id=theme["id"], name=profile["name"], style=profile["style"], description={"hr":profile["hr"],"en":profile["en"]}, theme=theme))
    write_json(args.output / "catalog.json", {"format":"simbioza-theme-collection", "version":1, "themes":catalog})
    print(f"Built {len(catalog)} native themes / {len(catalog)*2} colour variants in {args.output}")


if __name__ == "__main__":
    main()
