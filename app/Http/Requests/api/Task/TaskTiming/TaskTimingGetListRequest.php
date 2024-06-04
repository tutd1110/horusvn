<?php

namespace App\Http\Requests\api\Task\TaskTiming;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Get task timing by task_id base request class
*/
class TaskTimingGetListRequest extends ApiBaseRequest
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
        // $rules += array('task_id' => ['required', 'integer', 'exists:tasks,id',]);
        // $rules += array('task_assignment_id' => ['required', 'integer', 'exists:task_assignments,id',]);
        // $rules += array('field' => ['required', 'string',]);
        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        // $msgs += array('task_id.required' => __('MSG-E-004'));
        // $msgs += array('task_id.exists' => __('MSG-E-006'));
        // $msgs += array('task_id.integer' => __('MSG-E-005'));

        // $msgs += array('task_assignment_id.required' => __('MSG-E-004'));
        // $msgs += array('task_assignment_id.exists' => __('MSG-E-006'));
        // $msgs += array('task_assignment_id.integer' => __('MSG-E-005'));

        // $msgs += array('field.string' => __('MSG-E-005'));
        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            // 'task_id' => 'Công việc',
            // 'task_assignment_id' => 'Công việc gán',
            // 'field' => 'Trường'
        ];
    }
}
