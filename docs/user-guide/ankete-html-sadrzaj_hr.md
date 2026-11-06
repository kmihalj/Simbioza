# HTML sadržaj i sigurnost

[Za developere — API](ankete-developeri_hr.md)

HTML iz integracije prolazi isto serversko čišćenje kao tekst unesen kroz uređivač. Uređivanje izvora nije iznimka od sigurnosnih pravila.

<div class="table-responsive">

| Sadržaj | Ponašanje |
|----|----|
| Uobičajeni tekst, b/strong, i/em, naslovi, liste, tablice i sigurni mediji | Dopušteno u okviru pravila HTML Editora. |
| script, style i atributi poput onclick/onerror | Uklanjaju se; ne koristite ih za logiku ankete. |
| javascript:, data: i slični izvršivi URL-ovi | Ne prihvaćaju se. |
| Nepodržani atributi i CSS | Mogu biti uklonjeni i kada nisu opasni. |
| Grafikoni, planovi, tabovi, harmonike, padajući blokovi i interaktivne tablice | Podržani Editor HTML i postavke ostaju sačuvani i koriste zajednički prikaz Editora. |
| Blokovi kalendara, zadataka, uključene stranice i područja | Sigurne reference i postavke ostaju sačuvane. Bez servisa aplikacije koji razrješava blok ostaje neizvršiva oznaka mjesta za sadržaj. |
| Iframe ugradnje | Čuvaju se samo ugradnje koje prihvaćaju zajednička Editor pravila, uključujući njihove obvezne zaštitne postavke. |

</div>

Koristite kanonske strukture koje stvara Editor, a ne proizvoljni izvršivi HTML. Primjena i spremanje čuvaju podržani sadržaj; generirani prikaz grafikona ili plana ne zamjenjuje njegov izvor za uređivanje. Survey ne pretvara reference registra skripti u izvršive skripte niti daje pristup ciljevima dinamičkih blokova. Uvjete pitanja šaljite kao podržanu deklarativnu definiciju, ne kao JavaScript.

Pri ugradnji u drugi sustav poštujte granice pristupa privitcima. Poveznica na privatni sadržaj ne daje pravo čitanja. Odgovore ispitanika uvijek prikazujte kao podatke, ne kao izvršivi HTML.
