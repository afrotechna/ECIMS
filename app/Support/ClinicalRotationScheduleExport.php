<?php

namespace App\Support;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive as PhpZipArchive;

final class ClinicalRotationScheduleExport
{
    public static function filenameSlug(int $ntaLevel, Carbon $startMonday, string $ext): string
    {
        return 'nta'.$ntaLevel.'-clinical-rotation-schedule-'.$startMonday->format('Y-m-d').'.'.$ext;
    }

    public static function pdfResponse(array $payload, ?string $programmeName, ?string $semesterLabel): Response
    {
        if (! class_exists(Dompdf::class)) {
            abort(503, 'PDF export is not available yet: run `composer install` on the server after enabling the PHP zip extension (php.ini: extension=zip).');
        }

        $html = View::make('clinical-rotations.schedule-pdf', [
            'payload' => $payload,
            'programmeName' => $programmeName,
            'semesterName' => $semesterLabel,
        ])->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $name = self::filenameSlug((int) $payload['nta_level'], $payload['start_monday'], 'pdf');

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    public static function wordDownloadResponse(array $payload, ?string $programmeName, ?string $semesterLabel): BinaryFileResponse
    {
        if (extension_loaded('zip') && class_exists(PhpZipArchive::class)) {
            Settings::setZipClass(Settings::ZIPARCHIVE);
        } else {
            Settings::setZipClass(Settings::PCLZIP);
        }

        $prev = Settings::isOutputEscapingEnabled();
        Settings::setOutputEscapingEnabled(true);
        $tempFile = null;

        try {
            $phpWord = new PhpWord;
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(11);

            $section = $phpWord->addSection([
                'marginTop' => 1134,
                'marginBottom' => 1134,
                'marginLeft' => 1134,
                'marginRight' => 1134,
            ]);

            $section->addText('NTA Clinical Rotation Schedule — Confidential (internal use)', ['size' => 9, 'italic' => true]);
            $section->addTextBreak(1);

            $section->addText('NTA Level '.$payload['nta_level'].' — Clinical Rotation Schedule', ['bold' => true, 'size' => 14]);

            if ($programmeName) {
                $section->addText('Programme: '.$programmeName, [], ['spaceAfter' => 80]);
            }
            if ($semesterLabel) {
                $section->addText('Semester: '.$semesterLabel, [], ['spaceAfter' => 80]);
            }

            $wpb = (int) ($payload['weeks_per_block'] ?? 2);
            $section->addText('Duration: '.$payload['total_weeks'].' weeks', [], ['spaceAfter' => 80]);
            $section->addText(
                'Time in each department posting: '.$wpb.' week'.($wpb === 2 ? 's (fortnight block)' : ' (weekly block)'),
                [],
                ['spaceAfter' => 80]
            );
            $section->addText('Working days: Monday – Friday only', [], ['spaceAfter' => 80]);
            $section->addText('Start date: '.$payload['start_monday']->format('l, d F Y'), [], ['spaceAfter' => 120]);

            $section->addText('Weekly department allocation (each row = one Mon–Fri week)', ['bold' => true, 'size' => 12]);
            $section->addTextBreak(1);

            $n = (int) $payload['group_count'];
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
            $table->addRow();
            $table->addCell(900)->addText('Week', ['bold' => true]);
            $table->addCell(2000)->addText('Dates', ['bold' => true]);
            for ($g = 1; $g <= $n; $g++) {
                $table->addCell(1100)->addText('Group '.$g, ['bold' => true], ['alignment' => 'center']);
            }

            foreach ($payload['block_rows'] as $row) {
                $table->addRow();
                $table->addCell(900)->addText($row['week_label']);
                $table->addCell(2000)->addText($row['date_range']);
                foreach ($row['cells'] as $cell) {
                    $table->addCell(1100)->addText($cell, [], ['alignment' => 'center']);
                }
            }

            $section->addTextBreak(1);
            $section->addText('Rotation summary by group', ['bold' => true, 'size' => 12]);
            $section->addTextBreak(1);

            $sum = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80]);
            $sum->addRow();
            $sum->addCell(1200)->addText('Group', ['bold' => true]);
            $sum->addCell(9000)->addText('Rotation order (full cycle; '.$wpb.' week'.($wpb > 1 ? 's' : '').' per posting)', ['bold' => true]);
            foreach ($payload['group_order_lines'] as $slot => $line) {
                $sum->addRow();
                $sum->addCell(1200)->addText('Group '.$slot);
                $sum->addCell(9000)->addText($line);
            }

            $section->addTextBreak(1);
            $leg = [];
            foreach ($payload['legend'] as $abbr => $full) {
                $leg[] = $abbr.' = '.$full;
            }
            $section->addText('Abbreviations: '.implode('; ', $leg), ['size' => 9]);
            $section->addTextBreak(1);
            $section->addText(
                'Hospital placements and student rosters are managed on each rotation round in COHAS.',
                ['size' => 9, 'italic' => true]
            );

            $tempDir = storage_path('app/temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempFile = $tempDir.DIRECTORY_SEPARATOR.uniqid('crt_schedule_', true).'.docx';
            IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);
        } catch (\Throwable $e) {
            Log::error('Clinical rotation Word export failed', ['exception' => $e]);
            abort(500, 'Could not generate the Word file. Ensure PHP has the zip extension enabled, then restart the web server.');
        } finally {
            Settings::setOutputEscapingEnabled($prev);
        }

        if (! is_file($tempFile) || filesize($tempFile) < 100) {
            abort(500, 'Could not generate the schedule document.');
        }

        $name = self::filenameSlug((int) $payload['nta_level'], $payload['start_monday'], 'docx');

        return response()->download($tempFile, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
