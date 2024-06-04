<?php

namespace App\Http\Requests\api\Task;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Task;

/**
 * Task Quick Edit base request class
*/
class TaskMultipleEditRequest extends ApiBaseRequest
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

        $rules += array('id' => ['required', 'array']);
        $rules += array('multiple_status' => ['nullable', 'integer']);

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
        $msgs += array('id.array' => __('MSG-E-005'));
        $msgs += array('multiple_status.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        $attributes = [
            'id' => 'Công việc',
            'status' => 'Trạng thái công việc',
        ];

        return $attributes;
    }
}
