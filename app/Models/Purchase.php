<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Purchase extends AiConnectionModel
{
    use HasFactory,SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function purchaseSuppliers()
    {
        return $this->hasMany(PurchaseSupplier::class, 'purchase_id');
    }

}
