<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskProject extends DynamicConnectionModel
{
    use HasFactory, SoftDeletes;

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    // protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $guarded = ['id', 'deleted_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
