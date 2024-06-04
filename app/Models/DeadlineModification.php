<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class DeadlineModification extends DynamicConnectionModel
{
    use HasFactory, SoftDeletes;

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : null;
    }

    public function getOriginalDeadlineAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y') : null;
    }

    public function getRequestedDeadlineAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y') : null;
    }
}
