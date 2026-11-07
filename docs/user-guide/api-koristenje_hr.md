# API — povezivanje s drugim sustavima

Ove su upute za razvojne programere koji povezuju vlastitu aplikaciju sa Simbiozom. API omogućuje rad s postojećim korisnicima, sadržajem, područjima, kalendarima, anketama i osobnim podacima za koje vlasnik ključa već ima pravo. Ne zamjenjuje administratorsko podešavanje instalacije niti prijavu ispitanika u anketu.

## Kako započeti

1. Administrator instalira/aktivira API i izda ključ s potrebnim scopeovima.
2. U [početnom poglavlju](api-pocetak_hr.md) postavite adresu i pročitajte identitet `/api/v1/me`.
3. Provjerite dostupne resurse i OpenAPI na svojoj instalaciji.
4. Otvorite poglavlje resursa i slijedite primjer stvaranja, čitanja, izmjene ili izvoza.
5. Provjerite i pogreške: ključ ne daje pristup tuđem privatnom sadržaju.

## Poglavlja

| Poglavlje | Provjerene operacije |
|---|---|
| [API — početak i zajednička pravila](api-pocetak_hr.md) | 3 |
| [API — korisnici i grupe](api-korisnici-grupe_hr.md) | 11 |
| [API — područja, stablo, ovlasti i teme](api-podrucja_hr.md) | 30 |
| [API — HTML stranice, nacrti i privitci](api-html-stranice_hr.md) | 22 |
| [API — kalendari i događaji](api-kalendari_hr.md) | 13 |
| [API — ankete i rezultati](api-ankete_hr.md) | 18 |
| [API — zadaci u sadržaju](api-zadaci_hr.md) | 4 |
| [API — obavijesti i osobna praćenja](api-obavijesti-pracenja_hr.md) | 11 |
| [API — webhookovi i isporuke](api-webhookovi_hr.md) | 9 |
| [API — audit i pretraživanje](api-audit-pretraga_hr.md) | 2 |
| [Granice API-ja, CalDAV i ICS](api-granice-protokola_hr.md) | — |

Priručnik pokriva svih **114 registriranih REST ruta** u cjelovitoj instalaciji. Survey ruta s nazivom radnje ima 10 dopuštenih operacija, pa je prikazano ukupno **123 odvojeno provjerenih operacija**. Modularna instalacija prikazuje samo resurse čiji su moduli instalirani i aktivni. `OPTIONS` za CORS i zasebni CalDAV ne ulaze u taj zbroj.

Svaki primjer navodi metodu, putanju, ovlast, parametre, curl naredbu, stvaran odgovor i provjerene kodove pogreške. Adresa je zamijenjena primjerom vaše instalacije; ključevi i tajne nisu objavljeni. Snimke odgovora potječu iz sintetičkih podataka, zato se imena, ID-ovi i vremena razlikuju od vaših. Veliki odgovori ponekad imaju izričiti `jq` odabir; prikazani JSON tada je stvarni rezultat tog filtra.

[Upravljanje API ključevima za administratora](admin-api_hr.md). [API pravila, greške i potpuni PHP klijent](api-pocetak_hr.md).
