<?php

namespace App\Domains\Projects\Models;

use App\Core\User\Models\User;
use App\Domains\Addresses\Models\Address;
use App\Domains\Clients\Models\Client;
use App\Domains\Projects\Database\Factories\ProjectFactory;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    private const PROJECT_NUMBER_PADDING = 4;

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
        'is_active',
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
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (filled($project->project_number)) {
                return;
            }

            if (! setting_bool('projects.auto_generate_numbers', true)) {
                return;
            }

            $project->project_number = self::nextAutoProjectNumber();
        });
    }

    protected static function nextAutoProjectNumber(): string
    {
        $prefix = (string) setting('projects.number_prefix', 'PRJ-');
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

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
