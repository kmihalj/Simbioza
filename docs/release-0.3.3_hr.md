# Simbioza 0.3.3 — sigurnosne Docker nadogradnje

[Engleska verzija](https://github.com/kmihalj/Simbioza/blob/0.3.3/docs/release-0.3.3_en.md)

> Povijesna bilješka izdanja — za novu instalaciju koristite [aktualni pregled instalacije](installation_hr.md). Izvorni tag i image 0.3.3 ostaju nepromijenjeni.

Ova dopuna objavljuje nove Linux **AMD64 i ARM64** imagee s nadograđenim Debian
paketima. Framework ostaje **v0.0.25**. API **0.3.1** uključuje ispravljene upute
brzog početka, a ostali javni moduli ostaju **0.3.0**. Postojeći tagovi i imagei prethodnih izdanja ne zamjenjuju se.

## Promjene

- Zajednička PHP runtime faza nadograđuje naslijeđene Debian pakete prije
  izgradnje PHP proširenja. Sama instalacija build ovisnosti ostavljala je
  starije `curl`/`libcurl` pakete u osnovnom imageu zaključanom digestom.
- Obje arhitekture zadržavaju neprivilegirano pokretanje, nepromjenjivi
  aplikacijski kod, zasebne trajne runtime i PostgreSQL volumene te postojeću
  sigurnosnu provjeru. Popravljivi HIGH/CRITICAL nalazi i dalje blokiraju objavu.
- Instalacijske naredbe, Docker primjeri i ugrađene EN/HR instalacijske arhive
  koriste **0.3.3**. Regresijski test odbija razilaženje verzija u aktualnim
  Docker primjerima i README poveznicama.
- Konfiguracijske upute vode na objavljenu dokumentaciju registra skripti HTML
  Editora; API 0.3.1 isporučuje ispravljene poveznice na Auth CLI primjere.
- Provjere dokumentacije razlikuju samostalne verzije modula od aplikacije,
  uz nastavak odbijanja neispravnih PHP/JSON primjera.
- Nema novih aplikacijskih migracija baze ni promjena prijave. Arhivirani rad
  na Confluence site importu nije uključen; import pojedinačnog spacea ostaje
  nepromijenjen.

## Instalacija i nadogradnja

Za novu instalaciju slijediti
[cjeloviti pregled instalacije](https://github.com/kmihalj/Simbioza/blob/0.3.3/docs/installation_hr.md)
ili [Docker uputu](https://github.com/kmihalj/Simbioza/blob/0.3.3/docs/docker_hr.md).
Postojeća instalacija 0.3.x koristi svoj uobičajeni postupak nadogradnje; ne
ponavljati početnu instalaciju niti brisati bazu radi ove dopune.

Za Docker odabrati `ghcr.io/kmihalj/simbioza:0.3.3`, sačuvati oba imenovana
volumena, napraviti konzistentan backup i slijediti izričite korake migracije
imagea i ponovnog pokretanja iz Docker upute. Postojeći containeri ne mijenjaju
se dok održavatelj ne preuzme i postavi novi image. Za nadogradnju ne koristiti
`docker compose down -v`.

## Provjere i preuzimanja

CI izdanja provjerava PHP 8.2–8.5, čiste instalacije modula i baza, browser,
API i performance scenarije te nativnu Docker instalaciju, trajnost podataka i
sigurnost na obje arhitekture. Prilozi izdanja sadrže Compose paket, kontrolne
zbrojeve, zaključane ovisnosti, SBOM-ove i izvještaje ranjivosti.

[Izdanje i preuzimanja](https://github.com/kmihalj/Simbioza/releases/tag/0.3.3)
