<?php

namespace App\Support;

class PhoneNumber
{
  /**
   * Normalize a local or international number to E.164 (e.g. +255712345678).
   */
  public static function toE164(?string $raw, ?string $defaultCountryCode = null): ?string
  {
    if ($raw === null || trim($raw) === '') {
      return null;
    }

    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits === '' || $digits === null) {
      return null;
    }

    $cc = preg_replace('/\D+/', '', (string) ($defaultCountryCode ?? config('twilio.default_country_code', '255')));
    if ($cc === '') {
      $cc = '255';
    }

    if (str_starts_with($digits, '00')) {
      $digits = substr($digits, 2);
    }

    if (str_starts_with($digits, $cc)) {
      return '+'.$digits;
    }

    if (str_starts_with($digits, '0')) {
      return '+'.$cc.substr($digits, 1);
    }

    if (strlen($digits) === 9 && in_array($digits[0], ['6', '7'], true)) {
      return '+'.$cc.$digits;
    }

    if (strlen($digits) >= 10) {
      return '+'.$digits;
    }

    return null;
  }
}
