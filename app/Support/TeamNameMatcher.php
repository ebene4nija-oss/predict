<?php

namespace App\Support;

/**
 * Reconciles club names across the two data sources.
 *
 * The fixture provider says "Wolverhampton Wanderers FC"; the stats CSV says
 * "Wolves". Neither is wrong and neither is going to change, so names are
 * normalised down to a comparable core and the handful that still will not meet
 * are listed explicitly.
 *
 * A club that fails to match gets no rates, which reads downstream as "we
 * cannot price a corner market for this fixture" — the safe failure. Guessing
 * would attach one club's corner rate to another's.
 */
class TeamNameMatcher
{
    /**
     * Tokens that carry no identifying information.
     *
     * "Real" is deliberately absent: dropping it would collapse Real Sociedad
     * and Sociedad correctly but also make "Real Madrid" and "Madrid" match
     * Atletico's normalised form in some feeds.
     */
    protected const NOISE = [
        'fc', 'afc', 'cf', 'ac', 'ss', 'as', 'us', 'sc', 'rc', 'rcd', 'ssc', 'sv', 'tsg',
        'vfb', 'vfl', 'bv', 'fsv', 'spvgg', 'calcio', 'club', 'de', 'the', '1899', '1846',
        '04', '05', '96', '98', 'og', 'olympique', 'associazione', 'societa',
    ];

    /**
     * Normalised equivalences the token rules cannot reach.
     *
     * Abbreviations that are not prefixes ("nottm" for "nottingham"), and clubs
     * whose two sources share no common token at all.
     *
     * @var array<string, string>
     */
    protected const ALIASES = [
        'nottm forest' => 'nottingham forest',
        'sheffield weds' => 'sheffield wednesday',
        'ath madrid' => 'atletico madrid',
        'atletico' => 'atletico madrid',
        'ath bilbao' => 'athletic',
        'athletic bilbao' => 'athletic',
        'sociedad' => 'real sociedad',
        'betis' => 'real betis',
        'espanol' => 'espanyol',
        'vallecano' => 'rayo vallecano',
        'la coruna' => 'deportivo',
        'inter' => 'internazionale',
        'internazionale milano' => 'internazionale',
        'milan' => 'milan',
        'bayern munich' => 'bayern munchen',
        'ein frankfurt' => 'eintracht frankfurt',
        'mgladbach' => 'borussia monchengladbach',
        'monchengladbach' => 'borussia monchengladbach',
        'leverkusen' => 'bayer leverkusen',
        'hoffenheim' => 'hoffenheim',
        'paris sg' => 'paris saint germain',
        'paris' => 'paris saint germain',
        'st etienne' => 'saint etienne',
        'man united' => 'manchester united',
        'man city' => 'manchester city',
        'newcastle' => 'newcastle united',
        'tottenham' => 'tottenham hotspur',
        'wolves' => 'wolverhampton wanderers',
        'leeds' => 'leeds united',
        'west ham' => 'west ham united',
        'brighton' => 'brighton hove albion',
        'leicester' => 'leicester city',
        'norwich' => 'norwich city',
        'stoke' => 'stoke city',
        'swansea' => 'swansea city',
        'cardiff' => 'cardiff city',
        'hull' => 'hull city',
    ];

    /**
     * Strip a club name down to a comparable core.
     */
    public static function normalise(string $name): string
    {
        $value = mb_strtolower(trim($name));

        // Accents: "München" and "Munchen" are the same club.
        $value = self::stripAccents($value);

        // Apostrophes are dropped, not spaced: they sit *inside* words
        // ("Nott'm", "M'Gladbach"), and splitting there would turn one token
        // into two and break the abbreviation rule.
        $value = str_replace(["'", '’', '`'], '', $value);

        // Ampersands and remaining punctuation, but keep spaces between words.
        $value = str_replace('&', ' and ', $value);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;

        $tokens = array_values(array_filter(
            preg_split('/\s+/', $value) ?: [],
            static fn (string $token): bool => $token !== '' && ! in_array($token, self::NOISE, true),
        ));

        $core = implode(' ', $tokens);

        return self::ALIASES[$core] ?? $core;
    }

    /**
     * Whether two club names refer to the same club.
     */
    public static function matches(string $a, string $b): bool
    {
        $left = self::normalise($a);
        $right = self::normalise($b);

        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        return self::isAbbreviationOf($left, $right) || self::isAbbreviationOf($right, $left);
    }

    /**
     * Pick the best match for a name from a list of candidates.
     *
     * @param  array<int|string, string>  $candidates  Keyed however the caller needs the answer.
     * @return int|string|null  The matching key, or null when nothing matches.
     */
    public static function bestMatch(string $name, array $candidates): int|string|null
    {
        $normalised = self::normalise($name);

        foreach ($candidates as $key => $candidate) {
            if (self::normalise($candidate) === $normalised) {
                return $key;
            }
        }

        // Exact normalised matches are preferred over abbreviations, so the
        // looser rule only runs once nothing matched outright.
        foreach ($candidates as $key => $candidate) {
            if (self::matches($name, $candidate)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Whether every token of the shorter name opens a token of the longer one,
     * in order — "man united" against "manchester united".
     */
    protected static function isAbbreviationOf(string $short, string $long): bool
    {
        $shortTokens = explode(' ', $short);
        $longTokens = explode(' ', $long);

        if (count($shortTokens) > count($longTokens)) {
            return false;
        }

        $index = 0;

        foreach ($shortTokens as $token) {
            $found = false;

            while ($index < count($longTokens)) {
                $candidate = $longTokens[$index];
                $index++;

                // A single letter matching a whole word is not evidence.
                if (mb_strlen($token) >= 3 && str_starts_with($candidate, $token)) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                return false;
            }
        }

        return true;
    }

    protected static function stripAccents(string $value): string
    {
        $map = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ø' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ñ' => 'n', 'ç' => 'c', 'ß' => 'ss', 'ł' => 'l', 'š' => 's', 'ž' => 'z',
        ];

        return strtr($value, $map);
    }
}
