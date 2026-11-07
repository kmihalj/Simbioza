# Simbioza 0.3.35 — kontrast opisa bez dodanih podloga

> Povijesna bilješka izdanja. Pogledajte [aktualne upute za instalaciju](installation_hr.md) i [Simbiozu 0.3.44](release-0.3.44_hr.md).
[Engleska verzija](release-0.3.35_en.md)

Theme **0.3.11** uklanja neželjene pozadine dodane iza pratećih opisa u izdanju
0.3.33. Kontrast se prilagođava isključivo bojom slova. Izračun uzima stvarni
položaj i širinu redaka teksta umjesto cijelog hero gradijenta, pa čitljiva
zadana boja ostaje sačuvana.

Popravak obuhvaća podnaslove naslovnice, opise modula, nespremljene preglede tema,
prikaz na užim zaslonima, svijetle/tamne palete i nove prenosive izvoze.
Hero gradijenti, spremljene palete, izričito odabrane sjene slova i razmak
između navigacije i kartica sadržaja ostaju nepromijenjeni.

Dvanaest opcionalnih tema dostupno je u [zasebnom ZIP-u kolekcije priloženom
izdanju 0.3.33](https://github.com/kmihalj/Simbioza/releases/download/0.3.33/Simbioza-0.3.33-themes.zip).
Raspakirajte ga pa pojedine ZIP pakete uvezite kroz **Postavke → Tema → Uvezi
cijelu temu**. Nadogradnja aplikacije ne uvozi niti aktivira te teme.

Framework ostaje **0.0.25**. Ovaj popravak nema migraciju baze i ne resetira
sadržaj, ovlasti ili privatnu konfiguraciju. DEMO i apps-test nadograđuje
operater kroz postojeći CLI postupak ili GUI pravilno podešenog sigurnog FPM-a.
