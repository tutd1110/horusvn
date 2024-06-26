<?php

namespace App\Http\Requests\api\OrderStore;

use Illuminate\Foundation\Http\FormRequest;

class OrderStatistialRequest extends FormRequest
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
            'user_id'=>'nullable|exists:users,id',
            'department_id'=>'nullable|numeric',
            'start_date'=>'required|date',
            'end_date'=>'required|date',
            'status'=>'nullable|in:NONE,SENT,COMPLETED'
        ];
    }
}
