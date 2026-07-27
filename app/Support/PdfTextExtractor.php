<?php

namespace App\Support;

/**
 * Extract plain text from PDF files for question generation.
 * Prefer Poppler {@see pdftotext} when installed; otherwise use PHP fallbacks (text-based PDFs only).
 */
final class PdfTextExtractor
{
    public static function extract(string $absolutePath): string
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return '';
        }

        $viaCli = self::extractViaPdftotext($absolutePath);
        if ($viaCli !== '') {
            return $viaCli;
        }

        return self::extractViaPhp($absolutePath);
    }

    /**
     * Poppler {@see pdftotext} produces reliable output on Windows/Linux when on PATH or in known locations.
     */
    private static function extractViaPdftotext(string $absolutePath): string
    {
        $outputTxt = tempnam(sys_get_temp_dir(), 'pdft_');
        if ($outputTxt === false) {
            return '';
        }
        $outputTxt .= '.txt';

        $binaries = self::pdftotextCandidates();

        foreach ($binaries as $bin) {
            if ($bin === '') {
                continue;
            }
            if (! self::isPdftotextCandidateUsable($bin)) {
                continue;
            }

            $cmd = self::buildPdftotextCommand($bin, $absolutePath, $outputTxt);
            @exec($cmd, $_, $code);

            if ($code === 0 && is_file($outputTxt)) {
                $text = (string) file_get_contents($outputTxt);
                @unlink($outputTxt);
                $text = trim($text);

                if ($text !== '') {
                    return $text;
                }
            }

            @unlink($outputTxt);
        }

        @unlink($outputTxt);

        return '';
    }

    /**
     * @return list<string>
     */
    private static function pdftotextCandidates(): array
    {
        $extra = array_filter(array_map('trim', explode(PATH_SEPARATOR, (string) env('PDFTOTEXT_PATH', ''))));

        $defaults = ['pdftotext'];
        if (PHP_OS_FAMILY === 'Windows') {
            $defaults = array_merge([
                'pdftotext.exe',
                'C:\\Program Files\\poppler\\Library\\bin\\pdftotext.exe',
                'C:\\poppler\\Library\\bin\\pdftotext.exe',
                // Laragon / manual installs (oschwartz poppler-windows zip → Library\bin)
                'C:\\laragon\\bin\\poppler\\Library\\bin\\pdftotext.exe',
                'C:\\laragon\\bin\\poppler\\bin\\pdftotext.exe',
                'C:\\laragon\\bin\\poppler\\pdftotext.exe',
            ], $defaults);
        }

        return array_values(array_unique(array_merge($extra, $defaults)));
    }

    private static function isPdftotextCandidateUsable(string $bin): bool
    {
        if (str_contains($bin, DIRECTORY_SEPARATOR) || str_contains($bin, '\\') || str_contains($bin, '/')) {
            return is_file($bin);
        }

        return true;
    }

    private static function buildPdftotextCommand(string $bin, string $pdfPath, string $outTxt): string
    {
        $binEsc = escapeshellarg($bin);
        $pdfEsc = escapeshellarg($pdfPath);
        $outEsc = escapeshellarg($outTxt);

        if (PHP_OS_FAMILY === 'Windows') {
            return "{$binEsc} -layout -nopgbrk -enc UTF-8 {$pdfEsc} {$outEsc} 2>nul";
        }

        return "{$binEsc} -layout -nopgbrk -enc UTF-8 {$pdfEsc} {$outEsc} 2>/dev/null";
    }

    /**
     * Best-effort extraction without external tools (works for many compressed, text-based PDFs).
     */
    private static function extractViaPhp(string $absolutePath): string
    {
        $raw = (string) @file_get_contents($absolutePath);
        if ($raw === '') {
            return '';
        }

        $chunks = [];
        if (preg_match_all('/stream[\r\n]+(.+?)endstream/s', $raw, $streams)) {
            foreach ($streams[1] as $stream) {
                $decoded = self::decodePdfStream($stream);
                $chunks[] = self::extractTextOperatorsFromContent($decoded);
            }
        }

        $joined = trim(implode("\n", array_filter($chunks)));
        if ($joined !== '') {
            return self::normalizeWhitespace($joined);
        }

        $direct = self::extractTextOperatorsFromContent($raw);

        return self::normalizeWhitespace(trim($direct));
    }

    private static function decodePdfStream(string $stream): string
    {
        $stream = trim($stream);
        if ($stream === '') {
            return '';
        }

        $decoded = @gzuncompress($stream);
        if ($decoded !== false && $decoded !== '') {
            return $decoded;
        }

        $decoded = @gzdecode($stream);
        if ($decoded !== false && $decoded !== '') {
            return $decoded;
        }

        return $stream;
    }

    /**
     * Pull visible strings from PDF content streams (Tj, TJ, hex strings).
     */
    private static function extractTextOperatorsFromContent(string $content): string
    {
        $parts = [];

        if (preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)\s*Tj/s', $content, $tj)) {
            foreach ($tj[0] as $full) {
                if (preg_match('/\(((?:\\\\.|[^\\\\\)])*)\)\s*Tj/s', $full, $m)) {
                    $parts[] = self::unescapePdfLiteral($m[1]);
                }
            }
        }

        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $content, $arrays)) {
            foreach ($arrays[1] as $inner) {
                if (preg_match_all('/\(((?:\\\\.|[^\\\\\)])*)\)/s', $inner, $innerParts)) {
                    foreach ($innerParts[1] as $lit) {
                        $parts[] = self::unescapePdfLiteral($lit);
                    }
                }
            }
        }

        if (preg_match_all('/<([0-9A-Fa-f\s]+)>\s*Tj/s', $content, $hex)) {
            foreach ($hex[1] as $h) {
                $decoded = self::decodePdfHex($h);
                if ($decoded !== '') {
                    $parts[] = $decoded;
                }
            }
        }

        return implode(' ', array_filter($parts));
    }

    private static function unescapePdfLiteral(string $s): string
    {
        $out = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($c === '\\' && $i + 1 < $len) {
                $n = $s[++$i];
                $out .= match ($n) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'b' => "\b",
                    'f' => "\f",
                    '(' => '(',
                    ')' => ')',
                    '\\' => '\\',
                    default => $n,
                };
            } else {
                $out .= $c;
            }
        }

        return $out;
    }

    private static function decodePdfHex(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex) ?? '';
        if ($hex === '' || strlen($hex) % 2 !== 0) {
            return '';
        }

        $bin = @hex2bin($hex);
        if ($bin === false) {
            return '';
        }

        if (str_starts_with($bin, "\xFE\xFF")) {
            return (string) mb_convert_encoding(substr($bin, 2), 'UTF-8', 'UTF-16BE');
        }

        if (preg_match('/^[\x09\x0A\x0D\x20-\x7E]+$/', $bin)) {
            return $bin;
        }

        return (string) @mb_convert_encoding($bin, 'UTF-8', 'UTF-8');
    }

    private static function normalizeWhitespace(string $text): string
    {
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

        return trim((string) $text);
    }
}
