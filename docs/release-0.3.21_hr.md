# Simbioza 0.3.21 — navigacija pune aplikacije

[Engleska verzija](release-0.3.21_en.md)

> Povijesna bilješka izdanja. Za aktualne upute pogledajte
> [instalacijske upute](installation_hr.md) i [Simbiozu 0.3.22](release-0.3.22_hr.md).

Puna Simbioza koristi **Područja** za navigaciju svojim sadržajem. Uz uključena
Područja automatske stavke **HTML stranice** i **Ankete** u gornjem izborniku
prikazuju se samo kada postoji pripadajući samostalni sadržaj. HTML i ankete
provjeravaju se zasebno; sadržaj koji pripada područjima ne ulazi u tu provjeru.

Time se čuva navigacija nakon dodavanja Područja postojećoj HTML ili anketnoj
instalaciji. Nacrti ulaze u provjeru kako bi autori zadržali pristup tijekom
pripreme sadržaja. Obrisane ili isključene HTML stranice i arhivirane ankete ne
ulaze. Nakon premještanja posljednje samostalne stranice u područje ili njezina
uklanjanja automatska stavka nestaje. Isto vrijedi nakon smještanja posljednje
samostalne ankete u područje ili njezina arhiviranja.

Nadogradnja s 0.3.20 uklanja nepotrebne automatske stavke pri sljedećem prikazu navigacije.
Samostalne instalacije bez Područja zadržavaju ulaze u sadržaj i prije izrade prve
stranice ili ankete. Vlastite poveznice administratora ostaju. Postojeće adrese
sadržaja, privitci i ovlasti ne mijenjaju se. Provjere kataloga izvršavaju se
samo pri prikazu HTML navigacije, bez dodatnih upita za API ili CLI radnje.

Nadogradite aplikaciju kroz GUI sigurnog FPM-a ili dokumentiranim CLI postupkom.
Ovo izdanje ne mijenja verzije modula i ne dodaje migracije baze podataka. Docker
instalacije koriste `ghcr.io/kmihalj/simbioza:0.3.21` s postojećim trajnim volumenima.

Vidi [modularne instalacije](modularity_hr.md), [Docker upute](docker_hr.md) i
[verzije modula](module-dependencies_hr.md).
