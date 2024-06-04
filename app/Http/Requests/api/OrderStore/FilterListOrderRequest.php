<?php

namespace App\Http\Requests\api\OrderStore;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class FilterListOrderRequest extends FormRequest
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
            "date_order"=>"nullable|date",
            "department_id"=>"nullable|numeric",
            'user_id'=>"nullable|exists:users,id",
            'user_status'=>"nullable|numeric",
            'store_type'=>"nullable|string",
       ];
    }
}
