<?php

namespace App\Http\Requests\api\Purchase;

use App\Http\Requests\api\ApiBaseRequest;
use App\Models\Purchase;

/**
 * Store Task base request class
*/
class PurchaseUpdateRequest extends ApiBaseRequest
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
        $rules += array('name' => ['required', 'string']);
        $rules += array('project_id' => ['required']);
        $rules += array('type' => ['required']);

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
        $msgs += array('project_id.required' => __('MSG-E-004'));
        $msgs += array('type.required' => __('MSG-E-004'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        $attributes = [
            'name' => 'Tên yêu cầu',
            'project_id' => 'Dự án',
            'type' => 'Loại yêu cầu',
        ];

        return $attributes;
    }
}
