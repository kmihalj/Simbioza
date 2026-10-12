<?php

declare(strict_types=1);

namespace App\Setup;

use JsonException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * EN: Stores strictly typed Setup requests outside the web root. The ID is
 *     random and the privileged worker validates the content again.
 *
 * HR: Sprema strogo tipizirane Setup zahtjeve izvan web korijena. ID je
 *     nasumičan, a sadržaj će ponovno provjeriti privilegirani worker.
 */
final readonly class SetupRequestStore
{
    /**
     * EN: Receives only the private request directory.
     *
     * HR: Prima isključivo privatni direktorij zahtjeva.
     */
    public function __construct(private string $directory)
    {
    }

    /**
     * EN: Exposes only the actual path for diagnostics.
     *
     * HR: Otkriva samo stvarnu putanju za dijagnostiku.
     */
    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * EN: Creates a request and returns its unpredictable ID.
     *
     * HR: Stvara zahtjev i vraća njegov nepredvidivi ID.
     *
     * @param array<string,mixed> $payload
     */
    public function create(array $payload): string
    {
        $this->ensureDirectory();
        $id = bin2hex(random_bytes(24));
        $payload['id'] = $id;
        $payload['created_at'] = gmdate(DATE_ATOM);
        try {
            $json = json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $jsonException) {
            throw new RuntimeException('Setup request could not be encoded.', 0, $jsonException);
        }

        $path = $this->requestPath($id);
        if (file_put_contents($path, $json . "\n", LOCK_EX) === false || !chmod($path, 0640)) {
            throw new RuntimeException('Setup request could not be written.');
        }

        return $id;
    }

    /**
     * EN: Stores a size-limited JSON language pack in the worker's private directory.
     *
     * HR: Sprema ograničeni JSON jezični paket u privatni direktorij workera.
     */
    public function storeLanguagePack(UploadedFileInterface $upload): string
    {
        if ($upload->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Language-pack upload failed.');
        }

        $size = $upload->getSize();
        if (is_int($size) && ($size <= 0 || $size > 8 * 1024 * 1024)) {
            throw new RuntimeException('Language pack must be between 1 byte and 8 MiB.');
        }

        $contents = $upload->getStream()->getContents();
        if ($contents === '' || strlen($contents) > 8 * 1024 * 1024) {
            throw new RuntimeException('Language pack must be between 1 byte and 8 MiB.');
        }

        $this->ensureDirectory();
        $file = bin2hex(random_bytes(24)) . '.language.json';
        $path = $this->directory . '/' . $file;
        if (file_put_contents($path, $contents, LOCK_EX) === false || !chmod($path, 0640)) {
            throw new RuntimeException('Language pack could not be stored for Setup.');
        }

        return $file;
    }

    /**
     * EN: Removes a temporary language pack after failure.
     *
     * HR: Uklanja privremeni jezični paket nakon neuspjeha.
     */
    public function discardLanguagePack(string $file): void
    {
        if (preg_match('/\A[a-f0-9]{48}\.language\.json\z/D', $file) !== 1) {
            return;
        }

        $path = $this->directory . '/' . $file;
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * EN: Returns a safe absolute temporary language-pack path.
     *
     * HR: Vraća sigurnu apsolutnu putanju privremenog jezičnog paketa.
     */
    public function languagePackPath(string $file): string
    {
        if (preg_match('/\A[a-f0-9]{48}\.language\.json\z/D', $file) !== 1) {
            throw new RuntimeException('Invalid temporary language-pack name.');
        }

        return $this->directory . '/' . $file;
    }

    /**
     * EN: Reads the worker result and removes only completed request files.
     *
     * HR: Čita rezultat workera i uklanja samo završene datoteke zahtjeva.
     *
     * @return array{ok:bool,message:string}
     */
    public function consumeResult(string $id): array
    {
        $resultPath = $this->resultPath($id);
        $payload = is_file($resultPath)
        ? json_decode((string)file_get_contents($resultPath), true)
        : null;
        if (!is_array($payload) || !is_bool($payload['ok'] ?? null) || !is_string($payload['message'] ?? null)) {
            throw new RuntimeException('Setup worker did not return a valid result.');
        }

        $this->discard($id);

        return ['ok' => $payload['ok'], 'message' => $payload['message']];
    }

    /**
     * EN: Removes completed or failed files for one request.
     *
     * HR: Uklanja završene ili neuspjele datoteke jednog zahtjeva.
     */
    public function discard(string $id): void
    {
        $requestPath = $this->requestPath($id);
        $resultPath = $this->resultPath($id);
        if (is_file($requestPath)) {
            unlink($requestPath);
        }

        if (is_file($resultPath)) {
            unlink($resultPath);
        }
    }

    /**
     * EN: Returns an input-request path.
     *
     * HR: Vraća putanju ulaznog zahtjeva.
     */
    public function requestPath(string $id): string
    {
        return $this->directory . '/' . $this->validId($id) . '.json';
    }

    /**
     * EN: Returns an output-result path.
     *
     * HR: Vraća putanju izlaznog rezultata.
     */
    public function resultPath(string $id): string
    {
        return $this->directory . '/' . $this->validId($id) . '.result.json';
    }

    /**
     * EN: Ensures a private non-public directory.
     *
     * HR: Osigurava privatni direktorij bez javnog pristupa.
     */
    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Setup request directory could not be created.');
        }
    }

    /**
     * EN: Accepts only a 192-bit hexadecimal ID.
     *
     * HR: Prihvaća samo 192-bitni heksadecimalni ID.
     */
    private function validId(string $id): string
    {
        $id = strtolower(trim($id));
        if (preg_match('/\A[a-f0-9]{48}\z/D', $id) !== 1) {
            throw new RuntimeException('Invalid Setup request ID.');
        }

        return $id;
    }
}
