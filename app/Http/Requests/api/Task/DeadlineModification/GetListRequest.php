<?php

namespace App\Http\Requests\api\Task\DeadlineModification;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Get List base request class
*/
class GetListRequest extends ApiBaseRequest
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
        $rules += array('user_id' => ['nullable', 'integer']);
        $rules += array('department_id' => ['nullable', 'integer']);
        $rules += array('status' => ['required', 'integer']);
        $rules += array('current_page' => ['required', 'integer']);
        $rules += array('per_page' => ['required', 'integer']);
        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('user_id.integer' => __('MSG-E-005'));
        $msgs += array('department_id.integer' => __('MSG-E-005'));
        $msgs += array('status.required' => __('MSG-E-004'));
        $msgs += array('status.integer' => __('MSG-E-005'));
        $msgs += array('current_page.integer' => __('MSG-E-005'));
        $msgs += array('current_page.required' => __('MSG-E-004'));
        $msgs += array('per_page.integer' => __('MSG-E-005'));
        $msgs += array('per_page.required' => __('MSG-E-004'));
        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'user_id' => 'Employee',
            'status' => 'Status',
            'department_id' => 'Department',
            'current_page' => 'Số trang',
            'per_page' => 'Kích thước trang'
        ];
    }
}
