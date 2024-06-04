<?php

namespace App\Http\Requests\api\Sticker;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Sticker Edit Request
*/
class StickerEditRequest extends ApiBaseRequest
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
        $rules += array('name' => ['nullable', 'string']);
        $rules += array('department_id' => ['nullable', 'integer']);
        $rules += array('ordinal_number' => ['nullable', 'integer']);
        $rules += array('level_1' => ['nullable', 'numeric']);
        $rules += array('level_2' => ['nullable', 'numeric']);
        $rules += array('level_3' => ['nullable', 'numeric']);
        $rules += array('level_4' => ['nullable', 'numeric']);
        $rules += array('level_5' => ['nullable', 'numeric']);
        $rules += array('level_6' => ['nullable', 'numeric']);
        $rules += array('level_7' => ['nullable', 'numeric']);
        $rules += array('level_8' => ['nullable', 'numeric']);
        $rules += array('level_9' => ['nullable', 'numeric']);
        $rules += array('level_10' => ['nullable', 'numeric']);

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

        $msgs += array('name.string' => __('MSG-E-005'));

        $msgs += array('department_id.integer' => __('MSG-E-005'));
        $msgs += array('ordinal_number.integer' => __('MSG-E-005'));

        $msgs += array('level_1.numeric' => __('MSG-E-005'));
        $msgs += array('level_2.numeric' => __('MSG-E-005'));
        $msgs += array('level_3.numeric' => __('MSG-E-005'));
        $msgs += array('level_4.numeric' => __('MSG-E-005'));
        $msgs += array('level_5.numeric' => __('MSG-E-005'));
        $msgs += array('level_6.numeric' => __('MSG-E-005'));
        $msgs += array('level_7.numeric' => __('MSG-E-005'));
        $msgs += array('level_8.numeric' => __('MSG-E-005'));
        $msgs += array('level_9.numeric' => __('MSG-E-005'));
        $msgs += array('level_10.numeric' => __('MSG-E-005'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'id' => 'Loại công việc',
            'name' => 'Tiêu đề',
            'department_id' => 'Bộ phận',
            'ordinal_number' => 'Số thứ tự',
            'level_1' => 'Level 1',
            'level_2' => 'Level 2',
            'level_3' => 'Level 3',
            'level_4' => 'Level 4',
            'level_5' => 'Level 5',
            'level_6' => 'Level 6',
            'level_7' => 'Level 7',
            'level_8' => 'Level 8',
            'level_9' => 'Level 9',
            'level_10' => 'Level 10',
        ];
    }
}
