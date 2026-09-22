<?php

declare(strict_types=1);

namespace App\Localization;

use RuntimeException;

/**
 * HR: Izlaže izradu, provjeru i instalaciju objedinjene jezične datoteke.
 * EN: Exposes consolidated language-pack creation, validation, and installation.
 *
 * @phpstan-type ValidationResult array{
 *     locale:string,
 *     native_name:string,
 *     translated:int,
 *     reference:int,
 *     missing:list<string>,
 *     placeholder_errors:list<string>
 * }
 */
final readonly class LanguageCommand
{
    /** HR: Prima servis paketa i objavljenog kataloga. EN: Receives the pack service and released catalogue. */
    public function __construct(
        private LanguagePackManager $languages,
        private RepositoryLanguageManager $repository,
    ) {
    }

    /**
     * HR: Obrađuje `languages list|template|validate|add` podnaredbe.
     * EN: Handles `languages list|template|validate|add` subcommands.
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
            'available' => $this->available(),
            'template', 'export' => $this->template($rest, $options),
            'validate' => $this->validate($rest),
            'add' => $this->add($rest, $options),
            'install' => $this->installRepository($rest, $options),
            'enable' => $this->changeState($rest, true),
            'disable' => $this->changeState($rest, false),
            'remove' => $this->remove($rest),
            'update' => $this->updateRepository(),
            'help', '--help', '-h', '' => $this->help(),
            default => $this->unknown($subcommand),
        };
    }

    /** HR: Ispisuje jezike i postotak pokrivenosti. EN: Prints locales and translation coverage. */
    private function list(): int
    {
        fwrite(STDOUT, "LOCALE  NAME                 KEYS   REFERENCE  COVERAGE  STATE\n");
        foreach ($this->languages->status() as $language) {
            fwrite(STDOUT, sprintf(
                "%-7s %-20s %-6d %-10d %6.2f%%  %s\n",
                $language['locale'],
                mb_strimwidth($language['native_name'], 0, 20, '…'),
                $language['keys'],
                $language['reference_keys'],
                $language['coverage'],
                $language['active'] ? 'active' : 'disabled',
            ));
        }

        return 0;
    }

    /** HR: Ispisuje objavljene revizije i lokalno stanje. EN: Lists released revisions and local state. */
    private function available(): int
    {
        fwrite(STDOUT, "LOCALE  NAME                 VERSION        STATE\n");
        foreach ($this->repository->status() as $language) {
            $state = !$language['installed'] ? 'not installed'
            : ($language['update_available'] ? 'update available' : ($language['active'] ? 'active' : 'disabled'));
            fwrite(STDOUT, sprintf(
                "%-7s %-20s %-14s %s\n",
                $language['locale'],
                mb_strimwidth($language['native_name'], 0, 20, '…'),
                $language['version'],
                $state,
            ));
        }

        return 0;
    }

    /**
     * HR: Instalira ili osvježava objavljeni paket prema provjerenom digestu.
     * EN: Installs or refreshes a released pack against a verified digest.
     * @param list<string> $arguments
     * @param array<string,mixed> $options
     */
    private function installRepository(array $arguments, array $options): int
    {
        $locale = $this->requiredArgument($arguments, 0, 'Locale is required.');
        $this->repository->install($locale, isset($options['replace']));
        fwrite(STDOUT, 'Language installed: ' . $locale . PHP_EOL);
        return 0;
    }

    /**
     * HR: Mijenja stanje već instaliranog jezika.
     * EN: Changes the state of an installed language.
     * @param list<string> $arguments
     */
    private function changeState(array $arguments, bool $enabled): int
    {
        $locale = $this->requiredArgument($arguments, 0, 'Locale is required.');
        $this->languages->setActive($locale, $enabled);
        fwrite(STDOUT, 'Language ' . ($enabled ? 'enabled: ' : 'disabled: ') . $locale . PHP_EOL);
        return 0;
    }

    /**
     * HR: Deinstalira jezik i čuva najmanje jedan aktivni.
     * EN: Uninstalls a language while retaining at least one active locale.
     * @param list<string> $arguments
     */
    private function remove(array $arguments): int
    {
        $locale = $this->requiredArgument($arguments, 0, 'Locale is required.');
        $this->repository->uninstall($locale);
        fwrite(STDOUT, 'Language removed: ' . $locale . PHP_EOL);
        return 0;
    }

    /** HR: Osvježava instalirane pakete s novim objavljenim revizijama. EN: Refreshes installed packs with newer released revisions. */
    private function updateRepository(): int
    {
        $updated = $this->repository->updateInstalled();
        fwrite(STDOUT, 'Updated languages: ' . ($updated === [] ? 'none' : implode(', ', $updated)) . PHP_EOL);
        return 0;
    }

    /**
     * HR: Izrađuje objedinjeni JSON predložak.
     * EN: Creates a consolidated JSON template.
     *
     * @param list<string> $arguments
     * @param array<string,mixed> $options
     */
    private function template(array $arguments, array $options): int
    {
        $locale = $this->requiredArgument($arguments, 0, 'Target locale is required.');
        $source = is_string($options['source'] ?? null) ? $options['source'] : 'en';
        $output = is_string($options['output'] ?? null) ? $options['output'] : null;
        fwrite(STDOUT, $this->languages->createTemplate($locale, $source, $output) . PHP_EOL);

        return 0;
    }

    /**
     * HR: Provjerava paket bez zapisivanja.
     * EN: Validates a pack without writing.
     *
     * @param list<string> $arguments
     */
    private function validate(array $arguments): int
    {
        $result = $this->languages->validate($this->requiredArgument($arguments, 0, 'Pack path is required.'));
        $this->printValidation($result);

        return $result['placeholder_errors'] === [] && $result['missing'] === [] ? 0 : 2;
    }

    /**
     * HR: Instalira provjereni paket.
     * EN: Installs a validated pack.
     *
     * @param list<string> $arguments
     * @param array<string,mixed> $options
     */
    private function add(array $arguments, array $options): int
    {
        $result = $this->languages->install(
            $this->requiredArgument($arguments, 0, 'Pack path is required.'),
            isset($options['replace']),
            isset($options['allow-missing']),
        );
        $this->printValidation($result);
        fwrite(STDOUT, 'Language installed: ' . $result['locale'] . PHP_EOL);

        return 0;
    }

    /**
     * HR: Ispisuje sažetak provjere.
     * EN: Prints the validation summary.
     *
     * @param ValidationResult $result
     */
    private function printValidation(array $result): void
    {
        fwrite(STDOUT, sprintf(
            "%s: %d/%d keys, %d missing, %d placeholder errors\n",
            $result['locale'],
            $result['translated'],
            $result['reference'],
            count($result['missing']),
            count($result['placeholder_errors']),
        ));
    }

    /** HR: Ispisuje dvojezičnu pomoć. EN: Prints bilingual help. */
    private function help(): int
    {
        fwrite(STDOUT, <<<'HELP'
Simbioza language packs / Jezični paketi

  vendor/bin/hph languages list
  vendor/bin/hph languages available
  vendor/bin/hph languages install <locale> [--replace]
  vendor/bin/hph languages enable <locale>
  vendor/bin/hph languages disable <locale>
  vendor/bin/hph languages remove <locale>
  vendor/bin/hph languages update
  vendor/bin/hph languages template <locale> [--source=en] [--output=FILE]
  vendor/bin/hph languages validate <FILE>
  vendor/bin/hph languages add <FILE> [--replace] [--allow-missing]

`template` collects every application and currently installed module key into
one JSON file. Fill `names`, `native_name`, and `flag_svg`, translate only the
translation values, and keep placeholders unchanged. `add` validates and
installs the translation, multilingual name, and safe SVG flag together.
HELP . PHP_EOL);

        return 0;
    }

    /** HR: Odbija nepoznatu podnaredbu. EN: Rejects an unknown subcommand. */
    private function unknown(string $subcommand): int
    {
        fwrite(STDERR, 'Unknown languages subcommand: ' . $subcommand . PHP_EOL);

        return 2;
    }

    /**
     * HR: Vraća obavezni pozicijski argument.
     * EN: Returns a required positional argument.
     *
     * @param list<string> $arguments
     */
    private function requiredArgument(array $arguments, int $index, string $message): string
    {
        $value = trim((string)($arguments[$index] ?? ''));
        if ($value === '') {
            throw new RuntimeException($message);
        }

        return $value;
    }
}
