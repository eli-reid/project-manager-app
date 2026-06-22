<?php

namespace App\Domains\Projects\Models;

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Projects\Contracts\ProjectRef;
use App\Domains\Projects\Database\Factories\ProjectFactory;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Services\ProjectNumber;
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

    protected $fillable = [
        'name',
        'project_number',
        'description',
        'status',
        'start_date',
        'end_date',
        'address_id',
        'project_manager_id',
        'is_active',
        'budget',

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
        ];
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

            $project->project_number = ProjectNumber::getNext();
        });

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

    // Leave-related helpers removed from Project; use Timecards services/plugins instead.

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
