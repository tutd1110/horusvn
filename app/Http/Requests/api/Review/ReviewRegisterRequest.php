<?php

namespace App\Http\Requests\api\Review;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Review Register Request
*/
class ReviewRegisterRequest extends ApiBaseRequest
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

        $rules += array('employee_id' => ['required', 'integer', 'exists:users,id',]);
        $rules += array('period' => ['required', 'integer', 'min:0', 'max:2',]);
        $rules += array('start_date' => ['required', 'date_format:d/m/Y',]);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('employee_id.required' => __('MSG-E-004'));
        $msgs += array('employee_id.exists' => __('MSG-E-006'));
        $msgs += array('employee_id.integer' => __('MSG-E-005'));
        $msgs += array('period.required' => __('MSG-E-004'));
        $msgs += array('period.integer' => __('MSG-E-005'));
        $msgs += array('period.min' => __('MSG-E-005'));
        $msgs += array('period.max' => __('MSG-E-005'));
        $msgs += array('start_date.required' => __('MSG-E-004'));
        $msgs += array('start_date.date_format' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'employee_id' => 'Nhân viên',
            'period' => 'Loại đánh giá',
            'start_date' => 'Ngày đánh giá'
        ];
    }
}
