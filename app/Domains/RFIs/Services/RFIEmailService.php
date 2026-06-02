<?php

namespace App\Domains\RFIs\Services;

use App\Core\Identity\Models\User;
use App\Domains\RFIs\Mail\FormalRfiMailable;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Models\RFIEmailDelivery;
use Illuminate\Support\Facades\Mail;

class RFIEmailService
{
    /**
     * @param  array<int, string>  $recipients
     */
    public function sendFormalRfi(
        RFI $rfi,
        User $sentBy,
        array $recipients,
        ?string $coverMessage = null,
        ?string $subject = null,
    ): RFIEmailDelivery {
        $rfi->loadMissing(['project', 'requestedBy', 'answeredBy', 'documents']);

        $formalBody = $this->buildFormalBody($rfi);

        Mail::to($recipients)->send(new FormalRfiMailable(
            rfi: $rfi,
            formalBody: $formalBody,
            coverMessage: $coverMessage,
            customSubject: $subject,
        ));

        return RFIEmailDelivery::query()->create([
            'rfi_id' => $rfi->id,
            'sent_by_id' => $sentBy->id,
            'recipients' => array_values($recipients),
            'subject' => $subject ?: $this->defaultSubject($rfi),
            'cover_message' => $coverMessage,
            'sent_at' => now(),
        ]);
    }

    public function buildFormalBody(RFI $rfi): string
    {
        $projectNumber = (string) ($rfi->project?->project_number ?? 'N/A');
        $projectName = (string) ($rfi->project?->name ?? 'N/A');
        $requestedBy = (string) ($rfi->requestedBy?->full_name ?? 'N/A');
        $answeredBy = (string) ($rfi->answeredBy?->full_name ?? 'N/A');
        $dueDate = $rfi->due_date?->format('Y-m-d') ?? 'N/A';
        $submittedDate = $rfi->created_at?->format('Y-m-d') ?? 'N/A';
        $answerDate = $rfi->answered_at?->format('Y-m-d H:i') ?? 'N/A';
        $costImpact = $rfi->cost_impact !== null ? '$'.number_format((float) $rfi->cost_impact, 2) : 'N/A';
        $scheduleImpact = $rfi->schedule_impact_days !== null ? $rfi->schedule_impact_days.' day(s)' : 'N/A';

        $lines = [
            'REQUEST FOR INFORMATION (RFI)',
            '==============================',
            '',
            'Project Number: '.$projectNumber,
            'Project Name: '.$projectName,
            'RFI Number: '.$rfi->number,
            'Subject: '.$rfi->subject,
            'Status: '.strtoupper($rfi->status),
            '',
            'Submitted By: '.$requestedBy,
            'Submitted Date: '.$submittedDate,
            'Due Date: '.$dueDate,
            '',
            'Question / Request:',
            (string) ($rfi->body ?: 'N/A'),
            '',
            'Answer:',
            (string) ($rfi->answer ?: 'No answer has been recorded.'),
            '',
            'Answered By: '.$answeredBy,
            'Answered Date: '.$answerDate,
            'Cost Impact: '.$costImpact,
            'Schedule Impact: '.$scheduleImpact,
        ];

        if ($rfi->documents->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'Referenced Documents:';

            foreach ($rfi->documents as $document) {
                $role = (string) ($document->pivot?->document_role ?? RFI::DOCUMENT_ROLE_REFERENCE);
                $status = (string) ($document->pivot?->document_status ?? RFI::DOCUMENT_STATUS_ACTIVE);

                $lines[] = sprintf(
                    '- %s (Role: %s, Status: %s)',
                    (string) $document->title,
                    ucfirst($role),
                    ucfirst($status),
                );
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private function defaultSubject(RFI $rfi): string
    {
        return sprintf(
            'RFI %s-%s: %s',
            (string) ($rfi->project?->project_number ?? 'PROJECT'),
            (string) $rfi->number,
            (string) $rfi->subject,
        );
    }
}
