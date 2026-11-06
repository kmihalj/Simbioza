# Simbioza 0.3.38 — pouzdano HTML uređivanje i aktualna dokumentacija

HTML polja obrasca sada čuvaju sav podržani sadržaj editora kroz primjenu, spremanje, ponovno učitavanje i uređivanje. To uključuje kartice, stupce, obavijesti, tablice, tabove, padajuće blokove, slike, video, sigurne ugradnje, grafikone, vremenske planove i kanonske integracijske reference. Izvor koji se uređuje sprema se odvojeno od njegova prikaza. Otvaranje editora ne mijenja postojeće razmake medija, izgled tablica ni boje zaglavlja; privremene autorske radnje ne spremaju se kao sadržaj stranice.

## Moduli i dokumentacija

HTML Editor **0.3.15** i Survey **0.3.6** sadrže popravke zajedničkog polja i regresijske provjere. Usklađena izdanja modula obnavljaju njihovu englesku i hrvatsku dokumentaciju. Razvojni planovi i zastarjele bilješke prihvata uklonjeni su iz dokumentacije proizvoda; povijest podržanih izdanja 0.3.x ostaje dostupna.

Aplikacija uključuje **33 dvojezična poglavlja uputa** s 440 korištenih javnih resursa. Upute pokrivaju ispitanike, autore anketa, administratore i razvojne programere, kao i instalaciju, ovlasti, sigurnosne kopije, kalendare i uređivanje stranica. Novo razvojno poglavlje pokazuje kako druga aplikacija može koristiti puni HTML editor u običnom polju obrasca bez stvaranja Wiki stranice ili dokumenta Editora. Modul donosi potpuni primjer kontrolera, prikaza, JavaScripta i adaptera pohrane.

## Dodatni popravci

- API osobnog područja koristi registriranu ovlast `workspace:read`. Privatni sadržaj drugih korisnika ostaje nedostupan.
- Calendar **0.3.4**: arhive kalendara u području čuvaju potpune podatke sastanaka, provjeravaju pristup referenciranim kalendarima i izostavljaju nepovezane događaje resursnih kalendara. Nova arhiva koristi shemu 2; stari obični događaji iz sheme 1 i dalje se čitaju, a nepotpune stare arhive sastanaka odbijaju se prije obnove.
- Novi CLI izvozi sigurnosnih kopija evidentiraju se za čišćenje prema zadanom roku čuvanja. Uklanjaju se samo evidentirani istekli izvozi iz podešenog direktorija arhiva; neevidentirane arhive i ručno čuvanje ostaju nepromijenjeni.

## Nadogradnja i provjera

Framework ostaje **0.0.25**, bez izmjena. Izdanje ne zamjenjuje lokalne postavke instalacije. Postojeće se upute osvježavaju uz očuvanje identiteta i prava pristupa. Produkcijske instalacije zadržavaju uobičajeni GUI/CLI postupak nadogradnje, a zasebno održavani Demo dodatak vlastiti postupak koji pokreće operator.

Provjere izdanja uključuju testove modula, ponovljeno uređivanje u stvarnom kodu editora, pohranu polja obrasca preko HTTP-a, integrirane aplikacijske scenarije, provjeru paketa/arhiva i CI izdanja. Vidi [instalaciju](installation_hr.md), [potpunu uputu za polje obrasca](user-guide/html-editor-u-obrascu_hr.md) i [ovisnosti modula](module-dependencies_hr.md).
