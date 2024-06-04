<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\OptimisticLockObserverTrait;
use Carbon\Carbon;

class TaskAssignment extends DynamicConnectionModel
{
    use HasFactory, SoftDeletes;
    use OptimisticLockObserverTrait;

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['start_date', 'created_at', 'updated_at', 'deleted_at'];

    public function getStartDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y/m/d') : null;
    }
}
