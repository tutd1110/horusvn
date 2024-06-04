<?php

namespace App\Http\Requests\api\Review;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Review Edit Request
*/
class ReviewEditRequest extends ApiBaseRequest
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
        $rules = [];

        $rules += array('id' => ['required', 'integer', 'exists:reviews,id',]);
        $rules += array('start_date' => ['nullable', 'date_format:Y/m/d',]);
        $rules += array('next_date' => ['nullable', 'date_format:Y/m/d',]);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('id.required' => __('MSG-E-004'));
        $msgs += array('id.exists' => __('MSG-E-006'));
        $msgs += array('id.integer' => __('MSG-E-005'));
        $msgs += array('start_date.date_format' => __('MSG-E-005'));
        $msgs += array('next_date.date_format' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'id' => 'Đơn đánh giá',
            'start_date' => 'Ngày đánh giá',
            'next_date' => 'Ngày đánh giá tiếp theo',
        ];
    }
}
