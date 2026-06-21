<?php

namespace App\Domains\Projects\Models;

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Projects\Contracts\ProjectRef;
use App\Domains\Projects\Database\Factories\ProjectFactory;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use DomainException;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperProject
 */
class Project extends Model implements ProjectRef
{
    use HasFactory, HasUlids, SoftDeletes;

    public const BUILT_IN_SICK_PROJECT_NUMBER = 'LEAVE-SICK';

    public const BUILT_IN_VACATION_PROJECT_NUMBER = 'LEAVE-VACATION';

    private const PROJECT_NUMBER_PADDING = 4;

    protected $attributes = [
        'is_prevailing_wage' => false,
    ];

    protected $fillable = [
        'name',
        'project_number',
        'accounting_code',
        'accounting_code_id',
        'description',
        'status',
        'start_date',
        'end_date',
        'address_id',
        'project_manager_id',
        'leave_category',
        'is_active',
        'budget',
        'is_prevailing_wage',
        'wage_determination_id',
        'pay_rate_type_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatusEnum::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'budget' => 'decimal:2',
            'is_prevailing_wage' => 'boolean',
        ];
    }

    public function payRateType(): BelongsTo
    {
        throw new DomainException('Pay rate type access moved to plugin. Use ProjectPluginRegistry.');
    }

    public function accountingCode(): BelongsTo
    {
        throw new DomainException('Accounting access moved to plugin. Use ProjectPluginRegistry.');
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (filled($project->project_number)) {
                return;
            }

            if (! Settings::get('projects.auto_generate_numbers', true)->toBool()) {
                return;
            }

            $project->project_number = self::nextAutoProjectNumber();
        });

        static::updating(function (Project $project): void {
            if (! $project->isBuiltInLeaveProject()) {
                return;
            }

            if ($project->isDirty('project_number')) {
                throw new DomainException('Built-in leave projects cannot change project number.');
            }

            if ($project->isDirty('leave_category')) {
                throw new DomainException('Built-in leave projects cannot change leave category.');
            }
        });

        static::deleting(function (Project $project): void {
            if ($project->isBuiltInLeaveProject()) {
                throw new DomainException('Built-in leave projects cannot be deleted.');
            }
        });
    }

    protected static function nextAutoProjectNumber(): string
    {
        $prefix = Settings::get('projects.number_prefix', 'PRJ-')->toString();
        $highestSequence = self::highestSequenceForPrefix($prefix);
        $nextSequence = $highestSequence + 1;

        $candidate = self::formatProjectNumber($prefix, $nextSequence);

        while (self::query()->where('project_number', $candidate)->exists()) {
            $nextSequence++;
            $candidate = self::formatProjectNumber($prefix, $nextSequence);
        }

        return $candidate;
    }

    protected static function highestSequenceForPrefix(string $prefix): int
    {
        $projectNumbers = self::query()
            ->whereNotNull('project_number')
            ->when($prefix !== '', fn ($query) => $query->where('project_number', 'like', $prefix.'%'))
            ->pluck('project_number');

        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)$/';
        $max = 0;

        foreach ($projectNumbers as $projectNumber) {
            if (! is_string($projectNumber)) {
                continue;
            }

            if (preg_match($pattern, $projectNumber, $matches) !== 1) {
                continue;
            }

            $sequence = (int) ($matches[1] ?? 0);
            if ($sequence > $max) {
                $max = $sequence;
            }
        }

        return $max;
    }

    protected static function formatProjectNumber(string $prefix, int $sequence): string
    {
        return $prefix.str_pad((string) $sequence, self::PROJECT_NUMBER_PADDING, '0', STR_PAD_LEFT);
    }

    public function client(): BelongsTo
    {
        throw new DomainException('Client access moved to plugin. Use ProjectPluginRegistry.');
    }

    public function address(): BelongsTo
    {
        throw new DomainException('Address access moved to plugin. Use ProjectPluginRegistry.');
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function availableClientAddresses(): HasMany
    {
        throw new DomainException('Client addresses moved to plugin. Use ProjectPluginRegistry.');
    }

    public function dailyReports(): HasMany
    {
        throw new DomainException('Daily reports moved to plugin. Use ProjectPluginRegistry.');
    }

    public function isLeaveProject(): bool
    {
        return filled($this->leave_category);
    }

    public function isBuiltInLeaveProject(): bool
    {
        return in_array($this->project_number, [
            self::BUILT_IN_SICK_PROJECT_NUMBER,
            self::BUILT_IN_VACATION_PROJECT_NUMBER,
        ], true);
    }

    public function userAccesses(): HasMany
    {
        throw new DomainException('User accesses moved to plugin. Use ProjectPluginRegistry.');
    }

    public function roleAccesses(): HasMany
    {
        throw new DomainException('Role accesses moved to plugin. Use ProjectPluginRegistry.');
    }

    public function costCodes(): HasMany
    {
        throw new DomainException('Cost codes moved to plugin. Use ProjectPluginRegistry.');
    }

    public function changeOrders(): HasMany
    {
        throw new DomainException('Change orders moved to plugin. Use ProjectPluginRegistry.');
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }

    // ProjectRef implementation
    public function id(): string
    {
        return (string) $this->getKey();
    }

    // ProjectContext responsibilities moved to ProjectContextService
}
