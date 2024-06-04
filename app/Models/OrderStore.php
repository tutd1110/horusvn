<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStore extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'location', 'phone', 'type', 'price','max_item'];

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function menu(){
        return $this->hasMany(OrderStoreMenu::class,'store_id');
    }
}
