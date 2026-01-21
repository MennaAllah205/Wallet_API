<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'status',
        'amount',
        'sender_id',
        'receiver_id',
        'service_id',
        'notes'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }


}
