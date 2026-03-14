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
