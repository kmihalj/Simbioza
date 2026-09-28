# Simbioza 0.2.3

Alat za izolirani PHP-FPM sada prihvaća jedinstvenu oznaku `--instance` za
svaku Simbioza instalaciju na istom poslužitelju. Odvojeni sistemski računi,
grupe, pomoćni programi, FPM konfiguracije i servisi sprječavaju da druga
instalacija prepiše prvu. Ponovna uporaba oznake za drugi direktorij odbija
se prije promjena na sustavu. Postojeće instalacije bez opcije `--instance`
zadržavaju dosadašnja imena i ponašanje.

Upute za Apache mod_php na macOS-u sada objašnjavaju nasljedni ACL potreban
održavatelju. Bez njega grafički instaler može stvoriti privatne datoteke
koje kasniji CLI updater ne može čitati. FPM upute dodaju i čekanje web-
poslužitelja do 900 sekundi pri duljoj pripremi paketa iz grafičkog instalera;
ako postoji dodatni proxy, treba uskladiti i njegovo čekanje. To ne mijenja
prava nad paketima niti ograničenja drugih siteova.

Izdanje ne sadrži migraciju baze. Prije nadogradnje napravite obnovljivu
sigurnosnu kopiju. Za novu instalaciju pratite cjelovite
[instalacijske upute](installation_hr.md), uključujući provjere prava i
web-poslužitelja za odabrani PHP način.
