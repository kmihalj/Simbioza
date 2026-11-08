<?php

declare(strict_types=1);

namespace App\Work;

/**
 * EN: Normalizes untrusted query and metadata values without coercing arrays into identities.
 *
 * HR: Normalizira nepouzdane upite i metapodatke bez pretvaranja nizova u identitete.
 */
final class MyWorkValue
{
    /**
     * EN: Reads only scalar text.
     *
     * HR: Čita samo skalarni tekst.
     */
    public static function text(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }

    /**
     * EN: Reads a numeric identity or a neutral zero.
     *
     * HR: Čita brojčani identitet ili neutralnu nulu.
     */
    public static function integer(mixed $value): int
    {
        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * EN: Retains named fields only; numeric array keys cannot become query parameters.
     *
     * HR: Zadržava samo imenovana polja; brojčani ključevi ne mogu postati parametri upita.
     *
     * @param array<mixed> $values
     * @return array<string,mixed>
     */
    public static function named(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
