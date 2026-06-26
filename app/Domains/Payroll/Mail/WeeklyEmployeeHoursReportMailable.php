<?php

namespace App\Domains\Payroll\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyEmployeeHoursReportMailable extends Mailable
{
    use Queueable, SerializesModels;

    public string $body;

    public string $pdfPath;

    protected string $customSubject;

    public function __construct(string $subject, string $body, string $pdfPath)
    {
        $this->customSubject = $subject;
        $this->body = $body;
        $this->pdfPath = $pdfPath;
    }

    public function build(): self
    {
        return $this->subject($this->customSubject)
            ->view('payroll::emails.weekly-employee-hours-report')
            ->with(['body' => $this->body])
            ->attach($this->pdfPath, [
                'as' => 'WeeklyEmployeeHoursReport.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
