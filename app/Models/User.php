<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use App\Traits\OptimisticLockObserverTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    use OptimisticLockObserverTrait;

    protected $connection = 'horusvn_productions';

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    
    //specify primary key
    protected $primaryKey = 'id';
        
    //guard item
    protected $guarded = ['updated_at', 'deleted_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
    
    //field to cast to date
    protected $dates = ['date_official', 'birthday', 'email_verified_at', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function personalInfo()
    {
        return $this->hasOne(UserPersonalInfo::class, 'user_id');
    }
    
    public function timesheets()
    {
        return $this->hasMany(TimesheetDetail::class, 'user_code', 'user_code');
    }

    public function petitions()
    {
        return $this->hasMany(Petition::class, 'user_id');
    }

    public function checkouts()
    {
        return $this->hasMany(UserCheckout::class, 'user_code', 'user_code');
    }

    public function goouts()
    {
        return $this->hasMany(UserGoOut::class, 'user_code', 'user_code');
    }

    public function interviewers()
    {
        return $this->hasMany(Interviewer::class, 'user_id');
    }

    public static function withFilterByGroup()
    {
        $user = Auth()->user();

        if (in_array($user->id, [46, 107])) {
            return static::query();
        }

        $departmentId = $user->department_id;
        if ($departmentId == 12) {
            $compare = '=';
        } else {
            $compare = '!=';
        }

        return static::query()->where('users.department_id', $compare, 12);
    }

    public function orders()
    {
        return $this->hasMany(Order::class,'user_id');
    }

    public function orderUser(){
        return $this->hasOne(OrderUser::class,'user_id');
    }
}
