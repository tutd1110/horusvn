<?php

namespace App\Http\Requests\api\Task;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Task;

/**
 * Task Edit base request class
*/
class TaskEditRequest extends ApiBaseRequest
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

        $rules += array('project_ids' => ['required', 'array']);
        $rules += array('project_ids.*' => ['required', 'integer']);
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
        $rules += array('type' => ['required', 'string', $validate_type]);
        
        if ($input_data['type'] == 'child') {
            $rules += array('user_id' => ['nullable', 'integer', 'exists:users,id',]);
            $rules += array('priority' => ['nullable', 'integer',]);
            $rules += array('sticker_id' => ['nullable', 'integer',]);

            /**
            * function for validation
            *   $attribute: Attribute name under validation
            *   $value    : the value of the attribute being validated
            *   $fail     : method to call on failure
            */
            $validate_department_id = function ($attribute, $value, $fail) {

                //list departments
                $departments = config('const.departments_with_job');
                $element = array_column($departments, 'value');
                if (strlen(array_search($value, $element)) == 0) {
                    $fail(__('MSG-E-009', ['attribute' => 'Bộ phận']));
                }
            };
            $rules += array('department_id' => ['nullable', 'integer', $validate_department_id]);
            $rules += array('status' => ['required', 'integer']);
            $rules += array('start_time' => ['nullable', 'date']);
            $rules += array('time' => ['nullable', 'numeric']);
            $rules += array('weight' => ['nullable', 'numeric']);
        }
        $rules += array('name' => ['required', 'string']);
        $rules += array('check_updated_at' => ['required', 'date',]);

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
        $msgs += array('project_ids.array' => __('MSG-E-005'));
        $msgs += array('project_ids.required' => __('MSG-E-004'));
        $msgs += array('project_ids.*.required' => __('MSG-E-004'));
        $msgs += array('project_ids.*.integer' => __('MSG-E-005'));
        $msgs += array('task_parent.integer' => __('MSG-E-005'));
        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('name.required' => __('MSG-E-004'));
        $msgs += array('type.string' => __('MSG-E-005'));
        $msgs += array('type.required' => __('MSG-E-004'));
        $msgs += array('description.string' => __('MSG-E-005'));

        if ($input_data['type'] == 'child') {
            $msgs += array('user_id.exists' => __('MSG-E-006'));
            $msgs += array('user_id.integer' => __('MSG-E-005'));
            $msgs += array('sticker_id.integer' => __('MSG-E-005'));
            $msgs += array('priority.integer' => __('MSG-E-005'));
            $msgs += array('department_id.integer' => __('MSG-E-005'));
            $msgs += array('status.required' => __('MSG-E-004'));
            $msgs += array('status.integer' => __('MSG-E-005'));
            $msgs += array('start_time.date' => __('MSG-E-005'));
            $msgs += array('time.numeric' => __('MSG-E-005'));
            $msgs += array('weight.numeric' => __('MSG-E-005'));
        }

        $msgs += array('check_updated_at.required' => __('MSG-E-004'));
        $msgs += array('check_updated_at.date' => __('MSG-E-005'));

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
            'id' => 'Công việc',
            'project_ids' => 'Dự án',
            'project_ids.*' => 'Dự án',
            'description' => 'Thông tin công việc',
            'task_parent' => 'Công việc cha',
            'name' => 'Tên công việc',
            'type' => 'Loại công việc',
            'check_updated_at' => 'Ngày giờ sửa đổi',
        ];

        if ($input_data['type'] == 'child') {
            $attributes = array_merge($attributes, [
                'user_id' => 'Người thực hiện',
                'sticker_id' => 'Nhãn dán',
                'priority' => 'Mức độ ưu tiên',
                'department_id' => 'Bộ phận',
                'status' => 'Trạng thái công việc',
                'start_time' => 'Ngày bắt đầu',
                'time' => 'Thời lượng',
                'weight' => 'Trọng lượng'
            ]);
        }

        return $attributes;
    }
}
