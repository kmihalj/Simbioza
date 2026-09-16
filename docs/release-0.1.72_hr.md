# Simbioza 0.1.72

Ovo izdanje donosi upravljanje modulima i jezicima, sigurni GUI Setup uz
namjenski PHP-FPM te potpunije i preglednije verzioniranje privitaka.

## Modularna instalacija i Setup

Nova instalacija uključuje samo obavezne module, uz preporučeni modul Tema koji
se može isključiti. Opcionalni moduli mogu se kasnije instalirati, uključiti,
isključiti ili ukloniti kroz CLI. Kada je namjenski FPM ispravno podešen, iste
radnje, provjera verzija i cjelovita nadogradnja dostupne su administratoru kroz
**Postavke → Setup i moduli**. GUI nikada izravno ne pokreće proizvoljnu naredbu:
zahtjevi prolaze kroz privatni red i strogo ograničen helper.

Setup prikazuje instaliranu i dostupnu verziju aplikacije i svakog modula,
provjerava vlasništvo i prava bitnih putanja te se prilagođava mobilnom zaslonu.
Dodani su dvojezični alati i upute za izvoz svih prevodivih stringova te uvoz
provjerenog jezičnog paketa sa zastavicom i lokaliziranim nazivima.

## Namjenski FPM i SAML

Installer i dokumentacija podržavaju račune `fpm-simbioza` i
`simbioza-deploy`, odvojene deploy, read i runtime grupe te allowlistani Setup
worker. Root-owned ulaz helpera prihvaća samo ID provjerenog zahtjeva, a
systemd radnju izvršava kao neprivilegirani deploy račun. Linux pool ima
vlastiti systemd servis, `ProtectSystem=strict`, read-only kod i samo izričito
navedene zapisive runtime putanje. Privatna
SimpleSAMLphp konfiguracija može se vezati samo uz taj pool.
Aplikacija i njezin `/simbioza/simplesaml/` endpoint moraju koristiti isti pool;
upute navode EntityID, ACS i SLO registraciju, prijelaz bez prekida postojeće
prijave i završne provjere.

## Privitci i Confluence uvoz

Ponovno učitavanje datoteke istog naziva sada stvara novu verziju. Za aktualnu
verziju prikazuju se broj verzije, MIME tip, veličina, učitavač i vrijeme.
Povijest i starije verzije dostupne su samo u editoru korisnicima s pravom
uređivanja, objavljivanja ili upravljanja. Prikaz i editor su kompaktniji na
desktopu i ne prelaze rub mobilnog zaslona.

Backup, vraćanje, kopiranje, premještanje i Confluence uvoz čuvaju verzije,
metapodatke i atribuciju. Uvezene stranice tijekom normalnog pregleda više ne
ovise o privremenim Confluence tablicama, a upiti za pretke i privitke koriste
request cache i skupne dohvate.

## Ovlasti i veliki downloadi

Upravljanje uključuje sve radnje, dok Dodavanje autoru dopušta naknadno
uređivanje samo stranica koje je sam dodao. Objavljivač može otvoriti editor za
metapodatke i povijest privitaka. Veliki downloadi obnavljaju vremensko
ograničenje samo dok prijenos napreduje, pa kratki PHP limit više ne prekida
uredan prijenos.

Prije nadogradnje updater izrađuje sigurnosnu kopiju, zatim primjenjuje nove
migracije verzija privitaka i Confluence atribucije.
