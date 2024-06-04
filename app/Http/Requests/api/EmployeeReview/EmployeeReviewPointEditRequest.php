<?php

namespace App\Http\Requests\api\EmployeeReview;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Employee Review Point Edit Request
*/
class EmployeeReviewPointEditRequest extends ApiBaseRequest
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

        $rules += array('id' => ['required', 'integer', 'exists:employee_review_points,id',]);

        $rules += array('employee_point' => ['nullable', 'integer', 'min:1', 'max:5']);
        $rules += array('leader_point' => ['nullable', 'integer', 'min:1', 'max:5']);
        $rules += array('pm_point' => ['nullable', 'integer', 'min:1', 'max:5']);

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
        $msgs += array('employee_point.integer' => __('MSG-E-005'));
        $msgs += array('employee_point.max' => __('MSG-E-005'));
        $msgs += array('leader_point.integer' => __('MSG-E-005'));
        $msgs += array('leader_point.max' => __('MSG-E-005'));
        $msgs += array('pm_point.integer' => __('MSG-E-005'));
        $msgs += array('pm_point.max' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'id' => 'Dữ liệu',
            'employee_point' => 'Điểm',
            'leader_point' => 'Điểm',
            'pm_point' => 'Điểm',
        ];
    }
}
