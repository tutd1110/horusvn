<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStoreMenu extends Model
{
    use HasFactory, SoftDeletes;

    // fill item
    protected $fillable = ['store_id', 'name'];

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function store(){
        return $this->belongsToMany(OrderStore::class,'store_id');
    }
}
