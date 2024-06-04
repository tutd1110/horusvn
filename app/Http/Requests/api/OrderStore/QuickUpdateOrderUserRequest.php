<?php

namespace App\Http\Requests\api\OrderStore;

use App\Enums\OrderPaidReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class QuickUpdateOrderUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
       return [
            'user_id' => 'nullable|exists:users,id',
            'alias_name'=>'nullable|string|max:255',
            'note'=>'nullable|string|max:255',
            'paid_report_status'=>['nullable','string',new Enum(OrderPaidReportStatus::class)],
            'prepaid_amount'=>'nullable|numeric'
       ];
    }

    public function messages()
    {
        return [
            'prepaid_amount.numeric' => 'Số tiền trả trước phải là số'
        ];
    }
}
