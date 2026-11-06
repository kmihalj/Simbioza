# Za razvojne programere

Simbioza i HeartPhrame moduli mogu se povezati s drugim aplikacijama. Odaberite primjer prema tome gdje se nalazi vaša forma ili podaci.

## HTML editor u vlastitoj formi

Želite li da korisnik u tekstualnom polju uređuje oblikovani sadržaj, ugradite HTML editor kao komponentu. Korisnik odabere **Uredi sadržaj**, uredi tekst u dijalogu, primijeni promjenu i zatim spremi vašu formu. Vaša aplikacija provjerava ovlasti, sanitizira HTML i sprema ga u svoje polje.

Otvorite [HTML editor u polju obrasca](html-editor-u-obrascu_hr.md) za primjer, stvarne snimke i objašnjenje razlike između primjene teksta i spremanja obrasca. Ne morate stvarati Wiki područje ni dokument u Simbiozi.

## API za vanjske aplikacije

Za dohvat ili promjenu podataka preko HTTP-a potreban je instaliran modul **API**, aktivan ključ s odgovarajućim opsegom i korisnik koji ima pravo na sadržaj. Ključ ne proširuje korisnikove ovlasti. Ključeve držite na poslužitelju, nikada u JavaScriptu koji šaljete pregledniku.

- [API: ugovor, autentikacija i rute](../api-v1-contract_hr.md)
- [Ankete: cURL primjeri](ankete-api_hr.md)
- [Ankete: zasebni PHP primjeri](ankete-api-php_hr.md)

Primjeri koriste ogledne adrese i podatke. Zamijenite ih vrijednostima svoje instalacije; ne kopirajte tajne u dokumentaciju ili javni repozitorij.

## Razvoj i održavanje

Prije integracije provjerite ovisnosti konkretnog modula i registraciju njegovih ruta i servisa. Počnite od javnog API-ja modula, ne od internih tablica baze. HeartPhrame framework koristimo kao vanjsku ovisnost; ovaj projekt ne održava njegov izvorni kod.
