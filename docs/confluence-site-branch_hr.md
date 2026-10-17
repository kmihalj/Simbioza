# Snimka odgođenog uvoza cijelog Confluence sitea

Uvoz cijelog sitea odgođen je 17. listopada 2026. `confluence-site` čuva integraciju
u aplikaciju, migracijske omotače, instalacijske/provjerne ispravke, pakete uputa i
pregledničke testove pripremljene tijekom tog rada. Ovo nije izdanje i ne spaja se
samo radi objave postojećeg uvoza jednog područja.

Odgovarajuće grane postoje u `simbioza-module-confluence-import`,
`heartphrame-module-calendar` i `simbioza-module-user`. Framework i Auth ostaju
izvan ove snimke. Privatne HFC konfiguracije, nacrti uvoza, stvarne arhive i dokazi
mapiranja bez upisa ne spremaju se u Git.

Osnova aplikacije je `main` na `a41dc81` (Simbioza 0.3.2). Osnove importera,
Calendara i Usera njihove su objavljene `main` revizije 0.3.0. Promjene verzija i
ovisnosti kandidata u ovoj grani nisu objavljene i treba ih ponovno razmotriti prije
nastavka razvoja. Neovisno korisni instalacijski ispravci mogu se zasebno odabrati
i provjeriti bez uključivanja uvoza cijelog sitea u izdanje.

Preostale provjere opisane su u importerovim dokumentima
`docs/confluence-site-branch_hr.md` i `docs/site-import-plan_hr.md`. Čuvanje snimke
ne potvrđuje neriješena mapiranja korisnika/kalendara i ne pokreće uvoz.
