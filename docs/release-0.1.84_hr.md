# Simbioza 0.1.84

Ovo korektivno izdanje popravlja prikaz GUI nadogradnje na namjenskim FPM
instalacijama.

Stvarna nadogradnja mogla je uspješno završiti, a preglednik ipak ostati na
prikazu napretka. Takvo su ponašanje uzrokovale dvije neovisne FPM pojedinosti:

- maintenance front controller tražio je `data/` prema javnoj web-putanji
  umjesto prema stvarnom korijenu instalacije iz `HPH_APP_PATH`;
- nove datoteke izdanja nasljeđivale su restriktivni `umask 0007` deploy helpera
  pa su mogle ostati nečitljive izoliranom FPM korisniku.

Statusna adresa održavanja sada prije učitavanja Composera pronalazi stvarni
korijen aplikacije. Updater ujedno release datotekama dodaje čitanje, a
direktorijima prolaz za FPM, bez širenja prava pisanja i bez promjene privatne
konfiguracije, podataka ili administratorski upravljanih resource postavki.

Kada nadogradnju pokrene verzija 0.1.83, završni korak novog izdanja obavlja isti
popravak prava prije isključivanja održavanja. Zato se izravna GUI nadogradnja s
0.1.83 sama oporavlja iako ju je pokrenuo stariji updater.

Regresijski testovi reproduciraju restriktivni umask namjenskog helpera i
potvrđuju da privatne postavke ostaju privatne. Izdanje nema migracija ni
promjena poslovnih podataka.
