<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Violation extends Model
{
    use HasFactory;

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at'];

    public function getTimeAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('Y/m/d H:i:s') : null;
    }

    public function files()
    {
        return $this->hasMany(ViolationFile::class, 'violation_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($vioaltion) {
            $vioaltion->files()->delete();
        });
    }
}
