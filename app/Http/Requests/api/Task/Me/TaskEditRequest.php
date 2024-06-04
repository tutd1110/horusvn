<?php

namespace App\Http\Requests\api\Task\Me;

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

        $rules += array('id' => ['required', 'integer']);
        $rules += array('project_ids' => ['required', 'array']);
        $rules += array('project_ids.*' => ['required', 'integer']);
        $validate_description = function ($attribute, $value, $fail) {
            if (strpos($value, '<img src=') !== false) {
                $fail(__('MSG-E-005', ['attribute' => 'Thông tin công việc']));
            }
        };
        $rules += array('description' => ['nullable', 'string', $validate_description]);
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

        $msgs += array('id.required' => __('MSG-E-004'));
        $msgs += array('id.integer' => __('MSG-E-005'));
        $msgs += array('project_ids.array' => __('MSG-E-005'));
        $msgs += array('project_ids.required' => __('MSG-E-004'));
        $msgs += array('project_ids.*.required' => __('MSG-E-004'));
        $msgs += array('project_ids.*.integer' => __('MSG-E-005'));
        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('name.required' => __('MSG-E-004'));
        $msgs += array('description.string' => __('MSG-E-005'));
        $msgs += array('sticker_id.integer' => __('MSG-E-005'));
        $msgs += array('status.required' => __('MSG-E-004'));
        $msgs += array('status.integer' => __('MSG-E-005'));
        $msgs += array('check_updated_at.required' => __('MSG-E-004'));
        $msgs += array('check_updated_at.date' => __('MSG-E-005'));

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
            'name' => 'Tên công việc',
            'check_updated_at' => 'Ngày giờ sửa đổi',
            'sticker_id' => 'Loại công việc',
            'status' => 'Trạng thái công việc'
        ];

        return $attributes;
    }
}
