<?php

namespace App\Services\Sms;

use App\Models\MessageLog;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioSmsSender
{
  public function isEnabled(): bool
  {
    return (bool) config('twilio.enabled')
      && config('twilio.account_sid')
      && config('twilio.auth_token')
      && config('twilio.from');
  }

  /**
   * @param  array{
   *   student_id?: int|null,
   *   semester_id?: int|null,
   *   recipient_role?: string|null,
   *   template?: string|null,
   *   sent_by?: int|null
   * }  $context
   */
  public function send(string $to, string $body, array $context = []): MessageLog
  {
    $e164 = PhoneNumber::toE164($to);

    $log = MessageLog::create([
      'channel' => 'sms',
      'recipient' => $e164 ?? $to,
      'body' => $body,
      'student_id' => $context['student_id'] ?? null,
      'semester_id' => $context['semester_id'] ?? null,
      'recipient_role' => $context['recipient_role'] ?? null,
      'template' => $context['template'] ?? null,
      'status' => 'pending',
      'sent_by' => $context['sent_by'] ?? auth()->id(),
    ]);

    if (! $e164) {
      return $this->markFailed($log, 'Invalid or missing phone number.');
    }

    if (! $this->isEnabled()) {
      return $this->markFailed($log, 'Twilio is not configured (TWILIO_ENABLED=false or missing credentials).');
    }

    $sid = config('twilio.account_sid');
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

    $response = Http::withBasicAuth($sid, config('twilio.auth_token'))
      ->asForm()
      ->post($url, [
        'To' => $e164,
        'From' => config('twilio.from'),
        'Body' => $body,
      ]);

    if ($response->successful()) {
      $log->update([
        'status' => 'sent',
        'sent_at' => now(),
        'external_id' => $response->json('sid'),
      ]);

      return $log->fresh();
    }

    $error = $response->json('message') ?? $response->body();
    Log::warning('Twilio SMS failed', ['to' => $e164, 'status' => $response->status(), 'error' => $error]);

    return $this->markFailed($log, (string) $error);
  }

  private function markFailed(MessageLog $log, string $message): MessageLog
  {
    $log->update([
      'status' => 'failed',
      'error_message' => mb_substr($message, 0, 2000),
    ]);

    return $log->fresh();
  }
}
