# Simbioza 0.2.6

Nova instalacijska uputa zamjenjuje dosadašnju stranicu Instalacija u Korisničkim uputama. Podstranice za Apache mod_php i PHP-FPM obuhvaćaju obični i izolirani FPM, preduvjete, konfiguraciju web-poslužitelja, prvog administratora, pripremu paketa i kasnije održavanje. Upute su uključene u nove instalacije, a pri nadogradnji postojećeg sitea osvježavaju se bez zamjene drugih stranica ili njihovih prava.

Početni instalacijski čarobnjak sada unaprijed označava sve opcionalne module. One koji vam ne trebaju odznačite. Za Apache mod_php ili obični FPM bez namjenskog Setup pomoćnog programa, `php scripts/installation_packages.php prepare` priprema sve opcionalne pakete prije otvaranja čarobnjaka; `--modules=theme,calendar` priprema uži izbor, a `--modules=` nijedan. Odabir u CLI-ju mora odgovarati odabiru u čarobnjaku. Namjenski izolirani FPM može pripremiti odabrane pakete kroz Setup.

Nema migracija baze. Prije nadogradnje načinite obnovljivu sigurnosnu kopiju. Postojećim instalacijama moduli se ne dodaju automatski: novi zadani odabir vrijedi samo za novu instalaciju.
