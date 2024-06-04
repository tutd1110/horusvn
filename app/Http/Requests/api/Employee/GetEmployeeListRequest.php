<?php

namespace App\Http\Requests\api\Employee;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Employee List Request
*/
class GetEmployeeListRequest extends ApiBaseRequest
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
        $rules += array('id' => ['nullable', 'integer']);
        $rules += array('department_id' => ['nullable', 'integer']);
        // $rules += array('user_status' => ['required', 'integer']);
        $rules += array('date_official' => ['nullable', 'date']);
        $rules += array('created_at' => ['nullable', 'date']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('id.integer' => __('MSG-E-005'));
        $msgs += array('department_id.integer' => __('MSG-E-005'));
        $msgs += array('user_status.required' => __('MSG-E-004'));
        $msgs += array('user_status.integer' => __('MSG-E-005'));
        $msgs += array('date_official.date' => __('MSG-E-005'));
        $msgs += array('created_at.date' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'id' => 'Employee',
            'department_id' => 'Department',
            'user_status' => 'Status',
            'date_official' => 'Official working date',
            'created_at' => 'Start working date'
        ];
    }
}
