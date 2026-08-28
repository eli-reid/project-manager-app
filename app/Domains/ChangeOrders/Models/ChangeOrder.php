<?php

namespace App\Domains\ChangeOrders\Models;

use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Database\Factories\ChangeOrderFactory;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperChangeOrder
 */
class ChangeOrder extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_IMPLEMENTED = 'implemented';

    public const STATUS_CANCELLED = 'cancelled';

    public const DOCUMENT_ROLE_REFERENCE = 'reference';

    public const DOCUMENT_ROLE_SUPPORTING = 'supporting';

    public const DOCUMENT_STATUS_ACTIVE = 'active';

    public const DOCUMENT_STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'labor_amount',
        'materials_amount',
        'total_amount',
        'requested_by_id',
        'approved_by_id',
        'rejected_by_id',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'implemented_at',
        'cancelled_at',
        'client_approved_at',
        'client_approval_reference',
        'rejection_reason',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labor_amount' => 'decimal:2',
            'materials_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'implemented_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'client_approved_at' => 'datetime',
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'change_order_documents', 'change_order_id', 'document_id')
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

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_IMPLEMENTED,
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
            self::DOCUMENT_ROLE_SUPPORTING,
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

    public function recalculateTotal(): self
    {
        $this->total_amount = round((float) $this->labor_amount + (float) $this->materials_amount, 2);

        return $this;
    }

    protected static function newFactory(): ChangeOrderFactory
    {
        return ChangeOrderFactory::new();
    }
}
