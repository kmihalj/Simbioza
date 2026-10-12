# Simbioza 0.1.95

> Povijesna bilješka izdanja — nije aktualna uputa za instalaciju ni nadogradnju. Za čistu osnovu 0.3.0 koristite [instalaciju](installation_hr.md) i [aktualnu konfiguraciju](configuration_hr.md). Upute za nadogradnju starijih verzija u nastavku ostaju samo kao povijest.

Ovo izdanje pouzdano dodaje postavke opcionalnih modula u izbornik. Izbornik
0.1.23 prihvaća prijavu stavki prije ili nakon vlastitog pokretanja, dodaje
samo nedostajuće stavke te čuva administratorske nazive, redoslijed i stanje
uključenosti. Pristupačnost 0.1.2 koristi tu neobveznu integraciju kada
aplikacija ima rutu njezinih postavki. I dalje radi bez Izbornika ili Teme.

Grafički instalacijski čarobnjak sada razlikuje pogrešku pripreme jezika od
pogreške pripreme paketa. Na instalaciji bez pomoćnog programa održavatelj
može pripremiti objavljene jezike prije nastavka čarobnjaka. Zamjena
predmemorije jezičnog kataloga i zapis postojeće konfiguracije rade s
odvojenim FPM i deploy identitetima. Prava spremišta tema provjeravaju se
prije početka migracija baze.

Ako je Pristupačnost uklonjena, najprije nadogradite Simbiozu na 0.1.95, a
zatim ponovno instalirajte Pristupačnost kroz Setup. Sačuvanu kopiju podataka
modula ne treba brisati. Izbornik mora biti barem 0.1.23, a Pristupačnost
barem 0.1.2.
