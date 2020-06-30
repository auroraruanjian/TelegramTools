<?php

namespace Common\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramAccounts extends Model
{
    //
    protected $table='telegram_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'phone',
        'madeline_file',
    ];
}
