<?php

namespace App\Http\Requests\api\Task\TaskAssignment;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Store Task Assignment base request class
*/
class TaskAssignmentRegisterRequest extends ApiBaseRequest
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

        $rules += array('task_id' => ['nullable', 'integer']);
        $rules += array('assigned_department_id' => ['nullable', 'integer']);
        $rules += array('assigned_user_id' => ['nullable', 'integer', 'exists:users,id',]);
        $rules += array('description' => ['nullable', 'string']);
        $rules += array('start_date' => ['nullable', 'date']);
        $rules += array('status' => ['nullable', 'integer']);
        $rules += array('tag_test' => ['nullable', 'integer']);
        $validate_note = function ($attribute, $value, $fail) {
            if (strpos($value, '<img src=') !== false) {
                $fail(__('MSG-E-005', ['attribute' => 'Ghi chú']));
            }
        };
        $rules += array('note' => ['nullable', 'string', $validate_note]);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('task_id.integer' => __('MSG-E-005'));
        $msgs += array('assigned_department_id.integer' => __('MSG-E-005'));
        $msgs += array('assigned_user_id.exists' => __('MSG-E-006'));
        $msgs += array('assigned_user_id.integer' => __('MSG-E-005'));
        $msgs += array('description.string' => __('MSG-E-005'));
        $msgs += array('start_date.date' => __('MSG-E-005'));
        $msgs += array('status.integer' => __('MSG-E-005'));
        $msgs += array('tag_test.integer' => __('MSG-E-005'));
        $msgs += array('note.string' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        $attributes = [
            'task_id' => 'Chức năng',
            'assigned_department_id' => 'Bộ phận',
            'assigned_user_id' => 'Nhân viên',
            'description' => 'Nội dung',
            'start_date' => 'Ngày bắt đầu',
            'status' => 'Trạng thái fix',
            'tag_test' => 'Kết quả test',
            'note' => 'Ghi chú'
        ];

        return $attributes;
    }
}
