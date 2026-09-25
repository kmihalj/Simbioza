<?php

declare(strict_types=1);

namespace App\Module;

/**
 * HR: Registrira aplikacijske rute dodatka samo dok je njegov paket stvarno instaliran.
 * EN: Registers host routes for an extension only while its package is actually installed.
 */
final class OptionalModuleRoutes
{
    /**
     * HR: Isključen, ali instaliran modul zadržava administracijsku rutu; uklonjeni je nema.
     * EN: A disabled but installed module keeps its admin route; a removed one does not.
     *
     * @param list<mixed> $routes
     * @param (callable(string):bool)|null $isInstalled
     * @return list<mixed>
     */
    public static function whenInstalled(string $package, array $routes, ?callable $isInstalled = null): array
    {
        // HR: Stvarni direktorij paketa odražava GUI dodavanje/uklanjanje i uz FPM OPcache.
        // EN: The actual package directory reflects GUI add/remove even with FPM OPcache.
        $isInstalled ??= static fn(string $name): bool => is_dir(dirname(__DIR__, 2) . '/vendor/' . $name);

        return $isInstalled($package) ? $routes : [];
    }
}
