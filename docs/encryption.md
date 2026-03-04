# Encryption

The HeartPhrame application includes a pre-configured encryption component that
provides secure AES-256-GCM encryption and decryption capabilities. This
document describes how to configure and use encryption within the application.

## Configuration

The encryption configuration is managed through the environment configuration
file.

1. **Configuration File**: The encryption key is defined in `config/env.php`.
If this file does not exist, copy it from `config/env.php.dist`.

2. **Setting the Key**: Locate the `encryption_key` setting in `config/env.php`:

    ```php
    return [
        // ...
        'encryption_key' => 'your-secure-key-here',
        // ...
    ];
    ```

## Generating a Key

To ensure security, you should generate a strong, random encryption key. The
application provides a command-line tool for this purpose.

Run the following command from the project root:

```bash
vendor/bin/hph encryption:generate-key
```

Copy the output key and paste it into your `config/env.php` file as the value
for `encryption_key`.

## Usage

The encryption component is available via Dependency Injection. To use it,
type-hint the `HeartPhrame\Encryption\EncryptionInterface` in your classes.

### Example

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
        // Encrypt data
        return $this->encryption->encrypt($data);
    }

    public function retrieveSecret(string $encryptedData): string
    {
        // Decrypt data
        return $this->encryption->decrypt($encryptedData);
    }
}
```

### Features

The injected `EncryptionInterface` instance is already configured with the
application's encryption key, so you do not need to manage keys manually
within your services.

- **Encrypt**: `encrypt($data)` - Encrypts any serializable PHP data
(strings, arrays, objects).
-   **Decrypt**: `decrypt($data)` - Decrypts the data back to its original form.

### Error Handling

- `RuntimeException`: Thrown if decryption fails (e.g., wrong key,
tampered data).
- `InvalidArgumentException`: Thrown if the data to decrypt is invalid.

```php
try {
    $decrypted = $this->encryption->decrypt($data);
} catch (\RuntimeException $e) {
    // Handle decryption failure
}
```
