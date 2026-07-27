<?php

namespace App\Support;

/**
 * Minimal .xlsx reader (first worksheet) — no PhpSpreadsheet required.
 * Uses ZipArchive when available; otherwise falls back to scripts/xlsx_sheet_rows.py (openpyxl).
 */
class SimpleXlsxReader
{
    /**
     * @return list<list<string|null>>
     */
    public static function sheetRows(string $path, int $sheetIndex = 1): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return self::sheetRowsViaPython($path, $sheetIndex - 1);
        }

        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Could not open Excel file: '.$path);
        }

        $sharedStrings = self::readSharedStrings($zip);
        $sheetPath = 'xl/worksheets/sheet'.$sheetIndex.'.xml';
        if ($zip->locateName($sheetPath) === false) {
            $zip->close();
            throw new \RuntimeException('Worksheet not found in workbook.');
        }

        $xml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('Could not read worksheet XML.');
        }

        return self::parseSheetXml($xml, $sharedStrings);
    }

    /** @return list<string> */
    private static function readSharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $strings = [];
        if (preg_match_all('/<si>(.*?)<\/si>/s', $xml, $matches)) {
            foreach ($matches[1] as $chunk) {
                if (preg_match_all('/<t[^>]*>([^<]*)<\/t>/', $chunk, $parts)) {
                    $strings[] = html_entity_decode(implode('', $parts[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
                } else {
                    $strings[] = '';
                }
            }
        }

        return $strings;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<list<string|null>>
     */
    private static function parseSheetXml(string $xml, array $sharedStrings): array
    {
        $grid = [];
        if (! preg_match_all('/<row\b[^>]*r="(\d+)"[^>]*>(.*?)<\/row>/s', $xml, $rowMatches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($rowMatches as $rowMatch) {
            $rowNum = (int) $rowMatch[1];
            $rowXml = $rowMatch[2];
            if (! preg_match_all('/<c\b([^>]*)>(.*?)<\/c>/s', $rowXml, $cellMatches, PREG_SET_ORDER)) {
                $grid[$rowNum] = [];

                continue;
            }
            $rowCells = [];
            foreach ($cellMatches as $cellMatch) {
                $attrs = $cellMatch[1];
                $inner = $cellMatch[2];
                if (! preg_match('/\br="([A-Z]+)(\d+)"/', $attrs, $ref)) {
                    continue;
                }
                $col = self::columnLettersToIndex($ref[1]);
                $type = null;
                if (preg_match('/\bt="([^"]+)"/', $attrs, $tm)) {
                    $type = $tm[1];
                }
                $value = '';
                if (preg_match('/<v>([^<]*)<\/v>/', $inner, $vm)) {
                    $value = $vm[1];
                }
                if ($type === 's' && $value !== '' && ctype_digit($value)) {
                    $value = $sharedStrings[(int) $value] ?? '';
                }
                $rowCells[$col] = trim((string) $value);
            }
            if ($rowCells !== []) {
                $maxCol = max(array_keys($rowCells));
                $dense = [];
                for ($c = 0; $c <= $maxCol; $c++) {
                    $dense[$c] = $rowCells[$c] ?? null;
                }
                $grid[$rowNum] = $dense;
            }
        }

        ksort($grid);

        return array_values($grid);
    }

    /**
     * @return list<list<string|null>>
     */
    private static function sheetRowsViaPython(string $path, int $sheetIndexZeroBased = 0): array
    {
        $script = base_path('scripts/xlsx_sheet_rows.py');
        if (! is_file($script)) {
            throw new \RuntimeException('PHP ext-zip is not enabled and scripts/xlsx_sheet_rows.py was not found.');
        }

        $python = self::pythonBinary();
        $cmd = escapeshellarg($python).' '.escapeshellarg($script).' '.escapeshellarg($path).' '.(int) $sheetIndexZeroBased;
        $output = shell_exec($cmd.' 2>&1');
        if ($output === null || $output === '') {
            throw new \RuntimeException('Could not read Excel file (Python helper produced no output). Install openpyxl: python -m pip install openpyxl');
        }

        $decoded = json_decode(trim($output), true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Could not parse Excel helper output: '.substr(trim($output), 0, 200));
        }
        if (isset($decoded['error'])) {
            throw new \RuntimeException((string) $decoded['error']);
        }
        if (! isset($decoded['rows']) || ! is_array($decoded['rows'])) {
            throw new \RuntimeException('Unexpected Excel helper response.');
        }

        /** @var list<list<string|null>> $rows */
        $rows = $decoded['rows'];

        return $rows;
    }

    private static function pythonBinary(): string
    {
        $candidates = ['python3', 'python'];
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = ['python', 'py', 'python3'];
        }
        foreach ($candidates as $bin) {
            $which = PHP_OS_FAMILY === 'Windows'
                ? trim((string) shell_exec('where '.$bin.' 2>nul'))
                : trim((string) shell_exec('command -v '.$bin.' 2>/dev/null'));
            if ($which !== '') {
                return strtok($which, PHP_EOL) ?: $bin;
            }
        }

        return 'python';
    }

    private static function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }
}
