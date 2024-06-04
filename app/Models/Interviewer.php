<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interviewer extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function users()
    {
        return $this->belongsTo(User::class);
    }

    public function calendars()
    {
        return $this->belongsTo(Calendar::class);
    }
}

