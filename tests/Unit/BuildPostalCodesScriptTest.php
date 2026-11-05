<?php

declare(strict_types=1);

namespace IntrAgile\HungarianValidation\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Runs bin/build-postal-codes.php on a small workbook laid out like the KSH
 * Gazetteer of Hungary: a title row above the header, codes as numbers, shared
 * strings and inline strings, several codes in one cell, "*" for Budapest
 * district centres and a notes sheet keyed by KSH code.
 */
final class BuildPostalCodesScriptTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        if (! class_exists(ZipArchive::class)) {
            self::markTestSkipped('ext-zip is required.');
        }

        $this->directory = sys_get_temp_dir().'/hu-postal-codes-'.bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function test_builds_the_postal_code_map(): void
    {
        $output = $this->directory.'/postal-codes.php';

        [$exitCode, , $stdout] = $this->runScript($this->workbook(), '--output='.$output);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Wrote 10 postal codes', $stdout);
        self::assertSame([
            '2000' => ['Szentendre'],
            '6500' => ['Baja'],
            '6503' => ['Baja'],
            '7400' => ['Kaposvár', 'Orci'],
            '7461' => ['Kaposvár'],
            '7478' => ['Kaposvár'],
            '7479' => ['Kaposvár'],
            '8790' => ['Zalaszentgrót'],
            '8795' => ['Zalaszentgrót'],
            '9999' => ['Cún', 'Csabdi', 'Ócsa', 'Pécs'],
        ], $this->load($output));
    }

    public function test_header_names_the_source_and_licence(): void
    {
        $output = $this->directory.'/postal-codes.php';
        $this->runScript($this->workbook(), '--output='.$output);
        $content = (string) file_get_contents($output);

        self::assertStringContainsString('Magyarország helységnévtára 2026', $content);
        self::assertStringContainsString('reference date 2026-01-01', $content);
        self::assertStringContainsString('https://www.ksh.hu/docs/helysegnevtar/hnt_letoltes_2026.xlsx', $content);
        self::assertStringContainsString('CC BY 4.0', $content);
        self::assertStringContainsString('Modified: extracted and restructured', $content);
    }

    public function test_check_reports_drift(): void
    {
        $workbook = $this->workbook();
        $output = $this->directory.'/postal-codes.php';

        [$missing, $missingError] = $this->runScript($workbook, '--check', '--output='.$output);
        self::assertSame(1, $missing);
        self::assertStringContainsString('does not exist', $missingError);
        self::assertFileDoesNotExist($output);

        $this->runScript($workbook, '--output='.$output);
        [$upToDate, , $stdout] = $this->runScript($workbook, '--check', '--output='.$output);
        self::assertSame(0, $upToDate);
        self::assertStringContainsString('is up to date', $stdout);

        $content = (string) file_get_contents($output);
        $content = str_replace("    '6503' => ['Baja'],\n", '', $content);
        $content = str_replace("'7461' => ['Kaposvár']", "'7461' => ['Pécs']", $content);
        file_put_contents($output, $content."\n");

        [$drift, $error] = $this->runScript($workbook, '--check', '--output='.$output);
        self::assertSame(1, $drift);
        self::assertStringContainsString('added: 1 (6503)', $error);
        self::assertStringContainsString('removed: 0', $error);
        self::assertStringContainsString('changed: 1 (7461)', $error);
    }

    public function test_rejects_an_unexpected_postal_code_value(): void
    {
        $workbook = $this->workbook(extraPart: ['15440', 'Szentendre', 'Belváros', 'n/a']);

        [$exitCode, $error] = $this->runScript($workbook, '--output='.$this->directory.'/postal-codes.php');

        self::assertSame(2, $exitCode);
        self::assertStringContainsString('unexpected postal code "n"', $error);
    }

    public function test_rejects_a_workbook_without_the_expected_sheets(): void
    {
        $path = $this->directory.'/other.xlsx';
        $this->writeWorkbook($path, ['Munka1' => [1 => ['A' => 'x']]]);

        [$exitCode, $error] = $this->runScript($path, '--output='.$this->directory.'/postal-codes.php');

        self::assertSame(2, $exitCode);
        self::assertStringContainsString('No sheet named "Településrészek..."', $error);
    }

    /**
     * @return array<string, list<string>>
     */
    private function load(string $file): array
    {
        /** @var array<string, list<string>> $data */
        $data = require $file;

        return $data;
    }

    /**
     * @return array{int, string, string} Exit code, stderr, stdout.
     */
    private function runScript(string ...$arguments): array
    {
        $command = [PHP_BINARY, dirname(__DIR__, 2).'/bin/build-postal-codes.php', ...array_values($arguments)];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stderr, $stdout];
    }

    /**
     * @param  list<string>|null  $extraPart  KSH code, settlement, settlement part, postal code.
     */
    private function workbook(?array $extraPart = null): string
    {
        $parts = [
            1 => ['A' => 'A településrészek részletes adatai'],
            2 => ['A' => 'Helység KSH kód', 'B' => 'Helység hivatalos megnevezése', 'C' => 'Településrész megnevezése', 'D' => 'Postai irányító-szám'],
            3 => ['A' => '03522', 'B' => 'Baja', 'C' => 'Központi belterület', 'D' => 6500],
            4 => ['A' => '20473', 'B' => 'Kaposvár', 'C' => 'Toponár', 'D' => 7461],
            5 => ['A' => '20473', 'B' => 'Kaposvár', 'C' => 'Központi belterület', 'D' => 7400],
            6 => ['A' => '24536', 'B' => 'Orci', 'C' => 'Központi belterület', 'D' => 7400],
            7 => ['A' => '20473', 'B' => 'Kaposvár', 'C' => 'Külterület', 'D' => 7400],
            8 => ['A' => '32522', 'B' => 'Zalaszentgrót', 'C' => 'Csáford külterület', 'D' => '8790/8795'],
            9 => ['A' => '02112', 'B' => 'Budapest 17. kerület', 'C' => 'Cinkotaidűlő', 'D' => '1172/1173'],
            10 => ['A' => '16586', 'B' => 'Budapest 05. kerület', 'C' => 'Központi belterület', 'D' => '*'],
            11 => ['A' => '15440', 'B' => ['inline' => 'Szentendre'], 'C' => 'Központi belterület', 'D' => 2000],
            // Out-of-order names check the Hungarian ordering: c < cs, ó sorts with o.
            12 => ['A' => '90001', 'B' => 'Pécs', 'C' => 'Központi belterület', 'D' => 9999],
            13 => ['A' => '90002', 'B' => 'Ócsa', 'C' => 'Központi belterület', 'D' => 9999],
            14 => ['A' => '90003', 'B' => 'Csabdi', 'C' => 'Központi belterület', 'D' => 9999],
            15 => ['A' => '90004', 'B' => 'Cún', 'C' => 'Központi belterület', 'D' => 9999],
            16 => ['A' => '90005', 'B' => 'Pécs', 'C' => 'Belváros', 'D' => 9999],
        ];

        if ($extraPart !== null) {
            $parts[17] = array_combine(['A', 'B', 'C', 'D'], $extraPart);
        }

        $notes = [
            1 => ['A' => 'Helység KSH kód', 'B' => 'Megjegyzés a postai irányítószámhoz'],
            2 => ['A' => 3522, 'B' => 'A 6500-as postai irányítószám Baja város központi belterületén belül Bajaszentistván városrészre nem vonatkozik. Bajaszentistván városrész postai irányítószáma: 6503.'],
            3 => ['A' => 20473, 'B' => 'Toponár postai irányítószáma 7479; Kaposfüred postai irányítószáma:7478.'],
            4 => ['A' => 16586, 'B' => 'Budapest főváros területe postakézbesítés szempontjából körzetekre oszlik.'],
        ];

        $path = $this->directory.'/hnt_letoltes_2026.xlsx';
        $this->writeWorkbook($path, [
            'Helységek 2026.01.01.' => [1 => ['A' => 'A helységek részletes adatai']],
            'Településrészek 2026.01.01.' => $parts,
            'Megj. postai ir. számhoz' => $notes,
        ]);

        return $path;
    }

    /**
     * Writes a minimal xlsx; string cells go to the shared string table unless given as ['inline' => text].
     *
     * @param  array<string, array<int, array<string, string|int|array{inline: string}>>>  $sheets
     */
    private function writeWorkbook(string $path, array $sheets): void
    {
        $strings = [];
        $sheetXml = [];

        foreach (array_values($sheets) as $index => $rows) {
            $xml = '';

            foreach ($rows as $number => $cells) {
                $xml .= '<row r="'.$number.'">';

                foreach ($cells as $column => $value) {
                    $ref = $column.$number;

                    if (is_int($value)) {
                        $xml .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';
                    } elseif (is_array($value)) {
                        $xml .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.htmlspecialchars($value['inline']).'</t></is></c>';
                    } else {
                        $strings[$value] ??= count($strings);
                        $xml .= '<c r="'.$ref.'" t="s"><v>'.$strings[$value].'</v></c>';
                    }
                }

                $xml .= '</row>';
            }

            $sheetXml[$index + 1] = '<?xml version="1.0" encoding="UTF-8"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xml.'</sheetData></worksheet>';
        }

        $sheetList = '';
        $relationships = '';

        foreach (array_keys($sheets) as $index => $name) {
            $id = $index + 1;
            $sheetList .= '<sheet name="'.htmlspecialchars($name).'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
            $relationships .= '<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$id.'.xml"/>';
        }

        $relationships .= '<Relationship Id="rIdStrings" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';

        $sharedStrings = '';

        foreach (array_keys($strings) as $string) {
            // Rich text runs: the visible text is the concatenation of the runs.
            $sharedStrings .= str_contains((string) $string, ' ')
                ? '<si><r><t xml:space="preserve">'.htmlspecialchars(strstr((string) $string, ' ', true) ?: '').'</t></r><r><t xml:space="preserve">'.htmlspecialchars((string) strstr((string) $string, ' ')).'</t></r></si>'
                : '<si><t>'.htmlspecialchars((string) $string).'</t></si>';
        }

        $zip = new ZipArchive;
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheetList.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'</Relationships>');
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.$sharedStrings.'</sst>');

        foreach ($sheetXml as $id => $xml) {
            $zip->addFromString('xl/worksheets/sheet'.$id.'.xml', $xml);
        }

        $zip->close();
    }
}
