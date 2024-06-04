<?php

namespace App\Http\Requests\api\Task\Me;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Get my tasks base request class
*/
class GetTaskListRequest extends ApiBaseRequest
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

        $rules += array('project_id' => ['nullable', 'array',]);
        $rules += array('status' => ['nullable', 'array']);
        $rules += array('name' => ['nullable', 'string']);
        $rules += array('id_suggest' => ['nullable', 'integer']);
        $rules += array('is_pin_show' => ['required', 'integer']);

        /**
        * function for validation
        *   $attribute: Attribute name under validation
        *   $value    : the value of the attribute being validated
        *   $fail     : method to call on failure
        */
        $validate_report_date = function ($attribute, $value, $fail) {
            //get Input
            $input_data = $this->all();
            if (!empty($input_data['start_time']) && !empty($input_data['end_time'])) {
                if ($input_data['start_time'] > $input_data['end_time']) {
                    $fail(__('MSG-E-010', ['attribute' => 'Ngày kết thúc', 'attribute2' => 'Ngày bắt đầu']));
                }
            }
        };

        //get Input
        $input_data = $this->all();

        $rules += array('start_time' => ['bail', 'nullable', 'date']);
        if (isset($input_data['start_time']) && !empty($input_data['start_time'])) {
            $rules += array('end_time' => ['bail', 'required', 'date', $validate_report_date]);
        }
        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('project_id.array' => __('MSG-E-005'));
        $msgs += array('status.integer' => __('MSG-E-005'));
        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('id_suggest.integer' => __('MSG-E-005'));
        $msgs += array('is_pin_show.required' => __('MSG-E-004'));
        $msgs += array('is_pin_show.integer' => __('MSG-E-005'));
        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'project_id' => 'Dự án',
            'name' => 'Tên công việc',
            'status' => 'Trạng thái',
            'id_suggest' => 'Mã công việc gợi ý',
            'is_pin_show' => 'Chế độ lọc theo bài ghim'
        ];
    }
}
