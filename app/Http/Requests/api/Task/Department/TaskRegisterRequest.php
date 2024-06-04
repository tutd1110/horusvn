<?php

namespace App\Http\Requests\api\Task\Department;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Task;

/**
 * Store Task base request class
*/
class TaskRegisterRequest extends ApiBaseRequest
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

        $rules += array('project_ids' => ['nullable', 'array']);
        $rules += array('project_ids.*' => ['nullable', 'integer']);
        $rules += array('task_parent' => ['nullable', 'integer']);
        $validate_description = function ($attribute, $value, $fail) {
            if (strpos($value, '<img src=') !== false) {
                $fail(__('MSG-E-005', ['attribute' => 'Thông tin công việc']));
            }
        };
        $rules += array('description' => ['nullable', 'string', $validate_description]);

        /**
        * function for validation
        *   $attribute: Attribute name under validation
        *   $value    : the value of the attribute being validated
        *   $fail     : method to call on failure
        */
        $validate_type = function ($attribute, $value, $fail) {

            //list type_of_task
            $type = config('const.type_of_task');
            $element = array_column($type, 'value');
            if (strlen(array_search($value, $element)) == 0) {
                $fail(__('MSG-E-009', ['attribute' => 'Loại công việc']));
            }
        };
        $rules += array('type' => ['nullable', 'string', $validate_type]);
        
        if (isset($input_data['type']) && $input_data['type'] == 'child') {
            $rules += array('user_id' => ['required', 'integer', 'exists:users,id',]);
            $rules += array('priority' => ['nullable', 'integer',]);
            $rules += array('sticker_id' => ['nullable', 'integer',]);

            /**
            * function for validation
            *   $attribute: Attribute name under validation
            *   $value    : the value of the attribute being validated
            *   $fail     : method to call on failure
            */
            $validate_status = function ($attribute, $value, $fail) {
                //get Input
                $input_data = $this->all();

                if ($input_data['status'] == 4 && Auth()->user()->position < 1) {
                    $fail(__('MSG-E-019'));
                }
            };
            $rules += array('status' => ['required', 'integer', $validate_status]);

            $rules += array('start_time' => ['required', 'date']);

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
            $rules += array('end_time' => ['required', 'date', $validate_report_date]);

            $rules += array('weight' => ['nullable', 'numeric']);
        }

        $rules += array('name' => ['nullable', 'string']);

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

        $msgs += array('project_ids.array' => __('MSG-E-005'));
        $msgs += array('project_ids.*.integer' => __('MSG-E-005'));
        $msgs += array('task_parent.integer' => __('MSG-E-005'));
        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('name.required' => __('MSG-E-004'));
        $msgs += array('type.string' => __('MSG-E-005'));
        $msgs += array('type.required' => __('MSG-E-004'));
        $msgs += array('description.string' => __('MSG-E-005'));
        $msgs += array('description.required' => __('MSG-E-004'));

        if (isset($input_data['type']) && $input_data['type'] == 'child') {
            $msgs += array('user_id.required' => __('MSG-E-004'));
            $msgs += array('user_id.exists' => __('MSG-E-006'));
            $msgs += array('user_id.integer' => __('MSG-E-005'));
            $msgs += array('sticker_id.integer' => __('MSG-E-005'));
            $msgs += array('priority.integer' => __('MSG-E-005'));
            $msgs += array('status.required' => __('MSG-E-004'));
            $msgs += array('status.integer' => __('MSG-E-005'));
            $msgs += array('start_time.required' => __('MSG-E-004'));
            $msgs += array('start_time.date' => __('MSG-E-005'));
            $msgs += array('end_time.required' => __('MSG-E-004'));
            $msgs += array('end_time.date' => __('MSG-E-005'));
            $msgs += array('weight.numeric' => __('MSG-E-005'));
        }

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        //get all parameters
        $input_data = $this->all();

        $attributes = [
            'project_ids' => 'Dự án',
            'project_ids.*' => 'Dự án',
            'description' => 'Thông tin công việc',
            'task_parent' => 'Công việc cha',
            'name' => 'Tên công việc',
            'type' => 'Loại công việc',
        ];

        if (isset($input_data['type']) && $input_data['type'] == 'child') {
            $attributes = array_merge($attributes, [
                'user_id' => 'Người thực hiện',
                'sticker_id' => 'Nhãn dán',
                'priority' => 'Mức độ ưu tiên',
                'status' => 'Trạng thái công việc',
                'start_time' => 'Ngày bắt đầu',
                'end_time' => 'Ngày kết thúc',
                'weight' => 'Trọng lượng'
            ]);
        }

        return $attributes;
    }
}
