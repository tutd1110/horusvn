<?php

namespace App\Http\Requests\api\Journal;

use App\Http\Requests\api\ApiBaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Journal Edit Request
*/
class JournalEditRequest extends ApiBaseRequest
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

        $rules += array('id' => ['required', 'integer', 'exists:journals,id',]);
        $rules += array('title' => ['required', 'string']);
        $rules += array('description' => ['required', 'string']);
        $rules += array('department_id' => ['nullable', 'array']);
        $rules += array('game_id' => ['nullable', 'array']);
        $rules += array('files' => ['nullable', 'array']);
        $rules += array('files.*' => ['nullable', 'file']);
        $rules += array('department_id.*' => ['nullable', 'integer',]);
        $rules += array('game_id.*' => ['nullable', 'integer',]);

        $rules += array('status' => ['nullable']);

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
        $msgs += array('title.string' => __('MSG-E-005'));
        $msgs += array('title.required' => __('MSG-E-004'));
        $msgs += array('description.string' => __('MSG-E-005'));
        $msgs += array('description.required' => __('MSG-E-004'));
        $msgs += array('files.array' => __('MSG-E-005'));
        $msgs += array('files.*' => __('MSG-E-005'));
        $msgs += array('department_id.array' => __('MSG-E-005'));
        $msgs += array('department_id.*' => __('MSG-E-005'));
        $msgs += array('game_id.array' => __('MSG-E-005'));
        $msgs += array('game_id.*' => __('MSG-E-005'));

        $msgs += array('status.required' => __('MSG-E-004'));

        return $msgs;
    }

    /**
     * Set custom attribute name
    */
    public function attributes()
    {
        return [
            'title' => 'Tiêu đề',
            'description' => 'Nội dung',
            'files' => 'File',
            'files.*' => 'File',
            'department_id' => 'Bộ phận',
            'department_id.*' => 'Bộ phận',
            'game_id' => 'Game',
            'game_id.*' => 'Game',

            'status' => 'Loại hiển thị'
        ];
    }
}
