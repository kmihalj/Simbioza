# Simbioza 0.3.26 — kontrolirane skripte stranica i prevedene upute

[Engleska verzija](release-0.3.26_en.md)

Aplikacijski JavaScript, uključujući Bootstrap, učitava se lokalno. Objavljene stranice mogu koristiti administratorski odobrene pakete vanjskih skripti samo kada ih autor uključi na stranici. Content Security Policy dopušta aplikacijske predloške i točno odobrene skripte stranice; ugrađeni HTML događaji blokirani su.

Postavke HTML Editora prihvaćaju cijeli isječak za učitavanje JavaScripta i neobaveznog CSS-a te odvojeni kod za pokretanje. Prikaz koda čuva oblikovanje. Upute se uređuju postojećim WYSIWYG editorom, zasebno za instalirane jezike. Autor ih otvara upitnikom u kružiću uz odobrenu skriptu. Prikazuje se jezik sučelja, zatim engleski, pa prvi dostupan prijevod. Bez upute nema upitnika. Obrazac Nova skripta početno je zatvoren.

Ugrađeni DataTables primjer sadrži upute na hrvatskom, engleskom, njemačkom, španjolskom, francuskom i talijanskom. Nove instalacije i nadogradnje ostavljaju ga na čekanju za administratorski pregled. Samo odobrenje ne uključuje ga na stranicama. Postojeći lokalni nazivi, odobrenja i prijevodi ostaju sačuvani. Zasebna privatna priprema Dema odobrava primjer koji posjetitelji mogu odabrati; to nije pravilo automatskog odobravanja javnog modula.

HTML izvoz uključuje odobrene i odabrane skripte stranica: udaljeni resursi ostaju poveznice, a ugrađeni kod postaje lokalna datoteka. Upisani sažeci integriteta ostaju sačuvani. Sigurnosne kopije stranica, područja i sitea prenose resurse i prevedene upute bez dodjeljivanja novog odobrenja na odredištu.

Izdanje uklanja nepozivane privatne Auth metode i nepotrebnu servisnu ovisnost, pojednostavljuje ORM provjeru tipa i zaključava verzije alata za provjeru preglednikom. Protokoli prijave, Framework kod, boje tema i migracije baze ostaju isti.

Koristite GUI za secured FPM ili dokumentirani CLI updater. Postavke instalacije i odabir opcionalnih modula ostaju sačuvani. Docker koristi `ghcr.io/kmihalj/simbioza:0.3.26` s postojećim trajnim volumenima. Vidi [instalaciju](installation_hr.md), [ovisnosti modula](module-dependencies_hr.md) i [Docker](docker_hr.md).
