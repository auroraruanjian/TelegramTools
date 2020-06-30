<?php

namespace Common\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUsers extends Model
{
    //
    protected $table='telegram_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'phone',
        'photo',
        'extra',
    ];
}
