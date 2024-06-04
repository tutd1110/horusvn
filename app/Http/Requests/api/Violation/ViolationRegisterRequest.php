<?php

namespace App\Http\Requests\api\Violation;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Violation Register Request
*/
class ViolationRegisterRequest extends ApiBaseRequest
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
        $rules += array('employee_id' => ['required', 'integer', 'exists:users,id',]);
        $rules += array('type' => ['required', 'integer']);
        $rules += array('time' => ['required', 'date_format:Y/m/d H:i:s']);
        $rules += array('description' => ['required', 'string']);
        $rules += array('files' => ['nullable', 'array']);
        $rules += array('files.*' => ['nullable', 'file']);

        return $rules;
    }

    /**
     * Validation message
     */
    public function messages()
    {
        $msgs = [];
        $msgs += array('employee_id.required' => __('MSG-E-004'));
        $msgs += array('employee_id.exists' => __('MSG-E-006'));
        $msgs += array('employee_id.integer' => __('MSG-E-005'));
        $msgs += array('type.required' => __('MSG-E-004'));
        $msgs += array('type.integer' => __('MSG-E-005'));
        $msgs += array('time.required' => __('MSG-E-004'));
        $msgs += array('time.date_format' => __('MSG-E-005'));
        $msgs += array('description.required' => __('MSG-E-004'));
        $msgs += array('description.string' => __('MSG-E-005'));
        $msgs += array('files.array' => __('MSG-E-005'));
        $msgs += array('files.*.file' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'employee_id' => 'Nhân viên',
            'description' => 'Chi tiết vi phạm',
            'type' => 'Loại vi phạm',
            'files' => 'Hình ảnh vi phạm',
            'files.*' => 'Hình ảnh vi phạm',
            'time' => 'Thời gian vi phạm',
        ];
    }
}
