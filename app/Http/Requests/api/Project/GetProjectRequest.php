<?php

namespace App\Http\Requests\api\Project;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Project Request
*/
class GetProjectRequest extends ApiBaseRequest
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

        $rules += array('user_ids' => ['bail', 'nullable', 'array']);
        $rules += array('user_ids.*.id' => ['bail', 'nullable', 'integer', 'exists:users,id',]);

        $rules += array('name' => ['nullable']);
        $rules += array('code' => ['nullable']);

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
        $rules += array('start_date' => ['bail', 'nullable', 'date']);
        $rules += array('end_date' => ['bail', 'nullable', 'date', $validate_project_date]);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('user_ids.*.id.integer' => __('MSG-E-005'));
        $msgs += array('user_ids.array' => __('MSG-E-005'));
        $msgs += array('user_ids.*.id.exists' => __('MSG-E-006'));

        $msgs += array('start_date.date' => __('MSG-E-005'));
        $msgs += array('end_date.date' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'user_ids' => 'Danh sách nhân viên',
            'user_ids.*.id' => 'Nhân viên',
            'start_date' => 'Ngày bắt đầu',
            'end_date' => 'Ngày kết thúc',
            'name' => 'Tên dự án',
            'code' => 'Mã dự án'
        ];
    }
}
