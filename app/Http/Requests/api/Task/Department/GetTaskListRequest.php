<?php

namespace App\Http\Requests\api\Task\Department;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Get tasks by department base request class
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
        $input_data = $this->all();

        $rules += array('project_id' => ['nullable', 'array',]);
        $rules += array('user_id' => ['bail', 'nullable', 'array', 'exists:users,id',]);
        $rules += array('status' => ['nullable', 'array']);
        $rules += array('name' => ['nullable', 'string']);
        $rules += array('is_pin_show' => ['required', 'integer']);

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
        if (isset($input_data['start_time']) && !empty($input_data['start_time'])) {
            $rules += array('end_time' => ['bail', 'required', 'date', $validate_report_date]);
        }

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
        $msgs += array('project_id.array' => __('MSG-E-005'));
        $msgs += array('user_id.required' => __('MSG-E-004'));
        $msgs += array('user_id.exists' => __('MSG-E-006'));
        // $msgs += array('user_id.integer' => __('MSG-E-005'));
        $msgs += array('user_id.array' => __('MSG-E-005'));
        $msgs += array('status.integer' => __('MSG-E-005'));
        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('start_time.date' => __('MSG-E-005'));
        $msgs += array('end_time.required' => __('MSG-E-004'));
        $msgs += array('end_time.date' => __('MSG-E-005'));
        $msgs += array('is_pin_show.required' => __('MSG-E-004'));
        $msgs += array('is_pin_show.integer' => __('MSG-E-005'));

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
            'project_id' => 'Dự án',
            'start_time' => 'Ngày bắt đầu',
            'end_time' => 'Ngày kết thúc',
            'user_id' => 'Người thực hiện',
            'name' => 'Tên công việc',
            'status' => 'Trạng thái',
            'current_page' => 'Số trang',
            'per_page' => 'Kích thước trang',
            'is_pin_show' => 'Chế độ lọc theo bài ghim'
        ];
    }
}
