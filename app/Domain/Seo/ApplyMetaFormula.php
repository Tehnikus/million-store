<?php

namespace App\Domain\Seo;

class ApplyMetaFormula
{
    private const array FILTERS = ['upper', 'lower', 'capitalize', 'number', 'currency'];

    /**
     * Apply formula to string
     * @param array<string, mixed> $vars
     * @return array{text: string, errors: string[]}
     */
    public static function apply(string $formula, array $vars): array
    {
        $errors = [];

        $text = preg_replace_callback(
            '/{{(.*?)}}/s',
            function (array $matches) use ($vars, &$errors) {
                return self::resolvePlaceholder(trim($matches[1]), $vars, $errors);
            },
            $formula
        );

        return ['text' => $text, 'errors' => $errors];
    }

    /**
     * Resolve each {{token}} inside curly braces
     * @param string $content token itself
     * @param array $vars translated row vars one of which replace token if matches: {{manufacturer}} => ['manufacturer'] => 'Manufacturer name'
     * @param array $errors Errors collector if no matches found
     * @return string
     */
    private static function resolvePlaceholder(string $content, array $vars, array &$errors): string
    {
        $prefix = '';
        $suffix = '';
        $candidates = [];

        foreach (self::splitRespectingQuotes($content) as $token) {
            if (preg_match('/^prefix:"(.*)"$/', $token, $m)) {
                $prefix = $m[1];
                continue;
            }

            if (preg_match('/^suffix:"(.*)"$/', $token, $m)) {
                $suffix = $m[1];
                continue;
            }

            $candidates[] = self::parseCandidate($token);
        }

        $lastChecked = null;

        foreach ($candidates as $candidate) {
            $lastChecked = $candidate['token'];

            if ($candidate['isLiteral']) {
                return self::applyFilter($candidate['token'], $candidate['filter']);
            }

            $value = $vars[$candidate['token']] ?? null;

            if ($value === null || $value === '' || $value === 0 || $value === '0') {
                // $errors[] = $lastChecked;
                continue;
            }

            $value = self::applyFilter((string) $value, $candidate['filter']);

            return $prefix . $value . $suffix;
        }

        if ($lastChecked !== null) {
            $errors[] = $lastChecked;
        }

        return '';
    }

    /** @return array{token: string, filter: ?string, isLiteral: bool} */
    private static function parseCandidate(string $token): array
    {
        if (preg_match('/^"(.*)"(?::(\w+))?$/', $token, $m)) {
            $filter = isset($m[2]) && in_array(strtolower($m[2]), self::FILTERS, true) ? strtolower($m[2]) : null;

            return ['token' => $m[1], 'filter' => $filter, 'isLiteral' => true];
        }

        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*):(\w+)$/', $token, $m) && in_array(strtolower($m[2]), self::FILTERS, true)) {
            return ['token' => $m[1], 'filter' => strtolower($m[2]), 'isLiteral' => false];
        }

        return ['token' => $token, 'filter' => null, 'isLiteral' => false];
    }

    /**
     * Apply various filters to token value
     * @param string $value
     * @param mixed $filter
     * @return array|bool|string|null
     */
    private static function applyFilter(string $value, ?string $filter): string
    {
        return match ($filter) {
            'upper'      => mb_strtoupper($value),
            'lower'      => mb_strtolower($value),
            'capitalize' => mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1),
            'number'     => number_format((float) $value),
            'currency'   => $value, // TODO format currency
            default      => $value,
        };
    }

    private static function splitRespectingQuotes(string $content): array
    {
        $tokens = [];
        $current = '';
        $insideQuotes = false;

        foreach (mb_str_split($content) as $char) {
            if (!$insideQuotes && $char === '"') {
                $insideQuotes = true;
                $current .= $char;
            } elseif ($insideQuotes && $char === '"') {
                $insideQuotes = false;
                $current .= $char;
            } elseif (!$insideQuotes && $char === '|') {
                if (trim($current) !== '') {
                    $tokens[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $tokens[] = trim($current);
        }

        return $tokens;
    }
}