<?php

namespace App\Domains\Projects\Models;

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Addresses\Models\Address;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Clients\Models\Client;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Projects\Database\Factories\ProjectFactory;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use DomainException;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
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
        'description',
        'status',
        'start_date',
        'end_date',
        'client_id',
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
        return $this->belongsTo(PayRateType::class);
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
        return $this->belongsTo(Client::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function availableClientAddresses(): HasMany
    {
        return $this->hasMany(Address::class, 'client_id', 'client_id');
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
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
        return $this->hasMany(ProjectUserAccess::class);
    }

    public function roleAccesses(): HasMany
    {
        return $this->hasMany(ProjectRoleAccess::class);
    }

    public function costCodes(): HasMany
    {
        return $this->hasMany(CostCode::class);
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class);
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
