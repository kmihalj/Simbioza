# Dnevnik aktivnosti i tehničko zapisivanje

Simbioza namjerno vodi dva odvojena dnevnika jer odgovaraju na različita
pitanja i imaju različita pravila čuvanja, privatnosti i backupa.

## Dnevnik aktivnosti

Administrator otvara **Postavke → Dnevnici → Dnevnik aktivnosti**. Append-only
dnevnik u bazi odgovara tko je izveo koju poslovnu radnju, kada, kroz koji kanal,
s kojim ishodom te nad kojim područjem, stranicom ili drugim sigurnim ciljem.
Obuhvaća prijavljene korisnike, goste, workere, CLI poslove, API pozive,
odbijene radnje, preglede stranica nakon ACL provjere, uređivanje, objavu,
prijavu, statistiku pretrage, obavijesti, stanje isporuke e-pošte i webhooka,
backup/vraćanje, uvoz, izvoz, upload i promjene postavki modula.

Filtri uključuju vremenski raspon, korisnika, modul, radnju, ishod, način
prijave, kanal, područje, stranicu, ključ događaja i cilj. Vrijeme se prikazuje
u konfiguriranoj vremenskoj zoni i formatu jezika sučelja. CSV je praktičan za
tablične programe, a NDJSON čuva potpune tipizirane retke za alate i opsežniju
analizu.

Osjetljive vrijednosti ne pripadaju u ovu tablicu. Proizvođači događaja ne smiju
slati zaporke, tokene, kolačiće, tijela zahtjeva/odgovora, sadržaj dokumenta ili
e-pošte, tekst upita pretrage, webhook URL/potpis ni sadržaj datoteke. Audit
servis dodatno rekurzivno redigira sumnjive ključeve metapodataka.

Ako je Backup instaliran, **Dnevnik aktivnosti** je izborna cjelina za backup
sitea ili komponente. Stabilni identiteti zamjenjuju lokalne numeričke veze, a
UUID pravilo konflikta omogućuje sigurno ponovljeno `merge` vraćanje.

## Tehnički log

Administrator otvara **Postavke → Dnevnici → Tehnički log**. Prikaz čita samo
konfiguriranu aktivnu PSR-3 datoteku i filtrira po razini, modulu/kanalu i
tekstu. Izvoz preuzima aktivnu `.log` datoteku. Rotirajući handler koristi
`app.logs.filename`, `app.logs.max_bytes` i `app.logs.max_files` iz
`config/app.php` te `env.log_level` iz konfiguracije okruženja.

Moduli u tehnički log pišu neočekivane iznimke, kvar opcionalnih listenera,
ponovne i konačno neuspjele pokušaje workera, kvar održavanja i serverske 5xx
odgovore. Strukturirani kontekst treba sadržavati `module`, siguran UUID ili
broj resursa, podatak o pokušaju/statusu i iznimku. Slobodni poslovni sadržaj i
vjerodajnice su zabranjeni. Uobičajeni oblici vjerodajnica redigiraju se iz
poruke, strukturiranog konteksta i poruke iznimke.

Tehnički log može sadržavati deployment putanje, detalje kvara i operativni
šum. Zato je `data/logs` isključen iz potpunih i komponentnih backupa. Datoteke
čuvajte ili šaljite centralnom sustavu prema politici poslužitelja, a ne kroz
arhiv za vraćanje aplikacije.

## Primjer za developera

```php
// Dijagnostički kvar: PSR-3 tehnički log.
$logger->error('Ažuriranje stabla područja nije uspjelo.', [
    'module' => 'workspace',
    'workspace_id' => $workspaceId,
    'node_id' => $nodeId,
    'exception' => $exception,
]);

// Poslovna radnja: dnevnik aktivnosti u bazi.
$audit->record('workspace.page.publish', [
    'module' => 'workspace',
    'action' => 'publish',
    'workspace_id' => $workspaceId,
    'page_id' => $nodeId,
    'outcome' => 'success',
]);
```

Kvar audit zapisa ne smije prekinuti izvornu poslovnu radnju. Audit servis svoj
kvar prijavljuje u PSR-3 i zatim se vraća pozivatelju.
