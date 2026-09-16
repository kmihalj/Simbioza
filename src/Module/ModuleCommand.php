<?php

declare(strict_types=1);

namespace App\Module;

use RuntimeException;

use function array_slice;
use function array_values;
use function fgets;
use function fwrite;
use function is_string;
use function strtolower;
use function trim;

use const PHP_EOL;
use const STDERR;
use const STDIN;
use const STDOUT;

/** HR: Izlaže upravljanje ugrađenim modulima kroz jednu predvidljivu CLI naredbu. EN: Exposes bundled-module management through one predictable CLI command. */
final readonly class ModuleCommand
{
    /** HR: Prima poslovni servis životnog ciklusa. EN: Receives the lifecycle business service. */
    public function __construct(private ModuleLifecycleManager $modules)
    {
    }

    /**
     * HR: Obrađuje `modules list|add|enable|disable|remove|backups` podnaredbe.
     * EN: Handles `modules list|add|enable|disable|remove|backups` subcommands.
     *
     * @param list<string> $arguments
     * @param array<string,mixed> $options
     */
    public function run(array $arguments = [], array $options = []): int
    {
        $subcommand = strtolower(trim((string)($arguments[0] ?? 'help')));
        $rest = array_values(array_slice($arguments, 1));

        return match ($subcommand) {
            'list' => $this->list(),
            'add' => $this->add($rest, $options),
            'enable' => $this->enable($rest),
            'disable' => $this->disable($rest),
            'remove' => $this->remove($rest, $options),
            'backups' => $this->backups($rest),
            'help', '--help', '-h', '' => $this->help(),
            default => $this->unknown($subcommand),
        };
    }

    /** HR: Ispisuje stanje svih modula. EN: Prints every module state. */
    private function list(): int
    {
        fwrite(STDOUT, "MODULE              TYPE       STATE          BACKUP\n");
        foreach ($this->modules->status() as $module) {
            fwrite(STDOUT, sprintf(
                "%-19s %-10s %-14s %s\n",
                $module['slug'],
                $module['optional'] ? 'optional' : 'required',
                $module['state'],
                $module['backup_available'] ? 'yes' : 'no',
            ));
        }

        return 0;
    }

    /**
     * HR: Dodaje modul s praznom instalacijom ili povratom kopije.
     * EN: Adds a module as fresh installation or backup restore.
     *
     * @param list<string> $arguments
     * @param array<string,mixed> $options
     */
    private function add(array $arguments, array $options): int
    {
        $slug = $this->requiredSlug($arguments);
        $latest = $this->modules->backups($slug)[0] ?? null;
        $restore = isset($options['restore']);
        $fresh = isset($options['fresh']);
        if ($restore && $fresh) {
            throw new RuntimeException('Choose either --restore or --fresh.');
        }

        if ($latest !== null && !$restore && !$fresh) {
            $restore = $this->confirm('Backup found. Restore it instead of a fresh installation? [y/N] ');
            $fresh = !$restore;
        }

        $archive = $this->modules->add($slug, $restore);
        fwrite(STDOUT, 'Module added: ' . $slug . ($archive !== null ? ' (restored ' . $archive . ')' : '') . PHP_EOL);

        return 0;
    }

    /**
     * HR: Omogućuje modul.
     * EN: Enables a module.
     *
     * @param list<string> $arguments
     */
    private function enable(array $arguments): int
    {
        $slug = $this->requiredSlug($arguments);
        $this->modules->enable($slug);
        fwrite(STDOUT, 'Module enabled: ' . $slug . PHP_EOL);

        return 0;
    }

    /**
     * HR: Isključuje modul bez gubitka podataka.
     * EN: Disables a module without data loss.
     *
     * @param list<string> $arguments
     */
    private function disable(array $arguments): int
    {
        $slug = $this->requiredSlug($arguments);
        $this->modules->disable($slug);
        fwrite(STDOUT, 'Module disabled; data retained: ' . $slug . PHP_EOL);

        return 0;
    }

    /**
     * HR: Potvrđeno uklanja modul nakon sigurnosne kopije.
     * EN: Removes a confirmed module after backup.
     *
     * @param list<string> $arguments
     * @param array<string,mixed> $options
     */
    private function remove(array $arguments, array $options): int
    {
        $slug = $this->requiredSlug($arguments);
        if (!isset($options['yes']) && !$this->confirm('Backup data and remove this module schema? [y/N] ')) {
            fwrite(STDERR, "Removal cancelled.\n");
            return 2;
        }

        $archive = $this->modules->remove($slug);
        fwrite(STDOUT, 'Module removed: ' . $slug . PHP_EOL . 'Backup: ' . $archive . PHP_EOL);

        return 0;
    }

    /**
     * HR: Ispisuje dostupne kopije modula.
     * EN: Prints available module backups.
     *
     * @param list<string> $arguments
     */
    private function backups(array $arguments): int
    {
        $slug = $this->requiredSlug($arguments);
        $backups = $this->modules->backups($slug);
        if ($backups === []) {
            fwrite(STDOUT, "No backups.\n");
            return 0;
        }

        foreach ($backups as $backup) {
            fwrite(STDOUT, $backup . PHP_EOL);
        }

        return 0;
    }

    /** HR: Ispisuje kratku pomoć. EN: Prints concise help. */
    private function help(): int
    {
        fwrite(STDOUT, <<<'HELP'
Simbioza module manager / Upravljanje modulima

  vendor/bin/hph modules list
  vendor/bin/hph modules add <module> [--restore|--fresh]
  vendor/bin/hph modules enable <module>
  vendor/bin/hph modules disable <module>
  vendor/bin/hph modules remove <module> [--yes]
  vendor/bin/hph modules backups <module>

`disable` keeps data. `remove` first writes an NDJSON backup, then drops only
the selected optional module schema. Required modules cannot be removed.
HELP . PHP_EOL);

        return 0;
    }

    /** HR: Odbija nepoznatu podnaredbu. EN: Rejects an unknown subcommand. */
    private function unknown(string $subcommand): int
    {
        fwrite(STDERR, 'Unknown modules subcommand: ' . $subcommand . PHP_EOL);

        return 2;
    }

    /**
     * HR: Vraća obavezni kratki naziv modula.
     * EN: Returns the required module slug.
     *
     * @param list<string> $arguments
     */
    private function requiredSlug(array $arguments): string
    {
        $slug = trim((string)($arguments[0] ?? ''));
        if ($slug === '') {
            throw new RuntimeException('Module name is required.');
        }

        return $slug;
    }

    /** HR: Sigurno čita potvrdu samo iz interaktivnog terminala. EN: Safely reads confirmation only from an interactive terminal. */
    private function confirm(string $question): bool
    {
        if (!function_exists('posix_isatty') || !posix_isatty(STDIN)) {
            throw new RuntimeException('Interactive choice required; pass --restore, --fresh, or --yes.');
        }

        fwrite(STDOUT, $question);
        $answer = fgets(STDIN);

        return is_string($answer) && in_array(strtolower(trim($answer)), ['y', 'yes', 'd', 'da'], true);
    }
}
