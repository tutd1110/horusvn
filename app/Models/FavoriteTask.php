<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\OptimisticLockObserverTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Task;

class FavoriteTask extends DynamicConnectionModel
{
    use HasFactory, SoftDeletes;
    use OptimisticLockObserverTrait;

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
