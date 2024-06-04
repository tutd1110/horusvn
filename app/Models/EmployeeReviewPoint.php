<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\OptimisticLockObserverTrait;
use Carbon\Carbon;

class EmployeeReviewPoint extends Model
{
    use HasFactory, SoftDeletes;
    use OptimisticLockObserverTrait;

    //guard item
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Get the review that owns the employee review point.
     */
    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
