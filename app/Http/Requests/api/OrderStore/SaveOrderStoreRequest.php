<?php

namespace App\Http\Requests\api\OrderStore;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class SaveOrderStoreRequest extends FormRequest
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
        $rules = array();
        switch ($request->method()) {
            case 'POST':
                $rules = [
                    'name' => 'required|string|max:255',
                    'location' => 'nullable|string|max:255',
                    'phone' => 'nullable|string',
                    'type' => 'required|in:RICE,DYNAMIC',
                    'price' => 'nullable|integer',
                    'menu'=>'nullable|array',
                    'max_item'=>'required|integer'
                ];
                break;
            default: // PUT
                $rules = [
                    'name' => 'required|string|max:255',
                    'location' => 'nullable|string|max:255',
                    'phone' => 'nullable|string',
                    'type' => 'required|in:RICE,DYNAMIC',
                    'price' => 'nullable|integer',
                    'menu'=>'nullable|array',
                    'max_item'=>'required|integer'
                ];
                break;
        }
        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => __('MSG-E-004'),
            'name.string' => __('MSG-E-005'),
            'name.max'=> ':attribute tối đa là 255 kí tự',
            'location.string' => __('MSG-E-005'),
            'location.max'=> ':attribute tối đa là 255 kí tự',
            'phone.string' => __('MSG-E-005'),
            'price.integer' => __('MSG-E-005'),
            'type.required'=>__('MSG-E-004'),
            'type.in'=>':attribute không hợp lệ',
            'max_item.integer' => __('MSG-E-005'),
            'menu.array'=>':attribute phải là kiểu mảng',
            'max_item.required'=>__('MSG-E-004'),
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Tên cửa hàng', 
            'location'=>'Địa chỉ cửa hàng',
            'phone'=>'Số điện thoại',
            'price'=>'Giá tiền',
            'type'=>'Loại cửa hàng',
            'max_item'=>'Số món tối đa'
        ];
    }
}
