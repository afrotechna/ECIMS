<?php

namespace App\Services;

/**
 * Reads Excel 2003 XML (.xls) sheets into row arrays for the result importer.
 */
class NactvetSpreadsheetXmlReader
{
    /**
     * @return list<list<string|null>>
     */
    public static function rowsFromFile(string $absolutePath): array
    {
        $xml = @simplexml_load_file($absolutePath);
        if ($xml === false) {
            return [];
        }

        $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');

        $rows = [];
        foreach ($xml->xpath('//ss:Worksheet/ss:Table/ss:Row') ?: [] as $row) {
            $cells = [];
            foreach ($row->xpath('ss:Cell/ss:Data') ?: [] as $data) {
                $cells[] = trim((string) $data);
            }
            if ($cells !== [] || $rows === []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    public static function isSpreadsheetXml(string $absolutePath): bool
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['xls', 'xml'], true)) {
            return true;
        }

        $head = @file_get_contents($absolutePath, false, null, 0, 200);

        return is_string($head) && (str_contains($head, 'Excel.Sheet') || str_contains($head, 'schemas-microsoft-com:office:spreadsheet'));
    }
}
