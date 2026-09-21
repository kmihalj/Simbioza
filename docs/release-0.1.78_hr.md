# Simbioza 0.1.78

Ovo korektivno izdanje popravlja pokretanje nadogradnje iz GUI Setupa na
Linux instalaciji s namjenskim FPM-om.

Privremena systemd jedinica sada prati cijelu procesnu grupu Setup workera.
Kratki web zahtjev zato može završiti odmah, dok ograničeni
`simbioza-deploy` updater ostaje živ, nadziran i izoliran do završetka. Raniji
helper zaustavljao je pozadinski proces čim bi worker vratio početnu potvrdu,
pa je sučelje ostajalo na stanju **Nadogradnja čeka pokretanje** uz prazan
privatni dnevnik.

Zaostali status čiji PID više ne postoji sada se prikazuje kao neuspjela
nadogradnja i više ne zaključava ponovni pokušaj. Nisu dodane migracije ni
promjene poslovnih podataka.

Instalacije koje su FPM podesile izdanjem 0.1.77 ili starijim trebaju nakon
CLI nadogradnje jednom ponoviti `configure_fpm_setup.php --finalize`. Time se
osvježava root-owned helper; nakon toga buduće GUI i CLI nadogradnje ponovno
rade bez `sudo`.
