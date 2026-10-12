<?php

declare(strict_types=1);

namespace App\Module;

/**
 * EN: Registers host routes for an extension only while its package is actually installed.
 *
 * HR: Registrira aplikacijske rute dodatka samo dok je njegov paket stvarno instaliran.
 */
final class OptionalModuleRoutes
{
    /**
     * EN: A disabled but installed module keeps its admin route; a removed one does not.
     *
     * HR: Isključen, ali instaliran modul zadržava administracijsku rutu; uklonjeni je nema.
     *
     * @param list<mixed> $routes
     * @param (callable(string):bool)|null $isInstalled
     * @return list<mixed>
     */
    public static function whenInstalled(string $package, array $routes, ?callable $isInstalled = null): array
    {
        // EN: The actual package directory reflects GUI add/remove even with FPM OPcache.
        //
        // HR: Stvarni direktorij paketa odražava GUI dodavanje/uklanjanje i uz FPM OPcache.
        $isInstalled ??= static fn(string $name): bool => is_dir(dirname(__DIR__, 2) . '/vendor/' . $name);

        return $isInstalled($package) ? $routes : [];
    }
}
