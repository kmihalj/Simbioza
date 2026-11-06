# Uobičajeni razvojni postupci

Simbioza upravlja sastavljanjem aplikacije, moduli svojim domenskim ponašanjem,
a Framework daje postojeće temelje. Mijenjajte odgovorni repozitorij, očuvajte
administratorske postavke/podatke i provjerite pogođeni korisnički tok.

## HTML Editor u polju obrasca

Počnite s [cjelovitim primjerom polja obrasca](html-editor-forms_hr.md).
Objašnjava datoteke, prikaz editora uz textarea polje, sinkronizaciju HTML-a
prije predaje i serversku obradu. Nije potrebno stvarati Wiki stranicu.
Aplikacija i dalje prijavljuje korisnika, provjerava pravo uređivanja zapisa,
validira CSRF i sprema polje. HTML s klijenta ne postaje pouzdan samo zato što
je nastao u vizualnom editoru.

## Dodavanje aplikacijske stranice

1. Dodajte rutu u `config/routes.php` prema postojećem obliku deklaracije.
   Odaberite HTTP metodu, stabilan naziv rute i potrebni middleware.
2. Implementirajte radnju u `src/Controllers/`; mora vratiti PSR-7
   `ResponseInterface`. Poslovni dio smjestite u servis, ne u predložak.
3. Registrirajte ovisnosti u `config/services.php` i prikažite HTML predložak
   iz `views/` kroz uobičajeni servis odgovora/prikaza.
4. Poveznice stvarajte nazivom rute kako bi se sačuvao poddirektorij instalacije.
5. Isprobajte gosta, ovlaštenog korisnika i korisnika bez prava. Skrivena
   poveznica u meniju ne štiti ručno otvorenu adresu.

Detalji su u [rutama](routes_hr.md), [prikazima](views_hr.md) i
[konfiguraciji](configuration_hr.md). Na instaliranoj aplikaciji očuvajte
aktivne vanjske konfiguracijske slojeve.

## Nova mogućnost modula ili API resurs

Koristite servise vlasničkog modula i ugovore opcionalne integracije. Stavka
postavki prikazuje se samo dok je mogućnost uključena. U Simbiozi upravljajte
modulima kroz **Postavke → Moduli** ili dokumentirani CLI; uređivanje samo
`app.modules.enabled` zaobilazi središnji životni ciklus i pravila ovisnosti.

API resurs pripada API proširenju vlasničkog modula. Traži Bearer scopeove
**i** aktualne domenske ovlasti; kontroler web obrasca nije zamjenski API.
Slijedite [implementirani API ugovor](api-v1-contract_hr.md) i
[ovisnosti modula](module-dependencies_hr.md). Vlasništvo API ključa ne daje
pravo nastupanja kao tuđi ispitanik ankete.

## Promjena sheme ili trajnih postavki

Za instaliranu bazu dodajte novu migraciju; izmjena stare migracije ne znači
da će se ona ponovno pokrenuti. HTTP i CLI moraju imati iste konfiguracijske
korijene. Za upisive postavke koristite konfiguracijsko spremište i zaključavanje
vlasničkog modula, a ne proizvoljan upis u PHP datoteku. Očuvajte nepoznate
ključeve proširenja i ostale administratorske odabire. Opišite posljedice za
backup/restore ako ih promjena ima.

## Dopuna uputa i provjera

Promijenite oba jezika i slike kada se mijenja ponašanje. [Postupak održavanja
dokumentacije](documentation-quality_hr.md) obnavlja priručnik unutar Simbioze.
Najprije pokrenite ciljane provjere modula, zatim primjenjivi [izolirani
end-to-end tok](end-to-end-testing_hr.md) kada se moduli povezuju. Pregledajte
rezultat u stvarnom sučelju; prolaz jediničnog testa ne dokazuje da je dropdown
upotrebljiv ili da radnju vidi prava uloga.
