<?php

namespace App\Domains\RFIs\Models;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Database\Factories\RFIFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperRFI
 */
class RFI extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ANSWERED = 'answered';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const DOCUMENT_ROLE_REFERENCE = 'reference';

    public const DOCUMENT_ROLE_RESPONSE = 'response';

    public const DOCUMENT_STATUS_ACTIVE = 'active';

    public const DOCUMENT_STATUS_SUPERSEDED = 'superseded';

    protected $table = 'rfis';

    protected $fillable = [
        'project_id',
        'number',
        'subject',
        'body',
        'status',
        'requested_by_id',
        'answered_by_id',
        'answer',
        'due_date',
        'answered_at',
        'cost_impact',
        'schedule_impact_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'answered_at' => 'datetime',
            'cost_impact' => 'decimal:2',
            'schedule_impact_days' => 'integer',
            'number' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by_id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'rfi_documents', 'rfi_id', 'document_id')
            ->withTimestamps()
            ->withPivot(['document_role', 'document_status', 'revision', 'discipline']);
    }

    public function activeDocuments(): BelongsToMany
    {
        return $this->documents()->wherePivot('document_status', self::DOCUMENT_STATUS_ACTIVE);
    }

    public function documentsByRole(string $documentRole): BelongsToMany
    {
        return $this->documents()->wherePivot('document_role', $documentRole);
    }

    public function emailDeliveries(): HasMany
    {
        return $this->hasMany(RFIEmailDelivery::class, 'rfi_id');
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_ANSWERED,
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allowedDocumentRoles(): array
    {
        return [
            self::DOCUMENT_ROLE_REFERENCE,
            self::DOCUMENT_ROLE_RESPONSE,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allowedDocumentStatuses(): array
    {
        return [
            self::DOCUMENT_STATUS_ACTIVE,
            self::DOCUMENT_STATUS_SUPERSEDED,
        ];
    }

    protected static function newFactory(): RFIFactory
    {
        return RFIFactory::new();
    }
}
