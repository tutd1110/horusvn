<?php

namespace App\Http\Requests\api\Holiday;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Holiday Register Request
*/
class HolidayRegisterRequest extends ApiBaseRequest
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

        /**
        * function for validation
        *   $attribute: Attribute name under validation
        *   $value    : the value of the attribute being validated
        *   $fail     : method to call on failure
        */
        $validate_report_date = function ($attribute, $value, $fail) {
            //get Input
            $input_data = $this->all();
            if (!empty($input_data['start_date']) && !empty($input_data['end_date'])) {
                if ($input_data['start_date'] > $input_data['end_date']) {
                    $fail(__('MSG-E-010', ['attribute' => 'Ngày kết thúc', 'attribute2' => 'Ngày bắt đầu']));
                }
            }
        };
        $rules += array('start_date' => ['bail', 'nullable', 'date']);
        $rules += array('end_date' => ['bail', 'nullable', 'date', $validate_report_date]);

        $rules += array('name' => ['required', 'string']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('start_date.date' => __('MSG-E-005'));
        $msgs += array('end_date.date' => __('MSG-E-005'));

        $msgs += array('name.string' => __('MSG-E-005'));
        $msgs += array('name.required' => __('MSG-E-004'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'name' => 'Tiêu đề',
        ];
    }
}
