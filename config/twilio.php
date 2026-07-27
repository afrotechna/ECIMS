<?php

return [
  'enabled' => env('TWILIO_ENABLED', false),

  'account_sid' => env('TWILIO_ACCOUNT_SID'),
  'auth_token' => env('TWILIO_AUTH_TOKEN'),
  'from' => env('TWILIO_FROM_NUMBER'),

  /** Tanzania default; numbers without country code are prefixed with this (no +). */
  'default_country_code' => env('TWILIO_DEFAULT_COUNTRY_CODE', '255'),
];
