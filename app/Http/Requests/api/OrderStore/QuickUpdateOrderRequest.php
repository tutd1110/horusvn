<?php

namespace App\Http\Requests\api\OrderStore;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class QuickUpdateOrderRequest extends FormRequest
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
            'admin_note' => 'nullable|string|max:255',
            'total_amount'=>'nullable|numeric'
       ];
    }

    public function attributes()
    {
        return [
            'admin_note'=>'Ghi chú',
            'total_amount'=>'Giá tiền'
        ];
    }

    public function messages()
    {
        return[
            'total_amount'=>':attribute phải là số'
        ];
    }
}
