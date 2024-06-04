<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\OptimisticLockObserverTrait;
use Carbon\Carbon;
use App\Models\Task;

class TaskTiming extends DynamicConnectionModel
{
    use HasFactory, SoftDeletes;
    use OptimisticLockObserverTrait;

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['work_date', 'created_at', 'updated_at', 'deleted_at'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function taskTimingProjects()
    {
        return $this->hasMany(TaskTimingProject::class, 'task_timing_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($taskTiming) {
            $taskTiming->taskTimingProjects()->delete();
        });
    }

    public function getWorkDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y') : null;
    }
}
