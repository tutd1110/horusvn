<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LogRoute extends Model
{
    use HasFactory;

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : null;
    }
}
