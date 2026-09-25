# Pristupačnost — sedmi lokalni razvojni presjek

Datum: 24. rujna 2026. [Engleska verzija](accessibility-stage-7_en.md).

Ovo je nastavak [šestog presjeka](accessibility-stage-6_hr.md). I dalje je riječ o lokalnom prototipu, a ne o izdanju ili tvrdnji o WCAG sukladnosti. Zaštićeni repozitoriji HeartPhrame frameworka i demo modula nisu mijenjani.

## Provjera u samostalnoj aplikaciji

Accessibility paket sada ima `StandaloneHostTest` koji pokreće stvarnu, minimalnu HeartPhrame aplikaciju sa **samo** tim modulom. Provjerava učitavanje modula, registraciju obje rute lokalnih resursa i predaju globalnog renderera rasporedu. To je više od testa izoliranog renderera: paket ne traži Simbiozu, Područja, Editor, Temu, Izbornik, Autentikaciju ni bazu. Test prolazi s devet provjera. Zasebna integracija u Simbiozi ostaje za vizualne provjere.

## Oporavak lokalnog HFC Setupa

HFC stranica `settings/setup` padala je prije slanja odgovora jer je zastarjeli katalog jezika pokretao PHP HTTPS dohvat unutar forkiranog macOS Apache procesa s mod_php-om. Izvješće o padu pokazuje DNS poziv unutar `file_get_contents`, a Apache bilježi segmentacijske pogreške svojih procesa. Uzrok nisu ovlasti ni novi modul. Osvježavanje kataloga iz CLI-ja privremeno je vratilo stranicu. `LanguageRepository` sada za HTTPS dohvat koristi sistemski curl u zasebnom procesu **samo** pod Darwin `apache2handler`; drugi sustavi i CLI zadržavaju dosadašnji način. Ograničenja TLS-a, pet sekundi, protokola i veličine ostaju na snazi. Prijavljeni posjet s namjerno zastarjelim katalogom uspješno je otvorio stranicu i osvježio katalog bez novog pada Apachea.

Izolirani lokalni E2E prolazi svih 77 scenarija preglednika, API-ja i performansi, uključujući administratorsku Setup stranicu. Usmjereni testovi jezika prolaze (6 testova, 23 provjere), a accessibility paket četiri testa s 221 provjerom. Puni HFClean `composer on-commit` prolazi 96 testova s 4.261 provjerom i jednim postojećim Mac preskakanjem testa koji traži root ovlasti. Korisnički sadržaj i importirani dokumenti nisu mijenjani. Nema commita, pusha, releasea ni ažuriranja apps-testa.

## Preostali posao

Modulu još trebaju lokalni fontovi s provjerenim licencama i pokrivenošću znakova, prilagodbe visokog kontrasta i čitanja, puni vizualni i ručni pregled pomoćnim tehnologijama, provjera dugih prijevoda i cjelovitih radnji nad paketom. Nakon prethodnog testa prototip ostaje isključen u HFC runtimeu; lokalna Composer veza i dopušteni popis ostaju samo za razvoj.
