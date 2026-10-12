# Šifriranje

HeartPhrame aplikacija sadrži unaprijed podešenu komponentu za sigurno
AES-256-GCM šifriranje i dešifriranje. Ovaj dokument objašnjava kako je
podesiti i koristiti.

## Konfiguracija

Postavke šifriranja nalaze se u konfiguracijskoj datoteci okruženja.

1. **Konfiguracijska datoteka:** ključ se definira u
   `<HPH_CONFIG_PATH>/env.php` (zadano `config/env.php`). PHP format ostaje
   aktualan. Ako datoteka ne postoji, krenite od `config/env.php.dist` u odabranom
   konfiguracijskom sloju; primjerom nikad ne prepisujte postojeći ključ.
2. **Postavljanje ključa:** pronađite postavku `encryption_key` u
   efektivnom `env.php`; vanjske putanje i nadjačavanja opisani su u
   [konfiguracijskoj uputi](configuration_hr.md):

    ```php
    return [
        // ...
        'encryption_key' => 'ovdje-upisite-siguran-kljuc',
        // ...
    ];
    ```

## Generiranje ključa

Radi sigurnosti generirajte snažan slučajni ključ. Aplikacija za to sadrži CLI
naredbu. Pokrenite je iz korijena projekta:

```bash
vendor/bin/hph encryption:generate-key
```

Kopirajte ispisani ključ i postavite ga kao vrijednost `encryption_key` u
datoteci `env.php` odabranog konfiguracijskog sloja.

## Uporaba

Komponenta je dostupna kroz ubrizgavanje ovisnosti. U svojim klasama navedite
sučelje `HeartPhrame\Encryption\EncryptionInterface`.

### Primjer

```php
namespace App\Service;

use HeartPhrame\Encryption\EncryptionInterface;

class SecureDataService
{
    public function __construct(
        private EncryptionInterface $encryption
    ) {}

    public function storeSecret(string $data): string
    {
        // Šifriraj podatke.
        return $this->encryption->encrypt($data);
    }

    public function retrieveSecret(string $encryptedData): string
    {
        // Dešifriraj podatke.
        return $this->encryption->decrypt($encryptedData);
    }
}
```

### Mogućnosti

Instanca `EncryptionInterface` već je podešena ključem aplikacije pa servis ne
mora ručno upravljati ključevima.

- **Šifriranje:** `encrypt($data)` šifrira PHP podatke koje je moguće
  serijalizirati, uključujući nizove znakova, polja i objekte.
- **Dešifriranje:** `decrypt($data)` vraća podatke u izvorni oblik.

### Obrada pogrešaka

- `RuntimeException` se baca kada dešifriranje ne uspije, primjerice zbog
  pogrešnog ključa ili izmijenjenih podataka.
- `InvalidArgumentException` se baca kada podaci za dešifriranje nisu valjani.

```php
try {
    $decrypted = $this->encryption->decrypt($data);
} catch (\RuntimeException $e) {
    // Obradi neuspjelo dešifriranje.
}
```
