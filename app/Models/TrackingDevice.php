<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingDevice extends Model
{
    use HasFactory;

    const TYPE = [
        '0' => 'Vào/ra',
        '1' => 'Vào',
        '2' => 'Ra',
    ];

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at'];
}
