<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGoOut extends Model
{
    use HasFactory;

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at'];

    //field to cast to date
    protected $dates = ['date', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_code', 'user_code');
    }
}
