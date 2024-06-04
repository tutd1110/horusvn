<?php

namespace App\Http\Requests\api\Calendar;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Calendar;

/**
 * Store Task base request class
*/
class CalendarRegisterRequest extends ApiBaseRequest
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
        $rules += array('description' => ['nullable', 'string']);
        $rules += array('date' => ['required', 'date']);
        $rules += array('start_time' => ['required']);
        $rules += array('end_time' => ['required']);
        $rules += array('event_id' => ['required']);
        $rules += array('name' => ['required','nullable', 'string']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('name.required' => __('MSG-E-004'));
        $msgs += array('description.string' => __('MSG-E-005'));
        $msgs += array('sticker_id.integer' => __('MSG-E-005'));
        $msgs += array('date.required' => __('MSG-E-004'));
        $msgs += array('start_time.required' => __('MSG-E-004'));
        $msgs += array('end_time.required' => __('MSG-E-004'));
        $msgs += array('event_id.required' => __('MSG-E-004'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        $attributes = [
            // 'description' => 'Bộ phận',+
            'name' => 'Tên sự kiện',
            'start_time' => 'Thời gian bắt đầu',
            'end_time' => 'Thời gian kết thúc',
            'date' => 'Ngày diễn ra',
            'event_id' => 'Sự kiện',
            'description' => 'Nội dung',
        ];

        return $attributes;
    }
}
