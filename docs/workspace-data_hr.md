# Pohrana podataka područja i stranica

[Engleska verzija](workspace-data_en.md)

Konfiguracija instalacije pripada u `HPH_CONFIG_PATH/<modul>/`. Sadržaj područja
je aplikacijski podatak i ostaje pod `data/`, neovisno o konfiguracijskom
direktoriju. GUI, javne rute stranica, UUID URL-ovi privitaka i provjere prava
ostaju nepromijenjeni.

## Raspored direktorija

```text
data/workspaces/<oznaka-pohrane>--<ID-područja>/
  pages/<oznaka-stranice>--<UUID-dokumenta>/
    content/<jezik>/versions/000001.html
    content/<jezik>/draft/<naziv>.html
    attachments/<spremljeni naziv>
    menus/<hash ID-a konteksta>.json
  menus/<hash ID-a konteksta>.json
  theme/<datoteke i resursi privatne teme>
```

Filesystem HTML verzije i skice nalaze se uz privitke stranice. Čitljive ASCII
oznake imaju najviše 48 znakova; ID područja i puni UUID dokumenta sprječavaju sukobe.
`workspaces.storage_slug` čuva početnu oznaku područja, a postojeće reference datoteka
čuvaju oznaku stranice nakon preimenovanja ili brisanja u smeće i vraćanja.
Prikazani nazivi i javni URL slugovi nisu identiteti filesystema. Verzije privitaka dijele direktorij privitaka stranice;
postojeći spremljeni nazivi razlikuju povijesni sadržaj.

Kod pohrane HTML-a u bazu sadržaj ostaje u bazi i HTML direktorij nije potreban.
Metapodaci, prava i tijekovi objave ostaju u bazi u oba načina. Privitci ostaju
datoteke u oba načina. Postojeće radnje promjene pohrane Editora koriste ovaj
raspored za filesystem → baza i baza → filesystem. Novi Confluence uvozi koriste
istu Editor/Workspace integraciju za početni HTML, kasnije verzije i povijest
privitaka.

Baza koristi provjerene reference `@data/workspaces/...` za vlasničke datoteke,
a ne apsolutne putanje instalacije. To su interni identifikatori pohrane, ne javni
URL-ovi preuzimanja. Brisanje u smeće i vraćanje čuvaju identitet stranice i reference.

## Vlasništvo i zajednički podaci

Samo dokument s jednim deklariranim područjem vlasnikom dobiva vlasnički
direktorij. Samostalni dokumenti i dokumenti koje dijeli više područja koriste
zajedničke korijene podešene u Editoru. Generički Editor i Menu i dalje rade bez Workspacea.

Posebni meniji deklariraju `owner_target` kao `workspace.<ID>` ili
`workspace.<ID>.page.<ID čvora>`. Naziv konteksta sam ne određuje vlasnika. Proizvoljni
uzorci ruta sami ne određuju vlasništvo. Definicije vlasničkih menija područja ili
stranice nalaze se u odgovarajućem `menus/` direktoriju. `data/menu/contexts.json`
ostaje mali zajednički indeks `menu_ref` putanja i ugrađenih nevlasničkih konteksta,
bez dupliciranja vlasničkih definicija. Ručne izmjene moraju zadržati ispravan
JSON i ID konteksta koji određuje hash naziva datoteke; GUI čita i piše iste datoteke.
Backup područja odabire deklarirane vlasnike menija i kada uzorak njihove rute
ne sadrži slug područja. Nevlasnički zajednički konteksti zadržavaju odabir po
ruti prema pravilima zajedničkih konteksta.

Trajno brisanje stranice (uključujući odbacivanje nikad objavljene stranice)
uklanja njezine izričito vlasničke datoteke menija i reference indeksa prije
uklanjanja čvora stabla. Trajno brisanje područja uklanja i sve njegove vlasničke
menije stranica. Meko brisanje ih čuva za vraćanje. Zajednički konteksti ne brišu
se samo zato što im ruta odgovara obrisanom području. Ovo čišćenje treba Menu
repozitorij, ne njegove opcionalne GUI servise.

Privatne datoteke tema područja nalaze se u `data/workspaces/<oznaka-pohrane>--<ID>/theme/`.
Definicije tema ostaju u bazi područja; zajedničke sistemske teme ostaju u
`data/themes/`. Zajednički cache i generirani snapshotovi stilova ostaju zajednički
podaci Editora, ne sadržaj stranice.

## Nova instalacija i aktualni backup

0.3.0 izravno stvara ovaj raspored. Nema premještanja starih direktorija,
recovery journala ni nadogradnje sheme ranijih verzija Simbioze. Koristite novu
bazu; ne instalirajte preko starog podatkovnog stabla.

Područja su u `workspaces`, čvorovi i veze dokumenata u `workspace_nodes`,
inačice HTML-a u `editor_html_document_versions`, a ovlasti u `workspace_acl`
i `workspace_node_acl`. `storage_slug` je domenski metapodatak, ne postavka
instalacijske konfiguracije.

Backup formata 2 izvozi referencirani HTML, povijest privitaka, vlasničke izbornike
i privatne teme. Import mapira ciljne identitete, stvara aktualne vlasničke putanje
i koristi HTML driver odredišta. Datotečni/konfiguracijski provideri čuvaju rollback
stanje do uspjeha povrata. Stare Simbioza sheme arhiva odbijaju se; vanjski
Confluence XML uvoz zaseban je i ostaje podržan.

## Linkovi između siteova

Arhiva zapisuje javnu adresu izvora, uključujući osnovnu putanju aplikacije.
Povrat uspoređuje tu adresu s odredištem. Na istom siteu ostaje dosadašnje ponašanje
linkova; kopiranje i dalje mapira identitete na kopirani sadržaj. Povrat na drugi
site razrješava interne linkove stranica, privitaka i uključenih stranica preko
uvezenih ciljnih identiteta i ruta. Relativni i apsolutni linkovi izvornog sitea
dobivaju ciljne rute uz očuvanje query parametara i sidara.

Link čvorovi i vlasnički izbornici koriste isto mapiranje. Vanjski linkovi ne
prepisuju se globalno. Interna referenca bez uvezenog cilja ostaje usmjerena na
izvor i prijavljuje se kao nerazriješena, umjesto pogađanja preko nepovezane ciljne
stranice istog sluga. Provjera se temelji na metapodatcima arhive i uvezenim
identitetima, ne obilazi privatne ili vanjske URL-ove.

Zajednički cache, generirani CSS i datoteke koje nijedan izvezeni provider ne
referencira nisu sadržaj stranice. Prenosivi backup nije sirova kopija lozinki,
konfiguracijskih putanja, serverskog vlasništva ili deployment postavki.
