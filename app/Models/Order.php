<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Userstamps;

class Order extends Model
{
    use HasFactory, SoftDeletes, Userstamps;

    protected $fillable = ['user_id','store_id','items','status','total_amount','note','admin_note'];

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at','created_by','updated_by','deleted_by'];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function store(){
        return $this->belongsTo(OrderStore::class,'store_id');
    }
}
