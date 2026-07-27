<?php

namespace App\Support;

/**
 * Validates NACTVET / NACTE student registration numbers used as nactvet_reg_no.
 */
class StudentRegistrationNumber
{
    public static function isValid(string $raw): bool
    {
        $n = strtoupper(trim($raw));
        if ($n === '') {
            return false;
        }

        // NACTVET: S0000/0000/YYYY or P0000/0000/YYYY
        if (preg_match('/^[SP]\d{4}\/\d{4}\/\d{4}$/', $n)) {
            return true;
        }

        // NACTE: NS5391/0026/2024 (prefix + two numeric segments + year)
        if (preg_match('/^[A-Z]{2,}\d+\/\d+\/\d{4}$/', $n)) {
            return true;
        }

        // NACTE alternate: NEQ2024000909/2020 (prefix + long id + year)
        if (preg_match('/^[A-Z]{2,}\d{6,}\/\d{4}$/', $n)) {
            return true;
        }

        return false;
    }

    public static function validationMessage(): string
    {
        return 'Registration must be NACTVET (S0000/0000/YYYY), NACTE (e.g. NS5391/0026/2024), or NEQ-style (e.g. NEQ2024000909/2020).';
    }
}
