<?php

namespace MarceloCorrea\src\Models;

use Illuminate\Database\Eloquent\Model;

class LaravelLog extends Model
{
    protected $fillable = [
        'uid', 'type', 'message', 'user_id', 'application_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $uid = uniqid();
            while (self::where('uid', '=', $uid)->count() > 0) {
                $uid = uniqid();
            }
            $item->uid = $uid;
        });
    }
}
