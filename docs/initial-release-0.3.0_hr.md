# Simbioza 0.3.0 — čisto početno izdanje

[Engleska verzija](initial-release-0.3.0_en.md)

Odluka: 11. listopada 2026. Zamjenjuje ranije zahtjeve za nadogradnju i kompatibilnost arhiva u planu migracije konfiguracije.

0.3.0 je polazište za novu instalaciju: nova baza, aktualna konfiguracija u `HPH_CONFIG_PATH/<modul>/` i podaci organizirani po područjima/stranicama. Nema podržane nadogradnje s 0.2.x, obveznog prijelaznog izdanja ni uvoza starih shema Simbiozinih kopija. Samonadogradnja i rollback updatera ostaju za buduća izdanja.

Aktualni backup/restore ostaje prenosiv između siteova: sprema izvornu javnu adresu, očuva poveznice na istom siteu, interne poveznice stranica/privitaka na drugom siteu razriješi prema uvezenim ciljnim identitetima, sačuva vanjske poveznice i prijavi nerazriješene reference. Stari Confluence izvozi ostaju podržani kao vanjski ulaz, ne kao stare Simbiozine kopije.

## Popis za provedbu

- [x] R030-A: backup format 2 i točne sheme providera; uklonjena stara Simbiozina pretvaranja, provjereno ponašanje poveznica na istom i drugom siteu.
- [x] R030-B: Auth konfiguracijske datoteke mjerodavan su izvor; uklonjeni stari izvori konfiguracije iz baze i upgrade alati. Korisnici, grupe, identiteti i nužne projekcije ostaju u bazi.
- [x] R030-C: nova instalacija koristi 14 početnih migracija sheme; uklonjeni povijesni upgrade/move alati. Samonadogradnja updatera, predaja novom procesu i rollback ostaju za 0.3.x i novije.
- [x] R030-D: uklonjene zastarjele konfiguracijske, identitetske, API i sadržajne grane modula. Sačuvane opcionalne integracije, valjane zadane vrijednosti i podrška za vanjske Confluence formate.
- [x] R030-E: odvojene EN/HR upute, svih šest ugrađenih arhiva vodiča i demo seed/reset alati koriste aktualni format.
- [x] R030-F: prošle nove instalacije, jedinične/statičke provjere i svih 80 GUI scenarija; HFC koristi aktualne izvore modula i nema pending migracija.
- [x] R030-G: pripremljena verzija aplikacije 0.3.0, ovisnosti modula i završna lokalna testna matrica. Objava je zaseban, još nedovršen korak.

## Dovršene provjere — 11. listopada 2026.

Rezultati se odnose na čistu početnu osnovu prije registra vanjskih skripti,
ne na stare P1–P10 provjere ili objavljene tagove. Za prošireni kandidat vrijedi
[dodatni audit](pre-release-audit-0.3.0_hr.md); njegove nedovršene provjere nisu
obuhvaćene ovom tablicom.

| Provjera | Rezultat |
| --- | --- |
| Svih 18 javnih modula | Prošlo 1.045 testova / 8.943 provjere |
| Jedinični testovi aplikacije | 233 testa / 5.808 provjera; tri slučaja koji zahtijevaju root/Linux zasebno provjerena u Colimi |
| Matrica novih SQLite instalacija | Prošla svih 21 kombinacija modula |
| Potpune PostgreSQL 18, MySQL 8.4 i MariaDB 11.4 instalacije | Prošle; po 14 početnih migracija i nula pending migracija |
| Potpuna izolirana provjera kroz preglednik/API | Prošlo svih 80 scenarija, uključujući backup/restore i Confluence uvoz |
| Stvarni Linux FPM i sigurni FPM | Prošli HTTP početne/prijavne stranice i zapisi Auth/Menu/Theme konfiguracije uz kod samo za čitanje i zapisive runtime putanje |
| Ugrađene upute | Provjereno svih šest šifriranih arhiva aktualnog formata i njihovi sažeci |
| Privatni demo modul | Prošlo 12 testova / 231 provjera i svih šest izoliranih Linux scenarija reseta |
| HFC | Početna/prijavna stranica vraćaju HTTP 200; 14 izvršenih migracija, nula pending |
| Kod i dokumentacija | Prošle obvezne provjere stila/statičke analize, Composer validacija te provjere dokumentacije i prijevoda |

Izvještaji ostaju u `build/r030/`, `build/clean-install-matrix-*.json` i `build/playwright-report/index.html`. Privremene testne instalacije, spremnici baza i gostujući računi za provjeru reseta uklanjaju se nakon provjera. Izvori frameworka i privatne SSO postavke nisu mijenjani.

## Upute i demo sadržaj

`resources/installation/workspace/` sadrži arhive aktualnog formata za opću instalaciju, Apache mod_php, PHP-FPM, korisničke upute, sastanke i Confluence uvoz. Manifesti koriste backup format 2 i verziju aplikacije 0.3.0. Interne reference stranica/privitaka koriste kanonske putanje i uvezene identitete, ne fiksnu adresu ciljnog sitea.

Izvori privatnog demo modula u `DemoSeed/simbioza-module-demo/` koriste aktualne konfiguracijske putanje i vlasničku organizaciju područja/stranica. Početna kopija reseta u formatu 2 obuhvaća odabrane konfiguracijske slojeve, podatke aplikacije i dosljednu SQLite snimku. Za novi demo treba ponovno pripremiti sadržaj i snimiti novu početnu kopiju; uređivanje izvora ne nadograđuje niti resetira pokrenuti demo.

## Preostala objava

- [ ] Nakon zasebne upute objaviti usklađene tagove 0.3.0 za module/aplikaciju i provjeriti instalaciju iz tih objavljenih paketa.
- [ ] Korisnik ponovno instalira demo i apps-test, a zatim snima novu početnu kopiju demo reseta.

Do objave primjeri ovisnosti `^0.3.0` opisuju pripremljeno izdanje; razvojne provjere koriste lokalne izvore paketa. Ne zamjenjivati ih starim tagovima 0.2.x niti pokušavati nadogradnju s njih.

## Granice i nastavak

HeartPhrame Framework ne mijenjati. Sačuvati nepovezane izmjene i privatne postavke. Korisnik reinstalira demo/apps-test; aktivne siteove i baze ne mijenjati. HFC je razvojni site i ne treba backup sadržaja. Ovim zadatkom nisu objavljeni tagovi, commitovi ni izdanja.

2026-10-11: provjera čiste početne osnove dovršena. Prošireni audit je u tijeku;
objava i ponovna instalacija aktivnih siteova ostaju zasebni koraci.
Raniji P1–P10 rezultati sačuvani su samo kao povijesni kontekst.
