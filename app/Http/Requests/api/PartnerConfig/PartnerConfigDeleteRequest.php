<?php

namespace App\Http\Requests\api\PartnerConfig;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Delete partner base request class
*/
class PartnerConfigDeleteRequest extends ApiBaseRequest
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

        $rules += array('id' => ['required', 'integer', 'exists:partner_configs,id',]);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('id.required' => __('MSG-E-004'));
        $msgs += array('id.exists' => __('MSG-E-006'));
        $msgs += array('id.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'id' => 'Mã thiết bị',
        ];
    }
}
