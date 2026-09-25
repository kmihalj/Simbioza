<?php

declare(strict_types=1);

namespace App\Localization;

use JsonException;
use RuntimeException;

/**
 * HR: Čita javni katalog jezičnih paketa kao podatke i provjerava SHA-256 prije instalacije.
 * EN: Reads the public language-pack catalogue as data and verifies SHA-256 before installation.
 */
final readonly class LanguageRepository
{
    private const DEFAULT_MANIFEST = 'https://raw.githubusercontent.com/kmihalj/simbioza-languages/main/manifest.json';

    /** HR: Prima korijen aplikacije i neobavezni testni URL. EN: Receives the application root and optional test URL. */
    public function __construct(
        private string $appRoot,
        private string $manifestUrl = self::DEFAULT_MANIFEST,
    ) {
    }

    /**
     * HR: Vraća samo objavljene, strogo provjerene zapise iz svježeg ili predmemoriranog kataloga.
     * EN: Returns only published, strictly validated entries from a fresh or cached catalogue.
     *
     * @return array<string,array{locale:string,native_name:string,version:string,file:string,sha256:string}>
     */
    public function available(bool $refresh = false): array
    {
        $cache = $this->appRoot . '/data/cache/language-catalog.json';
        $raw = null;
        if (!$refresh && is_file($cache) && time() - (int)filemtime($cache) < 3600) {
            $raw = file_get_contents($cache);
        }

        if (!is_string($raw) || $raw === '') {
            try {
                $raw = $this->fetch($this->manifestUrl, 262144);
                $directory = dirname($cache);
                if (is_dir($directory) || mkdir($directory, 0770, true)) {
                    // HR: FPM i deploy korisnik smiju zamijeniti predmemoriju bez
                    //     preuzimanja vlasništva nad datotekom drugog korisnika.
                    // EN: FPM and deploy users may replace the cache without
                    //     taking ownership of each other's existing file.
                    $temporary = tempnam($directory, '.language-catalog-');
                    if (is_string($temporary)) {
                        try {
                            if (file_put_contents($temporary, $raw, LOCK_EX) !== false) {
                                chmod($temporary, 0660);
                                rename($temporary, $cache);
                            }
                        } finally {
                            if (is_file($temporary)) {
                                unlink($temporary);
                            }
                        }
                    }
                }
            } catch (RuntimeException) {
                $raw = is_file($cache) ? file_get_contents($cache) : null;
                if (!is_string($raw) || $raw === '') {
                    return [];
                }
            }
        }

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (
            !is_array($payload)
            || ($payload['format'] ?? null) !== 'simbioza-language-catalog'
            || ($payload['version'] ?? null) !== 1
            || !is_array($payload['languages'] ?? null)
        ) {
            return [];
        }

        $languages = [];
        foreach ($payload['languages'] as $entry) {
            if (!is_array($entry) || ($entry['status'] ?? null) !== 'released') {
                continue;
            }

            $locale = $entry['locale'] ?? null;
            $name = $entry['native_name'] ?? null;
            $version = $entry['version'] ?? null;
            $path = $entry['file'] ?? null;
            $hash = $entry['sha256'] ?? null;
            if (
                !is_string($locale) || preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $locale) !== 1
                || strlen($locale) > 32 || !is_string($name) || trim($name) === '' || mb_strlen($name) > 100
                || !is_string($version) || preg_match('/\A[0-9]{4}\.[0-9]{2}\.[0-9]{2}\.[0-9]+\z/D', $version) !== 1
                || !is_string($path) || $path !== 'packs/' . $locale . '.json'
                || !is_string($hash) || preg_match('/\A[a-f0-9]{64}\z/D', $hash) !== 1
            ) {
                continue;
            }

            $languages[$locale] = [
                'locale' => $locale,
                'native_name' => trim($name),
                'version' => $version,
                'file' => $path,
                'sha256' => $hash,
            ];
        }

        ksort($languages, SORT_STRING);
        return $languages;
    }

    /**
     * HR: Dohvaća paket ograničene veličine i provjerava sadržaj prema manifestu.
     * EN: Fetches a size-limited pack and verifies its bytes against the manifest.
     */
    public function download(string $locale): string
    {
        $entry = $this->available()[$locale] ?? null;
        if ($entry === null) {
            throw new RuntimeException('The language is not published in the catalogue.');
        }

        $base = substr($this->manifestUrl, 0, (int)strrpos($this->manifestUrl, '/') + 1);
        $contents = $this->fetch($base . $entry['file'], 2097152);
        if (!hash_equals($entry['sha256'], hash('sha256', $contents))) {
            throw new RuntimeException('The downloaded language pack does not match the catalogue checksum.');
        }

        $directory = $this->appRoot . '/data/language-packs';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the private language-pack directory.');
        }

        $path = tempnam($directory, '.simbioza-repository-');
        if (!is_string($path) || file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Unable to store the downloaded language pack.');
        }

        return $path;
    }

    /** HR: Čita najviše zadani broj bajtova uz kratak timeout. EN: Reads at most the requested bytes with a short timeout. */
    private function fetch(string $url, int $limit): string
    {
        if ($limit < 1 || $limit > 2_097_152) {
            throw new RuntimeException('The requested language download limit is invalid.');
        }

        // HR: macOS mod_php radi u Apacheovu forkiranom procesu. Mrežni PHP
        //     stream ondje može srušiti proces tijekom sustavnog DNS poziva;
        //     zaseban curl proces zadržava isti TLS i veličinski limit.
        // EN: macOS mod_php runs in a forked Apache process. A PHP network
        //     stream can crash it during system DNS resolution; a separate curl
        //     process preserves the same TLS and size limits.
        if (PHP_OS_FAMILY === 'Darwin' && PHP_SAPI === 'apache2handler' && str_starts_with($url, 'https://')) {
            return $this->fetchWithSystemCurl($url, $limit);
        }

        $context = stream_context_create([
            'http' => ['timeout' => 5, 'follow_location' => 0, 'ignore_errors' => false],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $contents = @file_get_contents($url, false, $context, 0, $limit + 1);
        if (!is_string($contents) || $contents === '' || strlen($contents) > $limit) {
            throw new RuntimeException('Unable to fetch the language catalogue or pack safely.');
        }

        return $contents;
    }

    /**
     * HR: Na macOS Apacheu izdvojeno dohvaća javni HTTPS sadržaj bez ljuske.
     * EN: Fetches public HTTPS content out of process on macOS Apache, without a shell.
     */
    private function fetchWithSystemCurl(string $url, int $limit): string
    {
        $command = [
            '/usr/bin/curl', '--silent', '--fail', '--max-time', '5',
            '--max-filesize', (string)$limit, '--proto', '=https', $url,
        ];
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start the language catalogue fetch.');
        }

        fclose($pipes[0]);
        $contents = stream_get_contents($pipes[1], $limit + 1);
        fclose($pipes[1]);
        if (!is_string($contents) || strlen($contents) > $limit) {
            proc_terminate($process);
        }

        $exitCode = proc_close($process);
        if (!is_string($contents) || $contents === '' || strlen($contents) > $limit || $exitCode !== 0) {
            throw new RuntimeException('Unable to fetch the language catalogue or pack safely.');
        }

        return $contents;
    }
}
