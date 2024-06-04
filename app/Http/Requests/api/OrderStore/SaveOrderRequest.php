<?php

namespace App\Http\Requests\api\OrderStore;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class SaveOrderRequest extends FormRequest
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
            'store_id'=>'required|exists:order_stores,id',
            'items' => 'required|string|max:255',
            // 'status' => 'required|in:PENDING, COMPLETED',
            'total_amount' => 'required|integer',
            'note' => 'nullable|string|max:255',
            'created_at' => 'nullable|date',
        ];;
    }
}
