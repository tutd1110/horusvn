<?php

namespace App\Http\Requests\api\Task\Me;

use App\Http\Requests\api\ApiBaseRequest;

/**
 * Delete task base request class
*/
class TaskDeleteRequest extends ApiBaseRequest
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
        $rules += array('id' => ['required', 'integer']);
        $rules += array('check_updated_at' => ['required', 'date',]);
        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('id.required' => __('MSG-E-004'));
        $msgs += array('id.integer' => __('MSG-E-005'));

        $msgs += array('check_updated_at.date' => __('MSG-E-005'));
        $msgs += array('check_updated_at.required' => __('MSG-E-004'));
        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'id' => 'Công việc',
            'check_updated_at' => 'Ngày giờ sửa đổi'
        ];
    }
}
