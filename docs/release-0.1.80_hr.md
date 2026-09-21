# Simbioza 0.1.80

Ovo korektivno izdanje učvršćuje GUI i CLI nadogradnje na namjenskim FPM
instalacijama.

Updater nakon Composer instalacije sada osigurava da je novo stablo `vendor`
čitljivo FPM procesu, bez promjene vlasnika, grupa ili prava pisanja. Time
restriktivni `umask 0007` ograničenog deploy helpera više ne može nakon uspješne
nadogradnje uzrokovati HTTP 500 pri učitavanju `vendor/autoload.php`. Isto se
pravilo primjenjuje i tijekom automatskog povratka prethodnog izdanja.

GUI više ne proglašava aktivnu pozadinsku nadogradnju neuspjelom samo zato što
Linuxova zaštita procesa skriva deploy PID od FPM korisnika. Postojanje procesa
provjerava se sigurnim POSIX signalom bez slanja stvarnog signala.

Izdanje nema migracija ni promjena poslovnih podataka.
