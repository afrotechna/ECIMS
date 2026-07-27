<?php

namespace App\Jobs;

use App\Models\Semester;
use App\Models\Student;
use App\Services\ResultReleaseSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendStudentResultSmsJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public int $tries = 3;

  public int $backoff = 30;

  public function __construct(
    public int $studentId,
    public int $semesterId,
    public string $template = ResultReleaseSmsService::TEMPLATE_FINAL,
    public ?int $sentBy = null,
  ) {}

  public function handle(ResultReleaseSmsService $sms): void
  {
    $student = Student::find($this->studentId);
    $semester = Semester::find($this->semesterId);

    if (! $student || ! $semester) {
      return;
    }

    $sms->sendForStudent($student, $semester, $this->template, $this->sentBy);
  }
}
