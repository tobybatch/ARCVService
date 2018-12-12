<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

// hard deletes on these; if only because we'll data-warehouse them at some point.


class VoucherState extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transition',
        'from',
        'user_id',
        'user_type',
        'voucher_id',
        'to',
        'source',
        'state_token_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function token()
    {
        return $this->belongsTo(StateToken::class);
    }
}
