<?php

namespace App\Http\Requests\api\Task\Deadline;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Task;

/**
 * Task Deadline Quick Edit base request class
*/
class TaskDeadlineQuickEditRequest extends ApiBaseRequest
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
        $rules += array('id' => ['required', 'integer']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('id.required' => __('MSG-E-004'));
        $msgs += array('id.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        $attributes = [
            'id' => 'Deadline',
        ];

        return $attributes;
    }
}
