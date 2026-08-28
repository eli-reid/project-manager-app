<div style="margin:0;padding:20px;background:#f0f0f0;font-family:Arial,Helvetica,sans-serif;color:#111;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:860px;margin:0 auto;background:#fff;border:1px solid #2a2a2a;border-collapse:collapse;">
        <tr>
            <td style="background:#000;color:#fff;text-align:center;font-size:16px;letter-spacing:0.5px;padding:10px 12px;font-weight:600;border-bottom:1px solid #2a2a2a;">
                REQUEST FOR INFORMATION (RFI)
            </td>
        </tr>

        @if ($coverMessage)
            <tr>
                <td style="padding:12px;border-bottom:1px solid #2a2a2a;background:#f8f8f8;font-size:13px;line-height:1.45;">
                    {!! nl2br(e($coverMessage)) !!}
                </td>
            </tr>
        @endif

        <tr>
            <td style="padding:0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
                    <tr>
                        <td style="width:50%;padding:0;vertical-align:top;border-right:1px solid #2a2a2a;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
                                <tr>
                                    <td style="width:34%;padding:6px 8px;border-bottom:1px solid #2a2a2a;background:#efefef;font-size:12px;font-weight:700;">RFI #:</td>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;font-size:12px;">{{ $rfi->number }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;background:#efefef;font-size:12px;font-weight:700;">DATE:</td>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;font-size:12px;">{{ $rfi->created_at?->format('Y-m-d') ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;background:#efefef;font-size:12px;font-weight:700;">NEEDED BY:</td>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;font-size:12px;">{{ $rfi->due_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;background:#efefef;font-size:12px;font-weight:700;">PROJECT:</td>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;font-size:12px;">{{ $rfi->project?->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 8px;background:#efefef;font-size:12px;font-weight:700;">PROJECT #:</td>
                                    <td style="padding:6px 8px;font-size:12px;">{{ $rfi->project?->project_number ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width:50%;padding:0;vertical-align:top;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;background:#efefef;font-size:12px;font-weight:700;">SUBMITTED TO:</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;font-size:12px;line-height:1.5;">
                                        {{ implode(', ', $recipients) !== '' ? implode(', ', $recipients) : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 8px;border-bottom:1px solid #2a2a2a;background:#efefef;font-size:12px;font-weight:700;">SUBMITTED BY:</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 8px;font-size:12px;line-height:1.5;">
                                        {{ $rfi->requestedBy?->full_name ?? 'N/A' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="background:#6d6d6d;color:#fff;text-align:center;padding:6px 10px;font-size:12px;font-weight:700;border-top:1px solid #2a2a2a;border-bottom:1px solid #2a2a2a;">
                RFI DESCRIPTION
            </td>
        </tr>
        <tr>
            <td style="padding:12px;min-height:160px;font-size:13px;line-height:1.55;border-bottom:1px solid #2a2a2a;white-space:pre-wrap;">
                {{ $rfi->body ?: 'N/A' }}
            </td>
        </tr>

        <tr>
            <td style="padding:8px 10px;border-bottom:1px solid #2a2a2a;font-size:12px;">
                <strong>ATTACHMENTS:</strong>
                @if ($rfi->documents->isNotEmpty())
                    {{ $rfi->documents->pluck('title')->implode(', ') }}
                @else
                    None
                @endif
            </td>
        </tr>

        <tr>
            <td style="padding:8px 10px;border-bottom:1px solid #2a2a2a;font-size:12px;">
                <strong>SUBMITTED BY:</strong> {{ $rfi->requestedBy?->full_name ?? 'N/A' }}
            </td>
        </tr>

        <tr>
            <td style="background:#6d6d6d;color:#fff;text-align:center;padding:6px 10px;font-size:12px;font-weight:700;border-bottom:1px solid #2a2a2a;">
                RESPONSE TO RFI
            </td>
        </tr>
        <tr>
            <td style="padding:12px;min-height:160px;font-size:13px;line-height:1.55;border-bottom:1px solid #2a2a2a;white-space:pre-wrap;">
                {{ $rfi->answer ?: 'Pending response.' }}
            </td>
        </tr>

        <tr>
            <td style="padding:0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
                    <tr>
                        <td style="width:50%;padding:8px 10px;border-right:1px solid #2a2a2a;font-size:12px;">
                            <strong>RESPONSE BY:</strong> {{ $rfi->answeredBy?->full_name ?? 'N/A' }}
                        </td>
                        <td style="width:50%;padding:8px 10px;font-size:12px;">
                            <strong>DATE:</strong> {{ $rfi->answered_at?->format('Y-m-d') ?? 'N/A' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
