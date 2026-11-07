# Korisničke upute s Wikijem i bez njega

[Engleska verzija](bundled-manual_en.md)

Simbioza isporučuje jedan javni priručnik koji ima **90 poglavlja na hrvatskom, engleskom, njemačkom, španjolskom, francuskom i talijanskom**, uključujući snimke zaslona. Obuhvaća instalaciju, svakodnevni rad, kalendare i sastanke, ankete, suradnju, teme, izbornike, administraciju i API svih dostupnih poslovnih modula. Za čitanje nije potrebno instalirati Wiki. Ponuđene jezike određuju jezici instalirani u aplikaciji.

## Odaberite upute prema svojoj ulozi

| Želite… | Otvorite |
| --- | --- |
| Čitati sadržaj i urediti vlastiti profil | [Moj profil](user-guide/osobni-profil_hr.md) i [Čitanje područja](user-guide/podrucja-citanje_hr.md) |
| Pisati stranice ili upravljati sadržajem | [HTML editor](user-guide/uredivanje-stranica_hr.md), [samostalne stranice](user-guide/samostalne-html-stranice_hr.md) i [Područja](user-guide/podrucja_hr.md) |
| Voditi događaje i sastanke | [Kalendari](user-guide/kalendari_hr.md) i [Sastanci](user-guide/sastanci_hr.md) |
| Sastavljati ili ispunjavati ankete | [Za sastavljače](user-guide/ankete-autori_hr.md) ili [za ispitanike](user-guide/ankete-ispunjavanje_hr.md) |
| Prilagoditi izgled ili navigaciju | [Teme](user-guide/teme_hr.md) i [Izbornici](user-guide/izbornici_hr.md) |
| Podešavati cijelu instalaciju | [Administriranje Simbioze](user-guide/admin-pregled_hr.md) |
| Povezati vlastitu aplikaciju | [Potpuni API priručnik](user-guide/api-koristenje_hr.md) |

Administratorske upute odvojene su od svakodnevnog rada. Za razvojne programere API ima 12 poglavlja: glavni pregled, početak, korisnike i grupe, područja, HTML stranice, kalendare, ankete, zadatke, obavijesti i praćenje, webhookove, audit i pretragu te granice drugih protokola. Obuhvaćeno je **114 deklariranih REST ruta i 123 konkretne operacije**, jer opća ruta promjene ankete ima više zasebnih radnji. Primjeri objašnjavaju ovlasti i parametre te prikazuju cURL/PHP zahtjeve i stvarne odgovore. CalDAV/ICS opisani su zasebno od REST-a.

## Instalacije s Wikijem

Instalacijski program uvozi priručnik kao obične stranice u područje **Korisničke upute**. Za kretanje koristite stablo i padajući izbornik jezika. Prijevodi, privitci i redovno uređivanje stranica ostaju dostupni prema pravima područja.

Nadogradnje osvježavaju isporučene stranice uputa uz očuvanje njihova identiteta i postojećih prava. Roditelji upravljanih poglavlja usklađuju se s isporučenim manifestom; identiteti, postojeći redoslijed i nepovezane stranice područja ne zamjenjuju se. Ako održavate vlastite izmjene isporučene upute, sačuvajte zasebnu kopiju ili prije nadogradnje prenesite promjenu u održavani izvor: sljedeće izdanje može osvježiti tekst upravljane stranice.

## Instalacije bez Wikija

U gornjem izborniku odaberite **Upute**. Simbioza isti pripremljeni priručnik prikazuje kao HTML stranice, bez stvaranja područja i bez instaliranja HTML Editora samo zbog dokumentacije.

- U **Sadržaju uputa** odaberite poglavlje. Na malom zaslonu proširite ga iznad teksta stranice.
- Nastavite gumbima **Prethodno poglavlje** i **Sljedeće poglavlje**.
- Izbornikom sa zastavicama odaberite neki od jezika priručnika instaliranih u aplikaciji.
- Snimke i ostale isporučene ilustracije ostaju dostupne. Poveznice između poglavlja ostaju unutar instalacije.

Ove stranice služe samo za čitanje i dolaze iz instaliranog izdanja. Ne ovise o živim oglednim anketama ni bazi drugog sitea. Nadogradnja Simbioze osvježava zapakirani primjerak; to nije drugo spremište za uređivanje sadržaja.

## Adrese i jezici

`/guides` otvara uvod. Primjerice, `/guides/ankete?lang=hr` bez Wikija otvara poglavlje anketa, a s uključenim Wikijem vodi na njegovu stranicu u području. Ako je aplikacija u poddirektoriju, dodajte ga ispred svake putanje. Javna adresa privitaka uputa poslužuje samo provjerene datoteke koje koriste objavljene isporučene upute, ne privitke živih dokumenata, privatnu konfiguraciju ni sigurnosne kopije.

Sučelje koristi postavke aktivnih jezika instalacije. Priručnik nudi prijevode koji postoje u paketu i instalirani su u aplikaciji. Svih 90 poglavlja isporučuje se na hrvatskom, engleskom, njemačkom, španjolskom, francuskom i talijanskom. Hrvatski i engleski imaju snimke odgovarajućeg sučelja. Njemački, španjolski, francuski i talijanski koriste snimke engleskog sučelja, uz napomenu i prevedene opise. Snimke su primjeri pa se nazivi i datumi mogu razlikovati od vaše instalacije.

## Održavanje priručnika

Održavatelji uređuju izvore odvojene po jezicima pod `docs/user-guide/` te instalacijske/pregledne izvore navedene u `resources/installation/manual-sources.json`. Popis privitaka je `resources/installation/manual-assets.json`. Iz tih izvora nastaju paketi Wiki stranica i priručnik bez Wikija; izmjene se ne održavaju neovisno u dva formata.

Postupak izbora izvora, obnove i provjere opisan je u [Održavanju instalacijskih uputa i cijelog priručnika](maintaining_installation_guides_hr.md). Sama izrada paketa ne mijenja aktivnu bazu niti nadograđuje drugu instalaciju.
