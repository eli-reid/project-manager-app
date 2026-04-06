<?php

namespace App\Domains\Timecards\Services;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Notifications\TimecardApprovedNotification;
use App\Domains\Timecards\Notifications\TimecardRejectedNotification;
use App\Domains\Timecards\Notifications\TimecardSubmittedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimecardLifecycleService
{
    public function __construct(
        private readonly TimecardWeekService $timecardWeekService,
        private readonly TimecardEntrySyncService $timecardEntrySyncService,
        private readonly TimecardNotificationRecipientService $timecardNotificationRecipientService,
    ) {}

    /**
     * @param  array{notes?:string|null}  $attributes
     */
    public function createDraftForUser(User $user, Carbon|string $weekStarting, array $attributes = []): Timecard
    {
        $normalizedWeekStart = $this->timecardWeekService->normalizeWeekStart($weekStarting);

        if ($this->timecardWeekService->hasExistingTimecardForWeek((string) $user->id, $normalizedWeekStart)) {
            throw ValidationException::withMessages([
                'week_starting' => 'A timecard already exists for that week.',
            ]);
        }

        return Timecard::query()->create([
            'user_id' => $user->id,
            'week_starting' => $normalizedWeekStart->toDateString(),
            'week_ending' => $this->timecardWeekService->weekEndingFor($normalizedWeekStart)->toDateString(),
            'status' => Timecard::STATUS_DRAFT,
            'notes' => $attributes['notes'] ?? null,
            'total_hours' => 0,
        ]);
    }

    /**
     * @param  array{notes?:string|null}  $attributes
     * @param  array<int, array<string, mixed>>|null  $entries
     */
    public function createForUser(User $user, Carbon|string $weekStarting, array $attributes = [], ?array $entries = null): Timecard
    {
        $timecard = $this->createDraftForUser($user, $weekStarting, $attributes);

        if ($entries !== null) {
            return $this->updateDraft($timecard, $attributes, $entries);
        }

        return $timecard;
    }

    /**
     * @param  array{notes?:string|null,week_starting?:string|null}  $attributes
     * @param  array<int, array<string, mixed>>|null  $entries
     */
    public function updateDraft(Timecard $timecard, array $attributes = [], ?array $entries = null): Timecard
    {
        if ($timecard->status !== Timecard::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'timecard' => 'Only draft timecards may be updated.',
            ]);
        }

        return DB::transaction(function () use ($timecard, $attributes, $entries): Timecard {
            $payload = [
                'notes' => $attributes['notes'] ?? $timecard->notes,
            ];

            if (! empty($attributes['week_starting'])) {
                $normalizedWeekStart = $this->timecardWeekService->normalizeWeekStart((string) $attributes['week_starting']);

                if ($this->timecardWeekService->hasExistingTimecardForWeek((string) $timecard->user_id, $normalizedWeekStart, (string) $timecard->id)) {
                    throw ValidationException::withMessages([
                        'week_starting' => 'A timecard already exists for that week.',
                    ]);
                }

                $payload['week_starting'] = $normalizedWeekStart->toDateString();
                $payload['week_ending'] = $this->timecardWeekService->weekEndingFor($normalizedWeekStart)->toDateString();
            }

            $timecard->update($payload);

            if ($entries !== null) {
                $this->timecardEntrySyncService->sync($timecard, $entries);
            }

            return $timecard->fresh();
        });
    }

    /**
     * @param  array{notes?:string|null,week_starting?:string|null}  $attributes
     * @param  array<int, array<string, mixed>>|null  $entries
     */
    public function updateForAdmin(Timecard $timecard, User $user, array $attributes = [], ?array $entries = null): Timecard
    {
        if ($timecard->status === Timecard::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'timecard' => 'Approved timecards may not be edited.',
            ]);
        }

        return DB::transaction(function () use ($timecard, $user, $attributes, $entries): Timecard {
            $payload = [
                'user_id' => $user->id,
                'notes' => $attributes['notes'] ?? $timecard->notes,
            ];

            if (! empty($attributes['week_starting'])) {
                $normalizedWeekStart = $this->timecardWeekService->normalizeWeekStart((string) $attributes['week_starting']);

                if ($this->timecardWeekService->hasExistingTimecardForWeek((string) $user->id, $normalizedWeekStart, (string) $timecard->id)) {
                    throw ValidationException::withMessages([
                        'week_starting' => 'A timecard already exists for that week.',
                    ]);
                }

                $payload['week_starting'] = $normalizedWeekStart->toDateString();
                $payload['week_ending'] = $this->timecardWeekService->weekEndingFor($normalizedWeekStart)->toDateString();
            }

            $timecard->update($payload);

            if ($entries !== null) {
                $this->timecardEntrySyncService->sync($timecard, $entries);
            }

            return $timecard->fresh();
        });
    }

    public function submit(Timecard $timecard): Timecard
    {
        if ($timecard->status !== Timecard::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'timecard' => 'Only draft timecards may be submitted.',
            ]);
        }

        $timecard = $this->timecardEntrySyncService->recalculateTotals($timecard);

        if (! $timecard->entries()->exists() || (float) $timecard->total_hours <= 0) {
            throw ValidationException::withMessages([
                'entries' => 'Add at least one time entry before submitting.',
            ]);
        }

        $timecard->update([
            'status' => Timecard::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $freshTimecard = $timecard->fresh(['user']);

        $this->timecardNotificationRecipientService
            ->approversForSubmittedTimecard($freshTimecard)
            ->each(function (User $recipient) use ($freshTimecard): void {
                $recipient->notify(new TimecardSubmittedNotification($freshTimecard));
            });

        return $freshTimecard;
    }

    public function approve(Timecard $timecard, User $approver): Timecard
    {
        if ($timecard->status !== Timecard::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'timecard' => 'Only submitted timecards may be approved.',
            ]);
        }

        $timecard->update([
            'status' => Timecard::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $freshTimecard = $timecard->fresh(['user']);
        $freshTimecard->user?->notify(new TimecardApprovedNotification($freshTimecard));

        return $freshTimecard;
    }

    public function reject(Timecard $timecard, User $rejector, ?string $reason = null): Timecard
    {
        if ($timecard->status !== Timecard::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'timecard' => 'Only submitted timecards may be rejected.',
            ]);
        }

        $timecard->update([
            'status' => Timecard::STATUS_REJECTED,
            'rejected_by' => $rejector->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $freshTimecard = $timecard->fresh(['user']);
        $freshTimecard->user?->notify(new TimecardRejectedNotification($freshTimecard));

        return $freshTimecard;
    }

    public function resetToDraft(Timecard $timecard): Timecard
    {
        if ($timecard->status !== Timecard::STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'timecard' => 'Only rejected timecards may be reset to draft.',
            ]);
        }

        $timecard->update([
            'status' => Timecard::STATUS_DRAFT,
            'submitted_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        return $timecard->fresh();
    }

    public function delete(Timecard $timecard): void
    {
        if ($timecard->status === Timecard::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'timecard' => 'Approved timecards may not be deleted.',
            ]);
        }

        $timecard->delete();
    }

    /**
     * @param  iterable<int, Timecard>  $timecards
     * @return array{processed:int,skipped:int}
     */
    public function applyBulkAction(iterable $timecards, string $action, User $actor, ?string $rejectionReason = null): array
    {
        $processed = 0;
        $skipped = 0;

        foreach ($timecards as $timecard) {
            try {
                if ($action === 'approve') {
                    $this->approve($timecard, $actor);
                    $processed++;

                    continue;
                }

                if ($action === 'reject') {
                    $this->reject($timecard, $actor, $rejectionReason);
                    $processed++;

                    continue;
                }

                if ($action === 'delete') {
                    $this->delete($timecard);
                    $processed++;

                    continue;
                }

                $skipped++;
            } catch (ValidationException) {
                $skipped++;
            }
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
        ];
    }
}
