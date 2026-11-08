# Kolekcija tema Simbioze

[English version](theme-collection_en.md)

Simbioza uključuje **23 zasebnih tema**, svaku sa svijetlim i tamnim izgledom. Koriste izvorne postavke modula Theme: boje, sistemske fontove, navigaciju, hero, kartice, obrube i sjene. Sve postavke možete uređivati u sučelju. Odabir teme mijenja izgled, ne sadržaj niti prava pristupa.

Potpuna kolekcija obuhvaća tri ranije dostupne teme (Simbioza, Simbioza Orbit i Standard), dvanaest stilova navedenih niže te [osam tema za sport, obrazovanje i kreativne zajednice](creative-themes_hr.md). [Potpuna galerija](theme-gallery_hr.md) prikazuje sve 23 teme i oba izgleda.

## Dvanaest uredničkih, modernih i retro stilova

| Tema | Izgled i namjena |
| --- | --- |
| Aurora | Sjeverno svjetlo, hladni plavi tonovi i prozračne kartice. Za moderne portale i timsko znanje. |
| Folio | Topao novinski papir, vinsko crveni naglasci i serifna tipografija. Za časopise, priče i osobne blogove. |
| Neon Arcade | Neonski cijan, ljubičasta i geometrijski gamepad. Za gaming zajednice, turnire i digitalne klubove. |
| Terminal 84 | Monospace, fosforno zelena i jasni četvrtasti obrubi. Retro računalni stil za tehničke timove. |
| Cassette Sunset | Zalazak sunca, kazete i zaobljene linije sedamdesetih. Za glazbu, kreativne zajednice i nostalgične projekte. |
| Botanika | Kadulja, šumska zelena i nacrtani listovi. Miran prostor za obrazovanje, održivost i zajednice. |
| Jadran | Jadransko plavetnilo, kamen i jedra. Svježa tema za udruge, putovanja i otvorene portale. |
| Blueprint | Tehnički nacrt, mreža i indigo. Precizna tema za dokumentaciju, inženjerstvo i istraživačke projekte. |
| Sakura | Ružičasti papir, šljiva i cvjetovi trešnje. Nježna tema za osobne bilješke, radionice i kreativne blogove. |
| Pop Studio | Kobalt, žuta i razigrani geometrijski oblici. Grafički snažna tema za dizajn, događanja i kreativne kolektive. |
| Espresso | Kava, bakar i dugi tekstovi u serifu. Urednička tema za dnevnike, eseje i stručne članke. |
| Nocturne | Ponoćno plava, mjesečeve orbite i zlatni detalji. Elegantna tema za kulturne ustanove i posebne projekte. |

## Odabir teme

Otvorite **Postavke → Tema**, u polju **Aktivna tema** odaberite naziv i spremite postavke. Odaberite automatski, svijetli ili tamni prikaz. Za izmjene pojedine teme odaberite je u uređivaču; postojeće teme ostaju zasebne. Kolekcija ne mijenja zadano aktivnu temu.

## Uvoz na drugu instalaciju

Institucijski preseti više nisu dio javne kolekcije. Jednokratno čišćenje pri nadogradnji povlači samo neizmijenjene primjerke evidentirane kao isporučene; uređene teme i izričiti lokalni odabiri čuvanja ostaju. Ako je povučena tema bila aktivna, site koristi Simbiozu, odnosno Standardnu temu kada Simbioza nije dostupna.

[Preuzmi ZIP kolekciju sve 23 teme](https://github.com/kmihalj/Simbioza/releases/download/0.3.46/Simbioza-0.3.46-themes.zip)

Nadogradnja od izdanja 0.3.36 dodaje nedostajuće priložene teme samo jednom. Administratorske prilagodbe i lokalni odabiri čuvanja tema ostaju sačuvani. Nakon što izbrišete dodatnu temu, sljedeće je nadogradnje neće vratiti. Evidencija uvoza nalazi se u konfiguraciji Tema i uključena je u backup/restore sitea. Temu možete ponovno ručno uvesti iz ZIP-a.

Svaki ZIP u `resources/installation/theme/<id-teme>.zip` potpuni je izvoz jedne teme; svih 23 ID-jeva navedeno je u `resources/installation/theme/collection.json`. U **Postavke → Tema** otvorite **Uvezi cijelu temu**, odaberite odgovarajući ZIP i uvezite ga. Zatim temu odaberite kao aktivnu. Ne učitavajte ZIP cijele kolekcije u obrazac za jednu temu; iz njega prvo raspakirajte pojedini paket.

Paketi sadrže obje palete, raspored i datoteke koje ta tema koristi, s kontrolnim sažecima. Teme ne učitavaju vanjske fontove, skripte ili slike. Znak Simbioze ostaje izvorni znak aplikacije; dekorativne vektorske ilustracije izrađene su posebno za kolekciju.

## Pristupačnost i prenosivost

Galerija prikazuje stvarni Theme izvoz uz jednak ogledni sadržaj. Izvorna poveznica za preskakanje sadržaja i kontrole tipkovnicom ostaju dostupne. Prije primjene prilagođene teme provjerite kontrast teksta (u pravilu najmanje 4,5:1), vidljivost fokusa, izbornike, navigacijsku putanju i kartice u oba izgleda te na velikom i uskom zaslonu. Sama provjera palete ne dokazuje pristupačnost svake stranice ili fotografije. Boje, visinu hero elementa, položaj ilustracije i razmake možete uređivati. Stranice bez hero elementa trebaju zadržati razmak između navigacije i sadržaja.

## Izvori za održavatelje

Dvanaest izvornih konfiguracija nalazi se u `resources/theme-collection/catalog.json`; osam kreativnih tema ima zasebne [upute o izvorima](creative-themes_hr.md). Mjerodavan instalacijski skup čine 23 provjerenih paketa i `resources/installation/theme/collection.json`.

`python3 scripts/generate_theme_collection.py --output output/theme-sources` ponovno izrađuje dvanaest izvornih definicija i ilustracije u zasebnom direktoriju. Treba instalirani Theme paket i lokalne izvorne datoteke Simbiozina znaka koje generator koristi. Ne instalira teme i ne mijenja aktivnu konfiguraciju. To je postupak održavatelja, ne korak instalacije ili nadogradnje. Pregledajte rezultat prije zamjene pripremljenih paketa.

[Potpuna galerija sve 23 teme](theme-gallery_hr.md).
