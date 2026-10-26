<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    protected $fillable = [
        'event_key',
        'category',
        'title',
        'message',
        'audience',
        'reference_type',
        'reference_id',
        'reference_number',
        'read_at',
    ];

    protected $casts = [
        'audience' => 'array',
        'reference_id' => 'integer',
        'read_at' => 'datetime',
    ];
}
