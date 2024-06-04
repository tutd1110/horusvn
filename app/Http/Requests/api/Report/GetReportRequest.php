<?php

namespace App\Http\Requests\api\Report;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Project;

/**
 * Report Request
*/
class GetReportRequest extends ApiBaseRequest
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

        $rules += array('project_ids' => ['bail', 'nullable', 'array']);
        $rules += array('project_ids.*.id' => ['bail', 'nullable', 'integer']);

        $rules += array('department_ids' => ['bail', 'nullable', 'array']);
        /**
        * function for validation
        *   $attribute: Attribute name under validation
        *   $value    : the value of the attribute being validated
        *   $fail     : method to call on failure
        */
        $validate_department_id = function ($attribute, $value, $fail) {

            //list departments
            $departments = config('const.departments');
            if (!isset($departments[$value])) {
                $fail(__('MSG-E-009', ['attribute' => 'Bộ phận đang tìm kiếm']));
            }
        };
        $rules += array('department_ids.*.id' => ['bail', 'nullable', 'integer', $validate_department_id]);

        /**
        * function for validation
        *   $attribute: Attribute name under validation
        *   $value    : the value of the attribute being validated
        *   $fail     : method to call on failure
        */
        $validate_report_date = function ($attribute, $value, $fail) {
            //get Input
            $input_data = $this->all();
            if (!empty($input_data['start_time']) && !empty($input_data['end_time'])) {
                if ($input_data['start_time'] > $input_data['end_time']) {
                    $fail(__('MSG-E-010', ['attribute' => 'Ngày kết thúc', 'attribute2' => 'Ngày bắt đầu']));
                }
            }
        };
        $rules += array('start_time' => ['bail', 'nullable', 'date']);
        $rules += array('end_time' => ['bail', 'nullable', 'date', $validate_report_date]);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];

        $msgs += array('project_ids.*.id.integer' => __('MSG-E-005'));
        $msgs += array('department_ids.*.id.integer' => __('MSG-E-005'));

        $msgs += array('start_time.date' => __('MSG-E-005'));
        $msgs += array('end_time.date' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'project_ids' => 'Dự án đang tìm kiếm',
            'project_ids.*.id' => 'Dự án đang tìm kiếm',
            'department_ids' => 'Bộ phận đang tìm kiếm',
            'department_ids.*.id' => 'Bộ phận đang tìm kiếm',
            'start_time' => 'Ngày bắt đầu',
            'end_time' => 'Ngày kết thúc'
        ];
    }
}
