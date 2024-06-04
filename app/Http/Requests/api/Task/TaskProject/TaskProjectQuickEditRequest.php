<?php

namespace App\Http\Requests\api\Task\TaskProject;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Task;

/**
 * Task Project Quick Edit base request class
*/
class TaskProjectQuickEditRequest extends ApiBaseRequest
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

        //get all parameters
        $input_data = $this->all();

        $rules += array('id' => ['required', 'integer']);
        $rules += array('weight' => ['nullable', 'numeric']);
        $rules += array('project_id' => ['nullable', 'integer']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        //get all parameters
        $input_data = $this->all();

        $msgs += array('id.required' => __('MSG-E-004'));
        $msgs += array('id.integer' => __('MSG-E-005'));
        $msgs += array('project_id.integer' => __('MSG-E-005'));
        $msgs += array('weight.numeric' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        $attributes = [
            'id' => 'Công việc',
            'project_id' => 'Dự án',
            'weight' => 'Trọng lượng'
        ];

        return $attributes;
    }
}
