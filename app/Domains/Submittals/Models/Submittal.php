<?php

namespace App\Domains\Submittals\Models;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Database\Factories\SubmittalFactory;
use App\Domains\Submittals\Enums\SubmittalStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperSubmittal
 */
class Submittal extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_ARCHITECT_REVIEW = 'architect_review';

    public const STATUS_OWNER_REVIEW = 'owner_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVISE = 'revise';

    public const STATUS_DISTRIBUTED = 'distributed';

    public const STATUS_CANCELLED = 'cancelled';

    public const DOCUMENT_ROLE_REFERENCE = 'reference';

    public const DOCUMENT_ROLE_PRIMARY = 'primary';

    public const DOCUMENT_ROLE_SUPPORTING = 'supporting';

    public const DOCUMENT_ROLE_COMPLIANCE = 'compliance';

    public const DOCUMENT_STATUS_ACTIVE = 'active';

    public const DOCUMENT_STATUS_DRAFT = 'draft';

    public const DOCUMENT_STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'project_id',
        'type',
        'spec_reference',
        'vendor',
        'need_by_date',
        'status',
        'submitted_by_id',
        'current_reviewer_id',
        'rejection_reason',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'distributed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubmittalStatusEnum::class,
            'need_by_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'distributed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function currentReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_reviewer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubmittalItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(SubmittalApproval::class);
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'submittal_documents', 'submittal_id', 'document_id')
            ->withPivot(['document_role', 'document_status', 'revision', 'discipline'])
            ->withTimestamps();
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
    public static function allowedDocumentRoles(): array
    {
        return [
            self::DOCUMENT_ROLE_REFERENCE,
            self::DOCUMENT_ROLE_PRIMARY,
            self::DOCUMENT_ROLE_SUPPORTING,
            self::DOCUMENT_ROLE_COMPLIANCE,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allowedDocumentStatuses(): array
    {
        return [
            self::DOCUMENT_STATUS_ACTIVE,
            self::DOCUMENT_STATUS_DRAFT,
            self::DOCUMENT_STATUS_SUPERSEDED,
        ];
    }

    public function isEditable(): bool
    {
        return in_array($this->statusValue(), [self::STATUS_DRAFT, self::STATUS_REJECTED, self::STATUS_REVISE], true);
    }

    public function statusValue(): string
    {
        $status = $this->status;

        if ($status instanceof SubmittalStatusEnum) {
            return $status->value;
        }

        return (string) $status;
    }

    public function statusLabel(): string
    {
        $status = $this->status;

        if ($status instanceof SubmittalStatusEnum) {
            return $status->label();
        }

        return ucfirst((string) $status);
    }

    protected static function newFactory(): SubmittalFactory
    {
        return SubmittalFactory::new();
    }
}
