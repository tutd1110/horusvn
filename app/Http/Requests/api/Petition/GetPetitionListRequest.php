<?php

namespace App\Http\Requests\api\Petition;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Petition List Request
*/
class GetPetitionListRequest extends ApiBaseRequest
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
        $input_data = $this->all();

        $rules += array('fullname' => ['nullable', 'string']);
        $rules += array('type_paid' => ['nullable', 'integer']);
        
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
        if (isset($input_data['start_date']) && !empty($input_data['start_date'])) {
            $rules += array('end_date' => ['bail', 'required', 'date', $validate_report_date]);
        }

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('fullname.string' => __('MSG-E-005'));
        $msgs += array('start_date.date' => __('MSG-E-005'));
        $msgs += array('end_date.date' => __('MSG-E-005'));
        $msgs += array('end_date.required' => __('MSG-E-004'));
        $msgs += array('type_paid.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'fullname' => 'Họ và tên',
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'type_paid' => 'Hình thức nghỉ phép'
        ];
    }
}
