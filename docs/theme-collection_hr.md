# Kolekcija tema Simbioze

[English version](theme-collection_en.md)

Kolekcija sadrži **12 zasebnih tema**, svaku sa svijetlom i tamnom varijantom. Teme koriste izvorne postavke modula Theme: boje, sistemske fontove, navigaciju, hero, kartice, obrube i sjene. Sve se postavke mogu uređivati u sučelju.

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

## Odabir na HFC-u

Otvorite **Postavke → Tema**, u polju **Aktivna tema** odaberite naziv i spremite postavke. Odaberite automatski, svijetli ili tamni prikaz. Za izmjene pojedine teme odaberite je u uređivaču; postojeće teme ostaju zasebne. Kolekcija ne mijenja zadano aktivnu temu.

## Uvoz na drugu instalaciju

Svaki ZIP u `resources/installation/theme/simbioza-*.zip` potpuni je izvoz jedne teme. U **Postavke → Tema** otvorite **Uvezi cijelu temu**, odaberite odgovarajući ZIP i uvezite ga. Zatim temu odaberite kao aktivnu. Ne učitavajte ZIP cijele kolekcije u obrazac za jednu temu; iz njega prvo raspakirajte pojedini paket.

Paketi sadrže obje palete, raspored i sve četiri pripadajuće slikovne datoteke s kontrolnim sažecima. Teme ne učitavaju vanjske fontove, skripte ili slike. Znak Simbioze ostaje izvorni znak aplikacije; dekorativne vektorske ilustracije izrađene su posebno za kolekciju.

## Pristupačnost i prenosivost

Tekst paleta provjeren je prema kontrastu 4,5:1, uključujući hero, gumbe, tablice i upozorenja. Izvorna poveznica za preskakanje sadržaja i kontrole tipkovnicom ostaju uključene. Pregledi koriste isti CSS koji izvozi modul Theme. Teme su provjerene u WebKitu na desktop širini i širini od 390 px, uključujući stvarne stranice HFC-a u svijetlom i tamnom prikazu. Na stranicama bez hero zaglavlja sadržaj zadržava razmak od navigacije. Sadržaj pojedine instalacije svejedno može zahtijevati prilagodbu rasporeda.

Likovni izvor i palete reproduciraju se skriptom `python3 scripts/generate_theme_collection.py`. Ona stvara datoteke u `output/playwright/themes-20261104` i ne prepisuje aktivne postavke. Konačne validirane konfiguracije nalaze se u `resources/theme-collection/catalog.json`.
