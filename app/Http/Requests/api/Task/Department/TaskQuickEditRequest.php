<?php

namespace App\Http\Requests\api\Task\Department;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Task;

/**
 * Task Quick Edit base request class
*/
class TaskQuickEditRequest extends ApiBaseRequest
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
        $rules += array('task_parent' => ['nullable', 'integer']);
        $validate_description = function ($attribute, $value, $fail) {
            if (strpos($value, '<img src=') !== false) {
                $fail(__('MSG-E-005', ['attribute' => 'Thông tin công việc']));
            }
        };
        $rules += array('description' => ['nullable', 'string', $validate_description]);
        $rules += array('user_id' => ['nullable', 'integer', 'exists:users,id',]);
        $rules += array('priority' => ['nullable', 'integer',]);
        $rules += array('sticker_id' => ['nullable', 'integer',]);
        $rules += array('project_ids' => ['nullable', 'array']);
        $rules += array('project_ids.*' => ['nullable', 'integer']);

        /**
        * function for validation
        *   $attribute: Attribute name under validation
        *   $value    : the value of the attribute being validated
        *   $fail     : method to call on failure
        */
        $validate_status = function ($attribute, $value, $fail) {
            //get Input
            $input_data = $this->all();

            if (isset($input_data['status'])) {
                if ($input_data['status'] == 4 && Auth()->user()->position < 1) {
                    $fail(__('MSG-E-019'));
                }
            }
        };
        $rules += array('status' => ['nullable', 'integer', $validate_status]);

        $rules += array('weight' => ['nullable', 'numeric']);
        $rules += array('name' => ['nullable', 'string']);
        $rules += array('progress' => ['nullable', 'integer']);

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
        $msgs += array('project_ids.array' => __('MSG-E-005'));
        $msgs += array('project_ids.*.integer' => __('MSG-E-005'));
        $msgs += array('task_parent.integer' => __('MSG-E-005'));
        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('type.string' => __('MSG-E-005'));
        $msgs += array('description.string' => __('MSG-E-005'));
        $msgs += array('user_id.exists' => __('MSG-E-006'));
        $msgs += array('user_id.integer' => __('MSG-E-005'));
        $msgs += array('sticker_id.integer' => __('MSG-E-005'));
        $msgs += array('priority.integer' => __('MSG-E-005'));
        $msgs += array('status.integer' => __('MSG-E-005'));
        $msgs += array('weight.numeric' => __('MSG-E-005'));
        $msgs += array('progress.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        $attributes = [
            'id' => 'Công việc',
            'project_ids' => 'Dự án',
            'project_ids.*' => 'Dự án',
            'description' => 'Thông tin công việc',
            'task_parent' => 'Công việc cha',
            'name' => 'Tên công việc',
            'type' => 'Loại công việc',
            'user_id' => 'Người thực hiện',
            'sticker_id' => 'Nhãn dán',
            'priority' => 'Mức độ ưu tiên',
            'status' => 'Trạng thái công việc',
            'weight' => 'Trọng lượng',
            'progress' => 'Tiến độ'
        ];

        return $attributes;
    }
}
