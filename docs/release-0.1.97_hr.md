# Simbioza 0.1.97

Istodobno uređivanje istog zajedničkog nacrta više ne prepisuje neprimjetno
izmjene drugog urednika. Spremanje sa zastarjelom revizijom odbija se, prikazuje
se aktualni nacrt s poslužitelja, a odbijene izmjene ostaju u lokalnoj kopiji
vezanoj uz korisnika. Urednik može usporediti oba izvorna sadržaja prije nego
što svjesno odluči vratiti ili odbaciti svoje izmjene. Vraćanje ne spaja
sukobljene verzije automatski. Lokalni nacrt briše se tek nakon što poslužitelj
potvrdi uspješno spremanje.

Pri prvom posjetu bira se uključeni jezik prema postavkama preglednika.
Regionalna oznaka poput `fr-CH` može koristiti instalirani paket `fr`.
Ako odgovarajući uključeni jezik ne postoji, koristi se primarni jezik
aplikacije. Ručni izbor u jezičnom izborniku ima prednost i pamti se godinu
dana u kolačiću vezanom uz putanju instalacije. Različite instalacije na
istoj domeni zato čuvaju odvojene izbore. Automatski odabir može se isključiti
postavkom `detect_browser_locale` na `false`.

Izdanje zahtijeva Editor 0.1.36 i Izbornik 0.1.25. Nema migracija baze.
Postojeći nacrti i objavljene stranice ostaju sačuvani; dovoljna je redovna
GUI ili CLI nadogradnja. Podrška preko GitHub Sponsorsa već je uključena u
ranija izdanja 0.1.92–0.1.96.
