<?php

namespace App\Http\Requests\api\Petition;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Petition;

/**
 * Petition Delete Request
*/
class PetitionDeleteRequest extends ApiBaseRequest
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

        $rules += array('id' => ['required', 'integer', 'exists:petitions,id',]);

        $rules += array('check_updated_at' => ['required', 'date',]);

        /**
        * function for validation
        *   $attribute: Attribute name under validation
        *   $value    : the value of the attribute being validated
        *   $fail     : method to call on failure
        */
        $validate_key = function ($attribute, $value, $fail) {
            //get Input
            $input_data = $this->all();
            if (isset($input_data['key']) && !empty($input_data['key'])) {
                if (!in_array($input_data['key'], [4])) {
                    $fail(__('MSG-E-006', ['attribute' => 'Hành động']));
                }
            }
        };

        $rules += array('key' => ['bail', 'required', 'integer', $validate_key]);

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

        $msgs += array('key.required' => __('MSG-E-004'));
        $msgs += array('key.integer' => __('MSG-E-005'));

        $msgs += array('check_updated_at.required' => __('MSG-E-004'));
        $msgs += array('check_updated_at.date' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'id' => 'Dự án',
            'key' => 'Hành động',
            'check_updated_at' => 'Ngày giờ sửa đổi'
        ];
    }
}
