<?php

declare(strict_types=1);

namespace App\Deployment;

use RuntimeException;

/**
 * EN: A code-owned image marker enforces immutable deployment independently of runtime configuration.
 * Only code/package mutation is prohibited; configuration, content and module activation remain writable.
 *
 * HR: Oznaka imagea u kodu provodi nepromjenjivi deployment neovisno o runtime konfiguraciji.
 * Zabranjene su samo izmjene koda/paketa; konfiguracija, sadržaj i aktivacija modula ostaju zapisivi.
 */
final readonly class DeploymentPolicy
{
    /**
     * EN: Takes the actual application root, never a path from an HTTP request.
     *
     * HR: Prima stvarni korijen aplikacije, nikada putanju iz HTTP zahtjeva.
     */
    public function __construct(private string $appRoot)
    {
    }

    /**
     * EN: The baked marker cannot be disabled by editing private settings or clearing an environment variable.
     * An explicit immutable environment also supports operator-owned read-only deployments.
     *
     * HR: Ugrađena oznaka ne može se isključiti privatnim postavkama ili brisanjem varijable okruženja.
     * Izričiti immutable okoliš podržava i održavateljeve read-only deploymente.
     */
    public function isImmutable(): bool
    {
        $mode = getenv('HPH_DEPLOYMENT_MODE');
        if ($mode !== false && !in_array($mode, ['', 'classic', 'immutable'], true)) {
            throw new RuntimeException('Invalid HPH_DEPLOYMENT_MODE; expected classic or immutable.');
        }

        return is_file($this->appRoot . '/resources/deployment/immutable.json') || $mode === 'immutable';
    }

    /**
     * EN: Fails before a mutator opens locks, launches helpers or changes database/module state.
     *
     * HR: Prekida prije otvaranja lockova, pokretanja helpera ili promjene baze/stanja modula.
     */
    public function assertCodeChangesAllowed(): void
    {
        if ($this->isImmutable()) {
            throw new RuntimeException(
                'Immutable deployment: replace the application image for code or package changes. '
                . 'Do not run update.php or modules add/remove inside the container.',
            );
        }
    }

    /**
     * EN: Blocks only fixed worker actions which mutate application code.
     *
     * HR: Blokira samo fiksne worker radnje koje mijenjaju aplikacijski kod.
     */
    public function assertWorkerActionAllowed(string $action): void
    {
        if (
            in_array($action, [
            'packages-prepare', 'packages-cleanup', 'package-install', 'package-uninstall',
            'application-update-start',
            ], true)
        ) {
            $this->assertCodeChangesAllowed();
        }
    }
}
