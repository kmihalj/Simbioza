# Simbioza 0.3.48 — veliki HTML izvozi područja

> Povijesna bilješka izdanja. Pogledajte [aktualno izdanje](release-0.3.49_hr.md).

Izvoz velikog područja u HTML mogao se zaustaviti usred preuzimanja kad bi PHP
dosegnuo ograničenje izvršavanja. Modul Workspace **0.3.8** daje ovlaštenim
izvozima iz obrasca i preko API-ja najmanje 900 sekundi izvršavanja PHP-a kad
web okruženje dopušta promjenu. To vrijeme obuhvaća pripremu i slanje ZIP-a.

[Upute za rješavanje problema](troubleshooting_hr.md#prekida-se-preuzimanje-html-izvoza-podru%C4%8Dja) opisuju postavke PHP-a, web poslužitelja i proxyja te provjeru cjelovitosti
preuzete arhive.

Za nadogradnju koristite GUI na pravilno podešenom zaštićenom PHP-FPM-u ili
dokumentirani CLI postupak na ostalim klasičnim instalacijama. Demo ima vlastiti
postupak nadogradnje. Ovo izdanje ne uvodi nove migracije baze.

Vidi [instalaciju](installation_hr.md), [Docker instalaciju](docker_hr.md)
i [povijest izdanja](releases_hr.md). Kolekcija od 23 teme dostupna je u
[paketu tema](https://github.com/kmihalj/Simbioza/releases/download/0.3.48/Simbioza-0.3.48-themes.zip).
