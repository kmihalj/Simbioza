# Simbioza 0.1.92

Ovo izdanje ispravlja instalaciju opcionalnog modula Pristupačnost iz GUI-ja. Modul je bio ispravno prikazan, ali instalacijski postupak koristio je zaseban popis Composer ograničenja u kojem Pristupačnosti nije bilo, pa je radnja odbijena prije pokretanja Composera. GUI i CLI sada čitaju dopuštenu verziju svakog opcionalnog modula iz manifesta izdanja. Regresijski test prolazi putanju instalacije Pristupačnosti i provjerava cijeli katalog opcionalnih modula.

Upute za instalaciju sada su razdvojene u postupne vodiče za namjenski PHP-FPM i Apache mod_php. Početni grafički instaler radi u oba načina; kasnije paketne radnje i nadogradnje kroz GUI traže namjenski FPM. Aktivni profil GitHub Sponsors povezan je s konfiguracijom financiranja repozitorija i README datotekama.

Nema migracije baze ni dodatne ručne promjene postavki. Nadogradite aplikaciju podržanim GUI ili CLI postupkom, zatim ponovno kliknite **Instaliraj** za Pristupačnost u **Postavke → Setup i moduli**. Postojeći jezici, stanje modula, postavke teme i autorski sadržaj ostaju podaci instalacije.
