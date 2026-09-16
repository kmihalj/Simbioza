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
    /** HR: Prima servis jezičnih paketa. EN: Receives the language-pack service. */
    public function __construct(private LanguagePackManager $languages)
    {
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
            'template', 'export' => $this->template($rest, $options),
            'validate' => $this->validate($rest),
            'add' => $this->add($rest, $options),
            'help', '--help', '-h', '' => $this->help(),
            default => $this->unknown($subcommand),
        };
    }

    /** HR: Ispisuje jezike i postotak pokrivenosti. EN: Prints locales and translation coverage. */
    private function list(): int
    {
        fwrite(STDOUT, "LOCALE  NAME                 KEYS   REFERENCE  COVERAGE\n");
        foreach ($this->languages->status() as $language) {
            fwrite(STDOUT, sprintf(
                "%-7s %-20s %-6d %-10d %6.2f%%\n",
                $language['locale'],
                mb_strimwidth($language['native_name'], 0, 20, '…'),
                $language['keys'],
                $language['reference_keys'],
                $language['coverage'],
            ));
        }

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
