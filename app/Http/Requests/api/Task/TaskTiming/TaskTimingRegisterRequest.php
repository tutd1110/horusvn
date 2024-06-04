<?php

namespace App\Http\Requests\api\Task\TaskTiming;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Task Timing Register Request base request class
*/
class TaskTimingRegisterRequest extends ApiBaseRequest
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
        $rules += array('task_id' => ['required', 'integer']);
        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('task_id.required' => __('MSG-E-004'));
        $msgs += array('task_id.integer' => __('MSG-E-005'));
        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'task_id' => 'Công việc',
        ];
    }
}
