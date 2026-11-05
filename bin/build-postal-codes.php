<?php

declare(strict_types=1);

/*
 * Builds resources/postal-codes.php from the KSH Gazetteer of Hungary
 * (Magyarország helységnévtára) workbook.
 *
 * Usage: php bin/build-postal-codes.php <hnt_letoltes_YYYY.xlsx> [--check] [--output=<file>]
 *
 *   --check          Compare the generated content with the existing file instead of
 *                    writing it; exit with 1 and a summary when they differ.
 *   --output=<file>  Target file, resources/postal-codes.php by default.
 *
 * Sources inside the workbook:
 *   - "Településrészek" sheet: settlement part rows with the postal code column. A cell may
 *     hold several codes ("1172/1173"); "*" (Budapest district centres) is skipped.
 *   - "Megj. postai ir. számhoz" sheet: free-text notes keyed by the settlement KSH code,
 *     naming further codes ("Badacsony városrész postai irányítószáma: 8261.").
 *
 * Budapest codes are omitted: every code starting with 1 belongs to Budapest, which the
 * library answers without the data file. Requires ext-zip and ext-dom only.
 */

const PARTS_SHEET_PREFIX = 'Településrészek';
const NOTES_SHEET_PREFIX = 'Megj. postai';
const SPREADSHEET_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
const RELATIONSHIP_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
const PACKAGE_RELATIONSHIP_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

exit(main(arguments()));

/**
 * @return list<string>
 */
function arguments(): array
{
    $argv = $_SERVER['argv'] ?? [];

    return is_array($argv) ? array_values(array_filter(array_slice($argv, 1), is_string(...))) : [];
}

/**
 * @param  list<string>  $arguments
 */
function main(array $arguments): int
{
    $check = false;
    $output = dirname(__DIR__).'/resources/postal-codes.php';
    $input = null;

    foreach ($arguments as $argument) {
        if ($argument === '--check') {
            $check = true;
        } elseif (str_starts_with($argument, '--output=')) {
            $output = substr($argument, strlen('--output='));
        } elseif ($input === null && ! str_starts_with($argument, '--')) {
            $input = $argument;
        } else {
            return usage("Unexpected argument: {$argument}");
        }
    }

    if ($input === null) {
        return usage('Missing workbook path.');
    }

    try {
        $workbook = read_workbook($input);
        [$edition, $referenceDate] = edition($workbook);
        $codes = extract_postal_codes($workbook);
    } catch (RuntimeException $e) {
        fwrite(STDERR, 'Error: '.$e->getMessage().PHP_EOL);

        return 2;
    }

    $content = render($codes, $edition, $referenceDate);

    if ($check) {
        return check($output, $content, $codes);
    }

    if (! is_dir(dirname($output)) && ! mkdir(dirname($output), 0777, true) && ! is_dir(dirname($output))) {
        fwrite(STDERR, "Error: cannot create the directory of {$output}.".PHP_EOL);

        return 2;
    }

    if (file_put_contents($output, $content) === false) {
        fwrite(STDERR, "Error: cannot write {$output}.".PHP_EOL);

        return 2;
    }

    fwrite(STDOUT, sprintf('Wrote %d postal codes to %s.', count($codes), $output).PHP_EOL);

    return 0;
}

function usage(string $error): int
{
    fwrite(STDERR, $error.PHP_EOL.'Usage: php bin/build-postal-codes.php <xlsx> [--check] [--output=<file>]'.PHP_EOL);

    return 2;
}

/**
 * Reads every sheet of an xlsx workbook as rows of cell values keyed by column letter.
 *
 * @return array<string, array<int, array<string, string>>> Sheet name => row number => column => value.
 *
 * @throws RuntimeException When the file is not a readable xlsx workbook.
 */
function read_workbook(string $path): array
{
    if (! is_file($path)) {
        throw new RuntimeException("File not found: {$path}");
    }

    $zip = new ZipArchive;

    if ($zip->open($path, ZipArchive::RDONLY) !== true) {
        throw new RuntimeException("Not a zip archive: {$path}");
    }

    try {
        $relationships = [];

        foreach (xpath(zip_entry($zip, 'xl/_rels/workbook.xml.rels'))->query('//p:Relationship') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $relationships[$node->getAttribute('Id')] = package_path($node->getAttribute('Target'));
            }
        }

        $sharedStrings = [];

        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $xpath = xpath(zip_entry($zip, 'xl/sharedStrings.xml'));

            foreach ($xpath->query('/m:sst/m:si') ?: [] as $item) {
                // Keep the index aligned with the table even for an unexpected node.
                $sharedStrings[] = $item instanceof DOMElement ? text_of($xpath, $item) : '';
            }
        }

        $sheets = [];

        foreach (xpath(zip_entry($zip, 'xl/workbook.xml'))->query('/m:workbook/m:sheets/m:sheet') ?: [] as $sheet) {
            if (! $sheet instanceof DOMElement) {
                continue;
            }

            $target = $relationships[$sheet->getAttributeNS(RELATIONSHIP_NS, 'id')] ?? null;

            if ($target === null) {
                throw new RuntimeException('Sheet without a relationship: '.$sheet->getAttribute('name'));
            }

            $sheets[$sheet->getAttribute('name')] = read_sheet(zip_entry($zip, $target), $sharedStrings);
        }
    } finally {
        $zip->close();
    }

    return $sheets;
}

/**
 * @param  list<string>  $sharedStrings
 * @return array<int, array<string, string>>
 */
function read_sheet(string $xml, array $sharedStrings): array
{
    $xpath = xpath($xml);
    $rows = [];

    foreach ($xpath->query('/m:worksheet/m:sheetData/m:row') ?: [] as $row) {
        if (! $row instanceof DOMElement) {
            continue;
        }

        $cells = [];

        foreach ($xpath->query('m:c', $row) ?: [] as $cell) {
            if (! $cell instanceof DOMElement || preg_match('/^([A-Z]+)/', $cell->getAttribute('r'), $m) !== 1) {
                continue;
            }

            $value = match ($cell->getAttribute('t')) {
                's' => $sharedStrings[(int) string_value($xpath, 'm:v', $cell)] ?? '',
                'inlineStr' => text_of($xpath, first_element($xpath, 'm:is', $cell)),
                default => string_value($xpath, 'm:v', $cell),
            };

            if ($value !== '') {
                $cells[$m[1]] = $value;
            }
        }

        if ($cells !== []) {
            $rows[(int) $row->getAttribute('r')] = $cells;
        }
    }

    return $rows;
}

/**
 * The visible text of a string item: its own runs, without phonetic hints.
 */
function text_of(DOMXPath $xpath, ?DOMNode $item): string
{
    if ($item === null) {
        return '';
    }

    $text = '';

    foreach ($xpath->query('m:t | m:r/m:t', $item) ?: [] as $node) {
        if ($node instanceof DOMNode) {
            $text .= $node->textContent;
        }
    }

    return $text;
}

function string_value(DOMXPath $xpath, string $expression, DOMNode $context): string
{
    $node = first_element($xpath, $expression, $context);

    return $node === null ? '' : $node->textContent;
}

function first_element(DOMXPath $xpath, string $expression, DOMNode $context): ?DOMElement
{
    $node = ($xpath->query($expression, $context) ?: null)?->item(0);

    return $node instanceof DOMElement ? $node : null;
}

function xpath(string $xml): DOMXPath
{
    $document = new DOMDocument;

    if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
        throw new RuntimeException('Malformed XML in workbook.');
    }

    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('m', SPREADSHEET_NS);
    $xpath->registerNamespace('p', PACKAGE_RELATIONSHIP_NS);

    return $xpath;
}

function zip_entry(ZipArchive $zip, string $name): string
{
    $content = $zip->getFromName($name);

    if ($content === false) {
        throw new RuntimeException("Missing workbook part: {$name}");
    }

    return $content;
}

/**
 * Resolves a relationship target of xl/workbook.xml to a package path.
 */
function package_path(string $target): string
{
    return str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
}

/**
 * @param  array<string, array<int, array<string, string>>>  $workbook
 * @return array{string, string} The edition year and the reference date (Y-m-d).
 *
 * @throws RuntimeException When the settlement part sheet name carries no date.
 */
function edition(array $workbook): array
{
    [$name] = sheet($workbook, PARTS_SHEET_PREFIX);

    if (preg_match('/(\d{4})\.\s*(\d{2})\.\s*(\d{2})\./', $name, $m) !== 1) {
        throw new RuntimeException("No reference date in sheet name: {$name}");
    }

    return [$m[1], "{$m[1]}-{$m[2]}-{$m[3]}"];
}

/**
 * Builds the postal code => settlement names map, Budapest codes excluded.
 *
 * @param  array<string, array<int, array<string, string>>>  $workbook
 * @return array<string, list<string>>
 *
 * @throws RuntimeException When the expected sheets, columns or values are missing.
 */
function extract_postal_codes(array $workbook): array
{
    [$partsName, $parts] = sheet($workbook, PARTS_SHEET_PREFIX);
    [$headerRow, $columns] = header_row($partsName, $parts, [
        'ksh' => 'helységkshkód',
        'name' => 'helységhivatalosmegnevezése',
        'postal' => 'postaiirányítószám',
    ]);

    /** @var array<string, array<string, true>> $map */
    $map = [];
    /** @var array<string, string> $settlements KSH code => settlement name. */
    $settlements = [];

    foreach ($parts as $number => $row) {
        if ($number <= $headerRow || ! isset($row[$columns['ksh']])) {
            continue;
        }

        $name = settlement_name($row[$columns['name']] ?? '');

        if ($name === '') {
            throw new RuntimeException("{$partsName} row {$number}: empty settlement name.");
        }

        $settlements[ksh_code($row[$columns['ksh']])] = $name;

        foreach (explode('/', $row[$columns['postal']] ?? '') as $code) {
            $code = trim($code);

            if ($code === '' || $code === '*') {
                continue;
            }

            if (preg_match('/^[0-9]{4}\z/', $code) !== 1) {
                throw new RuntimeException("{$partsName} row {$number}: unexpected postal code \"{$code}\".");
            }

            $map[$code][$name] = true;
        }
    }

    [$notesName, $notes] = sheet($workbook, NOTES_SHEET_PREFIX);
    [$notesHeader, $noteColumns] = header_row($notesName, $notes, [
        'ksh' => 'helységkshkód',
        'note' => 'megjegyzésapostaiirányítószámhoz',
    ]);

    foreach ($notes as $number => $row) {
        if ($number <= $notesHeader || ! isset($row[$noteColumns['ksh']])) {
            continue;
        }

        $name = $settlements[ksh_code($row[$noteColumns['ksh']])]
            ?? throw new RuntimeException("{$notesName} row {$number}: unknown KSH code {$row[$noteColumns['ksh']]}.");

        preg_match_all('/irányítószáma\s*:?\s*([0-9]{4})(?![0-9])/u', $row[$noteColumns['note']] ?? '', $matches);

        foreach ($matches[1] as $code) {
            $map[$code][$name] = true;
        }
    }

    $codes = [];

    foreach ($map as $code => $names) {
        $code = (string) $code;

        if (str_starts_with($code, '1')) {
            continue;
        }

        $names = array_map('strval', array_keys($names));
        usort($names, 'compare_names');
        $codes[$code] = $names;
    }

    ksort($codes, SORT_STRING);

    return $codes;
}

/**
 * @param  array<string, array<int, array<string, string>>>  $workbook
 * @return array{string, array<int, array<string, string>>}
 *
 * @throws RuntimeException When no sheet name starts with the prefix.
 */
function sheet(array $workbook, string $prefix): array
{
    foreach ($workbook as $name => $rows) {
        if (str_starts_with((string) $name, $prefix)) {
            return [(string) $name, $rows];
        }
    }

    throw new RuntimeException("No sheet named \"{$prefix}...\" in the workbook.");
}

/**
 * Finds the header row and the column letter of each required header.
 *
 * @param  array<int, array<string, string>>  $rows
 * @param  array<string, string>  $required  Key => normalised header text.
 * @return array{int, array<string, string>}
 *
 * @throws RuntimeException When no row holds every required header.
 */
function header_row(string $sheet, array $rows, array $required): array
{
    foreach ($rows as $number => $row) {
        $columns = [];

        foreach ($row as $column => $value) {
            $key = array_search(header_key($value), $required, true);

            if ($key !== false) {
                $columns[$key] = $column;
            }
        }

        if (count($columns) === count($required)) {
            return [$number, $columns];
        }
    }

    throw new RuntimeException("{$sheet}: missing columns ".implode(', ', $required).'.');
}

/**
 * Lowercases a header and drops whitespace and hyphens ("Postai irányító-szám").
 */
function header_key(string $header): string
{
    return lowercase((string) preg_replace('/[\s\-\x{00AD}]+/u', '', $header));
}

function ksh_code(string $value): string
{
    return ltrim(trim($value), '0');
}

/**
 * Collapses whitespace; Budapest districts ("Budapest 05. kerület") become "Budapest".
 */
function settlement_name(string $value): string
{
    $name = trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $value));

    return preg_match('/^Budapest\b/u', $name) === 1 ? 'Budapest' : $name;
}

function lowercase(string $value): string
{
    return strtr(strtolower($value), [
        'Á' => 'á', 'É' => 'é', 'Í' => 'í', 'Ó' => 'ó', 'Ö' => 'ö',
        'Ő' => 'ő', 'Ú' => 'ú', 'Ü' => 'ü', 'Ű' => 'ű',
    ]);
}

/**
 * Orders names by an approximation of the Hungarian alphabet: accented vowels sort with their
 * base vowel, while ö/ő, ü/ű and the digraphs (cs, dz, dzs, gy, ly, ny, sz, ty, zs) sort as
 * separate letters. Byte order breaks ties, so the result is deterministic on every platform.
 */
function compare_names(string $a, string $b): int
{
    return [sort_key($a), $a] <=> [sort_key($b), $b];
}

function sort_key(string $name): string
{
    // strtr() replaces the longest match first, so "dzs" wins over "dz".
    return strtr(lowercase($name), [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ö' => 'o~', 'ő' => 'o~', 'ü' => 'u~', 'ű' => 'u~',
        'cs' => 'c~', 'dzs' => 'd~~', 'dz' => 'd~', 'gy' => 'g~', 'ly' => 'l~',
        'ny' => 'n~', 'sz' => 's~', 'ty' => 't~', 'zs' => 'z~',
    ]);
}

/**
 * @param  array<string, list<string>>  $codes
 */
function render(array $codes, string $edition, string $referenceDate): string
{
    $url = "https://www.ksh.hu/docs/helysegnevtar/hnt_letoltes_{$edition}.xlsx";
    $lines = [];

    foreach ($codes as $code => $names) {
        $quoted = array_map(static fn (string $name): string => "'".addcslashes($name, "'\\")."'", $names);
        $lines[] = "    '{$code}' => [".implode(', ', $quoted).'],';
    }

    $body = implode("\n", $lines);

    return <<<PHP
        <?php

        declare(strict_types=1);

        /*
         * Hungarian postal code => settlement names.
         *
         * Source: KSH, Magyarország helységnévtára {$edition} (Gazetteer of Hungary), reference date {$referenceDate}
         * {$url}
         * License: CC BY 4.0 (https://creativecommons.org/licenses/by/4.0/), see DATA-LICENSE.md.
         * Modified: extracted and restructured; settlement parts are merged per postal code and
         * Budapest codes (1xxx) are omitted.
         *
         * Generated by bin/build-postal-codes.php; do not edit by hand.
         */

        return [
        {$body}
        ];

        PHP;
}

/**
 * @param  array<string, list<string>>  $codes
 */
function check(string $output, string $content, array $codes): int
{
    $existing = is_file($output) ? file_get_contents($output) : false;

    if ($existing === $content) {
        fwrite(STDOUT, sprintf('%s is up to date (%d postal codes).', $output, count($codes)).PHP_EOL);

        return 0;
    }

    $current = [];

    if ($existing !== false) {
        $loaded = (static fn (string $file): mixed => require $file)($output);
        $current = is_array($loaded) ? $loaded : [];
    }

    $added = array_keys(array_diff_key($codes, $current));
    $removed = array_keys(array_diff_key($current, $codes));
    $changed = [];

    foreach (array_intersect_key($codes, $current) as $code => $names) {
        if ($current[$code] !== $names) {
            $changed[] = $code;
        }
    }

    fwrite(STDERR, ($existing === false ? "{$output} does not exist." : "{$output} is out of date.").PHP_EOL);
    fwrite(STDERR, sprintf('  added: %d%s', count($added), sample($added)).PHP_EOL);
    fwrite(STDERR, sprintf('  removed: %d%s', count($removed), sample($removed)).PHP_EOL);
    fwrite(STDERR, sprintf('  changed: %d%s', count($changed), sample($changed)).PHP_EOL);

    if ($added === [] && $removed === [] && $changed === []) {
        fwrite(STDERR, '  the data is identical; the header or formatting differs.'.PHP_EOL);
    }

    return 1;
}

/**
 * @param  list<int|string>  $keys
 */
function sample(array $keys): string
{
    if ($keys === []) {
        return '';
    }

    return ' ('.implode(', ', array_slice($keys, 0, 10)).(count($keys) > 10 ? ', ...' : '').')';
}
