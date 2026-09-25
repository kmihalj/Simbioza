# Simbioza 0.1.93

Ovo održavateljsko izdanje ispravlja prava opcionalnih Composer paketa instaliranih ili uklonjenih kroz GUI i CLI. Ograničeni FPM pomoćni program koristi privatni umask; prije je novi paket mogao biti označen kao uključen iako web-proces nije mogao čitati njegov kod. Nakon svake uspješne paketne radnje Simbioza sada osigurava čitanje koda i prolaz kroz direktorije, bez širenja prava pisanja ili izlaganja Git metapodataka. Regresijski test reproducira restriktivna prava i provjerava rezultat.

Tablica modula u Setupu sada prelama dugačke oznake stanja unutar njihova stupca, pa gumbe više ne prekrivaju poruku o uklonjenom modulu i sačuvanoj kopiji podataka.

Nema migracije baze. Na instalaciji s koje je Pristupačnost uklonjena nakon neuspjelog pokušaja najprije nadogradite Simbiozu na 0.1.93, a zatim za Pristupačnost odaberite **Vrati iz kopije** ako želite zadržati spremljene podatke modula, odnosno **Nova instalacija** ako ih ne trebate. Nakon instalacije provjerite stavku **Postavke → Pristupačnost** i widget.
