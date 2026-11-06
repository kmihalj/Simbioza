# Korisničke upute s Wikijem i bez njega

[English version](bundled-manual_en.md)

Simbioza isporučuje jedan javni priručnik koji ima **31 poglavlje na hrvatskom i engleskom**, uključujući snimke zaslona. Obuhvaća instalaciju, svakodnevni rad, kalendare i sastanke, ankete, administraciju i API anketa. Za čitanje nije potrebno instalirati Wiki. Ponuđene jezike određuju jezici instalirani u aplikaciji.

## Instalacije s Wikijem

Instalacijski program uvozi priručnik kao obične stranice u područje **Korisničke upute**. Za kretanje koristite stablo i padajući izbornik jezika. Prijevodi, privitci i redovno uređivanje stranica ostaju dostupni prema pravima područja.

Nadogradnje osvježavaju isporučene stranice uputa uz očuvanje njihova identiteta i postojećih prava; nepovezane stranice područja ne zamjenjuju se. Ako održavate vlastite izmjene isporučene upute, sačuvajte zasebnu kopiju ili prije nadogradnje prenesite promjenu u održavani izvor: sljedeće izdanje može osvježiti tekst upravljane stranice.

## Instalacije bez Wikija

U gornjem izborniku odaberite **Upute**. Simbioza isti pripremljeni priručnik prikazuje kao HTML stranice, bez stvaranja područja i bez instaliranja HTML Editora samo zbog dokumentacije.

- U **Sadržaju uputa** odaberite poglavlje. Na malom zaslonu proširite ga iznad teksta stranice.
- Nastavite gumbima **Prethodno poglavlje** i **Sljedeće poglavlje**.
- Ako su instalirana oba jezika priručnika, izbornikom sa zastavicama odaberite hrvatski ili engleski.
- Snimke i ostale isporučene ilustracije ostaju dostupne. Poveznice između poglavlja ostaju unutar instalacije.

Ove stranice služe samo za čitanje i dolaze iz instaliranog izdanja. Ne ovise o živim oglednim anketama ni bazi drugog sitea. Nadogradnja Simbioze osvježava zapakirani primjerak; to nije drugo spremište za uređivanje sadržaja.

## Adrese i jezici

`/guides` otvara uvod. Primjerice, `/guides/ankete?lang=hr` bez Wikija otvara poglavlje anketa, a s uključenim Wikijem vodi na njegovu stranicu u području. Ako je aplikacija u poddirektoriju, dodajte ga ispred svake putanje. Javna adresa privitaka uputa poslužuje samo provjerene datoteke koje koriste objavljene isporučene upute, ne privitke živih dokumenata, privatnu konfiguraciju ni sigurnosne kopije.

Sučelje koristi postavke aktivnih jezika instalacije. Priručnik nudi prijevode koji postoje u paketu i instalirani su u aplikaciji. Tekst i snimke trenutačno su na hrvatskom i engleskom; instalacija drugog jezika sučelja ne prevodi ih automatski. Snimke su primjeri pa se nazivi i datumi mogu razlikovati od vaše instalacije.

## Održavanje priručnika

Održavatelji uređuju odvojene EN/HR izvore pod `docs/user-guide/` te instalacijske/pregledne izvore navedene u `resources/installation/manual-sources.json`. Popis privitaka je `resources/installation/manual-assets.json`. Iz tih izvora nastaju paketi Wiki stranica i priručnik bez Wikija; izmjene se ne održavaju neovisno u dva formata.

Postupak izbora izvora, obnove i provjere opisan je u [Održavanju instalacijskih uputa i cijelog priručnika](maintaining_installation_guides_hr.md). Sama izrada paketa ne mijenja aktivnu bazu niti nadograđuje drugu instalaciju.
