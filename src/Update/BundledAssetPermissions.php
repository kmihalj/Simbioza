<?php

declare(strict_types=1);

namespace App\Update;

use RuntimeException;

/**
 * HR: Uvezeni javno posluživi sadržaj nasljeđuje vlasništvo svojega runtime spremišta.
 * EN: Imported web-served content inherits its runtime storage ownership.
 */
final class BundledAssetPermissions
{
    /**
     * HR: Mijenja samo vlasništvo provjerenih datoteka i njihovih roditelja; prava ostaju ista.
     * EN: Changes only validated files' and parents' ownership; permission modes remain unchanged.
     * @param list<string> $relativePaths
     */
    public static function inheritOwnership(string $storageRoot, array $relativePaths): bool
    {
        if ($relativePaths === [] || PHP_OS_FAMILY === 'Windows') {
            return false;
        }

        $root = realpath($storageRoot);
        if (!is_string($root) || !is_dir($root)) {
            throw new RuntimeException('The bundled-asset storage root is unavailable.');
        }

        $metadata = stat($root);
        if (!is_array($metadata)) {
            throw new RuntimeException('The bundled-asset storage ownership is unavailable.');
        }

        // HR: Najprije provjerava sve putanje, da neispravan zapis ne uzrokuje djelomičan popravak.
        // EN: Validate every path first so an invalid record cannot cause a partial repair.
        $paths = [];
        foreach ($relativePaths as $relative) {
            $relative = str_replace('\\', '/', $relative);
            $segments = explode('/', $relative);
            if (
                $relative === '' || str_contains($relative, "\0")
                || preg_match('/^[A-Za-z]:/', $relative) === 1
                || array_intersect($segments, ['', '.', '..']) !== []
            ) {
                throw new RuntimeException('A bundled asset has an invalid storage path.');
            }

            $path = $root;
            foreach ($segments as $segment) {
                $path .= DIRECTORY_SEPARATOR . $segment;
                if (is_link($path) || !file_exists($path)) {
                    throw new RuntimeException('A bundled asset is missing or uses a symbolic link.');
                }

                $paths[$path] = true;
            }

            if (!is_file($path)) {
                throw new RuntimeException('A bundled asset is not a regular file.');
            }
        }

        $changed = false;
        foreach (array_keys($paths) as $path) {
            clearstatcache(true, $path);
            $current = stat($path);
            if (!is_array($current)) {
                throw new RuntimeException('Unable to read bundled-asset ownership.');
            }

            if ($current['uid'] !== $metadata['uid']) {
                if (!@chown($path, $metadata['uid'])) {
                    throw new RuntimeException('Unable to inherit bundled-asset storage owner: ' . $path);
                }

                $changed = true;
            }

            if ($current['gid'] !== $metadata['gid']) {
                if (!@chgrp($path, $metadata['gid'])) {
                    throw new RuntimeException('Unable to inherit bundled-asset storage group: ' . $path);
                }

                $changed = true;
            }
        }

        return $changed;
    }
}
