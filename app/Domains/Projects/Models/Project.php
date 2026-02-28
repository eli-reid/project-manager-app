<?php

namespace App\Domains\Projects\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\ProjectLaborCostService;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Enums\ProjectRoleEnum;
use App\Policies\ProjectPolicy;



class Project extends Model
{
    use HasFactory, SoftDeletes, HasUlids;

    const SICK_TIME_ID = 999998;
    const VACATION_TIME_ID = 999999;
    const BUDGET_WARNING_THRESHOLD = 85;
    const BUDGET_DANGER_THRESHOLD = 95;
    const STATUSENUM = ProjectStatusEnum::class;


    // --- Fillable attributes ---
    protected $fillable = [
        'name',
        'description',
        'project_number',
        'po_number',
        'status',
        'burden_rate',
        'start_date',
        'end_date',
        'estimated_completion_date',
        'actual_completion_date',
        'bid_amount',
        'collected_amount',
        'markup_percentage',
        'labor_budget',
        'materials_budget',
        'project_manager_id',
        'foreman_id',
        'pay_rate_type_id',
        'client_id',
        'address_id',
        'notes',
        'board_category_id',
        'board_order',
    ];


    // --- Attribute casting ---
    protected $casts = [
        'status' => ProjectStatusEnum::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'estimated_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'bid_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'labor_budget' => 'decimal:2',
        'materials_budget' => 'decimal:2',
        'burden_rate' => 'decimal:4',
        'archived_at' => 'datetime',
    ];

    protected $hidden = [
        'resource_id',
    ];

    // Note: Appended attributes removed to prevent N+1 queries on list views
    // These expensive calculations should only be computed when explicitly needed
    // Use: $project->append(['progress', 'total_cost']) or access directly: $project->progress
    protected $appends = [
        // 'progress',
        // 'status_color',
        // 'total_labor_cost',
        // 'total_materials_cost',
        // 'total_cost',
        // 'current_budget',
        // 'budget_used_percentage',
        // 'outstanding_balance',
        // 'expected_profit',
        // 'expected_profit_percentage',
        // 'change_orders_total',
        // 'burdened_labor_cost',
        // 'burden_amount',
        // 'total_burdened_cost',
        // 'burden_rate_percentage'
    ];

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function foreman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'foreman_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function boardCategory(): BelongsTo
    {
        return $this->belongsTo(ProjectBoardCategory::class, 'board_category_id');
    }

    public function payRateType(): BelongsTo
    {
        return $this->belongsTo(PayRateType::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function publicDocuments(): HasMany
    {
        return $this->hasMany(Document::class)->where('is_public', true);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_workers')
            ->withPivot('role', 'start_date', 'end_date', 'hourly_rate')
            ->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'resource_roles', 'project_id', 'role_id');
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class);
    }

    public function projectDocuments(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function stockOrders(): HasMany
    {
        return $this->hasMany(StockOrder::class);
    }

    public function timecards()
    {
        return $this->hasMany(Timecard::class);
    }

    public function allTimecards()
    {
        return Timecard::where(function ($query) {
            $query->where('project_id', $this->id)
                ->orWhere('address_id', $this->address_id);
        });
    }

    public function timecardEntries()
    {
        return $this->hasMany(TimecardEntry::class);
    }

    public function allTimecardEntries()
    {
        return TimecardEntry::where(function ($query) {
            $query->where('project_id', $this->id);
        });
    }

    public function laborCosts()
    {
        return $this->hasMany(ProjectLaborCost::class);
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    // =========================
    //   SCOPES
    // =========================

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeExcludeTimecardOnly($query)
    {
        // Exclude sick time and vacation time projects by name pattern since we use ULIDs
        return $query->where(function($q) {
            $q->whereNotIn('name', ['Sick Time', 'Vacation Time', 'Vacation', 'Sick'])
              ->orWhereNull('name');
        });
    }

    // =========================
    //   ACCESSORS & MUTATORS
    // =========================

    public function getBudgetStatusColorAttribute()
    {
        $percentUsed = $this->budget_used_percentage;
        if ($percentUsed >= self::BUDGET_DANGER_THRESHOLD) {
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        } elseif ($percentUsed >= self::BUDGET_WARNING_THRESHOLD) {
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        } else {
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        }
    }

    public function getProgressAttribute()
    {
        // If the project has tasks, calculate progress based on completed tasks
        if ($this->tasks_count > 0) {
            return (int) (($this->completed_tasks_count / $this->tasks_count) * 100);
        }

        // If we have milestones, calculate based on milestone completion
        if (isset($this->relations['milestones']) && $this->relations['milestones']->count() > 0) {
            $totalMilestones = $this->relations['milestones']->count();
            $completedMilestones = $this->relations['milestones']->where('status', 'completed')->count();
            return (int) (($completedMilestones / $totalMilestones) * 100);
        }

        // If we're tracking progress manually through a progress field, return that
        if (isset($this->attributes['progress'])) {
            return (int)$this->attributes['progress'];
        }

        // Default to 0% if we can't calculate progress
        return 0;
    }

    public function getTotalLaborCostAttribute()
    {
        $laborCostService = app(ProjectLaborCostService::class);
        $laborCosts = $laborCostService->calculateProjectLaborCosts($this);
        return round($laborCosts['base_cost'] ?? 0, 2);
    }

    public function materialInvoices(): HasMany
    {
        return $this->hasMany(MaterialInvoice::class);
    }

    public function adminLaborEntries(): HasMany
    {
        return $this->hasMany(AdminLaborEntry::class);
    }

    public function getTotalMaterialsCostAttribute(): float
    {
        return $this->materialInvoices()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount') ?? 0;
    }

    public function getPaidMaterialsCostAttribute(): float
    {
        return $this->materialInvoices()
            ->where('status', 'paid')
            ->sum('total_amount') ?? 0;
    }

    public function getPendingMaterialsCostAttribute(): float
    {
        return $this->materialInvoices()
            ->where('status', 'pending')
            ->sum('total_amount') ?? 0;
    }

    public function getTotalCostAttribute()
    {
        return $this->total_labor_cost + $this->total_materials_cost;
    }

    public function getChangeOrdersTotalAttribute()
    {
        try {
            return $this->changeOrders()
                ->whereIn('status', [
                    ChangeOrder::STATUS_APPROVED,
                    ChangeOrder::STATUS_IMPLEMENTED
                ])
                ->sum('amount') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getCurrentBudgetAttribute()
    {
        return ($this->bid_amount ?? 0) + $this->change_orders_total;
    }

    public function getBudgetUsedPercentageAttribute()
    {
        if (!$this->current_budget || $this->current_budget == 0) {
            return 0;
        }
        return min(100, round(($this->total_cost / $this->current_budget) * 100, 2));
    }

    public function getOutstandingBalanceAttribute()
    {
        $totalCostWithMarkup = $this->total_cost;
        if ($this->markup_percentage) {
            $markup = $this->total_cost * ($this->markup_percentage / 100);
            $totalCostWithMarkup += $markup;
        }
        return round($totalCostWithMarkup - ($this->collected_amount ?? 0), 2);
    }

    public function getExpectedProfitAttribute()
    {
        if (!$this->bid_amount) {
            return 0;
        }
        return round($this->current_budget - $this->total_cost, 2);
    }

    public function getExpectedProfitPercentageAttribute()
    {
        if (!$this->current_budget || $this->current_budget == 0) {
            return 0;
        }
        return round(($this->expected_profit / $this->current_budget) * 100, 2);
    }

    public function getEffectiveBurdenRate(): float
    {
        if (!is_null($this->burden_rate)) {
            return (float) $this->burden_rate;
        }
        return (float) setting('default_burden_rate', 0.0);
    }

    public function getBurdenedLaborCostAttribute(): float
    {
        $laborCostService = app(ProjectLaborCostService::class);
        $laborCosts = $laborCostService->calculateProjectLaborCosts($this);
        return round($laborCosts['total'] ?? 0, 2);
    }

    public function getBurdenAmountAttribute(): float
    {
        $laborCostService = app(ProjectLaborCostService::class);
        $laborCosts = $laborCostService->calculateProjectLaborCosts($this);
        return round($laborCosts['burden_cost'] ?? 0, 2);
    }

    public function getTotalBurdenedCostAttribute(): float
    {
        return round($this->burdened_labor_cost + $this->total_materials_cost, 2);
    }

    public function getBurdenRatePercentageAttribute(): string
    {
        return number_format($this->getEffectiveBurdenRate() * 100, 2) . '%';
    }

    public function getStatusColorAttribute()
    {
        return $this->status?->color() ?? 'bg-gray-100 text-gray-800';
    }

    public function getFormattedArchiveSize(): string
    {
        if (!$this->archive_size) {
            return 'N/A';
        }
        $bytes = $this->archive_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get non-financial attributes for users without financial permissions
     */
    public function getNonFinancialAttributes(): array
    {
        $nonFinancialFields = [
            'id', 'name', 'description', 'project_number', 'status', 
            'start_date', 'end_date', 'estimated_completion_date', 
            'actual_completion_date', 'project_manager_id', 'foreman_id',
            'client_id', 'address_id', 'notes', 'created_at', 'updated_at',
            'progress', 'status_color'
        ];
        
        return array_intersect_key($this->toArray(), array_flip($nonFinancialFields));
    }

    /**
     * Check if user can view financial data for this project
     * Enhanced to use ProjectRoleEnum
     */
    public function userCanViewFinancials($user): bool
    {
        // Use existing policy check first
        if ($user->can('viewFinancials', $this)) {
            return true;
        }

        // Additional role-based check
        $userRole = $this->getUserRole($user);
        return $userRole && $userRole->canViewFinancials();
    }

    /**
     * Get all users assigned to this project with their roles
     */
    public function getAssignedUsersWithRoles(): \Illuminate\Support\Collection
    {
        $assignedUsers = collect();

        // Add project manager
        if ($this->projectManager) {
            $assignedUsers->push([
                'user' => $this->projectManager,
                'role' => ProjectRoleEnum::PROJECT_MANAGER,
                'is_primary' => true,
                'start_date' => null,
                'end_date' => null,
                'hourly_rate' => null,
            ]);
        }

        // Add primary foreman
        if ($this->foreman && $this->foreman_id !== $this->project_manager_id) {
            $assignedUsers->push([
                'user' => $this->foreman,
                'role' => ProjectRoleEnum::FOREMAN,
                'is_primary' => true,
                'start_date' => null,
                'end_date' => null,
                'hourly_rate' => null,
            ]);
        }

        // Add workers from pivot table
        $this->workers->each(function ($worker) use ($assignedUsers) {
            $role = ProjectRoleEnum::tryFrom($worker->pivot->role) ?? ProjectRoleEnum::WORKER;
            
            $assignedUsers->push([
                'user' => $worker,
                'role' => $role,
                'is_primary' => false,
                'start_date' => $worker->pivot->start_date,
                'end_date' => $worker->pivot->end_date,
                'hourly_rate' => $worker->pivot->hourly_rate,
            ]);
        });

        return $assignedUsers;
    }

    /**
     * Assign a user to this project with a specific role
     */
    public function assignUser(User $user, ProjectRoleEnum $role, array $attributes = []): void
    {
        $this->workers()->syncWithoutDetaching([
            $user->id => array_merge([
                'role' => $role->value,
                'start_date' => now()->toDateString(),
            ], $attributes)
        ]);
    }

    /**
     * Remove a user from this project
     */
    public function removeUser(User $user): void
    {
        $this->workers()->detach($user->id);
    }

    /**
     * Update a user's role on this project
     */
    public function updateUserRole(User $user, ProjectRoleEnum $role, array $attributes = []): void
    {
        $this->workers()->updateExistingPivot($user->id, array_merge([
            'role' => $role->value,
        ], $attributes));
    }

    public function isOverdue(): bool
    {
        return !$this->actual_completion_date
            && $this->end_date
            && $this->end_date->isPast();
    }

    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    public function hasRelatedRecords(): bool
    {
        return $this->stockOrders()->exists() ||
            $this->workers()->exists() ||
            $this->roles()->exists() ||
            $this->timecardEntries()->exists() ||
            $this->changeOrders()->exists();
    }

    // =========================
    //   SCOPES & QUERIES
    // =========================

    public static function getModelPermissions(): array
    {
        return app(ProjectPolicy::class)::getPermissions();
    }

    public static function getStatuses(): array
    {
        // Use ProjectStatusEnum for status keys and labels
        $statuses = [];
        foreach (ProjectStatusEnum::cases() as $case) {
            $statuses[$case->value] = $case->label();
        }
        return $statuses;
    }

    public static function isLeaveType($projectId)
    {
        return in_array($projectId, [self::SICK_TIME_ID, self::VACATION_TIME_ID]);
    }

    public static function getAllWithLeaveTypes()
    {
        return self::orderBy('name')->get();
    }

    public static function getProjectsWithLeaveTypes()
    {
        return self::orderBy('name')->get();
    }

    public static function getActiveProjectsForDropdown()
    {
        return self::active()->orderBy('name')->get(['id', 'name', 'project_number']);
    }

    // =========================
    //   ELOQUENT BOOT
    // =========================

    protected static function booted()
    {
        static::addGlobalScope('withTaskCounts', function ($query) {
            $query->withCount([
                'tasks',
                'tasks as completed_tasks_count' => function ($query) {
                    $query->where('status', 'completed');
                }
            ]);
        });
    }
}
