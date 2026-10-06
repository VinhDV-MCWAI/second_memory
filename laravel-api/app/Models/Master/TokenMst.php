<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenMst extends Model
{
    use HasFactory;

    protected $table = 'token_mst';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'token_hash',
        'account_id',
        'device_name',
        'ip_address',
        'expired_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'token_hash' => 'string',
            'account_id' => 'integer',
            'device_name' => 'string',
            'ip_address' => 'string',
            'expired_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
