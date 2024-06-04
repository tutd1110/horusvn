<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at'];

    //field to cast to date
    protected $dates = ['start_date', 'end_date', 'created_at', 'updated_at'];
}
