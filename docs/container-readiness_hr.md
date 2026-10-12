# Spremnost za containere — kandidat 0.3.0

[Engleska verzija](container-readiness_en.md)

Ažurirano 12. listopada 2026. A4 sada implementira i provjerava nepromjenjivi
Docker kandidat u Colimi. Klasični HFC ostaje podržan i nije pretvoren u Docker.
Aktualne naredbe su u [Docker uputama](docker_hr.md), a dokazi u
[auditu prije izdanja](pre-release-audit-0.3.0_hr.md).

## Aktualna pohrana i konfiguracija

- Aplikacijski/PHP/vendor kod i tvorničke postavke pripadaju read-only imageu.
  Ne montirati trajni volumen preko aplikacijskog koda.
- Jedan runtime volumen sadrži privatni config, podatke i opcionalno SAML stanje.
  Poddirektorij podataka izravni je volume-subpath mount, ne symlink.
- PostgreSQL ima zaseban volumen i aplikacijskog korisnika bez superuser ovlasti.
- Tvornički config koristi `HPH_CONFIG_PATH=/opt/simbioza/config`;
  privatni zapisi idu u zadnji sloj `HPH_CONFIG_OVERRIDE_PATHS=/runtime/config`.
  Svaki modul zadržava vlastiti poddirektorij u tom sloju.
- Sadržaj, privitci, vlastiti meniji/teme, cache, logovi, poslovi, backupi,
  prijevodi i sesije su runtime stanje, ne programski kod.
- Instalirani prijevodi koriste `data/languages/catalogs/`; paketni `lang/`
  ostaje read-only. Odobrenja skripti ostaju lokalna na odredištu backupa/importa.

## Operacije potvrđene u A4

Kratki root init priprema samo nedostajuće privatno stanje. HTTP/CLI/workeri rade
kao UID/GID 33, uz read-only root i ograničene ovlasti. Migracije imagea su
izričite i zaključane; startup odbija nepodudaran ID imagea. Neuspjela migracija
ne označava uspjeh. Image bez prethodno primijenjenih migracija odbija se.

U image načinu rada zabranjene su samo Setup/Composer/updater radnje koje
mijenjaju kod. GUI konfiguracije/sadržaja/jezika i aktivacija modula ostaju.
Ugrađena oznaka ne može se isključiti privatnim configom. Klasične instalacije
zadržavaju svoj postojeći paketni/updater postupak.

Colima test pokriva čistu PostgreSQL instalaciju, očuvanje volumena, zamjenu
imagea, neuspjelu/ponovljenu migraciju, odbijanje nesigurnog downgradea, lokalnu
prijavu i očuvanje sesije, Setup aktivaciju, instalaciju jezika, backup/restore
područja i aplikacijskih jezika te SMTP worker s praznim redom. Prethodne A3
FPM/secure-FPM provjere read-only koda ostaju zaseban dokaz.

## Opcionalni SimpleSAMLphp

Auth treba biblioteku u istom PHP procesu; vanjski URL sam nije integracija
biblioteke. A4 koristi opcionalni read-only mount kompatibilnog koda uz privatni
runtime config/certifikate/metapodatke i sesije/cache. Na `/saml` se izlaže samo
javni direktorij biblioteke.

Provjereni su učitavanje SimpleSAMLphpa 2.4.4 i metapodaci izmišljenog SP-a.
Registracija stvarnog container SP-a/IdP-a i prijava ostaju održavateljeva provjera
deploymenta. HFC SSO tajne i postavke nisu ponovno korištene niti mijenjane.
Zadani image ne sadrži SimpleSAMLphp ni privatne certifikate. Pogledati Docker
upute; odvojeni SAML servis ili posebni image nisu implicitno podržani.

## Granice izdanja

A4 je lokalna provjera kandidata, ne objava niti odobrenje produkcije. Lokalni A5
je dovršen. Objavljeni image artefakti, provjera ovisnosti/ranjivosti i
stvarne HTTPS/proxy/SSO provjere pripadaju release/deployment koracima.
Demo/apps-test nisu nadograđeni, a framework nije mijenjan.
