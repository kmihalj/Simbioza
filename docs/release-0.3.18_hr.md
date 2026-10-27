# Simbioza 0.3.18 — ankete i zajednički HTML Editor

[English version](release-0.3.18_en.md)

## Mogućnosti

- **Survey 0.3.0** novi je opcionalni modul. Odaberite ga pri instalaciji ili
  dodajte kroz **Postavke → Moduli i provjere**. Obična nadogradnja prikazuje
  ga kao dostupan, ali ga ne instalira, uključuje niti migrira automatski.
- Ankete podržavaju nacrt, pregled i objavu, neobvezne prijevode, deset vrsta
  pitanja, više stranica, uvjetna pitanja, javno sudjelovanje, korisnike
  aplikacije i neovisne providere SAML/CAS/OIDC/OAuth2.
- Dovršenost anonimne ankete s prijavom bilježi se odvojeno od odgovora.
  Identificirano prikupljanje izričito je naznačeno. Uključeni su pristupačni
  grafovi, prava pregleda, privatni CSV, API s ovlastima i rasporedi izvještaja.
- Povezivanje s područjima dodaje stranice anketa, neobvezno stablo, zasebna
  autorska prava te iznimke pristupa stranici za korisnike, grupe i javnost,
  bez otkrivanja skrivenih susjednih stranica ili privitaka.
- Šifrirani backup područja/stranice uključuje definicije, postavke, povijest,
  odgovore i privitke anketa. Backup cijelog sitea uključuje i administraciju
  Surveya te neovisne postavke providera. Statični HTML izvoz izostavlja ankete.
- Puni HTML Editor dostupan je za zajednička HTML polja, s medijima,
  čišćenjem i dinamičkim elementima. Dokumenti koriste zajedničku komponentu.
- **Korisničke upute → Ankete** sadrže ilustrirane hrvatske i engleske stranice
  za autore, ispitanike, administratore i API developere, s odvojenim
  primjerima cURL i PHP. Ne ovise o testnim anketama ni lokalnim poveznicama.
- Ugrađena svijetla tema Simbioze koristi bijeli tekst sekundarnog gumba.
  Postojeće prilagodbe slijede uobičajena pravila očuvanja.

## Nadogradnja

Sačuvajte obnovljivu kopiju baze i privatne pohrane. U secured-FPM instalaciji
koristite postojeći GUI updater, a u ostalim klasičnim instalacijama njihov
dokumentirani CLI postupak. Provjerite konačni status, odabir modula i migracije
na čekanju pa po želji izričito instalirajte Survey. Isključivanje čuva podatke;
uklanjanje je blokirano dok operativne tablice sadrže retke.

Docker instalacije zamjenjuju image izdanjem `ghcr.io/kmihalj/simbioza:0.3.18`
uz očuvanje runtime i database volumena. Klasični updater ne koristi se u kontejneru.

Privatni Demo ima zaseban korak koji pokreće operater: instalira sve dostupne
opcionalne module, dodjeljuje prava oglednim autorima i snima početnu kopiju
bez anketa. Demo reset briše ankete posjetitelja, odgovore i privatne datoteke.
To ne mijenja obične instalacije i ne postavlja nijedan site automatski.

Framework ostaje **v0.0.25**, a Auth **0.3.1**; njihov kod nije mijenjan.
