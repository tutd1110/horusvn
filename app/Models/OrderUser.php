<?php

namespace App\Models;

use App\Enums\OrderPaidReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

class OrderUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $fillable = ['user_id','alias_name','note','paid_report_status','is_collected_debt','prepaid_amount'];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    protected $casts = [
        'paid_report_status' => OrderPaidReportStatus::class,
        'is_collected_debt'=> 'boolean'
    ];
}
