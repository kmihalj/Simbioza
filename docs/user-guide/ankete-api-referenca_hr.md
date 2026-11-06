# API — rute, opsezi i greške

[Za developere — API](ankete-developeri_hr.md)

Osnovni put je `/api/v1/surveys`, bez završne kose crte. Prema potrebi ispred njega dodajte poddirektorij instalacije. Svaki zahtjev traži valjan ključ, potreban opseg i aktualnu ovlast njegova vlasnika.

<div class="table-responsive">

| Metoda i nastavak puta                                | Opseg           |
|-------------------------------------------------------|-----------------|
| GET /, GET /{survey}                                  | surveys:read    |
| POST /, PATCH /{survey}                               | surveys:write   |
| POST /{survey}/submit-review, withdraw, copy, delete, discard-draft | surveys:write |
| POST /{survey}/publish, return-review, close, archive, restore | surveys:publish |
| GET /{survey}/results, responses                      | surveys:results |
| POST /{survey}/exports; GET /{survey}/exports/{token} | surveys:export  |

</div>

Prijelazi koriste `version`, `revision` i izričiti `confirmed`. Kopiranje prima novi naslov. Struktura ankete s odgovorima ne mijenja se u mjestu: koristite kopiju.

Popis podržava `q`, `state`, `page` i `per_page`. Rezultati podržavaju jezik i straničenje, a upravitelj i dane `from`/`to`. Pojedinačne predaje traže upravljanje; pravo čitanja grafova nije dovoljno.

Izvoz prima `mode` (aggregate/responses), `lang` i izričiti `include_identity`. Vraćeni token ima rok valjanosti i dostupan je samo istom ovlaštenom vlasniku. To nije javna poveznica ni backup.

<div class="table-responsive">

| Status | Postupak |
|----|----|
| 401 / 403 | Provjerite ključ, opseg, aktivnost računa i aktualna prava. |
| 404 | Resurs nije dostupan ili nedostaje uključeni modul; ne pokušavajte zaobići pristup. |
| 409 | Ponovno pročitajte aktualnu verziju i uskladite promjenu. |
| 422 | Ispravite definiciju, jezik ili filtre prema poruci odgovora. |

</div>

Uspjeh koristi omot `data`, `meta` i `links`. Identifikator zahtjeva pomaže dijagnostici. Odgovore i ključeve ne zapisujte u javne logove. API nema predaju odgovora tokenom u ime ispitanika; za nju vrijede redovna prijava, provjere i zaštita od ponavljanja.

## Brisanje i povrat preko API-ja

`delete` premješta cijelu anketu u smeće i čuva odgovore. `discard-draft` odbacuje radni nacrt; uz postojeću objavu ona ostaje netaknuta. Obje radnje traže aktualne `version`, `revision` i logički `confirmed: true`. `restore` traži `version`, `confirmed: true` i administratorsku ulogu uz navedeni opseg ključa. Ranije objavljena anketa vraća se zatvorena. Odgovor sadrži UUID, operaciju i `deleted`, koji govori je li anketa sada u smeću. HTTP 409 znači da treba ponovno dohvatiti stanje prije nove odluke.
