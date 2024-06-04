<?php

namespace App\Http\Requests\api\Task\Me;

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

        $rules += array('project_ids' => ['nullable', 'array']);
        $rules += array('project_ids.*' => ['nullable', 'integer']);
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

            if (isset($input_data['status'])) {
                if ($input_data['status'] == 4 && Auth()->user()->position < 1) {
                    $fail(__('MSG-E-019'));
                }
            }
        };
        $rules += array('status' => ['nullable', 'integer', $validate_status]);

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
        $rules += array('name' => ['nullable', 'string']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('project_ids.array' => __('MSG-E-005'));
        $msgs += array('project_ids.*.integer' => __('MSG-E-005'));
        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('description.string' => __('MSG-E-005'));
        $msgs += array('sticker_id.integer' => __('MSG-E-005'));
        $msgs += array('status.required' => __('MSG-E-004'));
        $msgs += array('status.integer' => __('MSG-E-005'));
        $msgs += array('start_time.required' => __('MSG-E-004'));
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
        $attributes = [
            'project_ids' => 'Dự án',
            'project_ids.*' => 'Dự án',
            'description' => 'Thông tin công việc',
            'name' => 'Tên công việc',
            'status' => 'Trạng thái công việc',
            'start_time' => 'Ngày bắt đầu',
            'end_time' => 'Ngày kết thúc',
            'sticker_id' => 'Nhãn dán',
        ];

        return $attributes;
    }
}
