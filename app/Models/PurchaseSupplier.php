<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseSupplier extends AiConnectionModel
{
    use HasFactory, SoftDeletes;

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    public function purchases()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function companySuppliers()
    {
        return $this->belongsTo(CompanySupplier::class);
    }
    
}
 