<?php

namespace App\Http\Requests\api\Task;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Get tasks base request class
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

        $inputData = $this->all();

        $rules += array('project_id' => ['required', 'array']);
        if (isset($inputData['user_id']) && !empty($inputData['user_id'])
            || isset($inputData['department_id']) && !empty($inputData['department_id'])
            || isset($inputData['status']) && !empty($inputData['status'])
            || isset($inputData['name']) && !empty($inputData['name'])
            || isset($inputData['start_time']) && !empty($inputData['start_time'])
            || isset($inputData['ids']) && !empty($inputData['ids'])
            || isset($inputData['exclude_project_ids']) && !empty($inputData['exclude_project_ids'])
        ) {
            $rules['project_id'] = ['nullable', 'array'];
        }
        $rules += array('user_id' => ['bail', 'nullable', 'array']);
        $rules += array('department_id' => ['bail', 'nullable', 'array']);
        $rules += array('status' => ['nullable', 'array']);
        $rules += array('name' => ['nullable', 'string']);
        $rules += array('ids' => ['nullable', 'array',]);
        $rules += array('ids.*' => ['integer']);

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
        $rules += array('start_time' => ['bail', 'nullable', 'date']);
        if (isset($inputData['start_time']) && !empty($inputData['start_time'])) {
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
        $msgs += array('project_id.required' => __('MSG-E-004'));
        $msgs += array('project_id.array' => __('MSG-E-005'));
        $msgs += array('user_id.array' => __('MSG-E-005'));
        $msgs += array('department_id.array' => __('MSG-E-005'));
        $msgs += array('status.array' => __('MSG-E-005'));
        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('start_time.date' => __('MSG-E-005'));
        $msgs += array('ids.array' => __('MSG-E-005'));
        $msgs += array('ids.*.integer' => __('MSG-E-005'));
        $msgs += array('start_time.date' => __('MSG-E-005'));
        $msgs += array('end_time.required' => __('MSG-E-004'));
        $msgs += array('end_time.date' => __('MSG-E-005'));
        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'project_id' => 'Dự án',
            'start_time' => 'Ngày bắt đầu',
            'end_time' => 'Ngày kết thúc',
            'user_id' => 'Người thực hiện',
            'department_id' => 'Bộ phận',
            'name' => 'Tên công việc',
            'status' => 'Trạng thái',
            'ids' => 'Mã ID công việc',
            'ids.*' => 'Mã ID công việc',
        ];
    }
}
