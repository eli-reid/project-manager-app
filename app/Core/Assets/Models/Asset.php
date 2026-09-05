<?php

<<<<<<< HEAD
declare(strict_types=1);

namespace App\Core\Assets\Models;

use App\Core\Assets\Database\Factories\AssetFactory;
use App\Core\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A stored binary blob. Has no owner of its own; ownership is expressed through
 * `asset_references`, which is also the unit of authorization.
 *
=======
namespace App\Core\Assets\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
>>>>>>> production
 * @mixin IdeHelperAsset
 */
class Asset extends Model
{
<<<<<<< HEAD
    use HasFactory, HasUlids;

    protected $fillable = [
=======
    use HasUlids;

    protected $table = 'assets';

    protected $fillable = [
        'title',
>>>>>>> production
        'original_name',
        'mime_type',
        'size_bytes',
        'storage_disk',
        'storage_path',
        'folder_path',
<<<<<<< HEAD
        'content_hash',
        'created_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function references(): HasMany
    {
        return $this->hasMany(AssetReference::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    protected static function newFactory(): AssetFactory
    {
        return AssetFactory::new();
    }
=======
        'created_by_id',
    ];

    public $incrementing = false;

    protected $keyType = 'string';
>>>>>>> production
}
