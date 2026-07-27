<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel 2003 XML (.xls) export — opens in Microsoft Excel without ext-zip.
 */
class ResultImportSpreadsheetExport
{
    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function download(string $filename, array $rows, string $sheetName = 'Results'): StreamedResponse
    {
        $xml = $this->buildXml($rows, $sheetName);

        return response()->streamDownload(function () use ($xml) {
            echo $xml;
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function save(string $absolutePath, array $rows, string $sheetName = 'Results'): void
    {
        $dir = dirname($absolutePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($absolutePath, $this->buildXml($rows, $sheetName));
    }

    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    private function buildXml(array $rows, string $sheetName): string
    {
        $safeName = htmlspecialchars(mb_substr($sheetName, 0, 31), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $out .= '<?mso-application progid="Excel.Sheet"?>'."\n";
        $out .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
        $out .= ' xmlns:o="urn:schemas-microsoft-com:office:office"';
        $out .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
        $out .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'."\n";
        $out .= '<Worksheet ss:Name="'.$safeName.'"><Table>'."\n";

        foreach ($rows as $row) {
            $out .= '<Row>';
            foreach ($row as $cell) {
                $text = trim((string) ($cell ?? ''));
                if ($text !== '' && is_numeric(str_replace(',', '.', $text))) {
                    $num = (float) str_replace(',', '.', $text);
                    $out .= '<Cell><Data ss:Type="Number">'.$num.'</Data></Cell>';
                } else {
                    $out .= '<Cell><Data ss:Type="String">'.htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</Data></Cell>';
                }
            }
            $out .= '</Row>'."\n";
        }

        $out .= '</Table></Worksheet></Workbook>';

        return $out;
    }
}
