<?php

namespace App\Http\Requests\api\Task\TaskAssignment;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Get Task Assignment List base request class
*/
class GetTaskAssignmentList extends ApiBaseRequest
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
            'current_page' => 'Số trang',
            'per_page' => 'Kích thước trang'
        ];
    }
}
