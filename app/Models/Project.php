<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\OptimisticLockObserverTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Task;

class Project extends DynamicConnectionModel
{
    use HasFactory, SoftDeletes;
    use OptimisticLockObserverTrait;

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['start_date', 'end_date', 'created_at', 'updated_at', 'deleted_at'];

    public static function boot()
    {
        parent::boot();

        // Listen for the 'deleting' event
        static::deleting(function ($project) {
            // Delete all associated tasks and task timings
            $project->tasks()->delete();
        });
    }

    public function getStartDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y') : null;
    }

    public function getEndDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y') : null;
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
