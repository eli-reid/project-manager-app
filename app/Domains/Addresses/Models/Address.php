<?php

namespace App\Domains\Addresses\Models;

use App\Domains\Addresses\Database\Factories\AddressFactory;
use App\Domains\Clients\Models\Client;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperAddress
 */
class Address extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        'client_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }
}
