<?php

namespace App\Http\Requests\api\Task\DeadlineModification;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Task;

/**
 * Deadline Modification Register base request class
*/
class DeadlineModRegisterRequest extends ApiBaseRequest
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

        $rules += array('deadline' => ['required', 'date']);
        $rules += array('reason' => ['required', 'string']);
        $rules += array('task_id' => ['required', 'integer']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('deadline.required' => __('MSG-E-004'));
        $msgs += array('deadline.date' => __('MSG-E-005'));
        $msgs += array('reason.required' => __('MSG-E-004'));
        $msgs += array('reason.string' => __('MSG-E-005'));
        $msgs += array('task_id.required' => __('MSG-E-004'));
        $msgs += array('task_id.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        $attributes = [
            'deadline' => 'Deadline',
            'reason' => 'Reason',
            'task_id' => 'Task',
        ];

        return $attributes;
    }
}
