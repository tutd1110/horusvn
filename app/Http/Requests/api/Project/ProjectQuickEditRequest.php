<?php

namespace App\Http\Requests\api\Project;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Project;

/**
 * Project Quick Update Request
*/
class ProjectQuickEditRequest extends ApiBaseRequest
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

        // $rules += array('ordinal_number' => ['required', 'integer',]);
        $rules += array('ordinal_number' => ['integer',]);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('ordinal_number.required' => __('MSG-E-004'));
        $msgs += array('ordinal_number.integer' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'ordinal_number' => 'Số thứ tự'
        ];
    }
}
