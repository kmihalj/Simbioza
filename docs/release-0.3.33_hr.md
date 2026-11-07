# Simbioza 0.3.33 — dvanaest tema i čitljivi opisi stranica

> Povijesna bilješka izdanja. Pogledajte [aktualne upute za instalaciju](installation_hr.md) i [Simbiozu 0.3.43](release-0.3.43_hr.md).

[Engleska verzija](release-0.3.33_en.md)

Theme **0.3.9** osigurava razmak navigacije i kartica na stranicama bez unutarnjeg
hero zaglavlja. Opisi stranica prate stvarnu podlogu, uključujući prijelom kojim
se na uskom zaslonu premjeste ispod hero područja. Nedovoljan kontrast teksta
ispravlja se bez promjene hero gradijenta. Podloga samo iza teksta koristi se
ako nijedna prednja boja ne postiže kontrast 4,5:1 preko cijelog gradijenta.

[Kolekcija tema](theme-collection_hr.md) sadrži dvanaest opcionalnih tema,
svaku sa svijetlom i tamnom varijantom, lokalnim ilustracijama i izvornim
postavkama za uređivanje. ZIP paketi za uvoz nalaze se u
`resources/installation/theme/`. U **Postavke → Tema → Uvezi cijelu temu**
uvezite odabrani paket i zatim ga odaberite kao aktivan. Postojeće teme i
aktivni odabir ostaju sačuvani. Stranica O aplikaciji više ne ponavlja naslov
kada je on smješten u karticu.

Framework ostaje **0.0.25**. Nema migracije baze niti resetiranja sadržaja,
ovlasti ili privatne konfiguracije. DEMO i apps-test nadograđuje operater kroz
postojeći CLI postupak ili GUI nadogradnju na pravilno podešenom sigurnom FPM-u.
