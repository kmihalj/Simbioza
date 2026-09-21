# Simbioza 0.1.76

Ovo korektivno izdanje dovršava obećano jednako ponašanje CLI nadogradnje na
namjenskoj FPM instalaciji i instalaciji bez FPM-a.

Na FPM instalaciji obični održavatelj sada pokreće istu naredbu `php update.php`
bez `sudo`. Updater provjerava da globalni helper izričito pripada upravo toj
instalaciji, zatim mu predaje strogo dopuštenu pozadinsku nadogradnju i u
terminalu prikazuje njezin novi zapis do završnog statusa. Release kod ostaje u
vlasništvu korisnika `simbioza-deploy`, a održavatelj ne dobiva opću root
ljusku. Na instalaciji bez FPM-a vlasnik koda i dalje izvršava isti updater
izravno.

FPM konfigurator sada pri `--finalize` osvježava helper i dodaje ograničenu
dozvolu grupi `deploy-simbioza`. Postojeće instalacije podešene izdanjem 0.1.75
ili starijim trebaju taj sistemski korak ponoviti samo jednom; nakon toga GUI i
CLI održavanje više ne traže `sudo`.

Dohvat izdanja odvojen je na tihi fetch i checkout pa anotirani Git tag više ne
ispisuje upozorenje da sam objekt taga nije commit. Regresijske provjere
potvrđuju vezivanje helpera uz točan instalacijski korijen, odluku prema
vlasniku koda te sudoers pravila za GUI i održavateljski CLI. Stvarna macOS FPM
instalacija dodatno je nadograđena istom CLI naredbom bez `sudo`, s 34 izvršene
i nula migracija na čekanju.
