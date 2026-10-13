# Održavanje instalacijskih uputa

[Engleska verzija](maintaining_installation_guides_en.md)

Održavani izvori su `installation_en.md`/`installation_hr.md` i odvojeni,
cjeloviti Markdown parovi `installation_mod_php_*`, `installation_fpm_*` i
`installation_fpm_secured_*`. Svaka instalacija kreće od početka i sadrži
vlastite naredbe; ne trebaju dijelovi preduvjeta iz druge instalacijske upute.

Instalirane Korisničke upute imaju preglednu stranicu, mod_php stranicu i
PHP-FPM stranicu. PHP-FPM stranica sadrži dva odvojena cjelovita postupka,
uz sidra `#ordinary-fpm` i `#secured-fpm`. Oba njezina jezika i arhiva cijelog
početnog područja generiraju se iz istih Markdown izvora.

## Uredite i provjerite

Iz razvojnog Git direktorija s instaliranim Composer ovisnostima:

```bash
php scripts/sync_installation_guides.php --apply
php scripts/sync_installation_guides.php --check
```

Samo `--apply` treba Pandoc (`brew install pandoc` ili
`sudo apt-get install pandoc`). Renderira javni Markdown, osvježava četiri
šifrirane arhive uputa i čuva identitete dokumenata, prava, nepovezane stranice
i datoteke privitaka. Ne povezuje se na aplikacijsku bazu niti kopira privatnu
konfiguraciju. `--check` radi offline i ne treba Pandoc; CI njime odbija
neusklađene izvore i pakete.

Šifrirani HTML sadržaj ne uređujte zasebno. Uredite EN/HR Markdown par,
ponovno izradite pakete, pregledajte razlike i pokrenite testove uputa/instalacije.
To su razvojne naredbe, ne dio instalacije krajnjeg korisnika.

## Instalirani siteovi i označena izdanja

GitHub `main` može sadržavati novije upute od instaliranog paketa sitea.
Promjena Markdowna ne mijenja izravno njegove pohranjene stranice.
Nadogradnje aplikacije osvježavaju ugrađene stranice kroz
`BundledAssetsUpdater`, bez zamjene nepovezanih stranica ili njihovih ACL-ova.
Nova instalacija uvozi pakete uputa koji pripadaju odabranom izdanju.

Pri razvojnom osvježavanju samo dokumentacije objavite obnovljene pakete i
osvježite upravljane upute razvojnog sitea njegovim aktualnim servisima.
Nemojte pretpostaviti da su time objavljene i na drugom siteu. Arhiva starog
izdanja ostaje povijesna snimka dok novo izdanje ne isporuči obnovljene pakete;
nemojte premještati tag aplikacije samo radi usklađivanja ugrađenih uputa s
`main`.
