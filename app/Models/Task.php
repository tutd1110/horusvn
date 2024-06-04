<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\OptimisticLockObserverTrait;
use App\Models\TaskTiming;
use App\Models\FavoriteTask;
use App\Models\PinTask;
use App\Models\TaskProject;
use App\Models\TaskFile;

class Task extends DynamicConnectionModel
{
    use HasFactory, SoftDeletes;
    use OptimisticLockObserverTrait;

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['start_time', 'end_time', 'created_at', 'updated_at', 'deleted_at'];

    public function timings()
    {
        return $this->hasMany(TaskTiming::class);
    }

    public function taskProjects()
    {
        return $this->hasMany(TaskProject::class, 'task_id');
    }

    public function files()
    {
        return $this->hasMany(TaskFile::class, 'task_id');
    }

    public function favoriteTasks()
    {
        return $this->hasMany(FavoriteTask::class);
    }

    public function pinTasks()
    {
        return $this->hasMany(PinTask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($task) {
            $task->timings()->delete();

            $task->files()->delete();

            $task->taskProjects()->delete();

            $task->favoriteTasks()->delete();

            $task->pinTasks()->delete();
        });
    }
}
