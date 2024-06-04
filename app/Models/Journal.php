<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Journal extends Model
{
    use HasFactory, SoftDeletes;

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function files()
    {
        return $this->hasMany(JournalFile::class, 'journal_id');
    }

    public function departments()
    {
        return $this->hasMany(JournalDepartment::class, 'journal_id');
    }

    public function games()
    {
        return $this->hasMany(JournalGame::class, 'journal_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($journal) {
            $journal->files()->delete();
        });
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : null;
    }
}
