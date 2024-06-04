<?php

namespace App\Http\Requests\api\WZAndroid0022;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Get Tracking In Out base request class
*/
class TrackingInOutRequest extends ApiBaseRequest
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
        $rules += array('api' => ['required', 'string',]);
        $rules += array('type' => ['required', 'integer',]);
        $rules += array('check_type' => ['required', 'integer',]);
        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('api.required' => __('MSG-E-004'));
        $msgs += array('api.string' => __('MSG-E-005'));
        $msgs += array('type.required' => __('MSG-E-004'));
        $msgs += array('type.integer' => __('MSG-E-005'));
        $msgs += array('check_type.required' => __('MSG-E-004'));
        $msgs += array('check_type.integer' => __('MSG-E-005'));
        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'api' => 'API',
            'type' => 'Loại dữ liệu',
            'check_type' => 'CheckType'
        ];
    }
}
