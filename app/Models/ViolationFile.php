<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolationFile extends Model
{
    use HasFactory;

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at'];

    public function violation()
    {
        return $this->belongsTo(Violation::class, 'violation_id');
    }
}
