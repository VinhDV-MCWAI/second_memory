<?php

namespace App\Models\History\Master;

use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Model;

class AdminMstHist extends Model
{
    protected $table = 'admin_mst_hist';

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'admin_mst_id',
        'email',
        'user_name',
        'password',
        'first_name',
        'last_name',
        'address',
        'phone_number',
        'birth',
        'gender',
        'status',
        'is_active',
        'avatar',
        'email_verified_at',
        'remember_token',
        'action',
        'author_id',
        'created_at',
    ];

    public function adminMst()
    {
        return $this->belongsTo(AdminMst::class, 'admin_mst_id');
    }

    public function author()
    {
        return $this->belongsTo(AdminMst::class, 'author_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'admin_mst_id' => 'integer',
            'email' => 'string',
            'user_name' => 'string',
            'password' => 'string',
            'first_name' => 'string',
            'last_name' => 'string',
            'address' => 'string',
            'phone_number' => 'string',
            'birth' => 'datetime',
            'gender' => 'integer',
            'status' => 'integer',
            'is_active' => 'boolean',
            'avatar' => 'string',
            'email_verified_at' => 'datetime',
            'remember_token' => 'string',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
