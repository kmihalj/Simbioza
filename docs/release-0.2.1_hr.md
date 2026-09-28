# Simbioza 0.2.1

Ovo izdanje ispravlja iscrpljivanje PHP memorije tijekom provjere velike
Confluence XML arhive. Posao uvoza sada u bazi čuva samo inventar potreban za
mapiranje identiteta, a ne sve stranice i privitke. Popis poslova i odustajanje
od prijenosa više ne učitavaju taj veliki sažetak.

Kasniji koraci uvoza mogu privremeno podići vlastiti PHP memorijski limit na
512 MB. Ako poslužitelj to zabranjuje, uvoznik prije izmjene sadržaja javlja
jasan zahtjev za memorijskim limitom. Za osobito velike arhive administrator
može prilagoditi `import_memory_limit_mb` u konfiguraciji modula za Confluence
uvoz. PHP-FPM mora dopuštati odabrani limit.

Prije nadogradnje napravite obnovljivu sigurnosnu kopiju. Aplikaciju zatim
nadogradite kroz Setup ili CLI; instalirani opcionalni moduli ažuriraju se
tijekom nadogradnje aplikacije. Modul za Confluence uvoz treba dosegnuti
verziju 0.1.38. Jezični paketi imaju zasebnu reviziju i po potrebi se mogu
osvježiti kroz Setup.

Neuspjeli prijenos iz prethodnog izdanja treba ukloniti prije novog pokušaja.
To ne uklanja izvornu Confluence arhivu. Stvarni uvoz velikog područja može
trajati dulje; ostavite prikaz uvoza otvorenim, a kod pogreške provjerite popis
nedavnih poslova ili tehnički zapis.
