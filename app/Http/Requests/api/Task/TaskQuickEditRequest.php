<?php

namespace App\Http\Requests\api\Task;

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

        //get all parameters
        $input_data = $this->all();

        $rules += array('id' => ['required', 'integer']);
        $rules += array('task_parent' => ['nullable', 'integer']);
        $validate_description = function ($attribute, $value, $fail) {
            if (strpos($value, '<img src=') !== false) {
                $fail(__('MSG-E-005', ['attribute' => 'Thông tin công việc']));
            }
        };
        $rules += array('description' => ['nullable', 'string', $validate_description]);
        $rules['task_parent'] = ['nullable', 'integer'];
        $rules += array('user_id' => ['nullable', 'integer', 'exists:users,id',]);
        $rules += array('priority' => ['nullable', 'integer',]);
        $rules += array('sticker_id' => ['nullable', 'integer',]);
        $rules += array('status' => ['nullable', 'integer']);
        $rules += array('start_time' => ['nullable', 'date']);
        $rules += array('time' => ['nullable', 'numeric']);
        $rules += array('weight' => ['nullable', 'numeric']);
        $rules += array('name' => ['nullable', 'string']);
        $rules += array('progress' => ['nullable', 'integer']);

        $rules += array('project_ids' => ['nullable', 'array']);
        $rules += array('project_ids.*' => ['nullable', 'integer']);

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
        $msgs += array('start_time.date' => __('MSG-E-005'));
        $msgs += array('time.numeric' => __('MSG-E-005'));
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
            'start_time' => 'Ngày bắt đầu',
            'time' => 'Thời lượng',
            'weight' => 'Trọng lượng',
            'progress' => 'Tiến độ'
        ];

        return $attributes;
    }
}
