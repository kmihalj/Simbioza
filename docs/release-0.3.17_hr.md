# Simbioza 0.3.17 — očuvanje dijeljene jezične konfiguracije pri nadogradnji

[English version](https://github.com/kmihalj/Simbioza/blob/0.3.17/docs/release-0.3.17_en.md)

> Povijesna bilješka izdanja. Za aktualne upute pogledajte
> [instalacijske upute](installation_hr.md) i [Simbiozu 0.3.18](release-0.3.18_hr.md).

Ova zakrpa aplikacije ispravlja dva problema s različitim korisnicima u
secured-FPM instalaciji. Verzije svih modula ostaju nepromijenjene:
Audit/Backup/Auth/API **0.3.1**, Email **0.3.3**, HTML Editor/Menu/Theme **0.3.2**,
ostali javni moduli **0.3.0**. Framework ostaje **v0.0.25**, updater **1.0.8**.
Postojeći tagovi ne pomiču se.

## Ispravci

- Nadogradnja jezika više ne zamjenjuje postojeće `installation.php` i
  `languages.php` datotekama deploy korisnika. Postojeća konfiguracija zadržava
  inode, vlasnika, grupu, prava i ACL; zapis se provodi pod zajedničkim lockom
  jezične radnje. Nove datoteke i dalje se objavljuju atomski s privatnim pravima.
- Osvježavanje već aktivnog jezika ne zapisuje ponovno nepromijenjene instalacijske
  postavke. Postojeći konfiguracijski slojevi i ostale administratorske postavke
  ostaju sačuvani.
- Postojeći ispravno zaštićeni `.packs.lock` koristi se bez pokušaja promjene
  njegovih prava. FPM i deploy naizmjenično ga koriste kroz postojeću runtime
  grupu, bez dodatnih ovlasti i širenja prava pisanja aplikacijskog koda.
- Postojeći jezični lockovi otvaraju se bez `O_CREAT`, uz poštivanje Linux zaštite
  `protected_regular` u sticky konfiguracijskim direktorijima. Novi runtime i
  download direktoriji zadržavaju grupno pisanje i uz restriktivan deploy CLI umask.
- Izdvojeni Linux secured-FPM test provjerava instalaciju jezika, uključivanje i
  isključivanje te završno vraćanje prava updatera pod oba identiteta, uz postojeći
  stvarni HTTP test nadogradnje jezika kroz GUI.
- Aktualne instalacijske upute, Docker primjeri i ugrađene instalacijske upute
  koriste **0.3.17**. Ova zakrpa ne uvodi promjenu sheme baze ni verzija modula.

## Nadogradnja

Na ispravnoj klasičnoj instalaciji u Setupu odaberite **0.3.17** ili pokrenite
postojeći aplikacijski updater prema uputi za tu instalaciju. U Docker instalaciji
zamijenite image izdanjem **0.3.17** uz očuvanje runtime i database volumena;
nemojte pokretati klasični updater unutar nepromjenjivog imagea.

Ako je prethodna nadogradnja stala na **96 %** uz grešku konfiguracijskih prava,
datoteka verzije već može prikazivati ciljno izdanje iako održavanje ostaje
uključeno. Nemojte naslijepo uklanjati održavanje ili vraćati stari kod: migracije
baze možda su već provedene. Sačuvajte privatni log nadogradnje i sigurnosnu kopiju,
iz kopije utvrdite izvorna prava pogođenih datoteka te neka administrator vrati
samo utvrđene razlike vlasništva i prava prije pokretanja ispravljenog izdanja.
Nemojte rekurzivno mijenjati vlasništvo aplikacije niti web korisniku dati pravo
pisanja koda.

Revizije jezičnih paketa neovisne su o izdanju aplikacije. Odgođeno osvježavanje
jezika ne znači da je cijela nadogradnja aplikacije neuspješna; provjerite završno
stanje i log, a jezike nakon ovog ispravka nadogradite kroz Setup.
