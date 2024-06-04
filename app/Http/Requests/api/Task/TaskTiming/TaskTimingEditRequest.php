<?php

namespace App\Http\Requests\api\Task\TaskTiming;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\TaskTiming;

/**
 * Task Timing Edit Request base request class
*/
class TaskTimingEditRequest extends ApiBaseRequest
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
        $rules += array('work_date' => ['nullable', 'date']);
        $rules += array('sticker_id' => ['nullable', 'integer']);
        $rules += array('priority' => ['nullable', 'integer']);
        $rules += array('weight' => ['nullable', 'numeric']);
        $rules += array('estimate_time' => ['nullable', 'numeric',]);
        $rules += array('time_spent' => ['nullable', 'numeric',]);
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
        $msgs += array('sticker_id.integer' => __('MSG-E-005'));
        $msgs += array('priority.integer' => __('MSG-E-005'));
        $msgs += array('weight.numeric' => __('MSG-E-005'));
        $msgs += array('work_date.date' => __('MSG-E-005'));
        $msgs += array('estimate_time.numeric' => __('MSG-E-005'));
        $msgs += array('time_spent.numeric' => __('MSG-E-005'));
        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'id' => 'Công việc',
            'work_date' => 'Ngày làm việc',
            'estimate_time' => 'Thời gian dự kiến',
            'time_spent' => 'Thời gian thực tế',
            'sticker_id' => 'Loại trọng số',
            'priority' => 'Mức độ',
            'weight' => 'Trọng số'
        ];
    }
}
