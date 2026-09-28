# Simbioza 0.2.0

Ovo izdanje omogućuje nastavivo trajno brisanje velikog, prethodno obrisanog
područja. Administrator jednom potvrđuje točan slug; preglednik zatim obrađuje
ograničene serije i prikazuje traku napretka čije se stanje čuva. Ako se
preglednik ili veza prekine, otvorite **Obrisana područja** i ponovite istu
radnju za nastavak. Područje čije je trajno brisanje započelo ne može se
vratiti u nepotpunom stanju.

HTML Editor sada čita i uklanja samo dokumente i privitke trenutačne serije.
Čišćenje Confluence uvoza dohvaća samo putanje upravljanih privremenih
datoteka, umjesto svih metapodataka privitaka. Time se uklanja uzrok
prekoračenja PHP memorije pri brisanju uvezenih područja s desecima tisuća
privitaka.

Modul područja dodaje jednu migraciju baze za oznaku započetog brisanja i
brojače napretka. Uobičajena nadogradnja aplikacije kroz GUI ili CLI pokreće
migraciju. Prije nadogradnje napravite sigurnosnu kopiju koju je moguće
vratiti. Nadogradnja sama ne briše prethodno obrisano područje: nakon uspješne
nadogradnje vratite se na **Obrisana područja** i izričito pokrenite ili
nastavite trajno brisanje. Dok traje, držite karticu otvorenom. Ako serija ne
uspije, prije ponovnog pokušaja provjerite administratorski zapis; dovršene
serije ostaju sačuvane.

Jezični paketi imaju zasebne verzije. Ako su instalirani dodatni jezici,
ažurirajte ih kroz Setup ili naredbom `vendor/bin/hph languages update` kako
bi i poruke napretka bile prevedene.
