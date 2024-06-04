<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Userstamps;

class OrderSetting extends Model
{
    use HasFactory, SoftDeletes, Userstamps;

    protected $fillable = ['store_id','time_alert', 'bank_qr_code', 'content_alert', 'date','start_time','end_time','is_active'];

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at','created_by','updated_by','deleted_by'];

    protected $casts = ['is_active'=>'boolean'];

    public function store()
    {
        return $this->hasOne(OrderStore::class,'id','store_id');
    }
}
