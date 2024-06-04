<?php

namespace App\Http\Requests\api\Timesheet\TimesheetDetail;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Sync Timesheet Detail Request
*/
class SyncTimesheetDetailRequest extends ApiBaseRequest
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
        $rules += array('start_date' => ['bail', 'required', 'date']);
        $rules += array('end_date' => ['bail', 'required', 'date', $validate_report_date]);

        $rules += array('device_code' => ['required', 'string', 'exists:tracking_devices,code',]);

        $rules += array('users' => ['bail', 'required', 'array']);
        $rules += array('users.*.user_code' => ['bail', 'required', 'string', 'exists:users,user_code',]);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('start_date.date' => __('MSG-E-005'));
        $msgs += array('start_date.required' => __('MSG-E-004'));
        $msgs += array('end_date.date' => __('MSG-E-005'));
        $msgs += array('end_date.required' => __('MSG-E-004'));

        $msgs += array('device_code.string' => __('MSG-E-005'));
        $msgs += array('device_code.required' => __('MSG-E-004'));
        $msgs += array('device_code.exists' => __('MSG-E-006'));

        $msgs += array('users.*.user_code.string' => __('MSG-E-005'));
        $msgs += array('users.required' => __('MSG-E-004'));
        $msgs += array('users.array' => __('MSG-E-005'));
        $msgs += array('users.*.user_code.required' => __('MSG-E-004'));
        $msgs += array('users.*.user_code.exists' => __('MSG-E-006'));

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
            'device_code' => 'Thiết bị',
            'users' => 'Mã nhân viên',
            'users.*.user_code' => 'Mã nhân viên'
        ];
    }
}
