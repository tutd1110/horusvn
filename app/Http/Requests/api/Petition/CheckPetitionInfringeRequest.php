<?php

namespace App\Http\Requests\api\Petition;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Petition Check Infringe Request
*/
class CheckPetitionInfringeRequest extends ApiBaseRequest
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

        //get Input
        $input_data = $this->all();

        /**
        * function for validation
        *   $attribute: Attribute name under validation
        *   $value    : the value of the attribute being validated
        *   $fail     : method to call on failure
        */
        $validate_type = function ($attribute, $value, $fail) {
            //list type_off
            $type = config('const.petition_type');
            $element = array_column($type, 'id');
            if (strlen(array_search($value, $element)) == 0) {
                $fail(__('MSG-E-009', ['attribute' => 'Loại yêu cầu']));
            }
        };
        $rules += array('type' => ['required', 'integer', $validate_type]);

        if ($input_data['type'] == 2) {
            /**
            * function for validation
            *   $attribute: Attribute name under validation
            *   $value    : the value of the attribute being validated
            *   $fail     : method to call on failure
            */
            $validate_type_off = function ($attribute, $value, $fail) {
                //list type_off
                $type = config('const.petition_time_period');
                $element = array_column($type, 'id');
                if (strlen(array_search($value, $element)) == 0) {
                    $fail(__('MSG-E-009', ['attribute' => 'Loại nghỉ phép']));
                }
            };
            $rules += array('type_off' => ['required', 'integer', $validate_type_off]);

            /**
            * function for validation
            *   $attribute: Attribute name under validation
            *   $value    : the value of the attribute being validated
            *   $fail     : method to call on failure
            */
            $validate_type_paid = function ($attribute, $value, $fail) {
                //list type_off
                $type = config('const.type_paid');
                $element = array_column($type, 'id');
                if (strlen(array_search($value, $element)) == 0) {
                    $fail(__('MSG-E-009', ['attribute' => 'Hình thức nghỉ phép']));
                }
            };
            $rules += array('type_paid' => ['required', 'integer', $validate_type_paid]);
        }

        $rules += array('reason' => ['required', 'string']);

        if (in_array($input_data['type'], [1, 5, 6, 7])) {
            $rules += array('start_time' => ['required', 'date_format:H:i:s']);
            $rules += array('end_time' => ['required', 'date_format:H:i:s', 'after:start_time']);
        }

        $rules += array('user_id' => ['required', 'integer', 'exists:users,id',]);

        $rules += array('start_date' => ['bail', 'required', 'date']);

        if ($input_data['type'] == 7) {
            $rules += array('type_go_out' => ['required', 'integer',]);
        }

        if (isset($input_data['type_off']) && $input_data['type_off'] == 4) {
            /**
            * function for validation
            *   $attribute: Attribute name under validation
            *   $value    : the value of the attribute being validated
            *   $fail     : method to call on failure
            */
            $validate_project_date = function ($attribute, $value, $fail) {
                //get Input
                $input_data = $this->all();
                if (!empty($input_data['start_date']) && !empty($input_data['end_date'])) {
                    if ($input_data['start_date'] > $input_data['end_date']) {
                        $fail(__('MSG-E-010', ['attribute' => 'Ngày kết thúc', 'attribute2' => 'Ngày bắt đầu']));
                    }
                }
            };

            $rules += array('end_date' => ['bail', 'required', 'date', $validate_project_date]);
        }

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        //get Input
        $input_data = $this->all();

        $msgs += array('type.required' => __('MSG-E-004'));
        $msgs += array('type.integer' => __('MSG-E-005'));

        if ($input_data['type'] == 2) {
            $msgs += array('type_off.required' => __('MSG-E-004'));
            $msgs += array('type_off.integer' => __('MSG-E-005'));

            $msgs += array('type_paid.required' => __('MSG-E-004'));
            $msgs += array('type_paid.integer' => __('MSG-E-005'));
        }

        $msgs += array('reason.required' => __('MSG-E-004'));
        $msgs += array('reason.string' => __('MSG-E-005'));

        if (in_array($input_data['type'], [1, 5, 6, 7])) {
            $msgs += array('start_time.required' => __('MSG-E-004'));
            $msgs += array('start_time.date_format' => __('MSG-E-005'));

            $msgs += array('end_time.required' => __('MSG-E-004'));
            $msgs += array('end_time.date_format' => __('MSG-E-005'));
            $msgs += array('end_time.after' => __('MSG-E-013'));

            $msgs += array('type_go_out.required' => __('MSG-E-004'));
            $msgs += array('type_go_out.integer' => __('MSG-E-005'));
        }

        $msgs += array('start_date.required' => __('MSG-E-004'));
        $msgs += array('start_date.date' => __('MSG-E-005'));
        if (isset($input_data['type_off']) && $input_data['type_off'] == 4) {
            $msgs += array('end_date.required' => __('MSG-E-004'));
            $msgs += array('end_date.date' => __('MSG-E-005'));
        }

        $msgs += array('user_id.required' => __('MSG-E-004'));
        $msgs += array('user_id.exists' => __('MSG-E-006'));
        $msgs += array('user_id.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'type' => 'Loại yêu cầu',
            'type_off' => 'Loại nghỉ phép',
            'type_go_out' => 'Hình thức ra ngoài',
            'type_paid' => 'Hình thức nghỉ phép',
            'reason' => 'Lý do',
            'start_time' => 'Thời gian bắt đầu',
            'end_time' => 'Thời gian kết thúc',
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'user_id' => 'Họ và tên'
        ];
    }
}
