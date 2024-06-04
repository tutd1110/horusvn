<?php

namespace App\Http\Requests\api\Project;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Project;

/**
 * Project Request
*/
class ProjectEditRequest extends ApiBaseRequest
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
        $rules += array('user_ids' => ['nullable', 'array']);
        $rules += array('user_ids.*.id' => ['nullable', 'integer', 'exists:users,id',]);
        $rules += array('name' => ['required']);

        /**
        * function for validation
        *   $attribute: Attribute name under validation
        *   $value    : the value of the attribute being validated
        *   $fail     : method to call on failure
        */
        $validate_project_code = function ($attribute, $value, $fail) {
            //get Input
            $input_data = $this->all();
            if (!empty($input_data['code'])) {
                $project = Project::where('projects.code', $input_data['code'])
                    ->where('projects.id', '!=', $input_data['id'])
                    ->first();
                if ($project) {
                    $fail(__('MSG-E-008', ['attribute' => 'Mã dự án']));
                }
            }
        };
        $rules += array('code' => ['bail', 'required', $validate_project_code]);
        $rules += array('description' => ['nullable']);
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
        $msgs += array('user_ids.*.id.integer' => __('MSG-E-005'));
        $msgs += array('user_ids.*.id.exists' => __('MSG-E-006'));
        $msgs += array('name.required' => __('MSG-E-004'));
        $msgs += array('code.required' => __('MSG-E-004'));
        $msgs += array('code.exists' => __('MSG-E-006'));
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
            'user_ids' => 'Người tham gia',
            'user_ids.*.id' => 'Người tham gia dự án',
            'name' => 'Tên dự án',
            'code' => 'Mã dự án',
            'description' => 'Mô tả dự án',
            'check_updated_at' => 'Ngày giờ sửa đổi'
        ];
    }
}
