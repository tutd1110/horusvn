<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calendar extends Model
{
    use HasFactory, SoftDeletes;

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function interviewers()
    {
        return $this->hasMany(Interviewer::class, 'calendar_id');
    }

    public function calendarEvents()
    {
        return $this->belongsTo(CalendarEvent::class);
    }
    
}
