<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\OptimisticLockObserverTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Post extends Model
{
    use HasFactory, SoftDeletes;
    use OptimisticLockObserverTrait;

    //specify primary key
    protected $primaryKey = 'id';

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    //field to cast to date
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function postFiles()
    {
        return $this->hasMany(PostFile::class, 'post_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($post) {
            $post->postFiles()->delete();
        });
    }
}
