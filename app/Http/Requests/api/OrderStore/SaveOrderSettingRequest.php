<?php

namespace App\Http\Requests\api\OrderStore;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class SaveOrderSettingRequest extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'store_id' => 'required|exists:order_stores,id',
            'content_alert' => 'nullable|string|max:255',
            'start_time'=>'required', // current time
            // 'end_time'=>'nullable', // not limit time
            'is_active' => 'nullable|boolean'
        ];
    }

    public function messages()
    {
        return [
            'store_id.required' => __('MSG-E-004'),
            'store_id.exists'=>':attribute không tồn tại',
            'time_alert.date' => __('MSG-E-005'),
            'content_alert.string' => __('MSG-E-005'),
            'is_active.boolean' => __('MSG-E-005'),
            'start_time.date'=>__('MSG-E-005'),
            'end_time.date'=>__('MSG-E-005'),
        ];
    }

    public function attributes()
    {
        return [
            'store_id' => 'Cửa hàng',
            'time_alert' => 'Thời gian thông báo',
            'content_alert' => 'Nội dung thông báo',
            'is_active' => 'Hoạt động',
            'start_time'=>'Thời gian bắt đầu đặt',
            'end_time'=>'Thời gian hết hạn đặt'
        ];
    }
}
