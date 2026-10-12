<?php

declare(strict_types=1);

namespace App\Tools;

use RuntimeException;

/**
 * EN: Checks language order using PHP tokens, not text inside strings or vendor files.
 * Normalization changes only comments and whitespace and preserves PHPDoc annotations.
 *
 * HR: Redoslijed jezika provjerava PHP tokenima, ne tekstom unutar stringova ili vendora.
 * Normalizacija mijenja samo komentare i razmake te čuva PHPDoc anotacije.
 */
final class BilingualCommentRules
{
    /**
     * EN: Returns the ordered language markers occurring in actual comment text.
     *
     * HR: Vraća uređene jezične oznake koje se pojavljuju u stvarnom komentaru.
     *
     * @return list<string>
     */
    public function markers(string $comment): array
    {
        preg_match_all('/(?<![a-zA-Z0-9_])(EN|HR):/', $comment, $matches);
        return array_values($matches[1]);
    }

    /**
     * EN: Both languages must form two separated blocks, English first, with no alternating lines.
     * Unmarked technical annotations and single-language comments are not rewritten by this rule.
     *
     * HR: Oba jezika moraju tvoriti dva odvojena bloka, engleski prvi, bez izmjeničnih redaka.
     * Neoznačene tehničke anotacije i jednojezični komentari ne mijenjaju se ovim pravilom.
     */
    public function isOrdered(string $comment): bool
    {
        $markers = $this->markers($comment);
        if (!in_array('EN', $markers, true) || !in_array('HR', $markers, true)) {
            return true;
        }
        if ($markers !== ['EN', 'HR']) {
            return false;
        }
        return preg_match('/\n\s*(?:\*|\/\/)?\s*\n\s*(?:\*|\/\/)\s*HR:/', $comment) === 1;
    }

    /**
     * EN: Splits inline/repeated labels into language blocks while retaining neutral headers and tags.
     *
     * HR: Oznake u istom retku/ponovljene oznake odvaja u jezične blokove uz očuvane naslove i tagove.
     */
    public function normalizeComment(string $comment): string
    {
        $markers = $this->markers($comment);
        if (!in_array('EN', $markers, true) || !in_array('HR', $markers, true) || $this->isOrdered($comment)) {
            return $comment;
        }
        $lineComment = str_starts_with(ltrim($comment), '//');
        $docComment = str_starts_with(ltrim($comment), '/**');
        $lines = preg_split('/\R/', $comment);
        if (!is_array($lines)) {
            throw new RuntimeException('Unable to split a comment.');
        }
        $body = [];
        foreach ($lines as $line) {
            $line = preg_replace('~^\s*(?:/\*\*?|\*|//)\s?~', '', $line) ?? $line;
            $line = preg_replace('~\s*\*/\s*$~', '', $line) ?? $line;
            if (trim($line) === '/') {
                continue;
            }
            $body[] = $line;
        }
        $parts = preg_split('/(?<![a-zA-Z0-9_])(EN|HR):\s*/', implode("\n", $body), -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts)) {
            throw new RuntimeException('Unable to split bilingual sections.');
        }
        $header = $this->trimLines(explode("\n", (string)array_shift($parts)));
        $sections = ['EN' => [], 'HR' => []];
        $annotations = [];
        for ($index = 0; $index + 1 < count($parts); $index += 2) {
            $language = $parts[$index];
            $content = explode("\n", $parts[$index + 1]);
            $tagStarted = false;
            foreach ($content as $line) {
                if (preg_match('/^\s*@(?:[a-zA-Z][a-zA-Z0-9_-]*)\b/', $line)) {
                    $tagStarted = true;
                }
                if ($tagStarted) {
                    $annotations[] = $line;
                } else {
                    $sections[$language][] = $line;
                }
            }
        }
        $output = $header;
        foreach (['EN', 'HR'] as $language) {
            $content = $this->trimLines($sections[$language]);
            if ($output !== []) {
                $output[] = '';
            }
            $first = array_shift($content) ?? '';
            $output[] = $language . ': ' . ltrim($first);
            array_push($output, ...$content);
        }
        $annotations = $this->trimLines($annotations);
        if ($annotations !== []) {
            $output[] = '';
            array_push($output, ...$annotations);
        }
        if ($lineComment) {
            return implode("\n", array_map(
                static fn(string $line): string => '//' . ($line === '' ? '' : ' ' . rtrim($line)),
                $output,
            ));
        }
        return ($docComment ? '/**' : '/*') . "\n" . implode("\n", array_map(
            static fn(string $line): string => ' *' . ($line === '' ? '' : ' ' . rtrim($line)),
            $output,
        )) . "\n */";
    }

    /**
     * EN: Trims only blank boundary lines, preserving internal examples and indentation.
     *
     * HR: Uklanja samo prazne rubne retke uz očuvane unutarnje primjere i uvlačenje.
     *
     * @param list<string> $lines
     * @return list<string>
     */
    private function trimLines(array $lines): array
    {
        while ($lines !== [] && trim($lines[0]) === '') {
            array_shift($lines);
        }
        while ($lines !== [] && trim($lines[count($lines) - 1]) === '') {
            array_pop($lines);
        }
        return array_values($lines);
    }

    /**
     * EN: Groups adjacent line comments so alternating labels cannot evade the check.
     *
     * HR: Grupira susjedne redne komentare kako izmjenične oznake ne bi zaobišle provjeru.
     *
     * @return list<array{offset:int,length:int,line:int,text:string}>
     */
    public function comments(string $source): array
    {
        $tokens = token_get_all($source);
        $comments = [];
        $offset = 0;
        $count = count($tokens);
        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            $bytes = is_array($token) ? $token[1] : $token;
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                $text = $bytes;
                if (str_starts_with($text, '//')) {
                    while ($index + 2 < $count) {
                        $space = $tokens[$index + 1];
                        $next = $tokens[$index + 2];
                        if (
                            !is_array($space) || $space[0] !== T_WHITESPACE
                            || substr_count($space[1], "\n") > 2
                            || !is_array($next) || $next[0] !== T_COMMENT || !str_starts_with($next[1], '//')
                        ) {
                            break;
                        }
                        $text .= $space[1] . $next[1];
                        $index += 2;
                    }
                }
                $comments[] = ['offset' => $offset, 'length' => strlen($text), 'line' => $token[2], 'text' => $text];
                $bytes = $text;
            }
            $offset += strlen($bytes);
        }
        return $comments;
    }

    /**
     * EN: Hashes executable tokens exactly; changes to strings, statements or inline HTML are forbidden.
     *
     * HR: Točno sažima izvršive tokene; zabranjene su izmjene stringova, naredbi ili inline HTML-a.
     */
    public function semanticHash(string $source): string
    {
        $hash = hash_init('sha256');
        foreach (token_get_all($source, TOKEN_PARSE) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true)) {
                continue;
            }
            hash_update($hash, is_array($token) ? $token[0] . ':' . $token[1] . "\0" : $token . "\0");
        }
        return hash_final($hash);
    }

    /**
     * EN: Applies only safe comment edits, retaining lexical code and PHPDoc tags.
     *
     * HR: Primjenjuje samo sigurne izmjene komentara uz očuvan leksički kod i PHPDoc tagove.
     */
    public function normalizeSource(string $source): string
    {
        $result = $source;
        foreach (array_reverse($this->comments($source)) as $comment) {
            $normalized = $this->normalizeComment($comment['text']);
            if ($normalized === $comment['text']) {
                continue;
            }
            if ($this->annotationSignatures($comment['text']) !== $this->annotationSignatures($normalized)) {
                throw new RuntimeException('Comment normalization would change PHPDoc tags.');
            }
            $lineStart = strrpos(substr($source, 0, $comment['offset']), "\n");
            $prefix = substr(
                $source,
                $lineStart === false ? 0 : $lineStart + 1,
                $comment['offset'] - ($lineStart === false ? 0 : $lineStart + 1),
            );
            $indent = trim($prefix) === '' ? $prefix : '';
            $normalized = preg_replace('/\n/', "\n" . $indent, $normalized) ?? $normalized;
            $result = substr_replace($result, $normalized, $comment['offset'], $comment['length']);
        }
        if ($this->semanticHash($result) !== $this->semanticHash($source)) {
            throw new RuntimeException('Comment normalization would alter executable code.');
        }
        return $result;
    }

    /**
     * EN: Keeps annotation types/names intact; bilingual prose moves to the surrounding language blocks.
     * Closing delimiters are syntax, not part of an annotation value.
     *
     * HR: Čuva tipove/nazive anotacija; dvojezični opis premješta u okolne jezične blokove.
     * Završni delimiteri su sintaksa, ne dio vrijednosti anotacije.
     *
     * @return list<string>
     */
    private function annotationSignatures(string $comment): array
    {
        preg_match_all('/^\s*\*\s*(@[^\n]*)/m', $comment, $matches);
        $signatures = [];
        foreach ($matches[1] as $value) {
            $value = preg_replace('~\s*\*/\s*$~', '', rtrim($value)) ?? $value;
            $signatures[] = preg_replace('~\s+(?:EN|HR):.*$~', '', $value) ?? $value;
        }
        return $signatures;
    }
}
