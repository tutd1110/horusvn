<?php

namespace App\Http\Requests\api\Employee;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Log Go Out Request
*/
class GetLogGoOutRequest extends ApiBaseRequest
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

        $rules += array('user_id' => ['required', 'integer', 'exists:users,id',]);

        $rules += array('date' => ['required', 'date']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('user_id.required' => __('MSG-E-004'));
        $msgs += array('user_id.exists' => __('MSG-E-006'));
        $msgs += array('user_id.integer' => __('MSG-E-005'));

        $msgs += array('date.date' => __('MSG-E-005'));
        $msgs += array('date.required' => __('MSG-E-004'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'user_id' => 'Nhân viên không tồn tại',
            'date' => 'Ngày tìm kiếm'
        ];
    }
}
